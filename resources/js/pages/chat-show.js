/**
 * REZI Chat — Real Messenger Experience
 * Features: optimistic UI, real-time via Echo, typing indicator, mark-as-read,
 * sound notifications, online status, infinite scroll, message editing/deleting
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
        loadingMore: false,
        editingMessageId: null,
        editContent: '',

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

            // Focus input
            this.$nextTick(() => this.$refs.messageInput?.focus());
        },

        destroy() {
            if (window.Echo) {
                window.Echo.leave(`conversation.${this.conversationId}`);
            }
            if (this._pollInterval) clearInterval(this._pollInterval);
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
                        this.markAsRead();
                    })
                    .listen('.messages.read', (data) => {
                        if (data.reader_id !== this.currentUserId) {
                            this.markAllVisibleAsRead();
                        }
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
            } catch (e) { /* silence */ }
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
                    // Replace temp with real
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
                    // Retry button
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

            this.sending = true;
            const tempId = `temp-${Date.now()}`;
            const isImage = file.type.startsWith('image/');
            let previewUrl = isImage ? URL.createObjectURL(file) : null;

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
            } catch (e) { /* silence */ }
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
                replyHtml = `<div class="mx-2.5 mt-2.5 mb-0 px-2.5 py-1.5 rounded-lg border-l-2 bg-orange-600/30 border-white/50"><p class="text-[11px] text-white/70 truncate">${this.esc(replyContent)}</p></div>`;
            }

            const ownAvatar = this.ownAvatarUrl
                ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.ownAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-orange-200 shadow-sm"></div>`
                : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-orange-200 shadow-sm">${this.esc(this.ownInitial)}</div></div>`;

            const html = `
            <div class="flex justify-end group msg-row" id="msg-${id}">
                <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] lg:max-w-[65%]">
                    <div class="space-y-0.5 items-end">
                        <div class="bg-orange-500 text-white rounded-2xl rounded-br-md">
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
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-orange-600/30">
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
                        <div class="bg-orange-500 text-white rounded-2xl rounded-br-md">${content}</div>
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

        appendMessage(data, isOwn) {
            if (document.getElementById(`msg-${data.id}`)) return;

            const container = this.$refs.messagesContainer?.querySelector('.messages-list');
            if (!container) return;

            const time = new Date(data.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const status = data.read_at ? 'read' : (data.delivered_at ? 'delivered' : 'sent');

            // Avatar for the other user (left side — received messages)
            const otherAvatar = this.otherAvatarUrl
                ? `<div class="shrink-0 mb-5"><img src="${this.esc(this.otherAvatarUrl)}" alt="" class="w-7 h-7 rounded-full object-cover ring-2 ring-white shadow-sm"></div>`
                : `<div class="shrink-0 mb-5"><div class="w-7 h-7 rounded-full bg-linear-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white text-[10px] font-bold ring-2 ring-white shadow-sm">${this.esc(this.otherInitial)}</div></div>`;

            const bubbleContent = data.content
                ? `<div class="px-3.5 py-2"><p class="msg-text text-[14.5px] leading-relaxed whitespace-pre-wrap wrap-break-word">${this.esc(data.content)}</p></div>`
                : '';

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

            // Restore cursor position after emoji
            this.$nextTick(() => {
                const newPos = start + emoji.length;
                input.selectionStart = newPos;
                input.selectionEnd = newPos;
                input.focus();
            });
        },

        // ========================
        // CONVERSATION ACTIONS (no reload!)
        // ========================
        async togglePin() {
            const endpoint = this.isPinned ? 'unpin' : 'pin';
            try {
                await this.fetchJson(`/chat/${this.conversationId}/${endpoint}`, { method: 'POST' });
                this.isPinned = !this.isPinned;
            } catch (e) { }
        },

        async toggleMute() {
            const endpoint = this.isMuted ? 'unmute' : 'mute';
            try {
                await this.fetchJson(`/chat/${this.conversationId}/${endpoint}`, { method: 'POST' });
                this.isMuted = !this.isMuted;
            } catch (e) { }
        },

        async archive() {
            const endpoint = this.isArchived ? 'unarchive' : 'archive';
            try {
                await this.fetchJson(`/chat/${this.conversationId}/${endpoint}`, { method: 'POST' });
                window.location.href = this.chatIndexUrl;
            } catch (e) { }
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
            } catch (e) { }
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
