# 💬 Chatbot d'Équipe - Guide d'Installation et d'Utilisation

## 📋 Résumé

J'ai créé un **système de chat moderne et professionnel** pour les équipes avec :
- ✅ Widget de chat flottant accessible sur toutes les pages
- ✅ Design moderne avec animations et effets
- ✅ Messages en temps réel avec polling automatique
- ✅ Interface responsive (mobile & desktop)
- ✅ Badges de notification
- ✅ Historique des messages

## 📁 Fichiers Créés

### Backend (PHP/Symfony)
1. **`src/Entity/ChatMessage.php`** - Entité pour les messages de chat
2. **`src/Repository/ChatMessageRepository.php`** - Repository avec méthodes utiles
3. **`src/Controller/ChatController.php`** - API REST pour le chat
4. **`migrations/Version20260213231500.php`** - Migration de base de données

### Frontend (CSS/JS)
5. **`public/css/chat-widget.css`** - Styles modernes pour le widget
6. **`public/js/chat-widget.js`** - Logique JavaScript du chat

### Templates Modifiés
7. **`templates/base.html.twig`** - Ajout des assets CSS/JS globaux
8. **`templates/equipes/show.html.twig`** - Ajout de `data-team-id`

## 🚀 Installation

### Étape 1: Exécuter la Migration

```bash
php bin/console doctrine:migrations:migrate
```

Si vous avez une erreur, exécutez manuellement le SQL :

```sql
CREATE TABLE chat_message (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    equipe_id INT NOT NULL,
    message LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    INDEX IDX_FAB3FC16A76ED395 (user_id),
    INDEX IDX_FAB3FC166D861B89 (equipe_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC166D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id);
```

### Étape 2: Vider le Cache

```bash
php bin/console cache:clear
```

### Étape 3: Tester

1. Connectez-vous à votre application
2. Naviguez vers une page d'équipe
3. Vous verrez un **bouton de chat flottant** en bas à droite
4. Cliquez dessus pour ouvrir le widget

## 🎨 Fonctionnalités

### Widget de Chat

#### Bouton Flottant
- **Position**: Bas-droite de l'écran
- **Design**: Gradient cyan → violet avec animation pulse
- **Badge**: Affiche le nombre de messages non lus
- **Hover**: Effet de zoom et glow

#### Fenêtre de Chat
- **Header**: Avatar de l'équipe + nom + statut
- **Messages**: Liste scrollable avec avatars
- **Input**: Champ de saisie avec bouton d'envoi
- **Animations**: Slide-up à l'ouverture, fade-in pour les messages

### Messages

#### Affichage
- **Messages propres**: Alignés à droite, fond violet
- **Messages des autres**: Alignés à gauche, fond cyan
- **Timestamp**: Relatif ("Il y a 5 min", "Il y a 2h")
- **Avatars**: Initiales avec gradient coloré

#### Fonctionnalités
- **Envoi**: Appuyez sur Entrée ou cliquez sur le bouton
- **Polling**: Actualisation automatique toutes les 5 secondes
- **Scroll auto**: Défile vers le bas à chaque nouveau message
- **Marquage lu**: Marque automatiquement les messages comme lus

## 🎯 API Endpoints

### GET `/chat/equipe/{id}/messages`
Récupère les 100 derniers messages d'une équipe

**Réponse**:
```json
[
  {
    "id": 1,
    "user": {
      "id": 5,
      "pseudo": "Player1"
    },
    "message": "Salut l'équipe!",
    "createdAt": "2026-02-13 23:15:00",
    "isRead": false
  }
]
```

### POST `/chat/equipe/{id}/send`
Envoie un nouveau message

**Body**:
```json
{
  "message": "Bonjour tout le monde!"
}
```

### POST `/chat/equipe/{id}/mark-read`
Marque tous les messages comme lus

## 🎨 Personnalisation CSS

### Variables Principales
```css
--primary-blue: #00D9FF
--primary-purple: #8257FF
--primary-pink: #FF006E
--text-primary: #E8ECF4
--text-secondary: #8B92A8
```

### Classes Importantes
- `.chat-button` - Bouton flottant
- `.chat-widget` - Conteneur principal
- `.chat-message` - Bulle de message
- `.chat-message.own` - Message de l'utilisateur actuel

## 📱 Responsive Design

### Desktop (> 768px)
- Widget: 380px × 550px
- Position: Bas-droite avec marges

### Mobile (≤ 768px)
- Widget: Pleine largeur avec marges
- Hauteur: 500px
- Bouton: Plus petit, repositionné

## 🔧 Configuration Avancée

### Modifier l'Intervalle de Polling

Dans `chat-widget.js`, ligne ~235:
```javascript
this.pollInterval = setInterval(() => {
    if (this.isOpen) {
        this.loadMessages();
    }
}, 5000); // 5000ms = 5 secondes
```

### Modifier le Nombre de Messages

Dans `ChatMessageRepository.php`:
```php
public function findRecentByEquipe(Equipe $equipe, int $limit = 50): array
{
    // Changez 50 par le nombre souhaité
}
```

## 🐛 Dépannage

### Le widget ne s'affiche pas
1. Vérifiez que les fichiers CSS/JS sont bien chargés
2. Vérifiez la console pour les erreurs JavaScript
3. Assurez-vous que `data-team-id` est présent sur la page

### Les messages ne s'envoient pas
1. Vérifiez les routes dans `config/routes.yaml`
2. Vérifiez que l'utilisateur est authentifié
3. Consultez les logs Symfony

### Erreur de migration
1. Vérifiez que la table `chat_message` n'existe pas déjà
2. Exécutez le SQL manuellement si nécessaire
3. Vérifiez les contraintes de clés étrangères

## ✨ Améliorations Futures

- [ ] WebSocket pour messages en temps réel
- [ ] Notifications push
- [ ] Emojis et GIFs
- [ ] Partage de fichiers
- [ ] Réponses citées
- [ ] Indicateur "en train d'écrire..."
- [ ] Messages vocaux
- [ ] Recherche dans l'historique

## 🎉 Résultat Final

Vous avez maintenant un **chatbot professionnel** avec :
- 🎨 Design moderne et élégant
- 💬 Communication en temps réel
- 📱 Interface responsive
- ⚡ Performances optimisées
- 🔒 Sécurisé (authentification requise)

Profitez de votre nouveau système de chat d'équipe ! 🚀
