# Scents by AS — Backend API

Laravel 13 + Filament 5 backend for the perfume storefront.

## Requirements

- PHP 8.3+ with `pdo_mysql`
- Composer
- MySQL 5.7+ / MariaDB

## Setup

```bash
cd AS_backend
cp .env.example .env
php artisan key:generate
```

Create the MySQL database (or run the helper script):

```bash
php database/scripts/create_mysql_database.php
```

Configure MySQL in `.env` (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Default database name: **`scents_by_as`**

Admin panel: http://localhost:8000/admin  
Default admin (from `.env`): `admin@scentsbyas.com` / `password`

## API (v1)

Base URL: `http://localhost:8000/api/v1`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/categories` | List categories |
| GET | `/products` | List products (`?category=Oriental`) |
| GET | `/products/{slug}` | Product detail with images & quantity offers |
| GET | `/slides` | Active homepage slides |
| POST | `/cart/preview` | Price cart with tiers & optional coupon |
| POST | `/orders` | Place COD order (creates/updates customer by phone) |

### Example: create order

```bash
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -d "{\"customer\":{\"name\":\"Jane\",\"phone\":\"+96170123456\",\"address\":\"Main St\",\"city\":\"Beirut\"},\"payment_method\":\"cod\",\"items\":[{\"product_id\":\"golden-oud\",\"size\":\"50ml\",\"quantity\":3}]}"
```

### Example: cart preview with coupon

```bash
curl -X POST http://localhost:8000/api/v1/cart/preview \
  -H "Content-Type: application/json" \
  -d "{\"items\":[{\"product_id\":\"golden-oud\",\"size\":\"50ml\",\"quantity\":3}],\"discount_code\":\"WELCOME10\"}"
```

## Features

- Multi-image products (`product_images`)
- Homepage slides
- Quantity-tier savings (e.g. buy 3 save $10 on Golden Oud)
- Discount coupons with usage limits and date windows
- Guest checkout with customer stored in `users` by phone
- Filament admin for catalog, slides, discounts, and orders

## Dev tools

Copy product images from the storefront assets (optional, after seeding):

```bash
php artisan products:sync-images
```

Root URL `/` redirects to the admin panel.
