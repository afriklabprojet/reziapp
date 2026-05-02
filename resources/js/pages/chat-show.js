/**
 * REZI Chat — Real Messenger Experience (v2)
 * Features: optimistic UI, real-time via Echo, typing indicator, mark-as-read,
 * sound notifications, online status, infinite scroll, message editing/deleting,
 * confirmation dialogs, delivered indicator, push notification request
 */
export default function chatShow(config = {}) {
    return {
        // State
        newMessage: '',
        sending: false,
        showTemplates: false,
        showDocuments: false,
        showQuickReplies: false,
        replyTo: null,
        replyToContent: '',
        isTyping: false,
        typingUser: '',
        typingTimeout: null,
        lastMessageId: config.lastMessageId || 0,
        conversationId: config.conversationId,
        currentUserId: config.currentUserId,
        isPinned: config.isPinned || false,
        isMuted: config.isMuted || false,
        isArchived: config.isArchived || false,
        chatIndexUrl: config.chatIndexUrl || '/chat',
        ownAvatarUrl: config.ownAvatarUrl || '',
        ownInitial: config.ownInitial || '?',
        otherAvatarUrl: config.otherAvatarUrl || '',
        otherInitial: config.otherInitial || '?',
        hasMoreMessages: config.hasMoreMessages || false,
        firstMessageId: config.firstMessageId || 0,
        loadingMore: false,
        showEmojiPicker: false,
        emojiTab: 0,
        emojiCategories: [
            { name: 'Smileys', icon: '😊', emojis: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🫢', '🤫', '🤔', '🫡', '🤐', '🤨', '😐', '😑', '😶', '🫥', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '🥸', '😎', '🤓', '🧐'] },
            { name: 'Gestes', icon: '👋', emojis: ['👋', '🤚', '🖐️', '✋', '🖖', '🫱', '🫲', '🫳', '🫴', '👌', '🤌', '🤏', '✌️', '🤞', '🫰', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '🫵', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '🫶', '👐', '🤲', '🤝', '🙏', '💪', '🦾', '🖤', '❤️', '🧡', '💛', '💚', '💙', '💜', '🤎', '🖤', '🤍', '💯', '💢', '💥', '💫', '💦', '💨'] },
            { name: 'Personnes', icon: '👨', emojis: ['👶', '👧', '🧒', '👦', '👩', '🧑', '👨', '👩‍🦱', '🧑‍🦱', '👨‍🦱', '👩‍🦰', '🧑‍🦰', '👨‍🦰', '👱‍♀️', '👱', '👱‍♂️', '👩‍🦳', '🧑‍🦳', '👨‍🦳', '👩‍🦲', '🧑‍🦲', '👨‍🦲', '🧔‍♀️', '🧔', '🧔‍♂️', '👵', '🧓', '👴', '👲', '👳‍♀️', '👳', '👳‍♂️', '🧕', '👮‍♀️', '👮', '👷‍♀️', '👷', '💂‍♀️', '💂', '🕵️‍♀️', '🕵️', '👩‍⚕️', '🧑‍⚕️', '👨‍⚕️', '👩‍🌾', '🧑‍🌾', '👨‍🌾', '👩‍🍳', '🧑‍🍳', '👨‍🍳', '👩‍🎓', '🧑‍🎓', '👨‍🎓'] },
            { name: 'Animaux', icon: '🐶', emojis: ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐻‍❄️', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉', '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🪱', '🐛', '🦋', '🐌', '🐞', '🐜', '🪰', '🪲', '🪳', '🦟', '🦗', '🕷️', '🕸️', '🦂', '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀', '🐡', '🐠', '🐟', '🐬', '🐳'] },
            { name: 'Nourriture', icon: '🍕', emojis: ['🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶️', '🫑', '🌽', '🥕', '🫒', '🧄', '🧅', '🥔', '🍠', '🫘', '🥐', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳', '🧈', '🥞', '🧇', '🥓', '🥩', '🍗', '🍖', '🌭', '🍔', '🍟', '🍕', '🫓', '🥪', '🥙', '🧆', '🌮', '🌯', '🫔', '🥗', '🍝', '🍜'] },
            { name: 'Voyage', icon: '✈️', emojis: ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚛', '🚜', '🏍️', '🛵', '🚲', '🛴', '🛹', '🛼', '🚁', '✈️', '🛩️', '🛫', '🛬', '🪂', '💺', '🚀', '🛸', '🚉', '🚊', '🚝', '🚞', '🚋', '🚃', '🚂', '🏠', '🏡', '🏘️', '🏗️', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦', '🏨', '🏪', '🏫', '🏩', '💒', '🏛️', '⛪', '🕌', '🕍', '🛕', '🕋', '⛩️', '🏰', '🏯', '🗼'] },
            { name: 'Objets', icon: '💡', emojis: ['⌚', '📱', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '💽', '💾', '💿', '📀', '📷', '📸', '📹', '🎥', '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️', '🎛️', '⏱️', '⏲️', '⏰', '🕰️', '💡', '🔦', '🕯️', '🪔', '💰', '💵', '💴', '💶', '💷', '🪙', '💸', '💳', '🧾', '✉️', '📧', '📨', '📩', '📦', '📫', '📪', '📬', '📭', '📮', '🗳️', '✏️', '✒️', '🖊️', '🖋️', '📝'] },
            { name: 'Symboles', icon: '❤️', emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❤️‍🔥', '❤️‍🩹', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '⭐', '🌟', '✨', '⚡', '🔥', '💫', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🎱', '✅', '❌', '⭕', '❓', '❗', '💯', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🟤', '🔶', '🔷', '🔸', '🔹'] },
        ],
        isOnline: false,
        editingMessageId: null,
        editContent: '',
        // Context menu
        contextMenuVisible: false,
        contextMenuX: 0,
        contextMenuY: 0,
        contextMenuMessageId: null,
        contextMenuIsOwn: false,
        contextMenuContent: '',
        // Messenger features
        themeColor: config.themeColor || 'orange',
        showGifPicker: false,
        gifSearchQuery: '',
        gifResults: [],
        gifLoading: false,
        isRecording: false,
        mediaRecorder: null,
        audioChunks: [],
        recordingDuration: '00:00',
        _recordingInterval: null,
        _recordingStart: null,
        showSearchPanel: false,
        searchQuery: '',
        searchResults: [],
        showThemePicker: false,

        // CSRF
        get csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        init() {
            this.scrollToBottom();
            this.markAsRead();
            this.setupEcho();
            this.setupPollingFallback();
            this.setupVisibility();
            this.setupInfiniteScroll();
            this.requestPushPermission();

            // Focus input
            this.$nextTick(() => {
                this.$refs.messageInput?.focus();
                this.initVoicePlayers();
            });

            // Close context menu on click outside
            document.addEventListener('click', () => { this.contextMenuVisible = false; });
        },

        destroy() {
            if (window.Echo) {
                window.Echo.leave(`conversation.${this.conversationId}`);
            }
            if (this._pollInterval) clearInterval(this._pollInterval);
        },

        // ========================
        // PUSH NOTIFICATION PERMISSION
        // ========================
        requestPushPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                // Demander après 5 secondes pour ne pas être intrusif
                setTimeout(() => {
                    Notification.requestPermission();
                }, 5000);
            }
        },

        // ========================
        // INFINITE SCROLL (load older messages)
        // ========================
        setupInfiniteScroll() {
            const container = this.$refs.messagesContainer;
            if (!container) return;

            container.addEventListener('scroll', () => {
                if (container.scrollTop < 100 && this.hasMoreMessages && !this.loadingMore) {
                    this.loadOlderMessages();
                }
            });
        },

        async loadOlderMessages() {
            if (this.loadingMore || !this.hasMoreMessages) return;
            this.loadingMore = true;

            const container = this.$refs.messagesContainer;
            const prevScrollHeight = container.scrollHeight;

            try {
                const res = await this.fetchJson(`/chat/${this.conversationId}/messages?before=${this.firstMessageId}`);

                if (res.messages && res.messages.length > 0) {
                    const messagesList = container.querySelector('.messages-list');
                    if (!messagesList) return;

                    // Insert older messages at the top
                    res.messages.forEach(msg => {
                        const html = this.buildMessageHtml(msg);
                        messagesList.insertAdjacentHTML('afterbegin', html);
                    });

                    this.firstMessageId = res.messages[0].id;
                    this.hasMoreMessages = res.has_more;

                    // Maintain scroll position
                    this.$nextTick(() => {
                        container.scrollTop = container.scrollHeight - prevScrollHeight;
                    });
                } else {
                    this.hasMoreMessages = false;
                }
            } catch (e) {
                console.warn('Load more failed:', e);
            } finally {
                this.loadingMore = false;
            }
        },

        buildMessageHtml(msg) {
            const time = new Date(msg.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const isOwn = msg.is_own;

            const imageHtml = (msg.type === 'image' && msg.attachments?.length)
                ? msg.attachments.map((att, idx) =>
                    `<div class="p-1.5"><img src="/messages/${msg.id}/image/${idx}" alt="${this.esc(att.name ?? 'Image')}" class="rounded-xl max-h-72 w-auto cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src,'_blank')" loading="lazy"></div>`
                ).join('')
                : '';
            const textHtml = msg.content
                ? `<div class="px-3.5 py-2"><p class="msg-text text-[14.5px] leading-relaxed whitespace-pre-wrap wrap-break-word">${this.esc(msg.content)}</p></div>`
                : '';
            const bubbleContent = imageHtml + textHtml;

            if (isOwn) {
                const avatar = this.ownAvatarUrl
                    ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.ownAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm"></div>`
                    : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">${this.esc(this.ownInitial)}</div></div>`;

                return `
                <div class="flex justify-end group msg-row" id="msg-${msg.id}" @contextmenu.prevent="showContextMenu($event, ${msg.id}, true, ${JSON.stringify(this.esc(msg.content || ''))})">
                    <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                        <div class="space-y-0.5 items-end">
                            <div class="bg-[#ff385c] text-white rounded-2xl rounded-br-md">${bubbleContent}</div>
                            <div class="msg-meta flex items-center gap-1.5 px-1 justify-end">
                                <span class="text-[10px] text-gray-400">${time}</span>
                                <span class="msg-status-icon" data-own="true">${this.statusSvg(msg.status)}</span>
                            </div>
                        </div>
                        ${avatar}
                    </div>
                </div>`;
            } else {
                const avatar = this.otherAvatarUrl
                    ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.otherAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-white shadow-sm"></div>`
                    : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-white shadow-sm">${this.esc(this.otherInitial)}</div></div>`;

                return `
                <div class="flex justify-start group msg-row" id="msg-${msg.id}">
                    <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                        ${avatar}
                        <div class="space-y-0.5 items-start">
                            <div class="bg-white border border-gray-100 text-gray-900 rounded-2xl rounded-bl-md shadow-sm">${bubbleContent}</div>
                            <div class="msg-meta flex items-center gap-1.5 px-1">
                                <span class="text-[10px] text-gray-400">${time}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
        },

        // ========================
        // REAL-TIME (Echo / Polling fallback)
        // ========================
        setupEcho() {
            if (!window.Echo) return;

            try {
                window.Echo.private(`conversation.${this.conversationId}`)
                    .listen('.message.sent', (data) => {
                        if (data.sender_id === this.currentUserId) return;
                        if (document.getElementById(`msg-${data.id}`)) return;

                        this.appendMessage(data, false);
                        this.playNotifSound();
                        this.showBrowserNotification(data);
                        this.markAsRead();
                    })
                    .listen('.messages.read', (data) => {
                        if (data.reader_id !== this.currentUserId) {
                            this.markAllVisibleAsRead();
                        }
                    })
                    .listen('.message.edited', (data) => {
                        this.handleMessageEdited(data);
                    })
                    .listen('.message.deleted', (data) => {
                        this.handleMessageDeleted(data);
                    })
                    .listen('.user.typing', (data) => {
                        if (data.user_id !== this.currentUserId) {
                            this.isTyping = true;
                            this.typingUser = data.user_name || '';
                            clearTimeout(this._typingHideTimeout);
                            this._typingHideTimeout = setTimeout(() => {
                                this.isTyping = false;
                            }, 3000);
                        }
                    });
            } catch (e) {
                console.warn('Echo setup failed, falling back to polling', e);
            }
        },

        setupPollingFallback() {
            this._pollInterval = setInterval(() => this.pollNewMessages(), 3000);
        },

        async pollNewMessages() {
            if (!this.conversationId) return;

            try {
                const res = await this.fetchJson(`/chat/${this.conversationId}/new?after=${this.lastMessageId}`);
                if (res.success && res.messages && res.messages.length > 0) {
                    res.messages.forEach((msg) => {
                        if (msg.sender_id === this.currentUserId) return;
                        if (document.getElementById(`msg-${msg.id}`)) return;
                        this.appendMessage(msg, false);
                    });

                    this.lastMessageId = res.messages[res.messages.length - 1].id;
                    this.playNotifSound();
                    this.markAsRead();
                }
            } catch (_e) { /* silence */ }
        },

        setupVisibility() {
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.markAsRead();
                    this.pollNewMessages();
                }
            });
        },

        // ========================
        // BROWSER NOTIFICATIONS
        // ========================
        showBrowserNotification(data) {
            if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
                const notif = new Notification('Nouveau message — REZI', {
                    body: data.content ? data.content.substring(0, 100) : 'Pièce jointe',
                    icon: '/images/logo-icon.png',
                    tag: `msg-${data.id}`,
                });
                notif.onclick = () => { window.focus(); notif.close(); };
                setTimeout(() => notif.close(), 5000);
            }
        },

        // ========================
        // SEND MESSAGE (Optimistic UI)
        // ========================
        async sendMessage() {
            const content = this.newMessage.trim();
            if (!content || this.sending) return;

            this.sending = true;
            this.newMessage = '';

            if (this.$refs.messageInput) {
                this.$refs.messageInput.style.height = 'auto';
            }

            // Optimistic insert
            const tempId = `temp-${Date.now()}`;
            this.insertOptimisticBubble(tempId, content, 'sending', this.replyToContent);
            this.scrollToBottom();

            const replyToId = this.replyTo;
            this.cancelReply();

            try {
                const res = await this.fetchJson(`/chat/${this.conversationId}/send`, {
                    method: 'POST',
                    body: JSON.stringify({ content, reply_to_id: replyToId }),
                });

                if (res.success && res.message) {
                    const tempEl = document.getElementById(`msg-${tempId}`);
                    if (tempEl) {
                        tempEl.id = `msg-${res.message.id}`;
                        this.updateBubbleStatus(tempEl, 'sent');
                    }
                    this.lastMessageId = res.message.id;
                }
            } catch (err) {
                console.error('Send failed:', err);
                const tempEl = document.getElementById(`msg-${tempId}`);
                if (tempEl) {
                    this.updateBubbleStatus(tempEl, 'failed');
                    const meta = tempEl.querySelector('.msg-meta');
                    if (meta) {
                        const btn = document.createElement('button');
                        btn.className = 'text-xs text-red-500 hover:text-red-700 flex items-center gap-1 mt-0.5';
                        btn.innerHTML = '↻ Réessayer';
                        btn.onclick = () => { tempEl.remove(); this.newMessage = content; this.sendMessage(); };
                        meta.appendChild(btn);
                    }
                }
            } finally {
                this.sending = false;
                this.$refs.messageInput?.focus();
            }
        },

        // ========================
        // FILE UPLOAD
        // ========================
        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Confirmation pour les gros fichiers
            if (file.size > 5 * 1024 * 1024) {
                if (!confirm(`Le fichier fait ${(file.size / 1024 / 1024).toFixed(1)} Mo. Envoyer quand même ?`)) {
                    event.target.value = '';
                    return;
                }
            }

            this.sending = true;
            const tempId = `temp-${Date.now()}`;
            const isImage = file.type.startsWith('image/');
            const previewUrl = isImage ? URL.createObjectURL(file) : null;

            this.insertOptimisticFileBubble(tempId, file.name, isImage, previewUrl, 'uploading');
            this.scrollToBottom();

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(`/chat/${this.conversationId}/attachment`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();

                if (data.success && data.html) {
                    const tempEl = document.getElementById(`msg-${tempId}`);
                    if (tempEl) tempEl.outerHTML = data.html;
                    this.lastMessageId = data.message.id;
                }
            } catch (err) {
                console.error('Upload failed:', err);
                const tempEl = document.getElementById(`msg-${tempId}`);
                if (tempEl) this.updateBubbleStatus(tempEl, 'failed');
            } finally {
                this.sending = false;
                event.target.value = '';
                if (previewUrl) URL.revokeObjectURL(previewUrl);
            }
        },

        // ========================
        // MESSAGE EDIT / DELETE
        // ========================
        showContextMenu(event, messageId, isOwn, content) {
            if (!isOwn) return;
            this.contextMenuMessageId = messageId;
            this.contextMenuIsOwn = isOwn;
            this.contextMenuContent = content;
            this.contextMenuX = Math.min(event.clientX, window.innerWidth - 200);
            this.contextMenuY = Math.min(event.clientY, window.innerHeight - 120);
            this.contextMenuVisible = true;
        },

        startEditMessage() {
            this.contextMenuVisible = false;
            this.editingMessageId = this.contextMenuMessageId;
            const el = document.getElementById(`msg-${this.editingMessageId}`);
            const textEl = el?.querySelector('.msg-text');
            this.editContent = textEl?.textContent?.trim() || '';
        },

        async saveEditMessage() {
            if (!this.editContent.trim() || !this.editingMessageId) return;

            try {
                const res = await this.fetchJson(`/messages/${this.editingMessageId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ content: this.editContent.trim() }),
                });

                if (res.success) {
                    // Update DOM
                    const el = document.getElementById(`msg-${this.editingMessageId}`);
                    const textEl = el?.querySelector('.msg-text');
                    if (textEl) {
                        textEl.textContent = this.editContent.trim();
                        // Add "modifié" badge
                        if (!el.querySelector('.edited-badge')) {
                            const badge = document.createElement('span');
                            badge.className = 'edited-badge text-[10px] text-white/60 ml-1';
                            badge.textContent = '(modifié)';
                            textEl.parentNode.appendChild(badge);
                        }
                    }
                }
            } catch (err) {
                console.error('Edit failed:', err);
                alert('Impossible de modifier ce message. Le délai de 15 minutes est peut-être dépassé.');
            } finally {
                this.editingMessageId = null;
                this.editContent = '';
            }
        },

        cancelEditMessage() {
            this.editingMessageId = null;
            this.editContent = '';
        },

        async confirmDeleteMessage() {
            this.contextMenuVisible = false;
            const id = this.contextMenuMessageId;

            if (!confirm('Supprimer ce message ? Cette action est irréversible.')) return;

            try {
                const res = await this.fetchJson(`/messages/${id}`, { method: 'DELETE' });
                if (res.success) {
                    const el = document.getElementById(`msg-${id}`);
                    if (el) {
                        el.style.transition = 'opacity 0.3s, transform 0.3s';
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.95)';
                        setTimeout(() => el.remove(), 300);
                    }
                }
            } catch (err) {
                console.error('Delete failed:', err);
            }
        },

        // Handle real-time edit/delete from other user
        handleMessageEdited(data) {
            const el = document.getElementById(`msg-${data.id}`);
            if (!el) return;
            const textEl = el.querySelector('.msg-text');
            if (textEl) {
                textEl.textContent = data.content;
                if (!el.querySelector('.edited-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'edited-badge text-[10px] text-gray-400 ml-1';
                    badge.textContent = '(modifié)';
                    textEl.parentNode.appendChild(badge);
                }
            }
        },

        handleMessageDeleted(data) {
            const el = document.getElementById(`msg-${data.id}`);
            if (el) {
                el.style.transition = 'opacity 0.3s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }
        },

        // ========================
        // TYPING INDICATOR
        // ========================
        emitTyping() {
            if (this.typingTimeout) return;
            this.typingTimeout = setTimeout(() => { this.typingTimeout = null; }, 2000);

            fetch(`/chat/${this.conversationId}/typing`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
            }).catch(() => { });
        },

        // ========================
        // MARK AS READ
        // ========================
        async markAsRead() {
            try {
                await this.fetchJson(`/chat/${this.conversationId}/read`, { method: 'POST' });
            } catch (_e) { /* silence */ }
        },

        markAllVisibleAsRead() {
            document.querySelectorAll('.msg-status-icon[data-own="true"]').forEach((el) => {
                el.innerHTML = this.statusSvg('read');
            });
        },

        // ========================
        // DOM BUILDERS
        // ========================
        insertOptimisticBubble(id, content, status, replyContent) {
            const container = this.$refs.messagesContainer?.querySelector('.messages-list');
            if (!container) return;

            const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            let replyHtml = '';
            if (replyContent) {
                replyHtml = `<div class="mx-2.5 mt-2.5 mb-0 px-2.5 py-1.5 rounded-lg border-l-2 bg-[#e00b41]/30 border-white/50"><p class="text-[11px] text-white/70 truncate">${this.esc(replyContent)}</p></div>`;
            }

            const ownAvatar = this.ownAvatarUrl
                ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.ownAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm"></div>`
                : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">${this.esc(this.ownInitial)}</div></div>`;

            const html = `
            <div class="flex justify-end group msg-row" id="msg-${id}">
                <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                    <div class="space-y-0.5 items-end">
                        <div class="bg-[#ff385c] text-white rounded-2xl rounded-br-md">
                            ${replyHtml}
                            <div class="px-3.5 py-2"><p class="msg-text text-[14.5px] leading-relaxed whitespace-pre-wrap wrap-break-word">${this.esc(content)}</p></div>
                        </div>
                        <div class="msg-meta flex items-center gap-1.5 px-1 justify-end">
                            <span class="text-[10px] text-gray-400">${time}</span>
                            <span class="msg-status-icon" data-own="true">${this.statusSvg(status)}</span>
                        </div>
                    </div>
                    ${ownAvatar}
                </div>
            </div>`;

            container.insertAdjacentHTML('beforeend', html);
        },

        insertOptimisticFileBubble(id, fileName, isImage, previewUrl, status) {
            const container = this.$refs.messagesContainer?.querySelector('.messages-list');
            if (!container) return;

            const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            let content = '';

            if (isImage && previewUrl) {
                content = `<div class="p-1.5"><img src="${previewUrl}" class="rounded-xl max-h-72 w-auto opacity-60" /></div>`;
            } else {
                content = `
                <div class="mx-2.5 my-2.5">
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-[#e00b41]/30">
                        <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <span class="text-sm font-medium text-white truncate">${this.esc(fileName)}</span>
                    </div>
                </div>`;
            }

            const ownAvatar = this.ownAvatarUrl
                ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.ownAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm"></div>`
                : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">${this.esc(this.ownInitial)}</div></div>`;

            const html = `
            <div class="flex justify-end group msg-row" id="msg-${id}">
                <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                    <div class="space-y-0.5 items-end">
                        <div class="bg-[#ff385c] text-white rounded-2xl rounded-br-md">${content}</div>
                        <div class="msg-meta flex items-center gap-1.5 px-1 justify-end">
                            <span class="text-[10px] text-gray-400">${time}</span>
                            <span class="msg-status-icon" data-own="true">${this.statusSvg(status)}</span>
                        </div>
                    </div>
                    ${ownAvatar}
                </div>
            </div>`;

            container.insertAdjacentHTML('beforeend', html);
        },

        appendMessage(data, _isOwn) {
            if (document.getElementById(`msg-${data.id}`)) return;

            const container = this.$refs.messagesContainer?.querySelector('.messages-list');
            if (!container) return;

            const time = new Date(data.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const _status = data.read_at ? 'read' : (data.delivered_at ? 'delivered' : 'sent');

            const otherAvatar = this.otherAvatarUrl
                ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.otherAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-white shadow-sm"></div>`
                : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-white shadow-sm">${this.esc(this.otherInitial)}</div></div>`;

            const imageHtml = (data.type === 'image' && data.attachments?.length)
                ? data.attachments.map((att, idx) =>
                    `<div class="p-1.5"><img src="/messages/${data.id}/image/${idx}" alt="${this.esc(att.name ?? 'Image')}" class="rounded-xl max-h-72 w-auto cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src,'_blank')" loading="lazy"></div>`
                ).join('')
                : '';
            const bubbleContent = imageHtml + (data.content
                ? `<div class="px-3.5 py-2"><p class="msg-text text-[14.5px] leading-relaxed whitespace-pre-wrap wrap-break-word">${this.esc(data.content)}</p></div>`
                : '');

            const html = `
            <div class="flex justify-start group msg-row animate-slide-in" id="msg-${data.id}">
                <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                    ${otherAvatar}
                    <div class="space-y-0.5 items-start">
                        <div class="bg-white border border-gray-100 text-gray-900 rounded-2xl rounded-bl-md shadow-sm">
                            ${bubbleContent}
                        </div>
                        <div class="msg-meta flex items-center gap-1.5 px-1">
                            <span class="text-[10px] text-gray-400">${time}</span>
                        </div>
                    </div>
                </div>
            </div>`;

            container.insertAdjacentHTML('beforeend', html);
            this.lastMessageId = Math.max(this.lastMessageId, data.id);
            this.scrollToBottom();
        },

        updateBubbleStatus(el, status) {
            const icon = el?.querySelector('.msg-status-icon');
            if (icon) icon.innerHTML = this.statusSvg(status);
        },

        statusSvg(status) {
            switch (status) {
                case 'read':
                    return '<svg class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2 13l4 4L14 7M10 13l4 4L22 7" /></svg>';
                case 'delivered':
                    return '<svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2 13l4 4L14 7M10 13l4 4L22 7" /></svg>';
                case 'sent':
                    return '<svg class="w-3 h-3 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
                case 'sending': case 'uploading':
                    return '<span class="inline-block w-3 h-3 border-2 border-gray-300 border-t-orange-500 rounded-full animate-spin"></span>';
                case 'failed':
                    return '<svg class="w-3.5 h-3.5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>';
                default: return '';
            }
        },

        // ========================
        // TEMPLATES & QUICK REPLIES
        // ========================
        useTemplate(id, content) {
            this.newMessage = content;
            this.showTemplates = false;
            this.$refs.messageInput?.focus();
        },

        useQuickReply(id, formattedContent) {
            this.newMessage = formattedContent;
            this.showQuickReplies = false;
            this.$refs.messageInput?.focus();

            fetch(`/owner/auto-replies/${id}/use`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ guest_name: '', residence_name: '' }),
            }).catch(() => { });
        },

        // ========================
        // REPLY
        // ========================
        setReplyTo(messageId, content) {
            this.replyTo = messageId;
            this.replyToContent = content;
            this.$refs.messageInput?.focus();
        },

        cancelReply() {
            this.replyTo = null;
            this.replyToContent = '';
        },

        // ========================
        // EMOJI PICKER
        // ========================
        insertEmoji(emoji) {
            const input = this.$refs.messageInput;
            if (!input) {
                this.newMessage += emoji;
                return;
            }

            const start = input.selectionStart;
            const end = input.selectionEnd;
            const before = this.newMessage.substring(0, start);
            const after = this.newMessage.substring(end);
            this.newMessage = before + emoji + after;

            this.$nextTick(() => {
                const newPos = start + emoji.length;
                input.selectionStart = newPos;
                input.selectionEnd = newPos;
                input.focus();
            });
        },

        // ========================
        // CONVERSATION ACTIONS (with confirmation)
        // ========================
        async togglePin() {
            const endpoint = this.isPinned ? 'unpin' : 'pin';
            try {
                await this.fetchJson(`/chat/${this.conversationId}/${endpoint}`, { method: 'POST' });
                this.isPinned = !this.isPinned;
            } catch (_e) { }
        },

        async toggleMute() {
            const endpoint = this.isMuted ? 'unmute' : 'mute';
            try {
                await this.fetchJson(`/chat/${this.conversationId}/${endpoint}`, { method: 'POST' });
                this.isMuted = !this.isMuted;
            } catch (_e) { }
        },

        async archive() {
            const action = this.isArchived ? 'désarchiver' : 'archiver';
            if (!confirm(`Voulez-vous ${action} cette conversation ?`)) return;

            const endpoint = this.isArchived ? 'unarchive' : 'archive';
            try {
                await this.fetchJson(`/chat/${this.conversationId}/${endpoint}`, { method: 'POST' });
                window.location.href = this.chatIndexUrl;
            } catch (_e) { }
        },

        // ========================
        // EMOJI REACTIONS
        // ========================
        async toggleReaction(messageId, emoji) {
            try {
                const res = await this.fetchJson(`/messages/${messageId}/reaction`, {
                    method: 'POST',
                    body: JSON.stringify({ emoji }),
                });
                if (res.success) {
                    // Update reactions display in DOM
                    this.updateReactionsDisplay(messageId, res.reactions);
                }
            } catch (e) {
                console.warn('Reaction failed:', e);
            }
        },

        updateReactionsDisplay(messageId, reactions) {
            const msgEl = document.getElementById(`msg-${messageId}`);
            if (!msgEl) return;

            // Find or create reactions container (after the bubble div)
            let reactionsDiv = msgEl.querySelector('.reactions-bar');
            const _bubble = msgEl.querySelector('.space-y-0\\.5, [class*="space-y-0.5"]') || msgEl.querySelector('.items-end, .items-start');

            if (!reactions || reactions.length === 0) {
                if (reactionsDiv) reactionsDiv.remove();
                return;
            }

            const isOwn = msgEl.classList.contains('justify-end') || msgEl.querySelector('[class*="justify-end"]');
            const html = reactions.map(r => {
                const isActive = r.users.includes(this.currentUserId);
                return `<button onclick="document.querySelector('[x-data]').__x.$data.toggleReaction(${messageId}, '${r.emoji}')"
                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 text-xs rounded-full border transition-all hover:scale-105
                    ${isActive ? 'bg-orange-50 border-orange-300 text-[#b5083a]' : 'bg-white border-gray-200 text-gray-600'}">
                    <span>${r.emoji}</span>${r.count > 1 ? `<span class="text-[10px] font-medium">${r.count}</span>` : ''}
                </button>`;
            }).join('');

            if (!reactionsDiv) {
                reactionsDiv = document.createElement('div');
                reactionsDiv.className = `reactions-bar flex flex-wrap gap-1 px-1 -mt-1 ${isOwn ? 'justify-end' : ''}`;
                // Insert after bubble, before meta
                const meta = msgEl.querySelector('.msg-meta');
                if (meta) meta.parentNode.insertBefore(reactionsDiv, meta);
            }
            reactionsDiv.innerHTML = html;
        },

        // ========================
        // GIF SEARCH & SEND
        // ========================
        async searchGifs() {
            const query = this.gifSearchQuery.trim();
            if (!query) { this.gifResults = []; return; }

            this.gifLoading = true;
            try {
                const res = await this.fetchJson(`/api/gifs/search?q=${encodeURIComponent(query)}&limit=20`);
                this.gifResults = res.gifs || [];
            } catch (e) {
                console.warn('GIF search failed:', e);
                this.gifResults = [];
            } finally {
                this.gifLoading = false;
            }
        },

        async sendGif(gif) {
            this.showGifPicker = false;
            this.gifSearchQuery = '';
            this.gifResults = [];

            const tempId = `temp-${Date.now()}`;
            // Insert optimistic GIF bubble
            const container = this.$refs.messagesContainer?.querySelector('.messages-list');
            if (container) {
                const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                const ownAvatar = this.ownAvatarUrl
                    ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.ownAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm"></div>`
                    : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">${this.esc(this.ownInitial)}</div></div>`;

                const html = `<div class="flex justify-end group msg-row" id="msg-${tempId}">
                    <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                        <div class="space-y-0.5 items-end">
                            <div class="bg-[#ff385c] text-white rounded-2xl rounded-br-md p-1.5">
                                <img src="${this.esc(gif.url)}" class="rounded-xl max-h-64" style="max-width:${Math.min(gif.width || 250, 300)}px">
                                <span class="text-[9px] text-white/40 px-1">GIF</span>
                            </div>
                            <div class="msg-meta flex items-center gap-1.5 px-1 justify-end">
                                <span class="text-[10px] text-gray-400">${time}</span>
                                <span class="msg-status-icon" data-own="true">${this.statusSvg('sending')}</span>
                            </div>
                        </div>
                        ${ownAvatar}
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
                this.scrollToBottom();
            }

            try {
                const res = await this.fetchJson(`/chat/${this.conversationId}/gif`, {
                    method: 'POST',
                    body: JSON.stringify({
                        gif_url: gif.url,
                        preview_url: gif.preview,
                        width: gif.width,
                        height: gif.height,
                    }),
                });
                if (res.success && res.message) {
                    const tempEl = document.getElementById(`msg-${tempId}`);
                    if (tempEl) {
                        tempEl.id = `msg-${res.message.id}`;
                        this.updateBubbleStatus(tempEl, 'sent');
                    }
                    this.lastMessageId = res.message.id;
                }
            } catch (e) {
                console.error('Send GIF failed:', e);
                const tempEl = document.getElementById(`msg-${tempId}`);
                if (tempEl) this.updateBubbleStatus(tempEl, 'failed');
            }
        },

        // ========================
        // VOICE RECORDING
        // ========================
        async startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
                this.audioChunks = [];

                this.mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) this.audioChunks.push(e.data);
                };

                this.mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(t => t.stop());
                };

                this.mediaRecorder.start();
                this.isRecording = true;
                this._recordingStart = Date.now();

                // Update timer
                this._recordingInterval = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - this._recordingStart) / 1000);
                    const mins = String(Math.floor(elapsed / 60)).padStart(2, '0');
                    const secs = String(elapsed % 60).padStart(2, '0');
                    this.recordingDuration = `${mins}:${secs}`;
                }, 1000);

            } catch (e) {
                console.error('Microphone access denied:', e);
                alert('Accès au microphone refusé. Vérifiez les permissions de votre navigateur.');
            }
        },

        async stopRecording() {
            if (!this.mediaRecorder || this.mediaRecorder.state !== 'recording') return;

            return new Promise((resolve) => {
                this.mediaRecorder.onstop = () => {
                    this.mediaRecorder.stream?.getTracks().forEach(t => t.stop());
                    clearInterval(this._recordingInterval);
                    this.isRecording = false;
                    const duration = Math.floor((Date.now() - this._recordingStart) / 1000);
                    this.sendVoiceMessage(duration);
                    this.recordingDuration = '00:00';
                    resolve();
                };
                this.mediaRecorder.stop();
            });
        },

        cancelRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
                this.mediaRecorder.stream?.getTracks().forEach(t => t.stop());
                this.mediaRecorder.stop();
            }
            clearInterval(this._recordingInterval);
            this.isRecording = false;
            this.audioChunks = [];
            this.recordingDuration = '00:00';
        },

        async sendVoiceMessage(duration) {
            if (this.audioChunks.length === 0) return;
            if (duration < 1) { this.audioChunks = []; return; } // Ignore too short

            const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
            this.audioChunks = [];

            const tempId = `temp-${Date.now()}`;
            // Optimistic insert
            const container = this.$refs.messagesContainer?.querySelector('.messages-list');
            if (container) {
                const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                const mins = String(Math.floor(duration / 60)).padStart(2, '0');
                const secs = String(duration % 60).padStart(2, '0');
                const ownAvatar = this.ownAvatarUrl
                    ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.ownAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm"></div>`
                    : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">${this.esc(this.ownInitial)}</div></div>`;

                const html = `<div class="flex justify-end group msg-row" id="msg-${tempId}">
                    <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                        <div class="space-y-0.5 items-end">
                            <div class="bg-[#ff385c] text-white rounded-2xl rounded-br-md px-3 py-2.5 flex items-center gap-3 min-w-50">
                                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z"/></svg>
                                </div>
                                <div class="flex-1">
                                    <div class="h-1 bg-white/20 rounded-full"><div class="h-full bg-white/60 rounded-full" style="width:0%"></div></div>
                                </div>
                                <span class="text-[11px] text-white/70 tabular-nums">${mins}:${secs}</span>
                            </div>
                            <div class="msg-meta flex items-center gap-1.5 px-1 justify-end">
                                <span class="text-[10px] text-gray-400">${time}</span>
                                <span class="msg-status-icon" data-own="true">${this.statusSvg('uploading')}</span>
                            </div>
                        </div>
                        ${ownAvatar}
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
                this.scrollToBottom();
            }

            const formData = new FormData();
            formData.append('audio', blob, 'voice.webm');
            formData.append('duration', duration);

            try {
                const res = await fetch(`/chat/${this.conversationId}/voice`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success && data.message) {
                    const tempEl = document.getElementById(`msg-${tempId}`);
                    if (tempEl) {
                        tempEl.id = `msg-${data.message.id}`;
                        this.updateBubbleStatus(tempEl, 'sent');
                    }
                    this.lastMessageId = data.message.id;
                }
            } catch (e) {
                console.error('Voice send failed:', e);
                const tempEl = document.getElementById(`msg-${tempId}`);
                if (tempEl) this.updateBubbleStatus(tempEl, 'failed');
            }
        },

        // ========================
        // THEME SWITCHING
        // ========================
        async changeTheme(color) {
            this.themeColor = color;
            try {
                await this.fetchJson(`/chat/${this.conversationId}/theme`, {
                    method: 'POST',
                    body: JSON.stringify({ theme_color: color }),
                });
                // Reload to apply theme to all bubbles
                window.location.reload();
            } catch (e) {
                console.warn('Theme change failed:', e);
            }
        },

        // ========================
        // SEARCH IN CONVERSATION
        // ========================
        async searchInConversation() {
            const q = this.searchQuery.trim();
            if (!q || q.length < 2) { this.searchResults = []; return; }

            try {
                const res = await this.fetchJson(`/chat/${this.conversationId}/search?q=${encodeURIComponent(q)}`);
                this.searchResults = res.results || [];
            } catch (_e) {
                this.searchResults = [];
            }
        },

        scrollToMessage(messageId) {
            const el = document.getElementById(`msg-${messageId}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('ring-2', 'ring-orange-400', 'rounded-xl');
                setTimeout(() => el.classList.remove('ring-2', 'ring-orange-400', 'rounded-xl'), 2000);
            }
            this.showSearchPanel = false;
        },

        // ========================
        // VOICE PLAYER
        // ========================
        initVoicePlayers() {
            document.querySelectorAll('.voice-player audio').forEach(audio => {
                if (audio._vpInit) return;
                audio._vpInit = true;

                const container = audio.closest('.voice-player');
                if (!container) return;

                const progressBar = container.querySelector('.h-full');
                const timeSpan = container.querySelector('.tabular-nums');
                const playBtn = container.closest('.flex')?.querySelector('button');

                audio.addEventListener('timeupdate', () => {
                    if (audio.duration && progressBar) {
                        progressBar.style.width = (audio.currentTime / audio.duration * 100) + '%';
                    }
                    if (timeSpan && audio.duration) {
                        const remaining = Math.floor(audio.duration - audio.currentTime);
                        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
                        const s = String(remaining % 60).padStart(2, '0');
                        timeSpan.textContent = `${m}:${s}`;
                    }
                });

                audio.addEventListener('ended', () => {
                    if (progressBar) progressBar.style.width = '0%';
                    if (playBtn) {
                        const svg = playBtn.querySelector('svg');
                        if (svg) svg.innerHTML = '<path d="M8 5v14l11-7z"/>';
                    }
                });

                audio.addEventListener('play', () => {
                    if (playBtn) {
                        const svg = playBtn.querySelector('svg');
                        if (svg) svg.innerHTML = '<path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>';
                    }
                });

                audio.addEventListener('pause', () => {
                    if (playBtn) {
                        const svg = playBtn.querySelector('svg');
                        if (svg) svg.innerHTML = '<path d="M8 5v14l11-7z"/>';
                    }
                });
            });
        },

        // ========================
        // HELPERS
        // ========================
        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
            });
        },

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        },

        playNotifSound() {
            if (this.isMuted || document.hidden) return;
            try {
                const audio = new Audio('/sounds/message.mp3');
                audio.volume = 0.3;
                audio.play().catch(() => { });
            } catch (_e) { }
        },

        esc(text) {
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        },

        async fetchJson(url, options = {}) {
            const res = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                    ...(options.headers || {}),
                },
                ...options,
            });
            return res.json();
        },
    };
}
