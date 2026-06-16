# STYLEE — Fashion E-Commerce Web App

**A full-stack fashion e-commerce platform built with vanilla PHP, MySQL, HTML, CSS, and JavaScript.**

![Language](https://img.shields.io/badge/language-PHP-777BB4.svg) ![Frontend](https://img.shields.io/badge/frontend-HTML%2FCSS%2FJS-orange.svg) ![Database](https://img.shields.io/badge/database-MySQL-blue.svg) ![License](https://img.shields.io/badge/license-MIT-lightgrey.svg)

---

## 🛍️ Project Overview

STYLEE is a multi-page fashion e-commerce web application with a complete shopping flow — from browsing products to checkout — backed by a PHP/MySQL backend with session-based authentication.

The project covers the full stack:
- **Frontend:** HTML, CSS, JavaScript — responsive UI across all pages
- **Backend:** PHP with a shared core for DB connection and session management
- **Database:** MySQL with relational schema for users, products, orders, and cart

---

## 🎥 Demo

### Home Page
![Home](Demo/Home.png)

### Products Page
![Products](Demo/Products.png)

### Product Details
![Product Details](Demo/Product_details.png)

### Register Page
![Register](Demo/Register.png)

### Login Page
![Login](Demo/Login.png)

### Cart
![Cart](Demo/Cart.png)

### Checkout
![Checkout](Demo/Checkout.png)

### Admin Dashboard
![Admin1](Demo/Admin1.png)
![Admin2](Demo/Admin2.png)

---

## ⚙️ Features

- User registration and login with session-based authentication
- Product listing with category filtering and search
- Product detail page with image, description, and add-to-cart
- Shopping cart with quantity management
- Checkout flow with order summary
- Admin dashboard for product and order management
- Shared PHP core for DB connection and routing (`core.php`)

---

## 🗂️ Project Structure

```
stylee-ecommerce/
├── admin/             ← Admin dashboard (product & order management)
├── assets/            ← Images, fonts, shared CSS
├── cart/              ← Cart page (PHP + JS)
├── checkout/          ← Checkout page
├── database/          ← SQL schema and seed data
├── home/              ← Landing page
├── login/             ← Login page
├── product-details/   ← Single product view
├── products/          ← Product listing and filtering
├── register/          ← User registration
├── shared/            ← Shared components (header, footer, nav)
├── core.php           ← DB connection and session bootstrap
├── demo/              ← Screenshots
└── README.md
```

---

## 🗄️ Database Schema

The database covers the core e-commerce entities:

- `users` — registered accounts with hashed passwords
- `products` — items with name, price, category, image, stock
- `orders` — user orders with status tracking
- `order_items` — line items per order
- `cart` — session-linked cart items

Import the schema:
```bash
mysql -u root -p stylee < database/stylee.sql
```

---

## 🚀 Setup & Run

**Requirements:** PHP 7.4+, MySQL, Apache (XAMPP / WAMP / LAMP)

```bash
# 1. Clone the repo
git clone https://github.com/mazenhamada3/stylee-ecommerce.git

# 2. Move to your server root
# XAMPP: C:/xampp/htdocs/stylee-ecommerce
# Linux: /var/www/html/stylee-ecommerce

# 3. Import the database
mysql -u root -p stylee < database/stylee.sql

# 4. Configure DB connection in core.php
$host = "localhost";
$db   = "stylee";
$user = "root";
$pass = "";

# 5. Start Apache + MySQL and open:
# http://localhost/stylee-ecommerce/home/
```

---

## 👤 My Contribution

| Page | Files | What I built |
|---|---|---|
| Register | `register/register.php`, `register/register.css` | Registration form, validation, password hashing, DB insert |
| Products | `products/products.php`, `products/products.css` | Product grid, category filter, dynamic PHP rendering from DB |

---

## ⚠️ Disclaimer

Built as an academic project. Not intended for production use — passwords should use stronger hashing and input sanitization should be hardened before any real deployment.

---

## 📚 References

- [PHP Manual](https://www.php.net/manual/en/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MDN Web Docs](https://developer.mozilla.org/)
