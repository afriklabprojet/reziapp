export default function mobileHeader(transparent = false) {
    return {
        scrolled: false,
        menuOpen: false,
        transparent: Boolean(transparent),
        scrollHandler: null,

        init() {
            this.scrollHandler = () => {
                this.scrolled = (globalThis.scrollY ?? 0) > 20;
            };

            this.scrollHandler();
            globalThis.addEventListener('scroll', this.scrollHandler, { passive: true });
        },

        destroy() {
            if (this.scrollHandler) {
                globalThis.removeEventListener('scroll', this.scrollHandler);
            }
        },
    };
}
