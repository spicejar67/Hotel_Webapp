# Hotel Metrodata — Database Reference

**Connection:** `127.0.0.1` | **Database:** `hotel_metrodata` | **User:** `hotel` | **Password:** `hotel123`

---

## Quick Access

| Method | Command | Description |
|---|---|---|
| Root access | `sudo mariadb hotel_metrodata` | Full privileges, no password needed via sudo |
| User access | `mysql -u hotel -photel123 hotel_metrodata` | Connect as the hotel database user |
| Browser | `http://localhost/wp-admin` → **Hotel Logs** | GUI view of activity logs |

---

## Core Tables

| Table | What it stores | Example query |
|---|---|---|
| `wp_posts` | Pages, rooms/products, orders, menus | `SELECT ID, post_title FROM wp_posts WHERE post_type = 'product';` |
| `wp_postmeta` | Prices, thumbnails, visibility flags | `SELECT post_id, meta_value FROM wp_postmeta WHERE meta_key = '_price';` |
| `wp_users` | Usernames, emails, hashed passwords | `SELECT ID, user_login, user_email FROM wp_users;` |
| `wp_usermeta` | Roles, capabilities, last activity | `SELECT user_id, meta_value FROM wp_usermeta WHERE meta_key = 'wp_capabilities';` |
| `wp_options` | Site URL, theme, active plugins, WC settings | `SELECT option_name, option_value FROM wp_options WHERE option_name IN ('siteurl','home');` |
| `wp_comments` | Product reviews and ratings | `SELECT comment_ID, comment_content FROM wp_comments WHERE comment_post_ID = 20;` |
| `wp_signup_codes` | 2FA verification codes (hotel custom) | `SELECT * FROM wp_signup_codes WHERE verified = 0;` |

---

## Common Tasks

| Task | Command (copy-paste ready) |
|---|---|
| Show all rooms | `SELECT ID, post_title FROM wp_posts WHERE post_type = 'product';` |
| Show rooms with prices | `SELECT p.ID, p.post_title, pm.meta_value AS price FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id WHERE p.post_type = 'product' AND pm.meta_key = '_price';` |
| Count rooms | `SELECT COUNT(*) FROM wp_posts WHERE post_type = 'product';` |
| Show all users | `SELECT ID, user_login, user_email FROM wp_users;` |
| Find user by email | `SELECT * FROM wp_users WHERE user_email = 'example@gmail.com';` |
| Reset admin password | `UPDATE wp_users SET user_pass = MD5('newpassword') WHERE ID = 1;` |
| Check site URL | `SELECT * FROM wp_options WHERE option_name IN ('siteurl', 'home');` |
| Fix redirect to wrong port | `UPDATE wp_options SET option_value = 'http://localhost' WHERE option_name IN ('siteurl', 'home');` |
| See active plugins | `SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';` |
| Show all orders | `SELECT ID, post_title, post_status FROM wp_posts WHERE post_type = 'shop_order';` |
| Count total orders | `SELECT COUNT(*) FROM wp_posts WHERE post_type = 'shop_order';` |
| View pending 2FA codes | `SELECT * FROM wp_signup_codes WHERE verified = 0 AND expires_at > UTC_TIMESTAMP();` |
| Clean expired 2FA codes | `DELETE FROM wp_signup_codes WHERE expires_at < UTC_TIMESTAMP();` |
| Block a user (replace 3 with ID) | `UPDATE wp_usermeta SET meta_value = 'a:1:{s:7:"blocked";b:1;}' WHERE user_id = 3 AND meta_key = 'wp_capabilities';` |

---

## Readable Output

| Tip | How |
|---|---|
| Output is too wide | Add `\G` instead of `;` at the end — flips rows vertical |
| Count only | `SELECT COUNT(*) FROM wp_posts;` |
| Output to file | `SELECT * FROM wp_users INTO OUTFILE '/tmp/users.txt';` |
| Pipe to pager | `sudo mariadb hotel_metrodata -e "SELECT * FROM wp_users;" \| less` |

---

## Backup & Restore

| Task | Command |
|---|---|
| Backup database | `sudo mysqldump hotel_metrodata \| gzip > ~/hotel_backup.sql.gz` |
| Restore database | `gunzip -c ~/hotel_backup.sql.gz \| sudo mariadb hotel_metrodata` |
| Backup files + DB | `sudo tar -czf ~/full_backup.tar.gz /var/www/hotel-metrodata` |
| Restore everything | `sudo tar -xzf ~/full_backup.tar.gz -C / && sudo systemctl restart php8.5-fpm nginx mariadb` |
