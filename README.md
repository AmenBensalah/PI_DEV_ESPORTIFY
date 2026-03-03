<p align="center">
  <img src="./public/images/banner.png" alt="Esportify Banner" width="800">
</p>

# 🎮 Esportify - Plateforme de Gestion E-Sport

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Symfony-6.4%2B-000000?style=for-the-badge&logo=symfony&logoColor=white" alt="Symfony Version">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Stripe-6772E5?style=for-the-badge&logo=stripe&logoColor=white" alt="Stripe">
  <img src="https://img.shields.io/badge/AI_Powered-FF6F61?style=for-the-badge&logo=openai&logoColor=white" alt="AI Powered">
  <img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" alt="License">
</p>

---

## 🌟 Overview

Ce projet a été développé dans le cadre du programme PIDEV – 3ème année du cycle ingénieur à **École Supérieure Privée d’Ingénierie et de Technologies (ESPRIT)** (Année Universitaire 2025–2026).

**Esportify** est une plateforme web complète et moderne conçue pour l'industrie de l'e-sport compétitif. Elle fait le lien entre les joueurs, les équipes et les organisateurs de tournois en offrant un écosystème unifié pour la gestion des compétitions, le recrutement d'équipes, l'engagement social et le commerce électronique, le tout avec une esthétique **"Cyberpunk"** de haute qualité. Le système intègre des connexions sociales avancées (Google/Discord), de la modération par IA, et des paiements gérés par Stripe.

---

## 🚀 Features

- **🏆 Gestion des Tournois** : Gestion complète du cycle de vie des tournois (FPS, Sports, Battle Royale). Prise en charge des modes Solo et Squad, suivi automatique des cagnottes (Prize Pool), classements des gagnants, et intégration des détails des jeux via **RAWG API**.
- **👥 Écosystème Joueurs & Équipes** : Création et gestion d'équipes e-sport, système de recrutement avancé (candidatures et offres de postes). Prise en charge d'authentification sociale sécurisée.
- **🛒 Boutique E-Sport** : Vitrine professionnelle pour l'achat d'équipements e-sport et d'articles numériques, intégrant des paiements sécurisés via **Stripe** et le suivi complet des commandes. Facturation PDF générée automatiquement.
- **💬 Hub de Communication** : Messagerie directe en temps réel, fil d'actualité communautaire et notifications par e-mail fiables via **Brevo API**.
- **🤖 Intelligence Artificielle et Sécurité** : Analyse des tendances du fil d'actualité, rapports e-sport autogénérés par l'IA, sécurisation des formulaires via **Google reCAPTCHA** contre les bots.
- **📊 Tableaux de Bord & Outils** : Visualisation des performances avec **Chart.js** et exportation intuitive de rapports au format PDF dynamique.

---

## 🛠️ Tech Stack

### 🎨 Frontend
- **Twig** (Moteur de templates ultra-rapide)
- **Vanilla JS**, **jQuery**, **Stimulus** & **Turbo** (Symfony UX fluide)
- **CSS Moderne** (Polices cyberpunk "Orbitron" & "Rajdhani" & thèmes de néons)
- **Chart.js** pour la visualisation des données et statistiques

### ⚙️ Backend
- **PHP 8.2+** 
- **Symfony 6.4 (LTS)**
- **MySQL** & **Doctrine ORM**
- **Utilitaires** : VichUploader (Gestion des fichiers), KnpSnappy / wkhtmltopdf (Impressions PDF personnalisées), LiipImagine (Optimisation et filtres conditionnels d'images).

### 🔗 APIs Intégrées / Services
- **Brevo API (Sendinblue)** : Notifications système et campagnes transactionnelles / par email.
- **OAuth2 Google Client** : Inscription et authentification rapide avec Google.
- **OAuth2 Discord Client** : Inscription et reconnexion au hub avec Discord.
- **Stripe API** : Gateway e-commerce (Achat hautement sécurisé sur la boutique).
- **Google reCAPTCHA v2 / v3** : Prévention active contre le spam ou le brute-forcing.
- **RAWG API** : Base de données riche pour obtenir toutes les informations et les trailers des jeux e-sports instantanément.

---

## 🏗 Architecture

Le projet repose sur l'architecture robuste **MVC** (Modèle-Vue-Contrôleur) de Symfony :

```text
src/
├── Controller/    # Logique métier, requêtes (Tournois, Équipes, Boutique, Utilisateurs)
├── Entity/        # Modèles de données ORM
├── Repository/    # Méthodes personnalisées de requêtes SQL interactives 
├── Form/          # Génération robuste et configuration prédictive de formulaires sécurisés
├── templates/     # Vues Twig modulaires structurées par domaine fonctionnel 
└── public/        # Ressources clientes accessibles (Images, CSS, scripts JS Vanilla/jQuery)
```

---

## 👥 Contributors

Groupe **Esportify** :
* Amen Bensalah
* Mohamed Bouzid
* Ilyes Zid
* Aysser Dhifallah
* Mohamed Ghaieth Bouamor
* Youssef Mejri

---

## 🎓 Academic Context

Developed at **École Supérieure Privée d’Ingénierie et de Technologies (ESPRIT) – Tunisia**  
PIDEV – 3A | 2025 – 2026

---

## 📦 Getting Started

### ✅ Prérequis
- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0+
- Installation locale de `wkhtmltopdf` configurée
- Symfony CLI (fortement recommandé)

### 💽 Étapes d'installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/AmenBensalah/PI_DEV_ESPORTIFY.git
   cd esportify
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   Copiez `.env` vers `.env.local` et configurez vos accès aux bases de données et API intégrées :
   ```env
   DATABASE_URL="mysql://root:@127.0.0.1:3306/esportify_db?serverVersion=8.0"
   BREVO_API_KEY="votre_clé_brevo"
   OAUTH_GOOGLE_CLIENT_ID="votre_client_google"
   OAUTH_DISCORD_CLIENT_ID="votre_client_discord"
   RECAPTCHA_SITE_KEY="votre_clé_site_recaptcha"
   RAWG_API_KEY="votre_clé_rawg"
   WKHTMLTOPDF_PATH="C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe"
   ```

4. **Initialiser la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Lancer le serveur local**
   ```bash
   symfony serve
   ```
   *L'application sera en ligne via l'URL locale `http://127.0.0.1:8000`*

---

## 🙏 Acknowledgments

Nous tenons à exprimer nos plus vifs remerciements à notre encadrante et tuteur, Madame **Ayari Asma**, pour son encadrement précieux, ses conseils avisés et son soutien constant tout au long du développement de ce projet. 

Un grand merci également à notre prestigieuse faculté, **École Supérieure Privée d’Ingénierie et de Technologies (ESPRIT)**, pour nous avoir offert un environnement d'apprentissage exceptionnel, des ressources de qualité, et pour nous préparer au mieux aux défis du monde de l'ingénierie et de la technologie.