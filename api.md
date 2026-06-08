# Hotel Metrodata — REST API Reference

**Base URL:** `http://localhost/wp-json/hotel/v1`

> All responses are JSON. Use `| jq .` to format output. Write endpoints require auth. Report bugs in GitHub Issues.

---

## Rooms

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `GET` | `/rooms` | List all rooms with prices and images | `curl -s --noproxy localhost /wp-json/hotel/v1/rooms \| jq '.rooms[] \| {name, price}'` |
| `GET` | `/rooms/(id)` | Get a single room by its ID | `curl -s --noproxy localhost /wp-json/hotel/v1/rooms/20 \| jq .` |
| `GET` | `/rooms/search?q=` | Search rooms by keyword (name, description) | `curl -s --noproxy localhost "/wp-json/hotel/v1/rooms/search?q=luxury" \| jq .` |
| `GET` | `/rooms/(id)/reviews` | Get reviews for a room | `curl -s --noproxy localhost /wp-json/hotel/v1/rooms/20/reviews \| jq .` |
| `POST` | `/rooms/(id)/reviews` | Submit a review (login required) | `curl -s --noproxy localhost -u "aus:KEY" -X POST -d "text=Amazing views!&rating=5" /wp-json/hotel/v1/rooms/20/reviews \| jq .` |

---

## Admin — Room CRUD (aus only)

All admin endpoints require authentication. Add `-u "aus:API_KEY"` to every command.

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `POST` | `/admin/rooms` | Create a new room listing | `curl -s --noproxy localhost -u "aus:KEY" -X POST -d "name=Ocean View&price=349&description=Beachfront room" /wp-json/hotel/v1/admin/rooms \| jq .` |
| `PUT` | `/admin/rooms/(id)` | Update room name, price, or description | `curl -s --noproxy localhost -u "aus:KEY" -X PUT -d "price=399" /wp-json/hotel/v1/admin/rooms/20 \| jq .` |
| `DELETE` | `/admin/rooms/(id)` | Delete a room permanently | `curl -s --noproxy localhost -u "aus:KEY" -X DELETE /wp-json/hotel/v1/admin/rooms/99 \| jq .` |

---

## Admin — Users (aus only)

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `GET` | `/admin/users` | List all registered users | `curl -s --noproxy localhost -u "aus:KEY" /wp-json/hotel/v1/admin/users \| jq '.[] \| {login, email, roles}'` |
| `POST` | `/admin/users/(id)/block` | Block or unblock a user | Block: `-d "block=1"` — Unblock: `-d "block=0"` |

---

## Admin — Orders (aus only)

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `GET` | `/admin/orders` | List all orders | `curl -s --noproxy localhost -u "aus:KEY" /wp-json/hotel/v1/admin/orders \| jq '.[] \| {id, status, total}'` |

---

## Admin — Stats (aus only)

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `GET` | `/admin/stats` | Site overview: total users, rooms, orders | `curl -s --noproxy localhost -u "aus:KEY" /wp-json/hotel/v1/admin/stats \| jq .` |

---

## Cart

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `GET` | `/cart` | View current cart contents | `curl -s --noproxy localhost /wp-json/hotel/v1/cart \| jq .` |
| `POST` | `/cart/add` | Add a room to the cart | `curl -s --noproxy localhost -X POST -d "product_id=20&quantity=1" /wp-json/hotel/v1/cart/add \| jq .` |
| `POST` | `/cart/remove` | Remove an item by its key | `curl -s --noproxy localhost -X POST -d "key=abc123" /wp-json/hotel/v1/cart/remove \| jq .` |
| `POST` | `/cart/update` | Change quantity of an item | `curl -s --noproxy localhost -X POST -d "key=abc123&quantity=3" /wp-json/hotel/v1/cart/update \| jq .` |
| `POST` | `/cart/clear` | Empty the entire cart | `curl -s --noproxy localhost -X POST /wp-json/hotel/v1/cart/clear \| jq .` |

---

## Auth

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `POST` | `/auth/login` | Log in with username and password | `curl -s --noproxy localhost -X POST -d "username=aus&password=admin123" /wp-json/hotel/v1/auth/login \| jq .` |
| `POST` | `/auth/register` | Create a new account | `curl -s --noproxy localhost -X POST -d "email=newuser@gmail.com&password=mypassword123" /wp-json/hotel/v1/auth/register \| jq .` |
| `POST` | `/auth/logout` | End current session | `curl -s --noproxy localhost -X POST /wp-json/hotel/v1/auth/logout \| jq .` |
| `GET` | `/auth/me` | Get currently logged-in user info | `curl -s --noproxy localhost -u "aus:KEY" /wp-json/hotel/v1/auth/me \| jq .` |

---

## Checkout

| Methods | Endpoint | Description | Example |
|---|---|---|---|
| `POST` | `/checkout` | Place an order from cart (login required) | `curl -s --noproxy localhost -u "aus:KEY" -X POST /wp-json/hotel/v1/checkout \| jq .` |

---

## Authentication

All endpoints marked "aus only" or "login required" need authentication. Pick one:

| Method | When to use | How |
|---|---|---|
| **Browser session** | You're logged into the site in your browser | API Explorer at `/hotel-api-explorer/` works automatically |
| **API Key** (apps, scripts, curl) | You're writing a script or external app | Generate at `/hotel-admin/` → API Keys tab. Add `-u "aus:KEY"` to curl |

---

## Rate Limits & Security

- 100 requests per minute per IP address
- 5 failed login attempts = 15-minute block
- All input sanitized — HTML/scripts stripped
- Suspicious requests logged to PHP error log
