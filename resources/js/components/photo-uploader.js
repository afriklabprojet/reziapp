/**
 * Photo Uploader - Alpine.js component for the photo upload section.
 * Used in residence creation form.
 * Extracted for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="photoUploadCreate()"
 */
export default function photoUploadCreate() {
    const MAX_FILES = 10;
    const MAX_SIZE = 5 * 1024 * 1024; // 5 MB

    return {
        previews: [],
        files: [],
        isDragging: false,

        handlePhotos(fileList) {
            for (let i = 0; i < fileList.length && this.files.length < MAX_FILES; i++) {
                const file = fileList[i];
                if (!file.type.startsWith('image/')) continue;
                if (file.size > MAX_SIZE) {
                    alert("L'image " + file.name + ' dépasse 5 Mo');
                    continue;
                }

                this.files = [...this.files, file];
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previews = [...this.previews, e.target.result];
                };
                reader.readAsDataURL(file);
            }

            this.updateFileInput();
        },

        removePhoto(index) {
            this.previews = this.previews.filter((_, i) => i !== index);
            this.files = this.files.filter((_, i) => i !== index);
            this.updateFileInput();
        },

        updateFileInput() {
            const dataTransfer = new DataTransfer();
            this.files.forEach(file => dataTransfer.items.add(file));
            this.$refs.photos.files = dataTransfer.files;
        },
    };
}
