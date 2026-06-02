# 🏨 Hotel Metrodata

WordPress + WooCommerce hotel reservation website with:
- Carousel homepage with room listings
- 5 room products with /month pricing
- 2FA email verification sign-up
- Cart & checkout with auto-logout
- Admin panel for managing users, rooms, prices
- Star rating system for rooms
- Tetris easter egg

## Quick Start

```bash
git clone <your-repo-url>
cd hotel-metrodata
bash setup.sh
```

The script will:
1. Install nginx, PHP, MariaDB (if needed)
2. Create the database
3. Download WordPress core
4. Import the demo content and products
5. Configure everything

After setup:
- **Site:** http://localhost
- **Admin:** http://localhost/wp-admin (user: `aus`, pass: `admin123`)
- **Admin Panel:** http://localhost/hotel-admin/ (login as aus)

## Manual Setup

1. Copy all files to your web root
2. Create a MySQL database
3. Import `database/seed.sql.gz`
4. Copy `wp-config-sample.php` → `wp-config.php` and fill in DB credentials
5. Point nginx/Apache to this folder

## Structure

```
├── setup.sh                  # One-click installer
├── wp-content/
│   ├── plugins/              # Custom plugins
│   │   ├── hotel-admin-panel.php
│   │   ├── hotel-cart-ui.php
│   │   ├── hotel-registration.php
│   │   └── hotel-payment-history.php
│   ├── mu-plugins/           # Auto-loaded plugins
│   │   ├── hotel-features.php
│   │   ├── hotel-footer-widgets.php
│   │   └── hotel-loader.php
│   ├── themes/storefront/    # Theme customizations
│   │   ├── templates/page-fullwidth.html
│   │   └── page-tetris.php
│   ├── hotel-*.css           # Custom styles
│   ├── hotel-carousel.js     # Carousel JS
│   └── uploads/hotel-pics/   # Room images
├── sim.html                  # Voting simulation game
├── tetris.html               # Tetris game
└── database/
    └── seed.sql.gz           # Demo database
```

## Requirements

- Ubuntu 24.04+ / Debian 12+ / WSL2
- PHP 8.2+
- MariaDB 10.4+
- nginx or Apache
