# Hotel Metrodata — Keycloak Reference

**URL:** `http://localhost:8080` | **Realm:** `hotel` | **Admin password:** Set during first login (not stored here)

---

## Start & Stop

| Task | Command |
|---|---|
| Start Keycloak | `cd ~/keycloak-26.2.0 && nohup bash bin/kc.sh start-dev --http-port=8080 > /tmp/keycloak.log 2>&1 &` |
| Stop Keycloak | `pkill -f keycloak` |
| Check status | `curl -s -o /dev/null -w "%{http_code}" http://localhost:8080` (200 = running) |

---

## Connection to WordPress

| Setting | Value |
|---|---|
| Plugin | `hotel-keycloak.php` (in `wp-content/plugins/`) |
| Client ID | `hotel-webapp` |
| Realm | `hotel` |
| Redirect URI | `http://localhost/wp-login.php?keycloak=callback` |

---

## User Flow

| Step | What happens |
|---|---|
| 1 | User visits `http://localhost/wp-login.php` |
| 2 | WordPress redirects to Keycloak login page |
| 3 | User enters Keycloak credentials |
| 4 | Keycloak redirects back to WordPress |
| 5 | WordPress finds or creates matching WP user by email |
| 6 | User is logged in to WordPress |

---

## Admin Monitoring

| Where | What to check |
|---|---|
| **Sessions** | All active sessions. Click **Logout** to force-logout anyone |
| **Events** → Login events | Every login/logout with timestamp, username, IP |
| **Clients** → hotel-webapp → Sessions | Sessions specific to your hotel site |
| **Users** | All users. Click one → Sessions, Credentials, Role mapping |

---

## Admin Actions

| Task | How |
|---|---|
| Create a user | Users → Add user → fill form → Create → Credentials tab → set password |
| Reset user password | Users → click user → Credentials → set new password → turn Temporary OFF → Save |
| Force logout a user | Sessions → find session → click Logout |
| Block brute force | Realm settings → Security defenses → turn ON Brute force detection |
| Set session timeout | Realm settings → Sessions → SSO Session Idle (e.g., 30 minutes) |
| Create a new client | Clients → Create client → Client ID → Next → Valid redirect URIs: `http://localhost/*` → Save |
| Get client secret | Clients → click client → Credentials tab → Client Secret |

---

## Files & Locations

| What | Path |
|---|---|
| Keycloak install | `~/keycloak-26.2.0/` |
| Log file | `/tmp/keycloak.log` |
| WordPress bridge plugin | `/var/www/hotel-metrodata/wp-content/plugins/hotel-keycloak.php` |
