# 🔧 CORRECTION RAPIDE - Erreur user_id

## Problème
La colonne `user_id` n'existe pas dans la table `commande` de ta base de données.

## Solution Express

### Méthode 1 : Via phpMyAdmin (XAMPP)

1. **Ouvre phpMyAdmin** : http://localhost/phpmyadmin
2. **Sélectionne la base** : `esportify`
3. **Va dans l'onglet SQL**
4. **Colle et exécute ce code** :

```sql
-- Ajouter user_id à commande
ALTER TABLE commande ADD COLUMN user_id INT DEFAULT NULL AFTER statut;
ALTER TABLE commande ADD INDEX IDX_6EEAA67DA76ED395 (user_id);
ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);

-- Créer la table recommendation
CREATE TABLE IF NOT EXISTS recommendation (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    produit_id INT NOT NULL,
    score DOUBLE PRECISION DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    INDEX IDX_433224D2A76ED395 (user_id),
    INDEX IDX_433224D2F347EFB (produit_id),
    CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id),
    CONSTRAINT FK_433224D2F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
```

5. **Clique sur "Exécuter"**

### Méthode 2 : Via ligne de commande MySQL

Si tu as MySQL en ligne de commande :

```bash
# Dans un terminal XAMPP
mysql -u root -p esportify < c:\Users\ilyes\pi_projects\ml\migration.sql
```

## ✅ Après l'exécution

Rafraîchis la page et teste à nouveau :
```
http://localhost:8000/produits
```

Le système devrait maintenant fonctionner ! 🚀

## 🔍 Vérification

Pour vérifier que ça a marché, exécute dans phpMyAdmin :

```sql
-- Vérifier la structure de commande
DESCRIBE commande;

-- Vérifier que la table recommendation existe
SHOW TABLES LIKE 'recommendation';
```

Tu devrais voir la colonne `user_id` dans `commande` et la table `recommendation`.
