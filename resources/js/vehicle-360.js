/**
 * 360° vehicle viewer.
 *
 * The viewer only starts loading when its tab becomes visible. Every frame is
 * preloaded behind a progress overlay; dragging, keyboard control and
 * auto-rotation are enabled once the full sequence is ready.
 */
const FRAME_RATE = 8;
const PRELOAD_CONCURRENCY = 6;
const DRAG_SENSITIVITY = 1.8;
const DRAG_THRESHOLD = 5;

class Vehicle360 {
    constructor(root) {
        this.root = root;
        this.frames = JSON.parse(root.dataset.frames || '[]');
        this.image = root.querySelector('[data-360-image]');
        this.canvas = root.querySelector('[data-360-canvas]');
        this.context = this.canvas?.getContext('2d') ?? null;
        this.loading = root.querySelector('[data-360-loading]');
        this.loadingTitle = root.querySelector('[data-360-loading-title]');
        this.spinner = root.querySelector('[data-360-spinner]');
        this.status = root.querySelector('[data-360-status]');
        this.progressWrap = root.querySelector('[data-360-progress-wrap]');
        this.progress = root.querySelector('[data-360-progress]');
        this.error = root.querySelector('[data-360-error]');
        this.retry = root.querySelector('[data-360-retry]');
        this.toggle = root.querySelector('[data-360-toggle]');
        this.prev = root.querySelector('[data-360-prev]');
        this.next = root.querySelector('[data-360-next]');
        this.index = 0;
        this.ready = false;
        this.visible = false;
        this.preloadStarted = false;
        this.preloaded = [];
        this.loadedCount = 0;
        this.failedCount = 0;
        this.observer = null;
        this.resizeObserver = null;
        this.dragging = false;
        this.dragMoved = false;
        this.startX = 0;
        this.startIndex = 0;
        this.autoRotate = root.dataset.autoplay !== 'false' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.lastTick = 0;
        this.raf = null;
        this.hideTimer = null;

        if (!this.image || this.frames.length < 2) {
            this.loading?.classList.add('hidden');

            return;
        }

        this.root.setAttribute('aria-busy', 'true');
        this.setControlsEnabled(false);
        this.bind();
        this.render();
        this.updateToggle();
        this.observeVisibility();
        this.observeResize();
    }

    bind() {
        this.retry?.addEventListener('click', () => {
            this.resetPreload();

            if (this.visible) {
                this.preloadAll();
            }
        });

        const endDrag = (event) => {
            if (!this.dragging) {
                return;
            }

            const moved = this.dragMoved;
            this.dragging = false;
            this.dragMoved = false;
            this.root.classList.remove('cursor-grabbing');

            if (event?.pointerId !== undefined) {
                try {
                    this.root.releasePointerCapture?.(event.pointerId);
                } catch (error) {
                    // The pointer may already have been released.
                }
            }

            if (!moved) {
                this.startIfReady();
            }
        };

        const moveDrag = (event) => {
            if (!this.ready || !this.dragging) {
                return;
            }

            if (event.pointerType === 'mouse' && event.buttons === 0) {
                endDrag(event);

                return;
            }

            const delta = event.clientX - this.startX;

            if (!this.dragMoved && Math.abs(delta) >= DRAG_THRESHOLD) {
                this.dragMoved = true;
                this.pauseAutoRotate();
            }

            if (!this.dragMoved) {
                return;
            }

            const step = Math.max(1, Math.round(this.root.clientWidth / this.frames.length));
            const direction = event.pointerType === 'touch' ? -1 : 1;
            this.show((this.startIndex + Math.round((delta / step) * DRAG_SENSITIVITY * direction)) % this.frames.length);
        };

        this.root.addEventListener('pointerdown', (event) => {
            if (!this.ready || (event.button !== undefined && event.button !== 0) || event.target.closest('button')) {
                return;
            }

            this.dragging = true;
            this.dragMoved = false;
            this.startX = event.clientX;
            this.startIndex = this.index;
            this.root.setPointerCapture?.(event.pointerId);
            this.root.classList.add('cursor-grabbing');
            this.stop();
        });

        window.addEventListener('pointermove', moveDrag);
        window.addEventListener('pointerup', endDrag);
        window.addEventListener('pointercancel', endDrag);

        const stepFrame = (direction) => {
            if (!this.ready) {
                return;
            }

            this.pauseAutoRotate();
            this.show(this.index + direction);
        };

        this.prev?.addEventListener('click', () => stepFrame(-1));
        this.next?.addEventListener('click', () => stepFrame(1));

        this.root.addEventListener('keydown', (event) => {
            if (!this.ready) {
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                stepFrame(1);
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                stepFrame(-1);
            }
        });

        this.toggle?.addEventListener('click', () => {
            if (!this.ready) {
                return;
            }

            this.autoRotate = !this.autoRotate;
            this.updateToggle();

            if (this.autoRotate) {
                this.startIfReady();
            } else {
                this.stop();
            }
        });
    }

    observeVisibility() {
        if (!('IntersectionObserver' in window)) {
            this.setVisible(true);

            return;
        }

        this.observer = new IntersectionObserver((entries) => {
            const visible = entries.some((entry) => entry.isIntersecting);
            this.setVisible(visible);
        }, { threshold: 0.05 });

        this.observer.observe(this.root);
    }

    observeResize() {
        if (!this.canvas) {
            return;
        }

        if ('ResizeObserver' in window) {
            this.resizeObserver = new ResizeObserver(() => this.render());
            this.resizeObserver.observe(this.root);

            return;
        }

        window.addEventListener('resize', () => this.render());
    }

    setVisible(visible) {
        if (this.visible === visible) {
            if (visible) {
                this.render();
                this.startIfReady();
            }

            return;
        }

        this.visible = visible;

        if (!visible) {
            this.stop();

            return;
        }

        if (this.ready) {
            this.render();
            this.startIfReady();

            return;
        }

        this.preloadAll();
    }

    preloadAll() {
        if (this.preloadStarted) {
            this.render();
            this.startIfReady();

            return;
        }

        this.preloadStarted = true;

        let nextIndex = 0;
        let active = 0;
        let completed = 0;
        const total = this.frames.length;

        const loadNext = () => {
            while (active < PRELOAD_CONCURRENCY && nextIndex < total) {
                const index = nextIndex;
                nextIndex += 1;
                active += 1;

                this.loadFrame(index, (success) => {
                    active -= 1;
                    completed += 1;

                    if (!success) {
                        this.failedCount += 1;
                    }

                    this.loadedCount = completed;
                    this.updateProgress(completed, total);

                    if (completed === total) {
                        this.finishPreload();

                        return;
                    }

                    loadNext();
                });
            }
        };

        this.updateProgress(0, total);
        loadNext();
    }

    loadFrame(index, onLoad) {
        const cached = this.preloaded[index];

        if (cached) {
            if (cached.status === 'loaded') {
                onLoad(true);
            } else if (cached.status === 'failed') {
                onLoad(false);
            } else {
                cached.callbacks.push(onLoad);
            }

            return;
        }

        const image = new Image();
        const record = { image, status: 'loading', callbacks: [onLoad] };
        this.preloaded[index] = record;

        const settle = (success) => {
            record.status = success ? 'loaded' : 'failed';
            const callbacks = record.callbacks;
            record.callbacks = [];
            callbacks.forEach((callback) => callback(success));
        };

        image.decoding = 'async';
        image.onload = () => {
            if (typeof image.decode !== 'function') {
                settle(true);

                return;
            }

            image.decode()
                .then(() => settle(true))
                .catch(() => settle(false));
        };
        image.onerror = () => settle(false);
        image.src = this.frames[index];
    }

    updateProgress(completed, total) {
        const percent = Math.round((completed / total) * 100);

        if (this.progress) {
            this.progress.style.width = `${percent}%`;
        }

        if (this.progressWrap) {
            this.progressWrap.setAttribute('aria-valuenow', String(percent));
        }

        if (this.status) {
            this.status.textContent = `Loading ${completed} of ${total} frames (${percent}%)`;
        }
    }

    finishPreload() {
        if (this.failedCount > 0) {
            this.ready = false;
            this.root.setAttribute('aria-busy', 'true');
            this.setControlsEnabled(false);
            this.showLoadError();

            return;
        }

        this.ready = true;
        this.root.setAttribute('aria-busy', 'false');
        this.setControlsEnabled(true);
        this.render();

        if (this.canvas && this.context) {
            this.canvas.classList.remove('opacity-0');
            this.canvas.classList.add('opacity-100');
        }

        this.hideLoading();
        this.startIfReady();
    }

    resetPreload() {
        window.clearTimeout(this.hideTimer);
        this.stop();
        this.ready = false;
        this.preloadStarted = false;
        this.preloaded = [];
        this.loadedCount = 0;
        this.failedCount = 0;
        this.root.setAttribute('aria-busy', 'true');
        this.setControlsEnabled(false);

        if (this.canvas) {
            this.canvas.classList.add('opacity-0');
            this.canvas.classList.remove('opacity-100');
        }

        this.resetLoadingUi();
    }

    resetLoadingUi() {
        if (this.loading) {
            this.loading.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
            this.loading.setAttribute('aria-hidden', 'false');
        }

        this.spinner?.classList.remove('hidden');
        this.status?.classList.remove('hidden');
        this.progressWrap?.classList.remove('hidden');
        this.error?.classList.add('hidden');

        if (this.loadingTitle) {
            this.loadingTitle.textContent = 'Loading 360° view';
        }

        if (this.status) {
            this.status.textContent = `Preparing ${this.frames.length} frames…`;
        }

        if (this.progress) {
            this.progress.style.width = '0%';
        }

        if (this.progressWrap) {
            this.progressWrap.setAttribute('aria-valuenow', '0');
        }
    }

    showLoadError() {
        this.spinner?.classList.add('hidden');
        this.status?.classList.add('hidden');
        this.progressWrap?.classList.add('hidden');
        this.error?.classList.remove('hidden');

        if (this.loadingTitle) {
            this.loadingTitle.textContent = '360° view could not load';
        }
    }

    hideLoading() {
        if (!this.loading) {
            return;
        }

        this.loading.classList.add('opacity-0', 'pointer-events-none');
        this.loading.setAttribute('aria-hidden', 'true');
        this.hideTimer = window.setTimeout(() => {
            this.loading.classList.add('hidden');
        }, 320);
    }

    setControlsEnabled(enabled) {
        [this.toggle, this.prev, this.next].forEach((control) => {
            if (!control) {
                return;
            }

            control.disabled = !enabled;
            control.setAttribute('aria-disabled', String(!enabled));
        });
    }

    pauseAutoRotate() {
        this.autoRotate = false;
        this.updateToggle();
        this.stop();
    }

    startIfReady() {
        if (this.ready && this.visible && this.autoRotate) {
            this.start();
        }
    }

    start() {
        if (!this.autoRotate || this.raf || !this.visible || !this.ready) {
            return;
        }

        const tick = (time) => {
            if (time - this.lastTick > 1000 / FRAME_RATE) {
                this.show(this.index + 1);
                this.lastTick = time;
            }

            this.raf = window.requestAnimationFrame(tick);
        };

        this.raf = window.requestAnimationFrame(tick);
    }

    stop() {
        window.cancelAnimationFrame(this.raf);
        this.raf = null;
    }

    show(index) {
        const total = this.frames.length;
        this.index = ((index % total) + total) % total;
        this.render();
    }

    render() {
        if (!this.frames[this.index]) {
            return;
        }

        this.root.dataset.frame = String(this.index + 1);

        if (!this.ready) {
            return;
        }

        if (!this.context) {
            this.image.src = this.frames[this.index];

            return;
        }

        this.drawFrame(this.index);
    }

    drawFrame(index) {
        const image = this.preloaded[index]?.status === 'loaded' ? this.preloaded[index].image : null;

        if (!this.context || !this.canvas || !image || !image.naturalWidth) {
            return;
        }

        const width = this.root.clientWidth;
        const height = this.root.clientHeight;

        if (!width || !height) {
            return;
        }

        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const pixelWidth = Math.round(width * dpr);
        const pixelHeight = Math.round(height * dpr);

        if (this.canvas.width !== pixelWidth || this.canvas.height !== pixelHeight) {
            this.canvas.width = pixelWidth;
            this.canvas.height = pixelHeight;
        }

        const scale = Math.max(pixelWidth / image.naturalWidth, pixelHeight / image.naturalHeight);
        const drawWidth = image.naturalWidth * scale;
        const drawHeight = image.naturalHeight * scale;

        this.context.fillStyle = '#ffffff';
        this.context.fillRect(0, 0, pixelWidth, pixelHeight);
        this.context.drawImage(
            image,
            (pixelWidth - drawWidth) / 2,
            (pixelHeight - drawHeight) / 2,
            drawWidth,
            drawHeight,
        );
    }

    updateToggle() {
        if (!this.toggle) {
            return;
        }

        this.toggle.setAttribute('aria-pressed', String(this.autoRotate));
        this.toggle.setAttribute('aria-label', this.autoRotate ? 'Pause auto-rotation' : 'Play auto-rotation');
        this.toggle.textContent = this.autoRotate ? 'Pause' : 'Play';
    }
}

const boot = () => {
    document.querySelectorAll('[data-360-viewer]').forEach((root) => {
        if (!root.dataset.initialised) {
            root.dataset.initialised = 'true';
            new Vehicle360(root);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
