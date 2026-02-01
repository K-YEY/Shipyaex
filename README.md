# ShipManager 🚢

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-F28D15?style=for-the-badge&logo=filament&logoColor=white)](https://filamentphp.com)
[![PWA](https://img.shields.io/badge/PWA-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](https://web.dev/progressive-web-apps/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

**ShipManager** is a comprehensive logistics and shipping management system designed specifically for the Egyptian market. It streamlines operations for administrators, clients, and captains (shippers), managing everything from order creation to final settlement.

## ✨ Key Features

-   **📊 Dynamic Dashboard:** Real-time statistics, delivery metrics, and intelligent alerts (e.g., package limits).
-   **📦 Order Management:** Seamless order entry (manual or Excel import), barcode scanning, and status tracking.
-   **💰 Financial Module:** Complete handling of driver remittances, client settlements, and profit calculation.
-   **📱 Progressive Web App (PWA):**
    -   **Installable:** Works as a native app on Desktop, Android, and iOS.
    -   **Offline Support:** Continue working with cached data even without internet.
    -   **Push Notifications:** Instant updates for new orders and status changes.
-   **🌍 Localization:** Built with full support for Arabic (Egyptian Dialect) and English.
-   **🚀 Optimized Performance:** Engineered to handle 100,000+ records efficiently.
-   **👥 Role-Based Access:** Dedicated interfaces for Admins, Clients, and Captains.

---

## 🚀 Getting Started

### Prerequisites
-   PHP 8.1+
-   Composer
-   Node.js & NPM
-   MySQL

### Installation

1.  **Clone the repository**
    ```bash
    git clone <repository-url>
    cd shipmanager
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Configure your database settings in the `.env` file.*

4.  **Database Migration & Seeding**
    ```bash
    php artisan migrate --seed
    ```

5.  **Build Assets**
    ```bash
    npm run build
    ```

6.  **Run the Application**
    ```bash
    php artisan serve
    ```

---

## PWA Configuration

ShipManager is PWA-ready. To enable Push Notifications:

1.  **Generate VAPID Keys**
    ```bash
    npm install -g web-push
    web-push generate-vapid-keys
    ```

2.  **Update `.env`**
    Add the generated keys to your `.env` file:
    ```env
    VAPID_PUBLIC_KEY=your_public_key
    VAPID_PRIVATE_KEY=your_private_key
    ```
    *Note: Update `public/js/pwa.js` with your Public Key if not automatically injected.*

---

## 📖 User Guide Summary

### 👨‍💼 Admin
-   **Dashboard:** Monitor overall performance and alerts.
-   **Orders:** Assign captains, manage statuses, and resolve issues.
-   **Finance:** Approve remittances from captains and process client settlements.

### 👤 Client
-   **Create Orders:** Add shipping requests with details and COD amount.
-   **Track:** Monitor order status (Pending, Delivered, Returned).
-   **Settlements:** Request payouts for collected funds.

### 🛵 Captain (Shipper)
-   **Receive Orders:** Get notified of new assignments immediately.
-   **Update Status:** Mark orders as Delivered, Returned, or Postponed via the mobile interface.
-   **Remit:** Submit collection reports to the admin.

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
