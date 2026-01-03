# 🚀 Quick Setup Guide

Schnellstart-Anleitung für Chnubber-Shop v2

## Prerequisites

- Docker Desktop installiert und läuft
- Git installiert

## Setup in 5 Minuten

### 1. Repository klonen (falls noch nicht geschehen)
```bash
cd /Users/robertobarlocci/GitHub/shoppinglist
```

### 2. Environment konfigurieren
```bash
cp .env.example .env
```

### 3. Docker Container starten
```bash
docker-compose up -d
```

Warte bis alle Container hochgefahren sind (ca. 30 Sekunden).

### 4. In den App-Container wechseln
```bash
docker exec -it chnubber-app sh
```

### 5. Composer Dependencies installieren
```bash
composer install
```

### 6. NPM Dependencies installieren
```bash
npm install
```

### 7. Application Key generieren
```bash
php artisan key:generate
```

### 8. Datenbank migrieren & Demo-Daten laden
```bash
php artisan migrate:fresh --seed
```

### 9. Frontend builden (in einem neuen Terminal)

**Terminal 1 (bleibt im Container):**
```bash
# Für Development mit Hot Reload
npm run dev
```

**Terminal 2 (außerhalb des Containers):**
Öffne Browser: `http://localhost:8585`

## 🎉 Fertig!

### Login
- **User 1:** fritz@example.com / password
- **User 2:** vreni@example.com / password

## Häufige Probleme

### Port 8585 bereits belegt
Ändere in `.env`: `APP_PORT=8586` (oder einen anderen freien Port)
Dann: `docker-compose down && docker-compose up -d`

### Permission Errors
```bash
docker exec -it chnubber-app chown -R www:www storage bootstrap/cache
```

### NPM Fehler
```bash
docker exec -it chnubber-app sh
rm -rf node_modules package-lock.json
npm install
```

### Datenbank-Fehler
```bash
docker-compose down -v
docker-compose up -d
# Warte 10 Sekunden
docker exec -it chnubber-app php artisan migrate:fresh --seed
```

## Development Workflow

### Backend ändern
Container neu starten nicht nötig, Laravel lädt automatisch.

### Frontend ändern
Mit `npm run dev` wird automatisch neu geladen (Hot Reload).

### Datenbank zurücksetzen
```bash
docker exec -it chnubber-app php artisan migrate:fresh --seed
```

### Tests ausführen
```bash
docker exec -it chnubber-app php artisan test
```

### Queue Worker starten (läuft bereits im Container)
```bash
docker exec -it chnubber-queue sh
```

### Scheduler (läuft bereits im Container)
```bash
docker exec -it chnubber-scheduler sh
```

## Nützliche Commands

```bash
# Container Status
docker-compose ps

# Logs ansehen
docker-compose logs -f

# Container stoppen
docker-compose down

# Container neu starten
docker-compose restart

# Alle Container neu bauen
docker-compose down -v
docker-compose up -d --build
```

## Production Build

```bash
# Assets für Production builden
npm run build

# Cache optimieren
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrationen ausführen
php artisan migrate --force
```

## PWA Testen

1. In Chrome: Developer Tools → Application → Manifest
2. Prüfe ob manifest.json geladen wird
3. Service Worker sollte registriert sein
4. "Install App" Button sollte erscheinen

## Viel Erfolg! 🎊

Bei Fragen: Siehe `README.md` für detaillierte Dokumentation.
