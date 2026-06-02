# Hotel Metrodata

A full-featured hotel reservation website built on WordPress + WooCommerce.

## One-Click Setup

```bash
git clone https://github.com/spicejar67/Hotel_Webapp.git
cd Hotel_Webapp
bash setup.sh
```

That's it. The script installs everything automatically — nginx, PHP, MariaDB, WordPress, WooCommerce, all plugins, products, and content. When it finishes, open:

- **Website:** http://localhost
- **Admin panel:** http://localhost/wp-admin (username: `aus`, password: `admin123`)
- **Hotel admin:** http://localhost/hotel-admin/ (login as aus)

## Start / Stop

```bash
bash start.sh    # Start the server
bash stop.sh     # Stop the server
```

These start/stop nginx, MariaDB, and PHP — not needed right after setup (services are already running), but useful after rebooting.

## Features

- Carousel homepage with 5 room listings
- Room shop with add-to-cart and monthly pricing
- 2FA email verification sign-up (Gmail SMTP)
- Cart, checkout (login required), payment history
- Auto-logout after 5 minutes of inactivity
- Star rating system for rooms
- Admin panel: manage users, edit prices, add/delete rooms with images
- Secret Tetris game (link in footer)

## Admin Account

| Field | Value |
|-------|-------|
| Username | `aus` |
| Password | `admin123` |
| Email | (set by boss) |

**Change the password after first login.**

## Requirements

- Ubuntu 24.04+ / Debian 12+ / WSL2 with Ubuntu
- Internet connection (for dependency installs)
- 2GB+ free disk space
