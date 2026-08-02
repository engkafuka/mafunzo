# Mafunzo Docker deployment (Phase 1 — safe)

Run **Laravel (PHP-FPM) + nginx in Docker**. Keep the **existing PostgreSQL on the host**.  
Test on port **8080** while the current site on **:80** stays online. Cut over only after checks pass.

---

## Architecture (Phase 1)

```text
Browser → :8080 → Docker nginx → Docker PHP-FPM (app)
                                      │
                                      ▼
                               Host PostgreSQL :5432
                               (unchanged live data)
```

Current (old) stack on `:80` keeps running until you switch.

---

## Prerequisites

- Live site already working at `http://41.59.85.9`
- SSH access as `wrrb01`
- Docker Engine + Docker Compose plugin on the server
- Postgres listening on `127.0.0.1:5432` (already true)

---

## Step 0 — Backup (do this first)

On the live server:

```bash
cd ~/mafunzo
cp .env .env.backup.$(date +%F)

# Database dump
mkdir -p ~/backups
export $(grep -E '^DB_' .env | sed 's/\r$//' | xargs)
PGPASSWORD="$DB_PASSWORD" pg_dump -h 127.0.0.1 -U "$DB_USERNAME" -d "$DB_DATABASE" \
  -F c -f ~/backups/mafunzo-$(date +%F).dump

ls -lh ~/backups/
```

---

## Step 1 — Install Docker on the live server

```bash
# If Docker is not installed yet:
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker wrrb01

# Log out and SSH back in, then:
docker --version
docker compose version
```

---

## Step 2 — Pull Docker files

On your PC (after this feature is committed/pushed):

```powershell
git add docker docker-compose.yml .dockerignore .env.docker.example docs/DOCKER_DEPLOY.md
git commit -m "Add Phase 1 Docker stack (app+nginx, host Postgres)."
git push origin main
```

On the server:

```bash
cd ~/mafunzo
git pull origin main
```

---

## Step 3 — Allow Docker containers to reach host Postgres

Containers use `host.docker.internal` → host gateway.

### 3a. Confirm Postgres listens on localhost

```bash
ss -lntp | grep 5432
# expect 127.0.0.1:5432
```

### 3b. Allow Docker bridge in `pg_hba.conf`

Find the file:

```bash
sudo -u postgres psql -c "SHOW hba_file;"
```

Edit it (path is usually under `/etc/postgresql/.../pg_hba.conf`):

```bash
sudo nano /etc/postgresql/*/main/pg_hba.conf
```

Add (near other `host` lines):

```text
# Docker bridge → host Postgres
host    mafunzo    mafunzo_user    172.16.0.0/12    scram-sha-256
host    mafunzo    mafunzo_user    172.16.0.0/12    md5
```

Reload Postgres:

```bash
sudo systemctl reload postgresql
```

### 3c. Point Laravel DB host for Docker

In `~/mafunzo/.env` add/update:

```env
DOCKER_DB_HOST=host.docker.internal
DB_HOST=host.docker.internal
DOCKER_HTTP_PORT=8080
RUN_MIGRATIONS=false
APP_URL=http://41.59.85.9:8080
```

> Keep `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` as they are.  
> **Note:** while `DB_HOST=host.docker.internal`, host-side `php artisan` on the server may fail until you temporarily set `DB_HOST=127.0.0.1` again. Prefer using `docker compose exec app php artisan ...` after containers are up.

Safer pattern: leave `.env` with `DB_HOST=127.0.0.1` for host PHP, and only override in Compose (already done via `environment: DB_HOST: host.docker.internal`). So **you can leave `.env` DB_HOST as `127.0.0.1`**.

---

## Step 4 — Build and start on port 8080

```bash
cd ~/mafunzo

# Build PHP image
docker compose build app

# Start app + nginx (does NOT touch host :80)
docker compose up -d

docker compose ps
docker compose logs -f --tail=100 app
```

Open in browser / Postman:

```text
http://41.59.85.9:8080
http://41.59.85.9:8080/api/v1/licensing/trained-staff
```

(Use Bearer token for the API.)

---

## Step 5 — Smoke test checklist

On Docker (`:8080`) verify:

1. Login page loads  
2. Staff can open Application Management  
3. Trainee dashboard works  
4. Licensing API returns JSON (not 404/502)  
5. File uploads / ID cards still work if you test them  
6. No new errors: `docker compose logs app --tail=50`

Compare with the old site on `:80` — both should work in parallel.

Useful commands:

```bash
docker compose exec app php artisan db:show
docker compose exec app php artisan route:list --path=api/v1/licensing
docker compose exec app php artisan migrate:status
```

---

## Step 6 — Cut over to port 80 (only when Step 5 is green)

### 6a. Stop host nginx (or disable site)

```bash
sudo systemctl stop nginx
# or: sudo systemctl disable --now nginx
```

### 6b. Switch Docker to port 80

In `.env`:

```env
DOCKER_HTTP_PORT=80
APP_URL=http://41.59.85.9
```

```bash
cd ~/mafunzo
docker compose up -d
```

### 6c. Confirm

```text
http://41.59.85.9
http://41.59.85.9/api/v1/licensing/trained-staff
```

If something is wrong, roll back quickly:

```bash
# Stop Docker publish on 80
docker compose down

# Start old nginx again
sudo systemctl start nginx
```

---

## Day-to-day deploy after cutover

```bash
cd ~/mafunzo
git pull origin main
docker compose build app
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `connection refused` to DB from app | `pg_hba.conf` missing Docker subnet; reload Postgres |
| `password authentication failed` | Wrong `DB_PASSWORD` in `.env` |
| `502 Bad Gateway` | `docker compose logs app` — PHP-FPM not up |
| Static CSS missing | Ensure `public/build` exists on host after `git pull` / `npm run build` |
| Permission denied in storage | `sudo chown -R www-data:www-data storage bootstrap/cache` then restart |

Check container → host DB:

```bash
docker compose exec app php -r 'try { new PDO("pgsql:host=host.docker.internal;port=5432;dbname=mafunzo", getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo "DB OK\n"; } catch (Throwable $e) { echo $e->getMessage(), "\n"; }'
```

---

## Phase 2 (later)

Move Postgres into Compose + volume backups. Do **not** start Phase 2 until Phase 1 is stable for several days.

---

## Rollback summary

```bash
docker compose down
sudo systemctl start nginx
# restore DB only if you changed data wrongly:
# pg_restore -h 127.0.0.1 -U mafunzo_user -d mafunzo ~/backups/mafunzo-YYYY-MM-DD.dump
```
