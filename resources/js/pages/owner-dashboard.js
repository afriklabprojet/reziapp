export default function ownerDashboard(config = {}) {
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const chartData = config.chartData || [];

    return {
        loaded: false,
        replyingTo: null,
        replyText: '',
        replySending: false,

        init() {
            // Simulate skeleton → reveal transition (Airbnb-style)
            this.$nextTick(() => {
                this.loaded = true;
                this.animateCounters();
                this.$nextTick(() => this.renderChart());
            });
        },

        animateCounters() {
            document.querySelectorAll('[data-counter]').forEach(el => {
                const target = parseInt(el.dataset.counter, 10);
                if (isNaN(target) || target === 0) return;
                let current = 0;
                const step = Math.max(1, Math.ceil(target / 40));
                const interval = setInterval(() => {
                    current = Math.min(current + step, target);
                    el.textContent = current.toLocaleString('fr-FR');
                    if (current >= target) clearInterval(interval);
                }, 25);
            });
        },

        renderChart() {
            const canvas = this.$refs.activityChart;
            if (!canvas || !chartData.length) return;

            const ctx = canvas.getContext('2d');
            const dpr = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();

            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            const W = rect.width;
            const H = rect.height;
            const pad = { top: 10, right: 10, bottom: 28, left: 10 };
            const chartW = W - pad.left - pad.right;
            const chartH = H - pad.top - pad.bottom;

            const views = chartData.map(d => d.views || 0);
            const contacts = chartData.map(d => d.contacts || 0);
            const labels = chartData.map(d => d.label || '');
            const n = chartData.length;

            const maxVal = Math.max(1, Math.max(...views, ...contacts));

            // Grid lines
            ctx.strokeStyle = '#f3f4f6';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 4; i++) {
                const y = pad.top + (chartH / 4) * i;
                ctx.beginPath();
                ctx.moveTo(pad.left, y);
                ctx.lineTo(W - pad.right, y);
                ctx.stroke();
            }

            // X-axis labels (every 5 days)
            ctx.fillStyle = '#9ca3af';
            ctx.font = '10px system-ui, sans-serif';
            ctx.textAlign = 'center';
            for (let i = 0; i < n; i += 5) {
                const x = pad.left + (i / (n - 1)) * chartW;
                ctx.fillText(labels[i], x, H - 6);
            }
            // Always show last label
            if (n > 1) {
                const x = pad.left + chartW;
                ctx.fillText(labels[n - 1], x, H - 6);
            }

            function drawArea(data, color, alpha) {
                if (n < 2) return;
                ctx.beginPath();
                for (let i = 0; i < n; i++) {
                    const x = pad.left + (i / (n - 1)) * chartW;
                    const y = pad.top + chartH - (data[i] / maxVal) * chartH;
                    if (i === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                // Close area
                ctx.lineTo(pad.left + chartW, pad.top + chartH);
                ctx.lineTo(pad.left, pad.top + chartH);
                ctx.closePath();
                ctx.fillStyle = color.replace('1)', `${alpha})`);
                ctx.fill();
            }

            function drawLine(data, color, width) {
                if (n < 2) return;
                ctx.beginPath();
                ctx.strokeStyle = color;
                ctx.lineWidth = width;
                ctx.lineJoin = 'round';
                ctx.lineCap = 'round';
                for (let i = 0; i < n; i++) {
                    const x = pad.left + (i / (n - 1)) * chartW;
                    const y = pad.top + chartH - (data[i] / maxVal) * chartH;
                    if (i === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.stroke();
            }

            // Draw views (blue)
            drawArea(views, 'rgba(59, 130, 246, 1)', 0.08);
            drawLine(views, 'rgba(59, 130, 246, 1)', 2);

            // Draw contacts (orange)
            drawArea(contacts, 'rgba(249, 115, 22, 1)', 0.08);
            drawLine(contacts, 'rgba(249, 115, 22, 1)', 2);

            // Dots on last point
            if (n > 0) {
                const lastX = pad.left + chartW;
                [[views, 'rgba(59, 130, 246, 1)'], [contacts, 'rgba(249, 115, 22, 1)']].forEach(([data, color]) => {
                    const lastY = pad.top + chartH - (data[n - 1] / maxVal) * chartH;
                    ctx.beginPath();
                    ctx.arc(lastX, lastY, 4, 0, Math.PI * 2);
                    ctx.fillStyle = '#fff';
                    ctx.fill();
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 2;
                    ctx.stroke();
                });
            }
        },

        async toggleAvailability(residenceId, currentStatus) {
            const action = currentStatus ? 'marquer comme indisponible' : 'marquer comme disponible';
            if (!confirm(`Voulez-vous ${action} cette résidence ?`)) return;

            try {
                const response = await fetch(`/owner/residences/${residenceId}/toggle-availability`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Une erreur est survenue');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Une erreur est survenue');
            }
        },

        toggleReply(reviewId) {
            if (this.replyingTo === reviewId) {
                this.replyingTo = null;
            } else {
                this.replyingTo = reviewId;
                this.replyText = '';
                this.$nextTick(() => this.$refs['replyInput' + reviewId]?.focus());
            }
        },

        async submitReviewReply(reviewId) {
            if (!this.replyText.trim()) return;
            this.replySending = true;

            try {
                const response = await fetch(`/reviews/${reviewId}/respond`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ owner_response: this.replyText.trim() })
                });

                if (response.ok) {
                    this.replyingTo = null;
                    this.replyText = '';
                    window.location.reload();
                } else {
                    alert('Erreur lors de l\'envoi de la réponse');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Une erreur est survenue');
            } finally {
                this.replySending = false;
            }
        }
    };
}
