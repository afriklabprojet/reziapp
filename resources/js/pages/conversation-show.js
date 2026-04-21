/**
 * Chat App - Alpine.js component for real-time conversation messaging
 * Extracted from resources/views/conversations/show-new.blade.php
 *
 * Usage in Blade:
 *   x-data="chatApp(@js($chatConfig))"
 *   where $chatConfig is passed from the controller/view
 */
export default function chatApp(config) {
    return {
        conversationId: config.conversationId,
        currentUserId: config.currentUserId,
        otherUserId: config.otherUserId,
        messages: config.messages,
        newMessage: '',
        sending: false,
        isTyping: false,
        isOnline: false,
        replyingTo: null,
        attachment: null,
        attachmentPreview: null,
        imagePreview: null,
        hasMoreMessages: config.hasMoreMessages,
        loadingMore: false,
        messageInputHeight: '42px',
        typingTimeout: null,
        pusher: null,
        channel: null,

        init() {
            this.scrollToBottom();
            this.initPusher();
            this.markAsRead();
        },

        initPusher() {
            // Initialiser Pusher/Echo pour le temps réel
            if (typeof window.Echo !== 'undefined') {
                this.channel = window.Echo.private(`conversation.${this.conversationId}`)
                    .listen('.message.sent', (e) => {
                        if (e.message.sender_id !== this.currentUserId) {
                            this.messages.push({
                                id: e.message.id,
                                content: e.message.content,
                                is_own: false,
                                created_at: e.message.created_at,
                                status: 'delivered',
                                reply_to: e.message.reply_to,
                                attachment: e.message.attachment,
                            });
                            this.scrollToBottom();
                            this.markAsRead();
                        }
                    })
                    .listen('.user.typing', (e) => {
                        if (e.user_id !== this.currentUserId) {
                            this.isTyping = true;
                            clearTimeout(this.typingTimeout);
                            this.typingTimeout = setTimeout(() => {
                                this.isTyping = false;
                            }, 3000);
                        }
                    })
                    .listen('.messages.read', (e) => {
                        if (e.reader_id !== this.currentUserId) {
                            this.messages.forEach(m => {
                                if (m.is_own) m.status = 'read';
                            });
                        }
                    });

                // Présence channel pour le statut en ligne
                window.Echo.join(`presence.conversation.${this.conversationId}`)
                    .here((users) => {
                        this.isOnline = users.some(u => u.id === this.otherUserId);
                    })
                    .joining((user) => {
                        if (user.id === this.otherUserId) this.isOnline = true;
                    })
                    .leaving((user) => {
                        if (user.id === this.otherUserId) this.isOnline = false;
                    });
            }
        },

        async sendMessage() {
            if ((!this.newMessage.trim() && !this.attachment) || this.sending) return;

            this.sending = true;
            const content = this.newMessage;
            const replyTo = this.replyingTo;

            // Ajouter optimistiquement
            const tempId = 'temp-' + Date.now();
            this.messages.push({
                id: tempId,
                content: content,
                is_own: true,
                created_at: new Date().toISOString(),
                status: 'sending',
                reply_to: replyTo,
                attachment: this.attachmentPreview ? { type: 'image', url: this.attachmentPreview } : null,
            });

            this.newMessage = '';
            this.replyingTo = null;
            this.scrollToBottom();

            try {
                const formData = new FormData();
                formData.append('content', content);
                if (replyTo) formData.append('reply_to_id', replyTo.id);
                if (this.attachment) formData.append('attachment', this.attachment);

                const response = await fetch(`/conversations/${this.conversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    // Remplacer le message temporaire
                    const index = this.messages.findIndex(m => m.id === tempId);
                    if (index !== -1) {
                        this.messages[index] = {
                            ...this.messages[index],
                            id: data.message.id,
                            status: 'sent',
                        };
                    }
                }
            } catch (error) {
                console.error('Erreur envoi message:', error);
                // Marquer comme échec
                const index = this.messages.findIndex(m => m.id === tempId);
                if (index !== -1) {
                    this.messages[index].status = 'failed';
                }
            }

            this.sending = false;
            this.removeAttachment();
        },

        handleTyping() {
            // Auto-resize textarea
            const input = this.$refs.messageInput;
            input.style.height = '42px';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';

            // Envoyer indicateur de frappe
            if (window.Echo) {
                window.Echo.private(`conversation.${this.conversationId}`)
                    .whisper('typing', { user_id: this.currentUserId });
            }
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('Le fichier est trop volumineux (max 5MB)');
                return;
            }

            this.attachment = file;
            this.attachmentPreview = URL.createObjectURL(file);
        },

        removeAttachment() {
            this.attachment = null;
            if (this.attachmentPreview) {
                URL.revokeObjectURL(this.attachmentPreview);
                this.attachmentPreview = null;
            }
        },

        openImagePreview(url) {
            this.imagePreview = url;
        },

        async markAsRead() {
            try {
                await fetch(`/conversations/${this.conversationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
            } catch (_e) {}
        },

        async loadMoreMessages() {
            if (this.loadingMore || !this.hasMoreMessages) return;

            this.loadingMore = true;
            const oldestId = this.messages[0]?.id;

            try {
                const response = await fetch(`/conversations/${this.conversationId}/messages?before=${oldestId}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();

                if (data.messages.length > 0) {
                    this.messages = [...data.messages, ...this.messages];
                }
                this.hasMoreMessages = data.has_more;
            } catch (e) {
                console.error('Erreur chargement messages:', e);
            }

            this.loadingMore = false;
        },

        handleScroll(e) {
            if (e.target.scrollTop === 0 && this.hasMoreMessages) {
                this.loadMoreMessages();
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        showDateSeparator(index) {
            if (index === 0) return true;
            const current = new Date(this.messages[index].created_at).toDateString();
            const previous = new Date(this.messages[index - 1].created_at).toDateString();
            return current !== previous;
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            if (date.toDateString() === today.toDateString()) {
                return "Aujourd'hui";
            } else if (date.toDateString() === yesterday.toDateString()) {
                return 'Hier';
            } else {
                return date.toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                });
            }
        },

        formatTime(dateString) {
            return new Date(dateString).toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        archiveConversation() {
            if (confirm('Archiver cette conversation ?')) {
                fetch(`/conversations/${this.conversationId}/archive`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/conversations';
                    }
                })
                .catch(() => alert('Une erreur est survenue.'));
            }
        },

        blockUser() {
            if (confirm('Bloquer cet utilisateur ? Il ne pourra plus vous envoyer de messages.')) {
                fetch(`/conversations/${this.conversationId}/block`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/conversations';
                    }
                })
                .catch(() => alert('Une erreur est survenue.'));
            }
        }
    };
}
