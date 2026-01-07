# 🛒 Shopping List

> Modern shared shopping list app for households – built with Laravel 11, Vue 3, PostgreSQL & Docker

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.4-4FC08D?style=flat-square&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://docker.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![CI](https://github.com/robertobarlocci/shoppinglist/actions/workflows/ci.yml/badge.svg)](https://github.com/robertobarlocci/shoppinglist/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

**🔗 Repository:** https://github.com/robertobarlocci/shoppinglist

**🐳 Docker Image:** `ghcr.io/robertobarlocci/shoppinglist:latest`

---

## 📖 About

**Shopping List** is a comprehensive family organization app designed for households. It combines shopping list management, meal planning, and lunchbox organization in one unified PWA with role-based access for parents and kids.

### ✨ Key Features

| Feature | Description |
|---------|-------------|
| 🛒 **Smart Shopping Lists** | Categorized items with intelligent autocomplete from your inventory |
| ⚡ **Quick Buy Mode** | Rapid entry for spontaneous purchases (kiosk runs, corner shop) |
| 📦 **Inventory Tracking** | Keep track of what you have at home |
| 🔄 **Recurring Items** | Automatically add items to your list on selected weekdays |
| 🍽️ **Meal Planner** | Weekly meal planning with 4 meal types (Frühstück, Mittagessen, Zvieri, Abendessen) |
| 🥗 **Ingredient Management** | Add meal ingredients with autocomplete and bulk-add to shopping list |
| 🍱 **Lunchbox Requests** | Kids can request items for their daily lunchbox, parents view all requests |
| 👨‍👩‍👧‍👦 **Family Roles** | Parent and Kid accounts with role-based permissions and meal suggestions |
| 👥 **Multi-User** | Shared lists, meals, and activities for the whole household |
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
│   │   │   │   ├── LunchboxController.php
│   │   │   │   ├── MealPlanController.php
│   │   │   │   └── SyncController.php
│   │   │   ├── Auth/             # Authentication controllers
│   │   │   └── DashboardController.php
│   │   ├── Middleware/           # Custom middleware
│   │   │   └── RestrictKidsAccess.php
│   │   ├── Policies/             # Authorization policies
│   │   │   ├── LunchboxItemPolicy.php
│   │   │   └── MealPlanPolicy.php
│   │   └── Resources/            # API Resource transformers
│   ├── Jobs/
│   │   └── CheckRecurringItems.php   # Scheduled job
│   ├── Models/                   # Eloquent models
│   │   ├── Activity.php
│   │   ├── Category.php
│   │   ├── Item.php
│   │   ├── LunchboxItem.php
│   │   ├── MealPlan.php
│   │   ├── MealPlanIngredient.php
│   │   ├── MealPlanSuggestion.php
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
│   │   │   ├── LunchboxCard.vue
│   │   │   ├── MealCard.vue
│   │   │   └── SuggestionsCard.vue
│   │   ├── Composables/          # Vue composables (hooks)
│   │   │   ├── useOfflineSync.js
│   │   │   ├── useSwipe.js
│   │   │   ├── useTheme.js
│   │   │   └── useToast.js
│   │   ├── Pages/                # Inertia page components
│   │   │   ├── Dashboard.vue
│   │   │   ├── LunchboxView.vue
│   │   │   └── MealPlanner.vue
│   │   └── Stores/               # Pinia stores
│   │       ├── activities.js
│   │       ├── categories.js
│   │       ├── items.js
│   │       ├── lunchbox.js
│   │       └── mealPlans.js
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

### Option 1: Deploy Pre-built Docker Image (Recommended for Servers)

The easiest way to run this app on your server. No building required!

```bash
# 1. Create a directory for the app
mkdir -p /opt/shoppinglist && cd /opt/shoppinglist

# 2. Download the production docker-compose file
curl -O https://raw.githubusercontent.com/robertobarlocci/shoppinglist/main/docker-compose.prod.yml

# 3. Download the nginx config
mkdir -p docker/nginx
curl -o docker/nginx/default.conf https://raw.githubusercontent.com/robertobarlocci/shoppinglist/main/docker/nginx/default.conf

# 4. Create your .env file
cat > .env << 'EOF'
APP_NAME="Shopping List"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip:8585

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=shoppinglist
DB_USERNAME=shoppinglist
DB_PASSWORD=CHANGE_THIS_TO_A_STRONG_PASSWORD

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
EOF

# 5. Generate a secure app key and add it to .env
APP_KEY=$(openssl rand -base64 32)
echo "APP_KEY=base64:$APP_KEY" >> .env

# 6. Pull and start the containers
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d

# 7. Wait for database to be ready (30 seconds)
sleep 30

# 8. Run database migrations
docker exec chnubber-app php artisan migrate --force

# 9. (Optional) Seed demo data
docker exec chnubber-app php artisan db:seed --force
```

**Your app is now running at `http://your-server-ip:8585`** 🎉

### Option 2: Local Development Setup

For developers who want to modify the code:

```bash
# 1. Clone the repository
git clone https://github.com/robertobarlocci/shoppinglist.git
cd shoppinglist

# 2. Copy environment file
cp .env.example .env

# 3. Start Docker containers
docker compose up -d

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

- **Application:** http://localhost:8585 (or your server IP)
- **Database (external):** localhost:54321

### Demo Credentials

| User | Role | Email | Password | Notes |
|------|------|-------|----------|-------|
| Fritz | Parent | `fritz@example.com` | `password` | Full access to all features |
| Vreni | Kid | `vreni@example.com` | `password` | Child of Fritz, can suggest meals |
| Ruedi | Kid | `ruedi@example.com` | `password` | Child of Fritz, can suggest meals |

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
                    ┌─────────────────────────┐
                    │        users            │
                    │  (role, parent_id)      │
                    └─────────────────────────┘
                      │  │  │  │  │  │  │
        ┌─────────────┘  │  │  │  │  │  └────────────────────┐
        │                │  │  │  │  └───────────────┐        │
        ▼                │  │  │  └──────────┐       │        │
┌──────────────┐         │  │  │             │       │        │
│  activities  │         │  │  │             │       │        │
└──────────────┘         │  │  │             │       │        │
                         │  │  │             ▼       ▼        ▼
                         │  │  │      ┌──────────┐ ┌──────────────┐ ┌──────────────────┐
                         │  │  │      │meal_plans│ │lunchbox_items│ │meal_plan_suggestions│
                         │  │  │      └──────────┘ └──────────────┘ └──────────────────┘
                         │  │  │            │
                         │  │  │            ▼
                         │  │  │      ┌──────────────────────┐
                         │  │  │      │meal_plan_ingredients │
                         │  │  │      └──────────────────────┘
                         │  │  │            │
                         ▼  ▼  ▼            ▼
                    ┌─────────────┐   ┌─────────────┐
                    │   items     │──<│ categories  │
                    └─────────────┘   └─────────────┘
                         │
                         ▼
                    ┌───────────────────┐
                    │recurring_schedules│
                    └───────────────────┘
```

### Tables Overview

| Table | Description |
|-------|-------------|
| `users` | User accounts with roles (parent/kid), parent_id, and avatar colors |
| `items` | Shopping items with list_type (quick_buy, to_buy, inventory, trash) |
| `categories` | 11 default + custom categories |
| `recurring_schedules` | Weekly recurring patterns for inventory items |
| `activities` | Activity log for all actions |
| `meal_plans` | Meal planning with date, meal_type, and title |
| `meal_plan_ingredients` | Ingredients for each meal with optional shopping list link |
| `meal_plan_suggestions` | Kids' meal suggestions awaiting parent approval |
| `lunchbox_items` | Daily lunchbox requests from kids |

### User Roles

| Role | Access | Capabilities |
|------|--------|--------------|
| `parent` | Full access | Manage shopping lists, create meals, view kids' lunchbox requests, approve/reject meal suggestions |
| `kid` | Restricted | View meal planner, suggest meals, manage own lunchbox requests |

### Item List Types

| Type | Description |
|------|-------------|
| `quick_buy` | Urgent purchases (kiosk mode) |
| `to_buy` | Regular shopping list |
| `inventory` | Items at home |
| `trash` | Soft-deleted items |

### Meal Types

| Type | German Name | Order |
|------|-------------|-------|
| `breakfast` | Frühstück | 1 |
| `lunch` | Mittagessen | 2 |
| `zvieri` | Zvieri | 3 |
| `dinner` | Abendessen | 4 |

---

## 👨‍👩‍👧‍👦 Family Features

### 🍽️ Meal Planner

The meal planner provides a comprehensive weekly view for organizing family meals.

**Features:**
- **Week View:** Navigate through weeks (Monday-Sunday) with calendar controls
- **4 Meal Types:** Breakfast (Frühstück), Lunch (Mittagessen), Zvieri, Dinner (Abendessen)
- **Meal Management:** Create, edit, and delete meals with autocomplete from your meal library
- **Ingredient Tracking:** Add ingredients with quantities and link to shopping list items
- **Autocomplete Suggestions:** Smart suggestions based on previously used meal titles
- **Shopping List Integration:** Bulk-add all meal ingredients to your shopping list with one click
- **Shared Planning:** All family members see the same meal plan

**Parent Capabilities:**
- Create and edit meals directly
- Add/remove ingredients
- Delete meals
- Approve or reject kids' meal suggestions

**Kid Capabilities:**
- View the weekly meal plan
- Suggest meals for any day/meal type (pending parent approval)
- Cannot directly create or modify meals

**Meal Suggestions Workflow:**
1. Kid suggests a meal for a specific date and meal type
2. Suggestion appears in parent's view with approve/reject options
3. Parent approves → meal is created automatically
4. Parent rejects → suggestion is removed

### 🍱 Lunchbox

The lunchbox feature allows kids to request items for their daily school lunches, giving parents visibility into what their children want.

**Features:**
- **Daily Requests:** Kids add items they want for each weekday's lunchbox
- **Autocomplete:** Smart suggestions from all family members' previous requests
- **Week Navigation:** Browse and plan ahead for the entire week
- **Parent Overview:** Parents see all children's lunchbox requests in one view
- **Simple Interface:** Fast, free-form input without complex meal structures

**Kid Capabilities:**
- Add items to their own lunchbox for any date
- Delete their own lunchbox items
- Cannot see or modify siblings' items

**Parent Capabilities:**
- View all children's lunchbox requests (read-only)
- See which child requested which items
- Cannot add or delete lunchbox items

**Use Cases:**
- Kids plan their own lunches
- Parents shop based on actual requests
- Reduce food waste by knowing preferences
- Teach kids meal planning skills

### 🔒 Role-Based Access

The app implements a parent-child role system with appropriate permissions.

**Parent Role:**
- Full access to shopping lists (create, edit, delete, move items)
- Full access to inventory management
- Create and manage meals directly
- View all children's lunchbox requests
- Approve/reject meal suggestions from kids
- Access to all app features

**Kid Role:**
- View shopping lists (read-only)
- View meal planner (read-only)
- Suggest meals (pending parent approval)
- Manage own lunchbox requests
- Cannot access dashboard or admin features
- Restricted to specific routes via middleware

**Technical Implementation:**
- `RestrictKidsAccess` middleware blocks kids from unauthorized routes
- Policy-based authorization (MealPlanPolicy, LunchboxItemPolicy)
- Parent-child relationships in database (`parent_id` column)
- Scoped queries ensure kids only see/modify their own data

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

### Meal Plans

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/meal-plans` | List meal plans (filter by `?start_date=`) |
| `POST` | `/api/meal-plans` | Create or update meal plan |
| `GET` | `/api/meal-plans/{id}` | Get single meal plan with ingredients |
| `PUT` | `/api/meal-plans/{id}` | Update meal plan |
| `DELETE` | `/api/meal-plans/{id}` | Delete meal plan |
| `GET` | `/api/meal-plans/suggest?q=` | Autocomplete meal title suggestions |
| `GET` | `/api/meal-plans/library` | Get all unique meals with usage counts |
| `POST` | `/api/meal-plans/{id}/ingredients` | Add ingredient to meal |
| `DELETE` | `/api/meal-plans/{id}/ingredients/{ingredientId}` | Remove ingredient |
| `POST` | `/api/meal-plans/{id}/add-to-shopping-list` | Bulk-add ingredients to shopping list |

### Meal Plan Suggestions (Kids)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/meal-plan-suggestions` | List suggestions (kids: own, parents: children's) |
| `POST` | `/api/meal-plan-suggestions` | Create suggestion (kids only) |
| `POST` | `/api/meal-plan-suggestions/{id}/approve` | Approve suggestion (parents only) |
| `POST` | `/api/meal-plan-suggestions/{id}/reject` | Reject suggestion (parents only) |
| `DELETE` | `/api/meal-plan-suggestions/{id}` | Delete suggestion (kids: own only) |

### Lunchbox

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/lunchbox` | List lunchbox items (kids: own, parents: children's) |
| `POST` | `/api/lunchbox` | Add lunchbox item (kids only) |
| `DELETE` | `/api/lunchbox/{id}` | Remove lunchbox item (kids: own only) |
| `GET` | `/api/lunchbox/suggest?q=` | Autocomplete suggestions (family vocabulary) |

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

### Docker Compose Files Explained

| File | Purpose | When to Use |
|------|---------|-------------|
| `docker-compose.yml` | **Development** | Local development with live code changes |
| `docker-compose.prod.yml` | **Production** | Server deployment with pre-built images |

### Update Your Production Server

```bash
cd /opt/shoppinglist

# Pull latest image (auto-built on GitHub)
docker pull ghcr.io/robertobarlocci/shoppinglist:latest

# Restart with new image
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d

# Run any new migrations
docker exec chnubber-app php artisan migrate --force
```

### Quick Production Build (for development compose)

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

The app includes comprehensive Feature tests for all major functionality.

```bash
# Run all tests
php artisan test

# Run with coverage report
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/ItemTest.php
php artisan test tests/Feature/LunchboxTest.php

# Run specific test method
php artisan test --filter test_user_can_create_item
```

**Test Coverage:**
- ✅ Shopping list CRUD operations
- ✅ Item movement between lists (quick_buy, to_buy, inventory, trash)
- ✅ Recurring schedules
- ✅ Lunchbox feature (12 tests, 26 assertions)
  - Kids can create/delete own items
  - Kids cannot delete siblings' items
  - Parents can view children's items (read-only)
  - Autocomplete within family boundaries
- ✅ Meal plan authorization and policies
- ✅ Category management
- ✅ Activity logging

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

1. Fork the repository at https://github.com/robertobarlocci/shoppinglist
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
  <strong>Version 3.0</strong> • Laravel 11 • PHP 8.3 • Vue 3.4 • PostgreSQL 16
</p>

<p align="center">
  <em>A comprehensive family organization app with shopping lists, meal planning, and lunchbox management</em>
</p>

<p align="center">
  Made with ❤️ (and Claude)
</p>
