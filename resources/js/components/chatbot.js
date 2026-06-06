/**
 * Chatbot IA ReziApp — widget flottant 24/7
 * Alpine component : x-data="chatbot(config)"
 */
export default function chatbot(config = {}) {
    return {
        // State
        open: false,
        loading: false,
        messages: [],
        input: '',
        error: null,

        // Context enrichi (commune/résidence visible)
        context: {
            commune:   config.commune   ?? null,
            budget:    config.budget    ?? null,
            residence: config.residence ?? null,
        },

        // -------------------------------------------------------
        // Init
        // -------------------------------------------------------
        init() {
            // Message de bienvenue
            this.messages = [{
                role: 'assistant',
                content: "Bonjour ! Je suis l'assistant ReziApp 🏠\nComment puis-je vous aider à trouver votre résidence idéale à Abidjan ?",
            }];

            // Scroll auto sur nouveaux messages
            this.$watch('messages', () => {
                this.$nextTick(() => this.scrollToBottom());
            });
        },

        // -------------------------------------------------------
        // Toggle widget
        // -------------------------------------------------------
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.input?.focus();
                });
            }
        },

        // -------------------------------------------------------
        // Envoyer un message
        // -------------------------------------------------------
        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;

            this.input   = '';
            this.error   = null;
            this.loading = true;

            // Ajouter le message user à l'historique
            this.messages.push({ role: 'user', content: text });

            // Construire l'historique à envoyer (max 10 tours = 20 messages)
            const history = this.messages.slice(-20);

            try {
                const res = await fetch('/api/v1/chatbot/message', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        messages: history,
                        context:  this.context,
                    }),
                });

                if (res.status === 429) {
                    this.messages.push({
                        role:    'assistant',
                        content: 'Vous envoyez trop de messages. Attendez une minute.',
                        limited: true,
                    });
                    return;
                }

                const data = await res.json();

                this.messages.push({
                    role:    'assistant',
                    content: data.reply ?? "Désolé, une erreur s'est produite.",
                    error:   data.error ?? false,
                });

            } catch (e) {
                this.messages.push({
                    role:    'assistant',
                    content: "Une erreur réseau s'est produite. Vérifiez votre connexion.",
                    error:   true,
                });
            } finally {
                this.loading = false;
            }
        },

        // -------------------------------------------------------
        // Touche Entrée (sans Shift)
        // -------------------------------------------------------
        handleKey(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.send();
            }
        },

        // -------------------------------------------------------
        // Scroll en bas de la liste
        // -------------------------------------------------------
        scrollToBottom() {
            const el = this.$refs.messagesList;
            if (el) el.scrollTop = el.scrollHeight;
        },

        // -------------------------------------------------------
        // Formater le contenu (retours à la ligne)
        // -------------------------------------------------------
        formatMessage(content) {
            return (content ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
        },

        // -------------------------------------------------------
        // Questions rapides (suggestions)
        // -------------------------------------------------------
        suggestions: [
            'Comment réserver une résidence ?',
            'Quels documents faut-il ?',
            'Comment fonctionne le paiement ?',
            'Quels sont les meilleurs quartiers ?',
        ],

        useSuggestion(text) {
            this.input = text;
            this.send();
        },
    };
}
