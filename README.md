# Hotel Metrodata

A full-featured hotel reservation website built on WordPress + WooCommerce.

## One-Click Setup

### Linux / WSL (Ubuntu/Debian)

```bash
git clone https://github.com/spicejar67/Hotel_Webapp.git
cd Hotel_Webapp
bash setup.sh
```

> **WSL users:** Clone inside WSL (not `/mnt/c/`). Script auto-moves to `/var/www/` if you forget.

### macOS

```bash
git clone https://github.com/spicejar67/Hotel_Webapp.git
cd Hotel_Webapp
bash setup-mac.sh
```

> Installs Homebrew automatically if you don't have it.

---

Both scripts auto-install everything — web server, PHP, database, WordPress, WooCommerce, plugins, products, content. No configuration needed.

When it finishes:

- **Website:** http://localhost
- **Admin panel:** http://localhost/wp-admin (username: `aus`, password: `admin123`)
- **Hotel admin:** http://localhost/hotel-admin/ (login as aus)

## Start / Stop

### Linux/WSL
```bash
bash start.sh    # Start nginx, MariaDB, PHP
bash stop.sh     # Stop all services
```

### macOS
```bash
brew services start nginx mariadb php    # Start
brew services stop nginx mariadb php     # Stop
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

- **Linux:** Ubuntu 24.04+ / Debian 12+ / WSL2 Ubuntu
- **macOS:** 2GB free disk
- Internet connection (pulls WordPress + dependencies)
