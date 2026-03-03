<p align="center">
  <img src="./public/images/banner.png" alt="Esportify Banner" width="800">
</p>

# 🎮 Esportify - Professional E-Sports Management Ecosystem

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

**Esportify** is a comprehensive, state-of-the-art platform designed for the competitive gaming industry. It bridges the gap between players, teams, and tournament organizers by providing a unified ecosystem for tournament management, team recruitment, social engagement, and e-commerce—all wrapped in a premium, modern "Cyberpunk" aesthetic.

Built with **Symfony 6.4**, Esportify leverages modern web technologies to deliver a fast, responsive, and data-driven experience for the global e-sports community.

## 🚀 Key Modules & Features

### 🏆 Tournament Arena
- **Comprehensive Lifecycle**: Manage tournaments from planning to completion.
- **Dynamic Categories**: specialized tracks for **FPS, Sports, Battle Royale, and Mind Games**.
- **Mode Support**: Flexible systems for both **Solo** and **Squad** competitions.
- **Prize Pool Tracking**: Automated prize management and winner rankings.

### 👥 Team & Player Ecosystem
- **Team Management**: Create, customize, and lead elite e-sports organizations.
- **Recruitment Hub**: Advanced recruitment system with candidatures and vacancy listings.
- **Manager Requests**: Formalized workflow for team management transitions.

### 🛒 E-Sport Boutique (Shop)
- **Professional Storefront**: Browse and purchase e-sports gear and digital assets.
- **Secure Payments**: Integrated with **Stripe** for seamless and secure transactions.
- **Order Management**: Full tracking from purchase to fulfillment.

### 💬 Communication Hub
- **Real-Time Chat**: Direct messaging and team chat capabilities.
- **Notifications**: Instant alerts for match updates, recruitment news, and platform activity.
- **Social Feed**: Community posts, likes, comments, and media sharing.

### 🤖 AI Strategy Insights
- **Feed Analysis**: AI-powered insights into community trends and feed activity.
- **Team Performance Reports**: Automated AI generation of team performance summaries.
- **Recommendations**: Personalized content and team suggestions based on playstyle.

### 📊 Interactive Dashboards
- **Player Stats**: Visualized performance metrics using **Chart.js**.
- **Admin Command Center**: A powerful, no-auth `/admin` dashboard for rapid tournament orchestration.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.2+ | Symfony 6.4 (LTS)
- **Database**: MySQL | Doctrine ORM
- **Frontend**: Twig | Vanilla JS | Stimulus | Turbo (Symfony UX)
- **Styling**: Modern CSS with "Orbitron" & "Rajdhani" typography
- **Payments**: Stripe API
- **AI Integration**: Custom AI analysis modules for feeds and reports
- **Utilities**: VichUploader (Files), KnpSnappy (PDFs), LiipImagine (Images)

---

## 📦 Getting Started

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0+
- Symfony CLI (Optional but recommended)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/AmenBensalah/PI_DEV_ESPORTIFY.git
   cd esportify
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   Edit the `.env` file and set your database credentials:
   ```env
   DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/esportify_db?serverVersion=8.0"
   STRIPE_PUBLIC_KEY=your_key
   STRIPE_SECRET_KEY=your_secret
   ```

4. **Initialize Database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Seed Sample Data (Optional)**
   ```bash
   php bin/console app:create-test-users
   php bin/console app:create-sample-tournoys
   ```

6. **Start the server**
   ```bash
   symfony serve
   ```
   *Your app will be live at `http://127.0.0.1:8000`*

---

## 🛡️ Admin Dashboard

The primary administration interface is accessible at:
👉 **[http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)**

*Note: In the current development version, admin routes are globally accessible for testing purposes. For production, ensure ROLE_ADMIN is enforced in `security.yaml`.*

---

## 🎨 Branding & UI/UX

Esportify features a **High-Octane E-Sport Design System**:
- **Palette**: Deep Purples (`#9d4edd`), Vibrant Pinks (`#f72585`), and Electric Cyans.
- **Vibe**: Dark mode by default, glassmorphism, and neon glowing effects.
- **UX**: Smooth transitions powered by Symfony Turbo.

---

## 📄 License

Distributed under the **Proprietary License**. See `LICENSE` for more information.

---

<p align="center">
  Developed with ❤️ for the E-Sports Community by <b>Amen Bensalah & The Esportify Team</b>
</p>