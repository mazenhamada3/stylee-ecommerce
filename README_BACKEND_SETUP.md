# STYLEE PHP + MySQL Backend Setup

This version keeps the original STYLEE frontend flow, but connects it to a PHP/MySQL backend instead of using only localStorage.

## What is included

- PHP API backend in `api/`
- MySQL schema and seed data in `database/database.sql`
- Real register/login with hashed passwords
- Session-based authentication
- Admin Panel hidden from non-admin users
- Backend-protected admin routes
- Product loading from MySQL
- Color-specific sizes and stock
- Admin product add/delete through backend API
- Orders saved in MySQL
- Admin order list with Delivered and Cancel buttons
- Checkout endpoint that checks stock and decreases the selected color/size stock
- Cancelling an order restores the product color/size quantity back into stock

## Requirements

Use XAMPP, MAMP, WAMP, Laragon, or any PHP/MySQL stack.

Required:

- PHP 8+
- MySQL or MariaDB
- PDO MySQL extension enabled

## Installation with XAMPP + VS Code

1. Start XAMPP:

   ```text
   Apache
   MySQL
   ```

2. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

3. Import this file:

   ```text
   database/database.sql
   ```

   It creates a fresh database:

   ```text
   stylee_store
   ```

   Important: this file uses `DROP DATABASE IF EXISTS stylee_store;`, so it resets the database when imported.

4. Open this file and update database credentials if needed:

   ```text
   api/config.php
   ```

   XAMPP default values:

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_NAME = 'stylee_store';
   const DB_USER = 'root';
   const DB_PASS = '';
   ```

5. Open the project folder in VS Code.

6. In the VS Code terminal, run from inside the project folder:

   ```powershell
   C:\xampp\php\php.exe -S localhost:8000 -t .
   ```

7. Open:

   ```text
   http://localhost:8000/home/home.html
   ```

## Admin login

Use this account to access the Admin Panel:

```text
Email: admin@stylee.com
Password: admin123
```

The Admin Panel button is hidden unless the logged-in user has the `admin` role.

## Main API routes

All routes go through:

```text
api/index.php?route=...
```

Available routes:

| Method | Route | Purpose |
|---|---|---|
| GET | `products` | List products with color-specific sizes |
| POST | `register` | Create account |
| POST | `login` | Login |
| POST | `logout` | Logout |
| GET | `me` | Current logged-in user |
| POST | `checkout` | Place order and reduce selected color/size stock |
| POST | `admin/products` | Add product, admin only |
| DELETE | `admin/products&id=PRODUCT_ID` | Delete product, admin only |
| GET | `admin/orders` | List orders, admin only |
| PATCH | `admin/orders` | Mark order as delivered or cancelled, admin only |
| POST | `admin/reset` | Reset demo products/orders, admin only |

## Notes

- The cart remains in browser localStorage for a simple ecommerce flow.
- Checkout is processed in the backend and stored in MySQL.
- Product stock is now per color and per size, not shared across all colors.
- Admin pages and admin APIs are protected by backend session role checking.


## Order status behavior

- New checkout orders are saved as `placed`.
- Admin can change a placed order to `delivered` or `cancelled`.
- When an order is cancelled, the exact ordered color/size quantities are added back to stock.
- Delivered or cancelled orders cannot be changed again from the admin panel.

## Updating from v2 without resetting database

If you already imported the previous backend database and do not want to reset your data, import this migration instead of `database.sql`:

```text
 database/migration_v3_delivered_cancelled_orders.sql
```

If this is a fresh setup, import only:

```text
database/database.sql
```
