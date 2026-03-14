export default function lazyImage(src, blurSrc = null) {
    return {
        actualSrc: '',
        blurSrc: blurSrc,
        loaded: false,
        error: false,
        observer: null,

        observe() {
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.loadImage();
                            this.observer.disconnect();
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });

                this.observer.observe(this.$el);
            } else {
                this.loadImage();
            }
        },

        loadImage() {
            this.actualSrc = this.$refs.img.dataset.src;
        },

        onLoad() {
            this.loaded = true;
            this.error = false;
        },

        onError() {
            this.error = true;
            this.loaded = true;
        }
    };
}
