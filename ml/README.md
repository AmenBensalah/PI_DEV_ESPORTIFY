# Système de Recommandation IA - E-Sportify

## 🚀 Mise en Place

### 1. Installation de Python et des dépendances

Avant de lancer les recommandations, vous devez installer Python et les bibliothèques nécessaires :

```bash
# Installer Python 3.8+ depuis python.org

# Installer les dépendances
cd c:\Users\ilyes\pi_projects\ml
pip install -r requirements.txt
```

### 2. Mettre à jour la base de données

```bash
php bin/console doctrine:schema:update --force
```

### 3. Générer les recommandations

```bash
php bin/console app:recommendations:generate
```

Cette commande va :
- ✅ Exporter les données d'achats
- ✅ Lancer le script Python ML
- ✅ Analyser les comportements d'achat
- ✅ Générer les recommandations personnalisées
- ✅ Sauvegarder dans la base de données

## 📊 Comment ça marche ?

### Architecture

```
User achète des produits
    ↓
Commande → LigneCommande (produit + quantité)
    ↓
Script Python (Machine Learning)
    ↓
Algorithme Collaborative Filtering (KNN)
    ↓
Recommendations (user + produit + score)
    ↓
Affichage Front Office
```

### Algorithme

Le système utilise **Collaborative Filtering** avec **K-Nearest Neighbors** :
1. Crée une matrice utilisateur-produit
2. Trouve les utilisateurs similaires
3. Recommande les produits aimés par ces utilisateurs
4. Calcule un score de pertinence

## 🎨 Interface Utilisateur

### Page Produits (index)
- Section "Recommandations IA pour vous"
- Badge IA animé
- Score de recommandation
- Design glassmorphisme

### Page Détail Produit (show)
- Section "Produits recommandés"
- Exclut le produit actuel
- Affichage du match %

## 🔄 Workflow

1. **Admin** : Gère les produits dans le Back Office
2. **Client connecté** : Achète des produits
3. **Cron/Manuel** : Lance `php bin/console app:recommendations:generate`
4. **Système** : Analyse et génère les recommandations
5. **Client** : Voit les recommandations personnalisées

## ⚡ Commandes Utiles

```bash
# Générer les recommandations
php bin/console app:recommendations:generate

# Vérifier le schéma de la base
php bin/console doctrine:schema:validate

# Voir les logs Python
tail -f var/ml/input.json
tail -f var/ml/output.json
```

## 🛠️ Personnalisation

### Modifier le nombre de recommandations :
Dans `ProductController.php`, ligne 40 :
```php
'recommendations' => $this->getUser() ? $recommendationRepository->findBy(['user' => $this->getUser()], ['score' => 'DESC'], 4) : [],
```
Changez `4` pour afficher plus/moins de recommandations.

### Modifier l'algorithme :
Dans `ml/recommendation.py`, vous pouvez ajuster :
- Le nombre de voisins (ligne 51) : `n_neighbors=min(6, len(user_ids))`
- La métrique de distance : `metric='cosine'`
- Le nombre de recommandations : `[:5]` (ligne 61)

## 🔐 Sécurité

- Les recommandations sont **uniquement pour les utilisateurs connectés**
- Le système ne montre que les produits disponibles
- Les données sont stockées de manière sécurisée dans la BDD

## 📈 Prochaines Étapes

- [ ] Ajouter un système de notation des produits
- [ ] Intégrer l'historique de navigation
- [ ] Ajouter des filtres par catégorie
- [ ] Créer un dashboard analytics pour l'admin
- [ ] Automatiser avec un cron job

## ❓ Dépannage

**Python non trouvé ?**
```bash
# Vérifier Python
python --version
# ou
python3 --version
```

**Erreur de migration ?**
```bash
# Forcer la mise à jour
php bin/console doctrine:schema:update --force
```

**Pas de recommandations ?**
Assurez-vous qu'il y a :
1. Des utilisateurs dans la BDD
2. Des commandes avec des lignes de commande
3. Une relation User ↔ Commande

---

💪 **Votre système de recommandation IA est prêt !**
