/**
 * 360° vehicle viewer.
 *
 * Progressive enhancement: the HTML contains the first frame and the full
 * frame list. Frames are preloaded in the background, pointer/touch drag swaps
 * frames, and auto-rotate uses requestAnimationFrame with a frame throttle.
 */
const FRAME_RATE = 24;
const DRAG_SENSITIVITY = 1.35;

class Vehicle360 {
    constructor(root) {
        this.root = root;
        this.frames = JSON.parse(root.dataset.frames || '[]');
        this.image = root.querySelector('[data-360-image]');
        this.loader = root.querySelector('[data-360-loader]');
        this.progress = root.querySelector('[data-360-progress]');
        this.toggle = root.querySelector('[data-360-toggle]');
        this.index = 0;
        this.loaded = 0;
        this.preloaded = [];
        this.dragging = false;
        this.startX = 0;
        this.startIndex = 0;
        this.autoRotate = root.dataset.autoplay !== 'false' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.lastTick = 0;
        this.raf = null;

        if (!this.image || this.frames.length < 2) {
            return;
        }

        this.bind();
        this.preload();
        this.render();
        this.updateToggle();
        this.start();
    }

    bind() {
        this.root.addEventListener('pointerdown', (event) => {
            if (event.target.closest('button')) {
                return;
            }

            this.dragging = true;
            this.startX = event.clientX;
            this.startIndex = this.index;
            this.root.setPointerCapture?.(event.pointerId);
            this.root.classList.add('cursor-grabbing');
            this.stop();
        });

        this.root.addEventListener('pointermove', (event) => {
            if (!this.dragging) {
                return;
            }

            const delta = event.clientX - this.startX;
            const step = Math.max(1, Math.round(this.root.clientWidth / this.frames.length));
            this.show((this.startIndex + Math.round((delta / step) * DRAG_SENSITIVITY)) % this.frames.length);
        });

        const end = () => {
            if (!this.dragging) {
                return;
            }

            this.dragging = false;
            this.root.classList.remove('cursor-grabbing');
            this.start();
        };

        this.root.addEventListener('pointerup', end);
        this.root.addEventListener('pointercancel', end);
        this.root.addEventListener('pointerleave', end);

        this.root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.show(this.index + 1);
                this.stop();
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.show(this.index - 1);
                this.stop();
            }
        });

        this.toggle?.addEventListener('click', () => {
            this.autoRotate = !this.autoRotate;
            this.updateToggle();

            if (this.autoRotate) {
                this.start();
            } else {
                this.stop();
            }
        });
    }

    preload() {
        this.frames.forEach((src, index) => {
            const image = new Image();
            image.decoding = 'async';
            image.onload = () => {
                this.loaded += 1;

                if (this.progress) {
                    this.progress.style.width = `${Math.round((this.loaded / this.frames.length) * 100)}%`;
                }

                if (this.loaded === this.frames.length) {
                    this.loader?.classList.add('hidden');
                }
            };
            image.src = src;
            this.preloaded[index] = image;
        });
    }

    start() {
        if (!this.autoRotate || this.raf) {
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
        this.image.src = this.frames[this.index];
        this.root.dataset.frame = String(this.index + 1);
    }

    updateToggle() {
        if (!this.toggle) {
            return;
        }

        this.toggle.setAttribute('aria-pressed', String(this.autoRotate));
        this.toggle.textContent = this.autoRotate ? 'Pause rotation' : 'Play rotation';
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
