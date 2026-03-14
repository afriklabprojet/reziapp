/**
 * Support ticket show — auto-scroll messages
 * Extracted from support/show.blade.php
 */
export default function supportShow() {
    return {
        init() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
    };
}
