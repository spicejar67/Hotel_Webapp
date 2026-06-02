# Hotel Metrodata

A full-featured hotel reservation website built on WordPress + WooCommerce.

## One-Click Setup

Clone and run inside WSL/Linux:

```bash
git clone https://github.com/spicejar67/Hotel_Webapp.git
cd Hotel_Webapp
bash setup.sh
```

The script auto-installs everything — nginx, PHP, MariaDB, WordPress, WooCommerce, all plugins, products, and content. No configuration needed.

> **WSL users:** Clone directly inside WSL (not from `/mnt/c/`). The script auto-moves itself to `/var/www/` if you forget.

When it finishes:

- **Website:** http://localhost
- **Admin panel:** http://localhost/wp-admin (username: `aus`, password: `admin123`)
- **Hotel admin:** http://localhost/hotel-admin/ (login as aus)

## Start / Stop

```bash
bash start.sh    # Start nginx, MariaDB, PHP
bash stop.sh     # Stop all services
```

## Features

- Carousel homepage with room listings
- Room shop with add-to-cart and `/month` pricing
- Email verification sign-up (Gmail SMTP)
- Cart, checkout (login required), payment history
- Auto-logout after 5 minutes of inactivity
- Star rating system for rooms
- Admin panel: manage users, edit prices, add/delete rooms with images
- Secret Tetris game (link in footer)

## Admin Account

| Field | Value |
|---|---|
| Username | `aus` |
| Password | `admin123` |

**Change the password after first login.**

## Requirements

- Ubuntu 24.04+ / Debian 12+ / WSL2 Ubuntu
- Internet connection (pulls WordPress + dependencies)
- 2GB+ disk space
