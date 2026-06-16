# stylee-ecommerce

A sleek, responsive streetwear e-commerce platform built with a component-based architecture using HTML, CSS, JavaScript, and PHP. 

STYLEE handles a complete shopping flow—from dynamic product filtering to checkout—featuring a custom UI and a modular folder structure that keeps assets, markup, and logic neatly organized per feature.

## 🌍 Live Deployment & Infrastructure
**STYLEE is fully deployed and live.** The application is hosted on our bare-metal Dell Precision 7810 server running Proxmox. This gives us full control over the Apache/MySQL environment and public domain routing, allowing the site to securely handle live user traffic and global access.

---

## 🎥 Demo

### 1. Home & Hero Section
*Clean, bold typography emphasizing brand identity.*
![Home Screen](demo/Screenshot%202026-06-16%20152445.jpg)

### 2. Shop Catalog & Filtering
*Intuitive product grid with custom category dropdowns.*
![Shop Screen](demo/Screenshot%202026-06-16%20152458.jpg)

### 3. Dynamic Product Details
*Real-time color and size selection with live variant toggling.*
![Product Detail Screen 1](demo/Screenshot%202026-06-16%20152512.jpg)
![Product Detail Screen 2](demo/Screenshot%202026-06-16%20152522.jpg)

### 4. Custom Authentication Flow
*Secure user registration and login forms built from scratch.*
![Register Page](demo/Screenshot%202026-06-16%20152532.png)
![Login Page](demo/Screenshot%202026-06-16%20152538.png)

### 5. Shopping Cart Management
*Persistent cart engine supporting active quantity tracking.*
![Cart](demo/Screenshot%202026-06-16%20152547.png)

### 6. Multi-Step Checkout Funnel
*Interactive 3-stage validation pipeline featuring a custom credit card visualizer.*
![Checkout Shipping](demo/Screenshot%202026-06-16%20152608.png)
![Checkout Payment Blank](demo/Screenshot%202026-06-16%20152644.jpg)
![Checkout Payment Active](demo/Screenshot%202026-06-16%20152714.jpg)

---

## ✨ Features

* **Component-Based Architecture:** Clean separation of features. Every page (Home, Cart, Login, etc.) is isolated in its own directory containing its respective HTML, CSS, JS, and PHP files.
* **Advanced Product Catalog:** Interactive grid layout featuring category filtering and responsive design.
* **Dynamic Product Details:** Real-time variant selection and image toggling built with vanilla JavaScript.
* **Custom Authentication:** Secure User Registration and Login portals utilizing PHP session management.
* **Persistent Cart & Checkout:** Client-side cart management supporting the complete order lifecycle.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3 (Custom root variables, flexbox/grid layouts), JavaScript (DOM manipulation, state management)
* **Backend API:** Vanilla PHP 7.4+ (Routing, session management, and view rendering)
* **Database:** MySQL
* **Infrastructure:** Self-hosted bare-metal server with public DNS routing.

---

## 🗂️ Project Structure

The project follows a strict modular structure where each feature contains its own assets and logic:
```
stylee-ecommerce/
├── admin/
│   ├── admin.css
│   ├── admin.html
│   ├── admin.js
│   └── admin.php
├── assets/
│   └── hero.png
├── cart/
│   ├── cart.css
│   ├── cart.html
│   ├── cart.js
│   └── cart.php
├── checkout/
│   ├── checkout.css
│   ├── checkout.html
│   └── checkout.js
├── database/
│   ├── database.sql
│   ├── migration_v2_color_swatches.sql
│   └── migration_v3_delivery_status.sql
├── demo/
│   ├── Screenshot 2026-06-16 152445.jpg
│   ├── Screenshot 2026-06-16 152458.jpg
│   ├── Screenshot 2026-06-16 152512.jpg
│   ├── Screenshot 2026-06-16 152522.jpg
│   ├── Screenshot 2026-06-16 152532.png
│   ├── Screenshot 2026-06-16 152538.png
│   ├── Screenshot 2026-06-16 152547.png
│   ├── Screenshot 2026-06-16 152608.png
│   ├── Screenshot 2026-06-16 152644.jpg
│   └── Screenshot 2026-06-16 152714.jpg
├── home/
│   ├── home.css
│   ├── home.html
│   ├── home.js
│   └── home.php
├── login/
│   ├── login.css
│   ├── login.html
│   ├── login.js
│   └── login.php
├── product-details/
│   ├── product-details.css
│   ├── product-details.html
│   ├── product-details.js
│   └── product-details.php
├── products/
│   ├── products.css
│   ├── products.html
│   ├── products.js
│   └── products.php
├── register/
│   ├── register.css
│   ├── register.html
│   └── register.js
├── shared/
│   ├── config.php
│   ├── data.js
│   ├── functions.php
│   ├── index.php
│   ├── shared.css
│   └── utils.js
├── core.php
└── README.md
---
```
## 🚀 Setup & Local Development

To run a local development instance of the live site:

1. **Clone the repository:**
   ```git clone https://github.com/oamrmm71/Web-project.git```

2. **Configure your local environment:**
   * Move the project directory to your local web server root (e.g., `htdocs` for XAMPP or `/var/www/html`).

3. **Import the Database:**
   * Open phpMyAdmin or your MySQL CLI.
   * Import the database schema file inside the `database/` folder to build the tables.

4. **Connect the Backend:**
   * Update the database credentials inside your connection configuration inside `shared/` or `core.php` to match your local MySQL setup.

5. **Run:**
   * Navigate to `http://localhost/stylee-ecommerce/home/home.html` in your browser.

---

## 👤 Team Contributions

**My Specific Focus Areas:**
I was responsible for the **Frontend UI/UX, Client-Side Logic, and View Architecture** for key user flows. *(Note: Backend database queries and schema design were handled by my team partner).*

| Module | Files | What I Built |
| :--- | :--- | :--- |
| **Registration Flow** | register/register.html, register.css, register.js | Engineered the visual layout, responsive structural design, and client-side form architecture/validation for user onboarding. |
| **Product Catalog** | products/products.html, products.css, products.js | Built the responsive product grid, styled the UI components, and wrote the frontend JavaScript logic to handle category filtering and rendering. |

---

## ⚠️ Disclaimer
While this project is actively deployed on our servers, it originated as a collaborative project. For enterprise-level scaling, the backend endpoints should be further hardened with advanced CSRF protection and strict CORS policies.
