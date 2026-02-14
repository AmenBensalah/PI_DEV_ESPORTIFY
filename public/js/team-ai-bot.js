// Team AI Bot - Independent Chatbot Widget
console.log('TEAM AI BOT SCRIPT LOADED');
class TeamAiBot {
    constructor() {
        this.isOpen = false;
        this.currentTeamId = null;
        this.isTyping = false;
        this.init();
    }

    init() {
        console.log('TeamAIBot: Init started');
        const teamElement = document.querySelector('[data-ai-bot-team-id]');
        const hubElement = document.querySelector('[data-ai-bot-hub]');

        if (!teamElement && !hubElement) {
            console.log('TeamAIBot: No target elements found');
            return;
        }

        if (teamElement) {
            this.currentTeamId = teamElement.dataset.aiBotTeamId;
            console.log('TeamAIBot: Team Mode with ID:', this.currentTeamId);
            this.createWidget();
            this.attachEventListeners();
            this.addBotMessage("Bonjour ! Je suis votre assistant Esportify AI. Je connais tout sur cette équipe. Posez-moi vos questions ! 🎮");
        } else if (hubElement) {
            this.currentTeamId = "hub";
            console.log('TeamAIBot: Hub Mode active');
            this.createWidget();
            this.attachEventListeners();
            this.addBotMessage("Bienvenue sur le Hub des Équipes ! 🚀 Je suis l'analyste IA d'Esportify. Je peux vous aider à chercher une équipe. Que voulez-vous savoir ?");
        }
    }

    createWidget() {
        const container = document.createElement('div');
        container.id = 'aiBotContainer';
        container.innerHTML = `
            <button class="ai-bot-button" id="aiBotBtn">
                <i class="fas fa-robot"></i>
            </button>

            <div class="ai-bot-widget" id="aiBotWidget">
                <div class="ai-bot-header">
                    <div class="ai-bot-header-info">
                        <div class="ai-bot-avatar">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="ai-bot-text">
                            <h3 class="ai-bot-title">Esportify AI</h3>
                            <div class="ai-bot-status">Assistant Analyste</div>
                        </div>
                    </div>
                    <button id="aiBotClose" style="background:none; border:none; color:rgba(255,255,255,0.5); cursor:pointer; font-size:20px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="ai-bot-messages" id="aiBotMessages"></div>

                <div class="ai-bot-input">
                    <form class="ai-bot-form" id="aiBotForm">
                        <input 
                            type="text" 
                            class="ai-bot-field" 
                            id="aiBotInput" 
                            placeholder="Posez une question à l'IA..."
                            autocomplete="off"
                        />
                        <button type="submit" class="ai-bot-send" id="aiBotSend">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(container);
    }

    attachEventListeners() {
        const btn = document.getElementById('aiBotBtn');
        const close = document.getElementById('aiBotClose');
        const form = document.getElementById('aiBotForm');

        btn?.addEventListener('click', () => this.toggleBot());
        close?.addEventListener('click', () => this.closeBot());
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    toggleBot() {
        this.isOpen = !this.isOpen;
        const widget = document.getElementById('aiBotWidget');
        widget?.classList.toggle('active', this.isOpen);
    }

    closeBot() {
        this.isOpen = false;
        document.getElementById('aiBotWidget')?.classList.remove('active');
    }

    handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('aiBotInput');
        const question = input?.value.trim();

        if (!question || this.isTyping) return;

        this.addUserMessage(question);
        input.value = '';
        this.askAi(question);
    }

    addUserMessage(text) {
        const container = document.getElementById('aiBotMessages');
        const msg = document.createElement('div');
        msg.className = 'ai-msg ai-msg-user';
        msg.textContent = text;
        container?.appendChild(msg);
        this.scrollToBottom();
    }

    addBotMessage(text) {
        const container = document.getElementById('aiBotMessages');
        const msg = document.createElement('div');
        msg.className = 'ai-msg ai-msg-bot';
        msg.innerHTML = this.formatBotResponse(text);
        container?.appendChild(msg);
        this.scrollToBottom();
    }

    showTyping() {
        this.isTyping = true;
        const container = document.getElementById('aiBotMessages');
        const typing = document.createElement('div');
        typing.id = 'aiTypingIndicator';
        typing.className = 'ai-typing';
        typing.innerHTML = '<div class="ai-dot"></div><div class="ai-dot"></div><div class="ai-dot"></div>';
        container?.appendChild(typing);
        this.scrollToBottom();
    }

    hideTyping() {
        this.isTyping = false;
        document.getElementById('aiTypingIndicator')?.remove();
    }

    async askAi(question) {
        this.showTyping();

        try {
            const url = `/api/team-bot/${this.currentTeamId}/ask`;
            console.log('TeamAIBot: Fetching from', url);

            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question })
            });

            const text = await response.text();
            console.log('TeamAIBot: Raw response', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('TeamAIBot: JSON Parse Error', e);
                this.hideTyping();
                this.addBotMessage("🚫 Erreur critique du serveur (Structure invalide). Vérifiez les logs Symfony.");
                return;
            }

            this.hideTyping();

            if (response.ok) {
                if (data.answer) {
                    this.addBotMessage(data.answer);
                } else {
                    this.addBotMessage("Désolé, je ne parviens pas à répondre. 😕");
                }
            } else {
                const errMsg = data.error || "Erreur inconnue";
                this.addBotMessage("⚠️ Erreur Serveur [" + response.status + "] : " + errMsg);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            this.hideTyping();
            this.addBotMessage("🔌 Impossible de contacter le serveur. Le dossier /api est-il accessible ?");
        }
    }

    scrollToBottom() {
        const container = document.getElementById('aiBotMessages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    formatBotResponse(text) {
        // Simple formatting for emojis and line breaks
        return text.replace(/\n/g, '<br>');
    }
}

// Initialize on team pages
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-ai-bot-team-id]') || document.querySelector('[data-ai-bot-hub]')) {
        window.teamAiBot = new TeamAiBot();
    }
});
