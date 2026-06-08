# Hotel Metrodata Database

## Connection

| Field | Value |
|---|---|
| Host | `127.0.0.1` |
| Database | `hotel_metrodata` |
| User | `hotel` |
| Password | `hotel123` |

**Connect:**
```bash
mysql -u hotel -photel123 hotel_metrodata
# or
sudo mariadb hotel_metrodata
```

## Key Tables

| Table | What's Inside |
|---|---|
| `wp_posts` | Pages, rooms/products, orders, menus |
| `wp_postmeta` | Prices (`_price`), thumbnails (`_thumbnail_id`) |
| `wp_users` | Usernames, emails, hashed passwords |
| `wp_usermeta` | Roles, capabilities, last activity |
| `wp_options` | Site URL, theme, active plugins, WooCommerce settings |
| `wp_comments` | Product reviews (with ratings in `wp_commentmeta`) |

## Common Queries

```sql
-- All rooms with prices
SELECT ID, post_title, post_status FROM wp_posts WHERE post_type = 'product';

-- Find user by email
SELECT * FROM wp_users WHERE user_email = 'example@gmail.com';

-- All prices
SELECT p.ID, p.post_title, pm.meta_value AS price
FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product' AND pm.meta_key = '_price';

-- Block a user
UPDATE wp_usermeta SET meta_value = 'a:1:{s:7:"blocked";b:1;}'
WHERE user_id = 3 AND meta_key = 'wp_capabilities';

-- Reset admin password
UPDATE wp_users SET user_pass = MD5('newpassword') WHERE ID = 1;

-- Site URL (fix redirects)
SELECT * FROM wp_options WHERE option_name IN ('siteurl', 'home');

-- Active plugins
SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';

-- Count users by role (terminal-friendly: add \G)
SELECT * FROM wp_users \G
```

## Backup & Restore

```bash
# Backup
sudo mysqldump hotel_metrodata | gzip > backup.sql.gz

# Restore
gunzip -c backup.sql.gz | sudo mariadb hotel_metrodata
```
