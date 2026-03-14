/**
 * Residence Page - Alpine.js component for residence detail page
 * Extracted from resources/views/residences/show.blade.php
 *
 * Usage in Blade:
 *   x-data="residencePage(@js(['totalPhotos' => $allPhotos->count(), 'title' => $residence->title]))"
 */
export function residencePage(config) {
    return {
        galleryOpen: false,
        currentPhoto: 0,
        totalPhotos: config.totalPhotos || 0,
        scrolled: false,
        activeSection: 'photos',

        init() {
            window.addEventListener('scroll', () => {
                this.scrolled = window.scrollY > 100;
            });

            const sections = ['photos', 'description', 'equipements', 'avis', 'emplacement', 'regles'];
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.activeSection = entry.target.id;
                    }
                });
            }, { rootMargin: '-100px 0px -60% 0px' });

            sections.forEach(id => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });

            window.addEventListener('keydown', (e) => {
                if (!this.galleryOpen) return;
                if (e.key === 'ArrowLeft') this.prevPhoto();
                if (e.key === 'ArrowRight') this.nextPhoto();
            });
        },

        openGallery(index) {
            this.currentPhoto = index;
            this.galleryOpen = true;
            document.body.style.overflow = 'hidden';
        },

        prevPhoto() {
            this.currentPhoto = this.currentPhoto > 0 ? this.currentPhoto - 1 : this.totalPhotos - 1;
        },

        nextPhoto() {
            this.currentPhoto = this.currentPhoto < this.totalPhotos - 1 ? this.currentPhoto + 1 : 0;
        },

        shareResidence() {
            if (navigator.share) {
                navigator.share({
                    title: config.title,
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Lien copié !');
            }
        }
    };
}

/**
 * Booking Form - Alpine.js component for quick booking sidebar
 *
 * Usage in Blade:
 *   x-data="bookingForm(@js([...config]))"
 */
export function bookingForm(config) {
    return {
        // State
        checkIn: '',
        checkOut: '',
        guests: 1,
        adults: 1,
        children: 0,
        infants: 0,
        showGuestPicker: false,
        message: '',
        promoCode: '',
        promoApplied: false,
        promoDiscount: 0,
        promoError: '',

        // Config from server
        pricePerNight: config.pricePerNight || 0,
        pricePerWeek: config.pricePerWeek || 0,
        pricePerMonth: config.pricePerMonth || 0,
        maxGuests: config.maxGuests || 10,
        minNights: config.minNights || 1,
        maxNights: config.maxNights || 365,
        instantBook: config.instantBook || false,
        residenceId: config.residenceId || 0,
        unavailableDates: config.unavailableDates || [],
        cleaningFee: config.cleaningFee || 0,
        isAuthenticated: config.isAuthenticated || false,

        // UI State
        loading: false,
        checking: false,
        available: null, // null = not checked, true/false
        availabilityMessage: '',
        serverPrice: null,
        showPriceBreakdown: false,
        error: '',

        init() {
            // Watch date changes to check availability
            this.$watch('checkIn', () => this.onDatesChange());
            this.$watch('checkOut', () => this.onDatesChange());

            // Close guest picker on outside click
            document.addEventListener('click', (e) => {
                if (!this.$refs.guestPicker?.contains(e.target) && !this.$refs.guestTrigger?.contains(e.target)) {
                    this.showGuestPicker = false;
                }
            });
        },

        // Computed
        get totalGuests() {
            return this.adults + this.children;
        },

        get guestLabel() {
            let parts = [];
            const total = this.adults + this.children;
            parts.push(`${total} voyageur${total > 1 ? 's' : ''}`);
            if (this.infants > 0) parts.push(`${this.infants} bébé${this.infants > 1 ? 's' : ''}`);
            return parts.join(', ');
        },

        get nights() {
            if (!this.checkIn || !this.checkOut) return 0;
            const start = new Date(this.checkIn);
            const end = new Date(this.checkOut);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            return diff > 0 ? diff : 0;
        },

        get nightsLabel() {
            if (this.nights === 0) return '';
            return `${this.nights} nuit${this.nights > 1 ? 's' : ''}`;
        },

        get unitPrice() {
            // Use best price based on duration
            if (this.nights >= 30 && this.pricePerMonth > 0) {
                return { amount: this.pricePerMonth, unit: 'mois', perNight: Math.round(this.pricePerMonth / 30) };
            }
            if (this.nights >= 7 && this.pricePerWeek > 0) {
                return { amount: this.pricePerWeek, unit: 'semaine', perNight: Math.round(this.pricePerWeek / 7) };
            }
            return { amount: this.pricePerNight, unit: 'nuit', perNight: this.pricePerNight };
        },

        get subtotal() {
            if (this.serverPrice?.subtotal) return this.serverPrice.subtotal;
            return this.nights * this.unitPrice.perNight;
        },

        get serviceFee() {
            if (this.serverPrice?.service_fee !== undefined) return this.serverPrice.service_fee;
            return Math.round(this.subtotal * 0.10);
        },

        get totalCleaningFee() {
            if (this.serverPrice?.cleaning_fee !== undefined) return this.serverPrice.cleaning_fee;
            return this.cleaningFee;
        },

        get discount() {
            if (this.serverPrice?.discount) return this.serverPrice.discount;
            if (this.promoApplied) return this.promoDiscount;
            // Weekly discount
            if (this.nights >= 7 && this.nights < 30 && this.pricePerWeek > 0) {
                const fullPrice = this.nights * this.pricePerNight;
                const weeklyPrice = this.subtotal;
                return fullPrice > weeklyPrice ? fullPrice - weeklyPrice : 0;
            }
            // Monthly discount
            if (this.nights >= 30 && this.pricePerMonth > 0) {
                const fullPrice = this.nights * this.pricePerNight;
                const monthlyPrice = this.subtotal;
                return fullPrice > monthlyPrice ? fullPrice - monthlyPrice : 0;
            }
            return 0;
        },

        get total() {
            if (this.serverPrice?.total) return this.serverPrice.total;
            return this.subtotal + this.serviceFee + this.totalCleaningFee - this.discount;
        },

        get canSubmit() {
            return this.checkIn && this.checkOut && this.nights >= this.minNights &&
                this.nights <= this.maxNights && this.totalGuests <= this.maxGuests &&
                this.available !== false && !this.loading && !this.checking;
        },

        get minNightsError() {
            if (this.nights > 0 && this.nights < this.minNights) {
                return `Minimum ${this.minNights} nuit${this.minNights > 1 ? 's' : ''}`;
            }
            return '';
        },

        get maxNightsError() {
            if (this.nights > this.maxNights) {
                return `Maximum ${this.maxNights} nuit${this.maxNights > 1 ? 's' : ''}`;
            }
            return '';
        },

        // Methods
        isDateBlocked(dateStr) {
            return this.unavailableDates.includes(dateStr);
        },

        async onDatesChange() {
            this.serverPrice = null;
            this.error = '';
            this.available = null;
            this.promoApplied = false;
            this.promoDiscount = 0;

            if (!this.checkIn || !this.checkOut || this.nights <= 0) return;
            if (this.nights < this.minNights) return;

            await this.checkAvailability();
        },

        async checkAvailability() {
            if (!this.isAuthenticated) {
                this.available = true;
                return;
            }
            this.checking = true;
            this.error = '';
            try {
                const response = await fetch(`/residences/${this.residenceId}/check-availability`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        check_in: this.checkIn,
                        check_out: this.checkOut,
                    }),
                });
                const data = await response.json();
                this.available = data.available ?? false;
                this.availabilityMessage = data.message || '';
                if (this.available) {
                    await this.fetchPrice();
                }
            } catch (e) {
                this.available = true; // Assume available on error
            } finally {
                this.checking = false;
            }
        },

        async fetchPrice() {
            this.loading = true;
            try {
                const response = await fetch(`/residences/${this.residenceId}/calculate-price`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        check_in: this.checkIn,
                        check_out: this.checkOut,
                        guests: this.totalGuests,
                        promo_code: this.promoCode || null,
                    }),
                });
                const data = await response.json();
                if (data.success && data.price) {
                    this.serverPrice = data.price;
                }
            } catch (e) {
                // Use client-side calculation as fallback
            } finally {
                this.loading = false;
            }
        },

        async applyPromo() {
            if (!this.promoCode.trim()) return;
            this.promoError = '';
            await this.fetchPrice();
            if (this.serverPrice?.discount > 0) {
                this.promoApplied = true;
                this.promoDiscount = this.serverPrice.discount;
            } else {
                this.promoError = 'Code promo invalide ou expiré';
            }
        },

        incrementGuest(type) {
            if (type === 'adults' && this.adults + this.children < this.maxGuests) this.adults++;
            if (type === 'children' && this.adults + this.children < this.maxGuests) this.children++;
            if (type === 'infants' && this.infants < 5) this.infants++;
        },

        decrementGuest(type) {
            if (type === 'adults' && this.adults > 1) this.adults--;
            if (type === 'children' && this.children > 0) this.children--;
            if (type === 'infants' && this.infants > 0) this.infants--;
        },

        formatPrice(amount) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA';
        },

        get todayStr() {
            return new Date().toISOString().split('T')[0];
        }
    };
}

/**
 * Residence Map - Initialize Leaflet map for residence location
 *
 * Usage in Blade:
 *   <script type="application/json" id="map-config">@json(['lat' => ..., 'lng' => ...])</script>
 *   Then call initResidenceMap() after DOM is ready
 */
export function initResidenceMap(config) {
    if (!window.L) return;

    const map = L.map('map', {
        scrollWheelZoom: false,
        attributionControl: false
    }).setView([config.lat, config.lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    L.circle([config.lat, config.lng], {
        color: '#f43f5e',
        fillColor: '#f43f5e',
        fillOpacity: 0.15,
        radius: 250,
        weight: 2
    }).addTo(map);

    L.circleMarker([config.lat, config.lng], {
        color: '#fff',
        fillColor: '#f43f5e',
        fillOpacity: 1,
        radius: 8,
        weight: 3
    }).addTo(map);

    map.getContainer().addEventListener('click', () => map.scrollWheelZoom.enable());
    map.getContainer().addEventListener('mouseleave', () => map.scrollWheelZoom.disable());

    return map;
}
