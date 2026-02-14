# 🧪 GUIDE COMPLET - Test des Recommandations IA

## 📋 Prérequis

Avant de commencer, assure-toi d'avoir :
- ✅ XAMPP lancé (Apache + MySQL)
- ✅ Symfony serve en cours d'exécution
- ✅ Des utilisateurs dans la base de données
- ✅ Des produits dans la base de données

---

## 🔧 ÉTAPE 1 : Ajouter user_id à la table commande

### Via phpMyAdmin (Le plus simple)

1. **Ouvre phpMyAdmin** : http://localhost/phpmyadmin
2. **Sélectionne** ta base `esportify`
3. **Clique** sur l'onglet **SQL** (en haut)
4. **Colle** ce code SQL :

```sql
-- Ajouter la colonne user_id
ALTER TABLE commande 
ADD COLUMN user_id INT DEFAULT NULL AFTER id;

-- Ajouter l'index
ALTER TABLE commande 
ADD INDEX IDX_6EEAA67DA76ED395 (user_id);

-- Ajouter la contrainte de clé étrangère
ALTER TABLE commande 
ADD CONSTRAINT FK_6EEAA67DA76ED395 
FOREIGN KEY (user_id) REFERENCES user (id);

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
    CONSTRAINT FK_433224D2A76ED395 
        FOREIGN KEY (user_id) REFERENCES user (id),
    CONSTRAINT FK_433224D2F347EFB 
        FOREIGN KEY (produit_id) REFERENCES produit (id)
) ENGINE = InnoDB;
```

5. **Clique** sur le bouton **Exécuter**

### ✅ Vérification
Execute cette requête pour vérifier :
```sql
DESCRIBE commande;
```
Tu dois voir la colonne `user_id` dans la liste !

---

## 🔧 ÉTAPE 2 : Réactiver les relations dans le code

### Fichier 1 : `src/Entity/Commande.php`

Trouve ces lignes (vers ligne 25) :
```php
// TEMPORAIRE : Relation User désactivée pour éviter l'erreur SQL
// #[ORM\ManyToOne(inversedBy: 'commandes')]
// private ?User $user = null;
```

**Remplace par :**
```php
#[ORM\ManyToOne(inversedBy: 'commandes')]
private ?User $user = null;
```

Puis trouve ces lignes (vers ligne 245) :
```php
// TEMPORAIRE : Méthodes User désactivées
// public function getUser(): ?User
// {
//     return $this->user;
// }
```

**Remplace par :**
```php
public function getUser(): ?User
{
    return $this->user;
}

public function setUser(?User $user): static
{
    $this->user = $user;
    return $this;
}
```

### Fichier 2 : `src/Entity/User.php`

Trouve ces lignes (vers ligne 104) :
```php
// TEMPORAIRE : Relation Commandes désactivée
// #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user')]
// private Collection $commandes;
```

**Remplace par :**
```php
#[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'user')]
private Collection $commandes;
```

Trouve aussi (vers ligne 118) :
```php
// $this->commandes = new ArrayCollection();
```

**Remplace par :**
```php
$this->commandes = new ArrayCollection();
```

Et réactive les méthodes (vers ligne 465) en décommentant tout le bloc `getCommandes()`, `addCommande()`, `removeCommande()`.

---

## 🔧 ÉTAPE 3 : Installer Python et les bibliothèques ML

### Sur Windows :

1. **Télécharge Python** : https://www.python.org/downloads/
2. ⚠️ **IMPORTANT** : Coche "Add Python to PATH" pendant l'installation
3. **Vérifie l'installation** :
```bash
python --version
# Devrait afficher : Python 3.x.x
```

4. **Installe les bibliothèques ML** :
```bash
cd c:\Users\ilyes\pi_projects\ml
pip install pandas scikit-learn numpy
```

---

## 🔧 ÉTAPE 4 : Créer des données de test

Tu as besoin de **commandes avec des produits achetés par des utilisateurs**.

### Option 1 : Via le site (Recommandé)

1. **Crée 2-3 comptes utilisateurs** sur `http://localhost:8000/register`
2. **Connecte-toi** avec le premier utilisateur
3. **Ajoute plusieurs produits au panier**
4. **Finalise la commande** (tu peux annuler le paiement Stripe, c'est ok)
5. **Déconnecte-toi** et répète avec un autre utilisateur

### Option 2 : Via SQL (Plus rapide)

```sql
-- Lier des commandes existantes à des utilisateurs
UPDATE commande SET user_id = 1 WHERE id IN (1, 2, 3);
UPDATE commande SET user_id = 2 WHERE id IN (4, 5);
UPDATE commande SET user_id = 3 WHERE id IN (6, 7);
```

### ✅ Vérification
```sql
SELECT 
    u.id as user_id, 
    u.email, 
    c.id as commande_id,
    COUNT(lc.id) as nb_produits
FROM user u
LEFT JOIN commande c ON c.user_id = u.id
LEFT JOIN ligne_commande lc ON lc.commande_id = c.id
GROUP BY u.id, c.id
HAVING nb_produits > 0;
```

Tu dois voir des utilisateurs avec des commandes et des produits !

---

## 🚀 ÉTAPE 5 : Générer les recommandations

```bash
cd c:\Users\ilyes\pi_projects
php bin/console app:recommendations:generate
```

### ✅ Sortie attendue :
```
Starting recommendation engine...
Exporting data...
Found 15 interactions.
Running Python ML script...
Importing recommendations...
Saved 12 recommendations.
```

### ❌ Si erreur "Python not found" :
- Vérifie : `python --version`
- Essaye : `python3 bin/console app:recommendations:generate`
- Ou ajoute Python au PATH et redémarre le terminal

---

## 👁️ ÉTAPE 6 : Voir les recommandations sur le site

1. **Connecte-toi** avec un compte utilisateur : `http://localhost:8000/login`
2. **Va sur la page produits** : `http://localhost:8000/produits`
3. **✨ Tu dois voir** une section "Recommandations IA pour vous" en bas de page !

### Ce que tu verras :
- 🎨 Section avec titre gradient "Recommandations IA pour vous"
- 🏷️ Badge "IA" sur chaque produit recommandé
- ⭐ Score de pertinence en pourcentage
- 🖼️ 4 produits recommandés maximum

### Sur une page produit :
1. **Clique sur un produit**
2. **Scroll en bas**
3. **Tu verras** "Produits recommandés pour vous"

---

## 🔍 DÉPANNAGE

### "Aucune recommandation affichée"

**Vérification 1** : Es-tu connecté ?
```
http://localhost:8000/login
```

**Vérification 2** : Y a-t-il des données dans la table ?
```sql
SELECT COUNT(*) FROM recommendation;
```

**Vérification 3** : Regarde les logs Python :
```bash
# Vérifie si les fichiers existent
dir c:\Users\ilyes\pi_projects\var\ml\
```

### "Column user_id not found"

➡️ Tu n'as pas exécuté l'étape 1 (SQL dans phpMyAdmin)

### "Python not found"

➡️ Installe Python et ajoute-le au PATH système

### "Not enough data for ML"

➡️ Il faut au moins 5 commandes avec des produits différents

---

## 📊 RÉSUMÉ VISUEL

```
Étape 1: SQL (phpMyAdmin)    → Ajouter user_id
         ↓
Étape 2: Code PHP            → Décommenter relations
         ↓
Étape 3: Install Python      → pip install pandas scikit-learn
         ↓
Étape 4: Données de test     → Créer commandes + produits
         ↓
Étape 5: Générer IA          → php bin/console app:recommendations:generate
         ↓
Étape 6: Tester !            → http://localhost:8000/produits
```

---

## 🎯 RACCOURCI (Test Rapide)

Si tu as déjà des commandes avec des produits dans ta base :

```bash
# 1. Exécute le SQL dans phpMyAdmin (Étape 1)
# 2. Décommente le code (Étape 2)
# 3. Puis :

pip install pandas scikit-learn numpy
php bin/console app:recommendations:generate
```

Ensuite connecte-toi et va sur `/produits` !

---

## ✅ CHECKLIST FINALE

- [ ] MySQL : Colonne `user_id` existe dans `commande`
- [ ] MySQL : Table `recommendation` créée
- [ ] Code : Relations décommentées dans `Commande.php` et `User.php`
- [ ] Python : Installé et dans le PATH
- [ ] ML : Bibliothèques installées (`pandas`, `scikit-learn`, `numpy`)
- [ ] Data : Au moins 5 commandes avec des produits
- [ ] Command : `app:recommendations:generate` exécutée sans erreur
- [ ] Front : Connecté en tant qu'utilisateur
- [ ] Résultat : Section "Recommandations IA" visible !

---

**Bonne chance ! 🚀 Si tu bloques à une étape, redis-moi où exactement !**
