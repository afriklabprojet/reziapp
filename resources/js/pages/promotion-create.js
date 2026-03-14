/**
 * Promotion creation form — live preview updater
 * Extracted from owner/marketing/promotions/create.blade.php
 */
export default function promotionCreate() {
    return {
        init() {
            const preview = document.getElementById('preview');
            const previewDiscount = document.getElementById('preview-discount');
            const previewName = document.getElementById('preview-name');
            const previewDates = document.getElementById('preview-dates');

            const nameInput = document.getElementById('name');
            const discountTypeInput = document.getElementById('discount_type');
            const discountValueInput = document.getElementById('discount_value');
            const startsAtInput = document.getElementById('starts_at');
            const endsAtInput = document.getElementById('ends_at');

            function updatePreview() {
                const name = nameInput.value || 'Nom de la promotion';
                const discountType = discountTypeInput.value;
                const discountValue = discountValueInput.value || 0;
                const startsAt = startsAtInput.value;
                const endsAt = endsAtInput.value;

                if (discountValue > 0) {
                    preview.classList.remove('hidden');
                }

                if (discountType === 'percentage') {
                    previewDiscount.textContent = `-${discountValue}%`;
                } else {
                    previewDiscount.textContent = `-${Number(discountValue).toLocaleString('fr-FR')} FCFA`;
                }

                previewName.textContent = name;

                if (startsAt && endsAt) {
                    const startDate = new Date(startsAt).toLocaleDateString('fr-FR');
                    const endDate = new Date(endsAt).toLocaleDateString('fr-FR');
                    previewDates.textContent = `Du ${startDate} au ${endDate}`;
                }
            }

            [nameInput, discountTypeInput, discountValueInput, startsAtInput, endsAtInput].forEach(input => {
                if (input) {
                    input.addEventListener('input', updatePreview);
                    input.addEventListener('change', updatePreview);
                }
            });

            // Set min date for ends_at based on starts_at
            if (startsAtInput && endsAtInput) {
                startsAtInput.addEventListener('change', function () {
                    endsAtInput.min = this.value;
                    if (endsAtInput.value && endsAtInput.value < this.value) {
                        endsAtInput.value = this.value;
                    }
                });
            }
        }
    };
}
