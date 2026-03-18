/**
 * Residence Wizard - Alpine.js component for multi-step residence creation
 * Extracted from resources/views/owner/residences/wizard.blade.php
 *
 * Usage in Blade:
 *   x-data="residenceWizard(@js($wizardConfig))"
 */
export default function residenceWizard(config) {
    return {
        currentStep: 0,
        isSubmitting: false,
        isDragging: false,
        aiLoading: false,
        aiTitleLoading: false,
        aiImproveLoading: false,
        aiError: '',

        steps: [
            { title: 'Type' },
            { title: 'Infos' },
            { title: 'Lieu' },
            { title: 'Équipements' },
            { title: 'Photos' },
            { title: 'Prix' },
            { title: 'Publier' }
        ],

        propertyTypes: [
            { value: 'apartment', label: 'Appartement', icon: '🏢', description: 'Studio, F2, F3...' },
            { value: 'house', label: 'Maison', icon: '🏠', description: 'Villa, duplex...' },
            { value: 'studio', label: 'Studio', icon: '🛏️', description: 'Pièce unique' },
            { value: 'villa', label: 'Villa', icon: '🏡', description: 'Avec jardin/piscine' },
            { value: 'duplex', label: 'Duplex', icon: '🏘️', description: 'Sur 2 niveaux' },
            { value: 'room', label: 'Chambre', icon: '🚪', description: 'Chez l\'habitant' }
        ],

        amenities: config.amenities || [],

        formData: {
            type: '',
            name: '',
            description: '',
            bedrooms: 1,
            bathrooms: 1,
            surface_area: null,
            commune: '',
            quartier: '',
            address: '',
            latitude: null,
            longitude: null,
            amenities: [],
            photos: [],
            price_per_day: null,
            price_per_week: null,
            price_per_month: null,
            is_available: true,
            accept_terms: false
        },

        init() {
            // Charger le brouillon si existant
            const draft = localStorage.getItem('residence_draft');
            if (draft) {
                const saved = JSON.parse(draft);
                // Ne pas restaurer les photos (objets File non sérialisables)
                delete saved.photos;
                this.formData = { ...this.formData, ...saved };
            }
        },

        canProceed() {
            switch (this.currentStep) {
                case 0: return this.formData.type !== '';
                case 1: return this.formData.name.length >= 10 && this.formData.description.length >= 50 && this.formData.bedrooms >= 0;
                case 2: return this.formData.commune && this.formData.quartier && this.formData.address;
                case 3: return true; // Équipements optionnels
                case 4: return this.formData.photos.length >= 1;
                case 5: return this.formData.price_per_day >= 5000;
                case 6: return this.formData.accept_terms;
                default: return true;
            }
        },

        nextStep() {
            if (this.canProceed() && this.currentStep < this.steps.length - 1) {
                this.currentStep++;
                this.saveDraft();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        goToStep(index) {
            if (index < this.currentStep) {
                this.currentStep = index;
            }
        },

        saveDraft() {
            const toSave = { ...this.formData };
            delete toSave.photos; // Non sérialisable
            localStorage.setItem('residence_draft', JSON.stringify(toSave));
        },

        detectLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.formData.latitude = pos.coords.latitude;
                        this.formData.longitude = pos.coords.longitude;
                        // Affiche la précision pour le propriétaire
                        const accuracy = Math.round(pos.coords.accuracy);
                        if (accuracy > 100) {
                            alert(`Position détectée (précision: ±${accuracy}m). Pour plus de précision, activez le GPS de votre appareil et réessayez.`);
                        }
                    },
                    (err) => {
                        if (err.code === 1) {
                            alert('Accès à la position refusé. Veuillez autoriser la géolocalisation dans les paramètres de votre navigateur.');
                        } else {
                            alert('Impossible de détecter votre position. Vérifiez que le GPS est activé.');
                        }
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
                );
            }
        },

        handleFiles(event) {
            const files = Array.from(event.target.files);
            this.addPhotos(files);
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            this.addPhotos(files);
        },

        addPhotos(files) {
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`${file.name} est trop volumineux (max 5MB)`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.formData.photos.push({
                        file: file,
                        preview: e.target.result,
                        isPrimary: this.formData.photos.length === 0
                    });
                };
                reader.readAsDataURL(file);
            });
        },

        removePhoto(index) {
            const wasPrimary = this.formData.photos[index].isPrimary;
            this.formData.photos.splice(index, 1);
            if (wasPrimary && this.formData.photos.length > 0) {
                this.formData.photos[0].isPrimary = true;
            }
        },

        setPrimaryPhoto(index) {
            this.formData.photos.forEach((p, i) => p.isPrimary = (i === index));
        },

        getTypeLabel(value) {
            const type = this.propertyTypes.find(t => t.value === value);
            return type ? type.label : '—';
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price || 0);
        },

        getAiContext() {
            return {
                type: this.formData.type || '',
                type_location: 'residence_meublee',
                commune: this.formData.commune || '',
                bedrooms: this.formData.bedrooms || '',
                bathrooms: this.formData.bathrooms || '',
                surface_area: this.formData.surface_area || '',
                price: this.formData.price_per_day || '',
            };
        },

        async aiGenerateDescription() {
            this.aiError = '';
            const ctx = this.getAiContext();
            if (!ctx.type) { this.aiError = 'Veuillez d\'abord sélectionner le type de résidence.'; return; }
            this.aiLoading = true;
            try {
                const res = await fetch((config.aiUrls && config.aiUrls.generateDescription) || '/owner/ai/generate-description', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(ctx),
                });
                const data = await res.json();
                if (data.description) {
                    this.formData.description = data.description;
                } else {
                    this.aiError = data.error || 'Erreur lors de la génération.';
                }
            } catch (e) { this.aiError = 'Erreur de connexion.'; }
            this.aiLoading = false;
        },

        async aiGenerateTitle() {
            this.aiError = '';
            const ctx = this.getAiContext();
            if (!ctx.type) { this.aiError = 'Veuillez d\'abord sélectionner le type de résidence.'; return; }
            this.aiTitleLoading = true;
            try {
                const res = await fetch((config.aiUrls && config.aiUrls.generateTitle) || '/owner/ai/generate-title', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(ctx),
                });
                const data = await res.json();
                if (data.title) {
                    this.formData.name = data.title;
                } else {
                    this.aiError = data.error || 'Erreur lors de la génération.';
                }
            } catch (e) { this.aiError = 'Erreur de connexion.'; }
            this.aiTitleLoading = false;
        },

        async aiImproveDescription() {
            if (this.formData.description.length < 10) { this.aiError = 'Écrivez au moins quelques mots avant d\'améliorer.'; return; }
            this.aiImproveLoading = true;
            this.aiError = '';
            try {
                const ctx = this.getAiContext();
                ctx.description = this.formData.description;
                const res = await fetch((config.aiUrls && config.aiUrls.improveDescription) || '/owner/ai/improve-description', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(ctx),
                });
                const data = await res.json();
                if (data.description) {
                    this.formData.description = data.description;
                } else {
                    this.aiError = data.error || 'Erreur lors de l\'amélioration.';
                }
            } catch (e) { this.aiError = 'Erreur de connexion.'; }
            this.aiImproveLoading = false;
        },

        async submitForm() {
            if (!this.formData.accept_terms) return;

            this.isSubmitting = true;

            const formData = new FormData();

            // Ajouter les champs
            Object.keys(this.formData).forEach(key => {
                if (key === 'photos') return;
                if (key === 'amenities') {
                    this.formData.amenities.forEach(id => formData.append('amenities[]', id));
                } else if (this.formData[key] !== null && this.formData[key] !== '') {
                    formData.append(key, this.formData[key]);
                }
            });

            // Ajouter les photos
            this.formData.photos.forEach((photo, index) => {
                formData.append('photos[]', photo.file);
                if (photo.isPrimary) {
                    formData.append('primary_photo', index);
                }
            });

            try {
                const response = await fetch(config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: formData
                });

                if (response.ok) {
                    localStorage.removeItem('residence_draft');
                    const data = await response.json();
                    window.location.href = data.redirect || config.indexUrl;
                } else {
                    const error = await response.json();
                    alert(error.message || 'Une erreur est survenue');
                }
            } catch (e) {
                alert('Erreur de connexion');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
