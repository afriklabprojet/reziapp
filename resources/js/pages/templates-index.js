export default function templatesManager(config = {}) {
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
        showCreateModal: false,
        showEditModal: false,
        saving: false,
        editingId: null,
        form: {
            name: '',
            category: 'greeting',
            content: '',
            shortcut: '',
        },

        closeModal() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.editingId = null;
            this.form = { name: '', category: 'greeting', content: '', shortcut: '' };
        },

        editTemplate(template) {
            this.form = {
                name: template.name,
                category: template.category,
                content: template.content,
                shortcut: template.shortcut || '',
            };
            this.editingId = template.id;
            this.showEditModal = true;
        },

        async saveTemplate() {
            this.saving = true;

            try {
                const url = this.showEditModal
                    ? `/templates/${this.editingId}`
                    : '/templates';

                const method = this.showEditModal ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else if (data.error) {
                    alert(data.error);
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.saving = false;
            }
        },

        async deleteTemplate(id) {
            if (!confirm('Supprimer ce template ?')) return;

            await fetch(`/templates/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            location.reload();
        },

        async duplicateTemplate(id) {
            await fetch(`/templates/${id}/duplicate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            location.reload();
        },
    };
}
