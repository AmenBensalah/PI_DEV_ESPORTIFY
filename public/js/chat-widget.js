// Chat Widget Manager (Strictly for team members chat)
class ChatWidget {
    constructor() {
        this.isOpen = false;
        this.currentTeamId = null;
        this.messages = [];
        this.currentUserId = null;
        this.pollInterval = null;
        this.init();
    }

    init() {
        this.checkUserTeam();
    }

    createWidget() {
        const widget = document.createElement('div');
        widget.innerHTML = `
            <!-- Chat Button -->
            <button class="chat-button" id="chatButton">
                <i class="fas fa-comments"></i>
                <span class="chat-notification-badge" id="chatBadge" style="display: none;">0</span>
            </button>

            <!-- Chat Widget -->
            <div class="chat-widget" id="chatWidget">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="chat-header-avatar" id="chatTeamAvatar">T</div>
                        <div class="chat-header-text">
                            <h3 id="chatTeamName">Chat d'équipe</h3>
                            <p id="chatTeamStatus">En ligne</p>
                        </div>
                    </div>
                    <button class="chat-header-close" id="chatClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="chat-empty">
                        <div class="chat-empty-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="chat-empty-title">Aucun message</div>
                        <div class="chat-empty-text">Soyez le premier à envoyer un message !</div>
                    </div>
                </div>

                <div class="chat-input">
                    <form class="chat-input-form" id="chatForm">
                        <input 
                            type="text" 
                            class="chat-input-field" 
                            id="chatInput" 
                            placeholder="Écrivez votre message..."
                            autocomplete="off"
                        />
                        <button type="submit" class="chat-input-send" id="chatSend">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(widget);
    }

    attachEventListeners() {
        const chatButton = document.getElementById('chatButton');
        const chatClose = document.getElementById('chatClose');
        const chatForm = document.getElementById('chatForm');

        chatButton?.addEventListener('click', () => this.toggleChat());
        chatClose?.addEventListener('click', () => this.closeChat());
        chatForm?.addEventListener('submit', (e) => this.sendMessage(e));
    }

    async checkUserTeam() {
        // Get current user ID from body attribute
        const bodyElement = document.querySelector('body[data-user-id]');
        if (bodyElement) {
            this.currentUserId = parseInt(bodyElement.dataset.userId) || null;
        }

        // Get team ID from session or page context
        const teamIdElement = document.querySelector('[data-team-id]');
        if (teamIdElement) {
            this.currentTeamId = teamIdElement.dataset.teamId;
            this.createWidget();
            this.attachEventListeners();
            this.loadMessages();
            this.startPolling();
        }
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        const widget = document.getElementById('chatWidget');
        const button = document.getElementById('chatButton');

        if (this.isOpen) {
            widget?.classList.add('active');
            button?.classList.add('active');
            this.loadMessages();
            this.markAsRead();
        } else {
            widget?.classList.remove('active');
            button?.classList.remove('active');
        }
    }

    closeChat() {
        this.isOpen = false;
        document.getElementById('chatWidget')?.classList.remove('active');
        document.getElementById('chatButton')?.classList.remove('active');
    }

    async loadMessages() {
        if (!this.currentTeamId) return;

        try {
            const response = await fetch(`/chat/equipe/${this.currentTeamId}/messages`);
            if (!response.ok) throw new Error('Failed to load messages');

            const messages = await response.json();
            this.messages = messages;
            this.renderMessages();
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    renderMessages() {
        const container = document.getElementById('chatMessages');
        if (!container) return;

        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="chat-empty">
                    <div class="chat-empty-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="chat-empty-title">Aucun message</div>
                    <div class="chat-empty-text">Soyez le premier à envoyer un message !</div>
                </div>
            `;
            return;
        }

        container.innerHTML = this.messages.map(msg => this.createMessageHTML(msg)).join('');
        this.scrollToBottom();
    }

    createMessageHTML(message) {
        const isOwn = message.user.id === this.currentUserId;
        const time = this.formatTime(message.createdAt);
        const initial = message.user.pseudo?.charAt(0).toUpperCase() || 'U';

        return `
            <div class="chat-message ${isOwn ? 'own' : ''}">
                <div class="chat-message-avatar">${initial}</div>
                <div class="chat-message-content">
                    <div class="chat-message-author">${message.user.pseudo}</div>
                    <div class="chat-message-bubble">${this.escapeHtml(message.message)}</div>
                    <div class="chat-message-time">${time}</div>
                </div>
            </div>
        `;
    }

    async sendMessage(e) {
        e.preventDefault();

        const input = document.getElementById('chatInput');
        const sendButton = document.getElementById('chatSend');
        const message = input?.value.trim();

        if (!message || !this.currentTeamId) return;

        // Disable button during send
        if (sendButton) sendButton.disabled = true;

        try {
            const response = await fetch(`/chat/equipe/${this.currentTeamId}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message })
            });

            if (!response.ok) throw new Error('Failed to send message');

            const data = await response.json();
            if (data.success || data.message) {
                input.value = '';
                await this.loadMessages();
            }
        } catch (error) {
            console.error('Error sending message:', error);
        } finally {
            if (sendButton) sendButton.disabled = false;
        }
    }

    async markAsRead() {
        if (!this.currentTeamId) return;

        try {
            await fetch(`/chat/equipe/${this.currentTeamId}/mark-read`, {
                method: 'POST'
            });
            this.updateBadge(0);
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }

    updateBadge(count) {
        const badge = document.getElementById('chatBadge');
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    startPolling() {
        this.pollInterval = setInterval(() => {
            if (this.isOpen) {
                this.loadMessages();
            }
        }, 5000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    scrollToBottom() {
        const container = document.getElementById('chatMessages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        if (diff < 60000) return 'À l\'instant';
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `Il y a ${minutes} min`;
        }
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `Il y a ${hours}h`;
        }
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatWidget = new ChatWidget();
});
