-- ============================================
-- CHAT WIDGET - Installation SQL
-- ============================================

-- Créer la table chat_message
CREATE TABLE IF NOT EXISTS chat_message (
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

-- Ajouter les contraintes de clés étrangères
ALTER TABLE chat_message 
ADD CONSTRAINT FK_FAB3FC16A76ED395 
FOREIGN KEY (user_id) REFERENCES user (id) 
ON DELETE CASCADE;

ALTER TABLE chat_message 
ADD CONSTRAINT FK_FAB3FC166D861B89 
FOREIGN KEY (equipe_id) REFERENCES equipe (id) 
ON DELETE CASCADE;

-- Créer un index pour améliorer les performances
CREATE INDEX idx_chat_created_at ON chat_message(created_at DESC);
CREATE INDEX idx_chat_is_read ON chat_message(is_read);

-- Messages de test (optionnel - à supprimer en production)
-- INSERT INTO chat_message (user_id, equipe_id, message, created_at, is_read) 
-- VALUES 
-- (1, 1, 'Bienvenue dans le chat d\'équipe!', NOW(), 0),
-- (2, 1, 'Merci! Content d\'être ici 🎮', NOW(), 0);

-- Vérifier l'installation
SELECT 'Installation terminée! Table chat_message créée avec succès.' AS status;
