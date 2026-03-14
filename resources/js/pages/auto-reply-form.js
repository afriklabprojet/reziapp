/**
 * Auto-reply form — keyword management + variable insertion
 * Extracted from owner/auto-replies/create.blade.php
 */
export default function autoReplyForm(config = {}) {
    return {
        triggerType: config.triggerType || 'manual',
        keywords: config.keywords || [],
        newKeyword: '',

        addKeyword() {
            if (this.newKeyword.trim() && !this.keywords.includes(this.newKeyword.trim())) {
                this.keywords.push(this.newKeyword.trim().toLowerCase());
                this.newKeyword = '';
            }
        },

        insertVariable(variable) {
            const textarea = document.querySelector('textarea[name="message"]');
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + variable + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + variable.length, start + variable.length);
        }
    };
}
