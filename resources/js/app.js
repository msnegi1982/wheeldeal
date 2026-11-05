import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('catalogFilters', (endpoint) => ({
        endpoint,
        loading: false,
        filtersOpen: false,
        count: null,
        controller: null,
        timer: null,

        schedule() {
            window.clearTimeout(this.timer);
            this.timer = window.setTimeout(() => this.fetchResults(), 300);
        },

        async fetchResults() {
            if (this.controller) {
                this.controller.abort();
            }

            this.controller = new AbortController();
            this.loading = true;

            const form = this.$refs.filterForm;
            const params = new URLSearchParams(new FormData(form));

            [...params.entries()].forEach(([key, value]) => {
                if (value === '' || value === null) {
                    params.delete(key);
                }
            });

            try {
                const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: this.controller.signal,
                });

                if (!response.ok) {
                    throw new Error('Filter request failed');
                }

                const data = await response.json();
                this.$refs.results.innerHTML = data.html;
                this.count = data.count;
                window.history.replaceState({}, '', data.url);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    window.location.href = `${form.action}?${params.toString()}`;
                    return;
                }
            } finally {
                this.loading = false;
            }
        },

        clearFilter(names) {
            const form = this.$refs.filterForm;
            const fields = Array.isArray(names) ? names : [names];

            fields.forEach((name) => {
                form.querySelectorAll(`[name="${name}"]`).forEach((field) => {
                    if (field.dataset.locked === 'true') {
                        return;
                    }

                    if (field.type === 'checkbox' || field.type === 'radio') {
                        field.checked = false;
                    } else {
                        field.value = '';
                    }
                });
            });

            this.fetchResults();
        },

        reset() {
            const form = this.$refs.filterForm;

            [...form.elements].forEach((field) => {
                if (field.dataset.locked === 'true' || field.name === 'per_page') {
                    return;
                }

                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                } else if (field.name === 'sort') {
                    field.value = 'latest';
                } else {
                    field.value = '';
                }
            });

            this.fetchResults();
        },
    }));

    Alpine.data('vehicleGallery', (images = [], initial = 0) => ({
        images,
        active: initial,

        get current() {
            return this.images[this.active] || { url: '', srcset: '', alt: '', width: 1280, height: 800 };
        },

        select(index) {
            this.active = index;
        },

        next() {
            this.active = (this.active + 1) % this.images.length;
        },

        previous() {
            this.active = (this.active - 1 + this.images.length) % this.images.length;
        },

        onKeydown(event) {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.next();
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.previous();
            }
        },
    }));

    Alpine.data('photoUpload', () => ({
        previews: [],

        onChange(event) {
            this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
            this.previews = [...event.target.files].map((file) => ({
                name: file.name,
                url: URL.createObjectURL(file),
            }));
        },
    }));

    Alpine.data('resendCountdown', (seconds = 0) => ({
        seconds,
        timer: null,

        init() {
            if (this.seconds > 0) {
                this.start();
            }
        },

        start() {
            window.clearInterval(this.timer);
            this.timer = window.setInterval(() => {
                this.seconds = Math.max(0, this.seconds - 1);

                if (this.seconds === 0) {
                    window.clearInterval(this.timer);
                }
            }, 1000);
        },

        get label() {
            return this.seconds > 0 ? `Resend in ${this.seconds}s` : 'Resend code';
        },
    }));
});

Alpine.start();

// Format the single OTP field as digits only while typing.
document.addEventListener('input', (event) => {
    if (event.target.matches('[data-otp-input]')) {
        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 6);
    }
});
