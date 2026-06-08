# Hotel Metrodata REST API

Base URL: `http://localhost/wp-json/hotel/v1`

## Rooms

| Method | Endpoint | Description |
|---|---|---|
| GET | `/rooms` | List all rooms |
| GET | `/rooms/20` | Single room by ID |
| GET | `/rooms/search?q=luxury` | Search rooms |
| GET | `/rooms/20/reviews` | Room reviews |
| POST | `/rooms/20/reviews` | Submit review (login required) |

## Cart

| Method | Endpoint | Description |
|---|---|---|
| GET | `/cart` | View cart |
| POST | `/cart/add` | Add to cart `product_id=20&quantity=1` |
| POST | `/cart/remove` | Remove item `key=abc123` |
| POST | `/cart/update` | Change quantity `key=abc123&quantity=3` |
| POST | `/cart/clear` | Empty cart |

## Auth

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/login` | Login `username=aus&password=...` |
| POST | `/auth/register` | Register `email=...&password=...` |
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Current user info |

## Checkout

| Method | Endpoint | Description |
|---|---|---|
| POST | `/checkout` | Place order (login required) |

## Admin (aus only)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/users` | All users |
| GET | `/admin/orders` | All orders |
| GET | `/admin/stats` | Site stats |
| POST | `/admin/users/3/block` | Block/unblock `block=1` or `block=0` |
| POST | `/admin/rooms` | Create room `name=...&price=...&description=...` |
| PUT | `/admin/rooms/20` | Update room `name=...&price=...` |
| DELETE | `/admin/rooms/20` | Delete room |

## Examples

```bash
# Create a room
curl --noproxy localhost -u "aus:KEY" -X POST -d "name=VIP Suite&price=599&short_description=Luxury suite" http://localhost/wp-json/hotel/v1/admin/rooms | jq .

# Update room price
curl --noproxy localhost -u "aus:KEY" -X PUT -d "price=699" http://localhost/wp-json/hotel/v1/admin/rooms/20 | jq .

# Delete a room
curl --noproxy localhost -u "aus:KEY" -X DELETE http://localhost/wp-json/hotel/v1/admin/rooms/99 | jq .

# Register new user
curl --noproxy localhost -X POST -d "email=guest@example.com&password=secure123" http://localhost/wp-json/hotel/v1/auth/register | jq .

# Login
curl --noproxy localhost -X POST -d "username=aus&password=admin123" http://localhost/wp-json/hotel/v1/auth/login | jq .
```
