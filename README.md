# Laravel Product Admin Dashboard

A full-stack product management system with a Laravel 13 RESTful API backend and a Vue 3 admin dashboard frontend.

---

## Tech Stack

**Backend**
- Laravel 13 / PHP 8.3
- MySQL 8
- Laravel Sanctum (token auth)
- Laravel Excel (export)
- L5-Swagger (API docs)
- PHPUnit (testing)

**Frontend**
- Vue 3 + Vite
- Axios
- Vue Router

**Infrastructure**
- Docker + Nginx + PHP-FPM

---

## Project Structure

```
├── app/          # Laravel backend
├── frontend/     # Vue 3 frontend
├── docker/       # Dockerfile configs (php, nginx, frontend)
└── docker-compose.yml
```

---

## Setup

### With Docker (recommended)

```bash
# 1. Clone the repo
git clone <repo-url> && cd <repo>

# 2. Start all services (backend + frontend + db)
docker-compose up -d --build

# 3. Install backend dependencies
docker-compose exec app composer install

# 4. Configure backend
cp app/.env.example app/.env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

| Service   | URL                              |
|-----------|----------------------------------|
| Frontend  | http://localhost:3000            |
| Backend   | http://localhost:8000            |
| Swagger   | http://localhost:8000/api/documentation |

---

### Without Docker

**Backend**
```bash
cd app
composer install
cp .env.example .env
# Update DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
Backend available at **http://localhost:8000**

**Frontend**
```bash
cd frontend
npm install
npm run dev
```
Frontend available at **http://localhost:3000**

> **Windows/Docker note:** Hot reload uses polling (`usePolling: true` in `vite.config.js`) to detect file changes across the Windows volume boundary.

---

## Seeded Credentials

| Email             | Password |
|-------------------|----------|
| admin@example.com | password |

---

## API Endpoints

All endpoints except `/api/login` require `Authorization: Bearer <token>` header.

### Auth

| Method | Endpoint      | Auth | Description                    |
|--------|---------------|------|--------------------------------|
| POST   | /api/login    | No   | Login and get a Bearer token   |
| POST   | /api/logout   | Yes  | Revoke current token           |

**Login body:**
```json
{ "email": "admin@example.com", "password": "password" }
```

**Login response:**
```json
{ "token": "<bearer-token>", "user": { "id": 1, "name": "Admin", "email": "admin@example.com" } }
```

> Logging in revokes all previous tokens — only one active session per user.

---

### Categories

| Method | Endpoint        | Description         |
|--------|-----------------|---------------------|
| GET    | /api/categories | List all categories |

---

### Products

| Method | Endpoint                  | Description                                    |
|--------|---------------------------|------------------------------------------------|
| GET    | /api/products             | List products (paginated, filterable)          |
| POST   | /api/products             | Create a product                               |
| GET    | /api/products/{id}        | Get product detail                             |
| PUT    | /api/products/{id}        | Update a product (all fields required)         |
| DELETE | /api/products/{id}        | Soft delete a product                          |
| DELETE | /api/products/bulk        | Bulk soft delete products                      |
| GET    | /api/products/export-link | Get a temporary signed URL to download Excel   |
| GET    | /api/products/export      | Download Excel file (via signed URL, no token) |

**Query parameters for GET /api/products:**

| Param       | Type    | Description              |
|-------------|---------|--------------------------|
| category_id | integer | Filter by category       |
| enabled     | boolean | Filter by enabled status |
| page        | integer | Page number (default: 1) |

**POST /api/products body:**
```json
{
  "name": "Product Name",
  "category_id": 1,
  "description": "Optional",
  "price": 19.99,
  "stock": 100,
  "enabled": true
}
```

**PUT /api/products/{id} body** — all fields required:
```json
{
  "name": "Updated Name",
  "category_id": 1,
  "description": "Updated",
  "price": 29.99,
  "stock": 50,
  "enabled": true
}
```

**DELETE /api/products/bulk body:**
```json
{ "ids": [1, 2, 3] }
```

**Excel export flow:**
1. `GET /api/products/export-link` → returns a signed URL (expires in 5 min)
2. Open the URL in a browser → file downloads automatically

---

## API Documentation (Swagger)

```bash
docker-compose exec app php artisan l5-swagger:generate
```

Visit: **http://localhost:8000/api/documentation**

---

## Running Tests

```bash
# With Docker
docker-compose exec app php artisan test

# Without Docker
cd app && php artisan test
```

---

## Design Choices

- **Sanctum token auth** — stateless Bearer tokens; any authenticated user has full access since this is an admin-only dashboard.
- **Single session** — logging in revokes all previous tokens for that user.
- **Soft deletes** — products are never permanently removed.
- **Signed export URL** — tokenless but signed, expires in 5 minutes, safe to open in a browser.
- **ForceJsonResponse middleware** — forces `Accept: application/json` on all API routes so errors always return JSON.
- **Bulk/export routes before `apiResource`** — prevents route model binding from treating `bulk` or `export` as a product ID.
- **Platform pinned to PHP 8.3** — keeps the Composer lock file compatible with the Docker container even when Composer runs locally on a newer PHP.
