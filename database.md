# Hotel Metrodata Database

## Before you start

You need a terminal. On Linux/Mac: open **Terminal**. On Windows: open **WSL** (search "wsl" in Start).

Every code block below can be copied and pasted directly. Press Enter to run.

## 1. Open the database

Copy and paste this into your terminal:

```bash
sudo mariadb hotel_metrodata
```

You should see a prompt like `MariaDB [hotel_metrodata]>`. You're now inside the database. Type commands at this prompt.

When you're done, type `exit` and press Enter.

## 2. Connection details

| Field | Value |
|---|---|
| Host | `127.0.0.1` |
| Database | `hotel_metrodata` |
| User | `hotel` |
| Password | `hotel123` |

If you need to connect with a specific user instead of sudo:

```bash
mysql -u hotel -photel123 hotel_metrodata
```

## 3. What's stored where

| Table | What's inside it |
|---|---|
| `wp_posts` | Pages, rooms/products, orders, menus — almost everything |
| `wp_postmeta` | Extra data like prices (`_price`), thumbnail image IDs |
| `wp_users` | Usernames, emails, passwords (hashed, not readable) |
| `wp_usermeta` | User roles, capabilities, last activity time |
| `wp_options` | Site URL, theme, active plugins, all WooCommerce settings |
| `wp_comments` | Product reviews and ratings |

## 4. Common tasks (copy and paste exactly)

### Show all users

```
SELECT ID, user_login, user_email FROM wp_users;
```

### Find a user by email

```
SELECT * FROM wp_users WHERE user_email = 'example@gmail.com';
```

### Show all rooms and their prices

```
SELECT p.ID, p.post_title, pm.meta_value AS price
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product' AND pm.meta_key = '_price';
```

### Reset the admin password (replace newpass123 with your new password)

```
UPDATE wp_users SET user_pass = MD5('newpass123') WHERE ID = 1;
```

### Check site URL (fixes redirect issues)

```
SELECT * FROM wp_options WHERE option_name IN ('siteurl', 'home');
```

### Change site URL (if site keeps redirecting wrong)

```
UPDATE wp_options SET option_value = 'http://localhost' WHERE option_name IN ('siteurl', 'home');
```

### See which plugins are active

```
SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';
```

### Count total rooms

```
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'product';
```

### Count total users

```
SELECT COUNT(*) FROM wp_users;
```

### Show all orders

```
SELECT ID, post_title, post_status FROM wp_posts WHERE post_type = 'shop_order';
```

### If the output is too wide to read

Add `\G` at the end instead of `;` — it flips everything vertical:

```
SELECT * FROM wp_users \G
```

## 5. Backup and restore

### Create a backup (copy and paste)

```bash
sudo mysqldump hotel_metrodata | gzip > ~/hotel_backup.sql.gz
```

This saves a file called `hotel_backup.sql.gz` in your home directory.

### Restore from backup (copy and paste)

```bash
gunzip -c ~/hotel_backup.sql.gz | sudo mariadb hotel_metrodata
```

## 6. Exit the database

Type `exit` and press Enter. Or press `Ctrl+C`.
