# Hotel Metrodata REST API

Base URL: `http://localhost/wp-json/hotel/v1`

## Endpoints

### Rooms

**GET /rooms** — List all rooms
```
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms | jq .
```
Returns: `{total, rooms: [{id, name, price, image, rating, url}]}`

**GET /rooms/{id}** — Single room
```
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms/20 | jq .
```

**GET /rooms/search?q=** — Search rooms
```
curl --noproxy localhost "http://localhost/wp-json/hotel/v1/rooms/search?q=luxury" | jq .
```

### Cart

**GET /cart** — View cart
```
curl --noproxy localhost http://localhost/wp-json/hotel/v1/cart | jq .
```

**POST /cart/add** — Add to cart
```
curl --noproxy localhost -X POST -d "product_id=20&quantity=1" http://localhost/wp-json/hotel/v1/cart/add | jq .
```

### Auth

**GET /auth/me** — Current user (login required)
```
curl --noproxy localhost -u "aus:PASSWORD" http://localhost/wp-json/hotel/v1/auth/me | jq .
```

### Admin (aus only)

**GET /admin/users** — All users
**GET /admin/stats** — Site stats

## Authentication

- **Browser:** Login normally, API works via session cookie
- **CLI/Scripts:** Use Basic Auth: `-u "aus:API_KEY"`
- **Generate API keys:** `/hotel-admin/` → API Keys tab

## Filtering with jq

```bash
# List room names and prices only
curl -s --noproxy localhost http://localhost/wp-json/hotel/v1/rooms | jq '.rooms[] | {name, price}'

# Count rooms
curl -s --noproxy localhost http://localhost/wp-json/hotel/v1/rooms | jq '.total'

# Filter rooms above $300
curl -s --noproxy localhost http://localhost/wp-json/hotel/v1/rooms | jq '.rooms[] | select(.price > 300)'
```
