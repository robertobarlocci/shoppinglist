# 🛒 Shopping List

> Modern shared shopping list app for households – built with Laravel 11, Vue 3, PostgreSQL & Docker

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.4-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://docker.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

---

## 📖 About

**Shopping List** is a collaborative shopping list application designed for households. Multiple users share the same list, see activity updates from other users, and enjoy a smooth PWA experience with offline support.

### ✨ Key Features

| Feature | Description |
|---------|-------------|
| 🛒 **Smart Shopping Lists** | Categorized items with intelligent autocomplete from your inventory |
| ⚡ **Quick Buy Mode** | Rapid entry for spontaneous purchases (kiosk runs, corner shop) |
| 📦 **Inventory Tracking** | Keep track of what you have at home |
| 🔄 **Recurring Items** | Automatically add items to your list on selected weekdays |
| 👥 **Multi-User** | Shared lists for the whole household |
| 📊 **Activity Feed** | See who added, checked off, or deleted items (polling-based) |
| 📱 **PWA** | Installable app with offline support |
| 🌙 **Dark/Light Mode** | Dark mode by default, toggle in settings |
| 👆 **Swipe Gestures** | Mobile-optimized touch interactions |

---

## 🏗️ Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | Laravel (PHP) | 11.x (PHP 8.3) |
| **Frontend** | Vue.js + Inertia.js | 3.4 |
| **Styling** | Tailwind CSS | 3.x |
| **State Management** | Pinia | 2.x |
| **Database** | PostgreSQL | 16 |
| **Cache/Queue** | Redis | 7 |
| **Container** | Docker Compose | 3.8 |
| **Auth** | Laravel Breeze + Sanctum | - |
| **PWA** | Service Worker (Workbox) | - |

---

## 📁 Project Structure

```
shoppinglist/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # REST API controllers
│   │   │   │   ├── ActivityController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ItemController.php
│   │   │   │   └── SyncController.php
│   │   │   └── Auth/             # Authentication controllers
│   │   ├── Middleware/           # Custom middleware
│   │   └── Resources/            # API Resource transformers
│   ├── Jobs/
│   │   └── CheckRecurringItems.php   # Scheduled job
│   ├── Models/                   # Eloquent models
│   │   ├── Activity.php
│   │   ├── Category.php
│   │   ├── Item.php
│   │   ├── RecurringSchedule.php
│   │   └── User.php
│   ├── Providers/
│   └── Services/                 # Business logic layer
│       ├── ActivityLogger.php
│       ├── OfflineSyncService.php
│       └── RecurringService.php
├── database/
│   ├── factories/                # Model factories for testing
│   ├── migrations/               # Database schema
│   └── seeders/                  # Demo data
├── docker/
│   ├── nginx/                    # Nginx configuration
│   ├── php/                      # PHP-FPM Dockerfile & config
│   └── postgres/                 # PostgreSQL init scripts
├── public/
│   ├── icons/                    # PWA icons
│   ├── manifest.json             # PWA manifest
│   └── sw.js                     # Service Worker
├── resources/
│   ├── css/                      # Tailwind CSS
│   ├── js/
│   │   ├── Components/           # Vue components
│   │   ├── Composables/          # Vue composables (hooks)
│   │   │   ├── useOfflineSync.js
│   │   │   ├── useSwipe.js
│   │   │   ├── useTheme.js
│   │   │   └── useToast.js
│   │   ├── Pages/                # Inertia page components
│   │   └── Stores/               # Pinia stores
│   │       ├── activities.js
│   │       ├── categories.js
│   │       └── items.js
│   └── views/                    # Blade templates
├── routes/
│   ├── api.php                   # API routes
│   ├── console.php               # Console commands
│   └── web.php                   # Web routes
├── scripts/
│   ├── backup.sh                 # Database backup script
│   ├── deploy.sh                 # Deployment script
│   └── restore.sh                # Database restore script
├── tests/
│   ├── Feature/                  # Feature tests
│   └── Unit/                     # Unit tests
├── docker-compose.yml            # Development containers
├── docker-compose.prod.yml       # Production containers
└── README.md                     # You are here! 📍
```

---

## 🚀 Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose
- [Git](https://git-scm.com/)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-username/shoppinglist.git
cd shoppinglist

# 2. Copy environment file
cp .env.example .env

# 3. Start Docker containers
docker-compose up -d

# 4. Enter the app container
docker exec -it shoppinglist-app sh

# 5. Install dependencies
composer install
npm install

# 6. Generate application key
php artisan key:generate

# 7. Run migrations and seed demo data
php artisan migrate:fresh --seed

# 8. Build frontend assets
npm run build    # Production
# OR
npm run dev      # Development with hot reload
```

### Access the App

- **Application:** http://localhost:8585
- **Database (external):** localhost:54321

### Demo Credentials

| User | Email | Password |
|------|-------|----------|
| Fritz | `fritz@example.com` | `password` |
| Vreni | `vreni@example.com` | `password` |

---

## 🔧 Development

### Docker Commands

```bash
# Start all containers
docker-compose up -d

# Stop all containers
docker-compose down

# View logs
docker-compose logs -f

# Enter app container
docker exec -it shoppinglist-app sh

# Rebuild containers
docker-compose up -d --build
```

### Artisan Commands

```bash
# Database commands
php artisan migrate                    # Run migrations
php artisan migrate:fresh --seed       # Reset & seed database
php artisan db:seed                    # Run seeders only

# Cache commands
php artisan config:cache               # Cache configuration
php artisan route:cache                # Cache routes
php artisan view:cache                 # Cache views
php artisan cache:clear                # Clear application cache

# Custom commands
php artisan app:check-recurring-items  # Manually check recurring items

# Queue & Scheduler
php artisan queue:work                 # Start queue worker
php artisan schedule:work              # Start scheduler
```

### Frontend Development

```bash
# Development with hot reload
npm run dev

# Production build
npm run build

# Watch mode (build on change)
npm run watch
```

### Code Quality

```bash
# PHP linting with Laravel Pint
./vendor/bin/pint

# Run all tests
php artisan test

# Run specific test
php artisan test --filter ItemTest

# Run tests with coverage
php artisan test --coverage
```

---

## 🗄️ Database Schema

### Entity Relationship

```
┌─────────────┐     ┌─────────────┐     ┌───────────────────┐
│   users     │────<│   items     │────<│ recurring_schedules│
└─────────────┘     └─────────────┘     └───────────────────┘
      │                   │
      │                   │
      ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ activities  │     │ categories  │
└─────────────┘     └─────────────┘
```

### Tables Overview

| Table | Description |
|-------|-------------|
| `users` | User accounts with avatar colors |
| `items` | Shopping items with list_type (quick_buy, to_buy, inventory, trash) |
| `categories` | 11 default + custom categories |
| `recurring_schedules` | Weekly recurring patterns |
| `activities` | Activity log for all actions |

### Item List Types

| Type | Description |
|------|-------------|
| `quick_buy` | Urgent purchases (kiosk mode) |
| `to_buy` | Regular shopping list |
| `inventory` | Items at home |
| `trash` | Soft-deleted items |

---

## 🔌 API Reference

All API routes require authentication via Laravel Sanctum.

### Items

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/items` | List all items (filter by `?list_type=`) |
| `POST` | `/api/items` | Create new item |
| `GET` | `/api/items/{id}` | Get single item |
| `PUT` | `/api/items/{id}` | Update item |
| `DELETE` | `/api/items/{id}` | Soft delete item |
| `GET` | `/api/items/suggest?q=` | Autocomplete suggestions |
| `POST` | `/api/items/{id}/move` | Move item between lists |
| `POST` | `/api/items/{id}/restore` | Restore from trash |
| `DELETE` | `/api/items/{id}/permanent` | Permanently delete |
| `POST` | `/api/items/{id}/recurring` | Set recurring schedule |
| `DELETE` | `/api/items/{id}/recurring` | Remove recurring |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/categories` | List all categories |
| `POST` | `/api/categories` | Create custom category |
| `PUT` | `/api/categories/{id}` | Update category |
| `DELETE` | `/api/categories/{id}` | Delete category |

### Activities

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/activities` | Activity feed |
| `GET` | `/api/activities/unread` | Unread activities |
| `POST` | `/api/activities/mark-read` | Mark as read |

### Sync

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/sync` | Sync offline actions |

---

## 📱 PWA Installation

### Desktop (Chrome/Edge)

1. Click the install icon in the address bar
2. Click "Install"

### iOS (Safari)

1. Open in Safari
2. Tap Share button → "Add to Home Screen"

### Android (Chrome)

1. Open in Chrome
2. Tap Menu → "Add to Home Screen"

---

## ⚙️ Configuration

### Key Environment Variables

```env
# Application
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost:8585

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_DATABASE=shoppinglist
DB_USERNAME=shoppinglist
DB_PASSWORD=secret

# Session (30 days)
SESSION_LIFETIME=43200

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

### Timezone

Default timezone is `Europe/Zurich`. Change in `.env`:

```env
APP_TIMEZONE=Europe/Zurich
```

---

## 🔄 Recurring Items

Recurring items are checked daily at 06:00 by the scheduler:

1. Items in inventory with a recurring schedule are checked
2. If today matches a scheduled day, a copy is added to `to_buy`
3. Activity is logged
4. Users see a toast notification

### Scheduler Setup

The scheduler runs automatically in the `shoppinglist-scheduler` container.

For manual testing:
```bash
php artisan app:check-recurring-items
```

---

## 🐛 Troubleshooting

### Containers won't start

```bash
docker-compose down -v
docker-compose up -d --build
```

### Database errors

```bash
docker exec -it shoppinglist-app php artisan migrate:fresh --seed
```

### Assets not loading

```bash
docker exec -it shoppinglist-app npm run build
```

### Permission errors

```bash
docker exec -it shoppinglist-app chown -R www:www storage bootstrap/cache
```

### View logs

```bash
# Laravel logs
docker exec -it shoppinglist-app tail -f storage/logs/laravel.log

# All container logs
docker-compose logs -f
```

---

## 🚀 Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed production deployment instructions.

### Quick Production Build

```bash
# Build assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

### Production Environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage report
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/ItemTest.php

# Run specific test method
php artisan test --filter test_user_can_create_item
```

---

## 📋 Default Categories

| Icon | Category | Color |
|------|----------|-------|
| 🥬 | Obst & Gemüse | `#4CAF50` |
| 🥛 | Milchprodukte | `#2196F3` |
| 🥩 | Fleisch & Fisch | `#F44336` |
| 🍞 | Backwaren | `#FF9800` |
| 🥤 | Getränke | `#00BCD4` |
| 🧊 | Tiefkühl | `#9C27B0` |
| 🍝 | Vorräte | `#795548` |
| 🧹 | Haushalt | `#607D8B` |
| 🧴 | Körperpflege | `#E91E63` |
| 🐕 | Tierbedarf | `#8BC34A` |
| 📦 | Sonstiges | `#9E9E9E` |

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👥 Authors

- **Rob** - *Initial work & concept*

---

<p align="center">
  <strong>Version 2.0</strong> • Laravel 11 • PHP 8.3 • Vue 3.4 • PostgreSQL 16
</p>

<p align="center">
  Made with ❤️ (and Claude)
</p>
