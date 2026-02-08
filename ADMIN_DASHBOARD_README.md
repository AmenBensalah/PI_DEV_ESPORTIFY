# Admin Tournament Management System

## Overview
This is a professional E-Sportify themed admin dashboard for managing esports tournaments. The system provides direct access to the admin panel without requiring authentication, allowing full tournament management capabilities.

## Features

### Admin Dashboard (Home)
- **URL**: `/admin`
- **Description**: Main dashboard displaying key statistics and recent tournament activity
- **Stats Displayed**:
  - Total Registered Teams
  - Active Users
  - Created Tournaments
  - Recent Tournament Activity

### Tournament Management

#### View All Tournaments
- **URL**: `/admin/tournoi`
- **Features**:
  - Table view of all tournaments
  - Filter by game type (FPS, Sports, Battle Royale, Mind)
  - View tournament details
  - Edit/Delete actions
  - Status badges (Active, Planned, Completed)

#### Create Tournament
- **URL**: `/admin/tournoi/create`
- **Method**: POST
- **Fields**:
  - Tournament Name
  - Game Type (FPS, Sports, Battle_royale, Mind)
  - Tournament Type (solo, squad)
  - Game Title
  - Start Date/Time
  - End Date/Time
  - Status (planned, active, completed)
  - Prize Pool Amount

#### Edit Tournament
- **URL**: `/admin/tournoi/{id}/edit`
- **Method**: POST
- **Features**: Modify all tournament details

#### View Tournament Details
- **URL**: `/admin/tournoi/{id}/show`
- **Features**:
  - Full tournament information
  - Result details (if available)
  - Action buttons for edit/delete

#### Delete Tournament
- **URL**: `/admin/tournoi/{id}/delete`
- **Method**: POST
- **Feature**: Remove tournament from system

### Filter by Category
- **FPS Tournaments**: `/admin/tournoi/categorie/FPS`
- **Sports Tournaments**: `/admin/tournoi/categorie/Sports`
- **Battle Royale**: `/admin/tournoi/categorie/Battle_royale`
- **Mind Games**: `/admin/tournoi/categorie/Mind`

### Filter by Category and Tournament Type
- **Format**: `/admin/tournoi/categorie/{typeGame}/{typeTournoi}`
- **Examples**:
  - `/admin/tournoi/categorie/FPS/squad`
  - `/admin/tournoi/categorie/Sports/solo`

## Setup & Installation

### Prerequisites
- PHP 8.2+
- MySQL/MariaDB
- Symfony 7.0+
- Composer

### Installation Steps

1. **Clone/Setup the Project**
```bash
cd mon_projet
composer install
```

2. **Create Database**
```bash
php bin/console doctrine:database:create
```

3. **Run Migrations**
```bash
php bin/console doctrine:migrations:migrate
```

4. **Create Test Admin User**
```bash
php bin/console app:create-test-users
```

5. **Populate Sample Tournaments** (Optional)
```bash
php bin/console app:create-sample-tournoys
```

6. **Start Development Server**
```bash
symfony serve
```

Access the admin dashboard at: **http://127.0.0.1:8000/admin**

## Available Commands

### Create Test Users
```bash
php bin/console app:create-test-users
```
Creates default users:
- `admin@tournoi.com` (Admin)
- `user1@tournoi.com` (Regular User)
- `user2@tournoi.com` (Regular User)

### Create Sample Tournaments
```bash
php bin/console app:create-sample-tournoys
```
Populates the database with 6 sample tournaments across different game types and tournament types.

### Clear Cache
```bash
php bin/console cache:clear
```

## Design System

### Colors
- **Primary Purple**: `#9d4edd`
- **Accent Pink**: `#f72585`
- **Cyan**: `#4cc9f0`
- **Blue**: `#4361ee`

### Typography
- **Headers**: Orbitron
- **Body**: Rajdhani, Poppins

### Layout
- **Sidebar Navigation**: Always visible on desktop
- **Responsive**: Mobile-friendly design
- **Cards**: Gradient backgrounds with hover effects
- **Tables**: Sortable, filterable data display

## Database Schema

### Entities

#### Tournoi (Tournament)
- `id`: Primary Key
- `name`: Tournament Name
- `type_game`: Game Category (FPS, Sports, Battle_royale, Mind)
- `type_tournoi`: Tournament Type (solo, squad)
- `game`: Specific Game Title
- `startDate`: Tournament Start DateTime
- `endDate`: Tournament End DateTime
- `status`: Status (planned, active, completed)
- `prize_won`: Prize Pool Amount
- `creator_id`: Foreign Key to User

#### ResultatTournoi (Tournament Results)
- `id_resultat`: Primary Key
- `tournoi_id`: Foreign Key to Tournoi (OneToOne)
- `rank`: Final Rank/Position
- `score`: Final Score

#### User
- `id`: Primary Key
- `email`: User Email (Unique)
- `roles`: JSON Array of Roles
- `password`: Hashed Password

## Security

**Note**: The `/admin` route is currently configured with `PUBLIC_ACCESS`, meaning it requires no authentication. To add authentication later, update `config/packages/security.yaml`:

```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
```

## File Structure

```
src/
├── Controller/
│   └── Admin/
│       └── TournoiAdminController.php
├── Entity/
│   ├── Tournoi.php
│   ├── ResultatTournoi.php
│   └── User.php
├── Repository/
│   ├── TournoiRepository.php
│   ├── ResultatTournoiRepository.php
│   └── UserRepository.php
└── Command/
    ├── CreateTestUsersCommand.php
    └── CreateSampleTournoysCommand.php

templates/
├── admin/
│   ├── layout.html.twig
│   ├── dashboard.html.twig
│   └── tournoi/
│       ├── index.html.twig
│       ├── create.html.twig
│       ├── edit.html.twig
│       ├── show.html.twig
│       ├── category.html.twig
│       └── sub_category.html.twig
```

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check `DATABASE_URL` in `.env` file
- Ensure database exists

### Cache Issues
- Clear cache: `php bin/console cache:clear`
- Delete `var/cache` directory

### Missing Sample Data
- Run: `php bin/console app:create-sample-tournoys`

## Future Enhancements
- User authentication/login system (currently disabled)
- Tournament bracket visualization
- Player registration and management
- Real-time score updates
- Email notifications for tournament updates
- Advanced analytics and reporting

## Support
For issues or questions, please refer to the Symfony documentation or check the error logs in `var/log/`.
