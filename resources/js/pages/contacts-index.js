/**
 * Contacts auto mark-as-viewed
 * Extracted from owner/contacts/index.blade.php
 */
export default function contactsIndex() {
    return {
        init() {
            const pendingContacts = document.querySelectorAll('[data-contact-pending]');
            pendingContacts.forEach(contact => {
                setTimeout(() => {
                    fetch(contact.dataset.viewUrl, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                }, 2000);
            });
        }
    };
}
