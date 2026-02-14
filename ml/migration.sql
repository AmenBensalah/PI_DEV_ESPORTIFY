-- Migration manuelle pour ajouter user_id à commande et créer la table recommendation

-- Ajouter la colonne user_id à la table commande
ALTER TABLE commande ADD COLUMN user_id INT DEFAULT NULL AFTER statut;

-- Ajouter l'index et la contrainte de clé étrangère
ALTER TABLE commande ADD INDEX IDX_6EEAA67DA76ED395 (user_id);
ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);

-- Créer la table recommendation
CREATE TABLE IF NOT EXISTS recommendation (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    produit_id INT NOT NULL,
    score DOUBLE PRECISION DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id),
    INDEX IDX_433224D2A76ED395 (user_id),
    INDEX IDX_433224D2F347EFB (produit_id),
    CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id),
    CONSTRAINT FK_433224D2F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
