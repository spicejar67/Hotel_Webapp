# Hotel Metrodata REST API

## Before you start

You need a terminal. On Linux/Mac: open **Terminal**. On Windows: open **WSL** (search "wsl" in Start). Every code block below can be copied and pasted directly into your terminal. Press Enter to run.

## Try it in the browser first (no terminal needed)

1. Open your browser and go to `http://localhost/wp-json/`
2. Install the **JSON Formatter** browser extension (Chrome Web Store or Firefox Add-ons)
3. Now click any link — the data appears formatted and readable

## Try it from the terminal

### 1. Check it works — list all rooms

Copy and paste this into your terminal, then press Enter:

```bash
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms
```

You should see a big block of text. That's JSON — it's the list of all rooms.

### 2. Make it readable — install jq

`jq` is a tool that formats JSON so humans can read it. Copy and paste this once:

```bash
sudo apt-get install -y jq
```

Now try the rooms command again, but pipe it through jq:

```bash
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms | jq .
```

Much better, right? From now on, add `| jq .` to the end of any command to make it readable.

## All endpoints (copy-paste ready)

### Rooms

**List all rooms:**
```bash
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms | jq .
```

**View one room (replace 20 with any room ID):**
```bash
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms/20 | jq .
```

**Search rooms by keyword:**
```bash
curl --noproxy localhost "http://localhost/wp-json/hotel/v1/rooms/search?q=luxury" | jq .
```

**See reviews for a room:**
```bash
curl --noproxy localhost http://localhost/wp-json/hotel/v1/rooms/20/reviews | jq .
```

**Submit a review (you must be logged in first — see Auth section):**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X POST -d "text=Amazing room!&rating=5" http://localhost/wp-json/hotel/v1/rooms/20/reviews | jq .
```

### Cart

**View cart:**
```bash
curl --noproxy localhost http://localhost/wp-json/hotel/v1/cart | jq .
```

**Add item to cart (replace 20 with room ID):**
```bash
curl --noproxy localhost -X POST -d "product_id=20&quantity=1" http://localhost/wp-json/hotel/v1/cart/add | jq .
```

**Remove item from cart (replace abc123 with the item key from View cart):**
```bash
curl --noproxy localhost -X POST -d "key=abc123" http://localhost/wp-json/hotel/v1/cart/remove | jq .
```

**Change quantity of an item:**
```bash
curl --noproxy localhost -X POST -d "key=abc123&quantity=3" http://localhost/wp-json/hotel/v1/cart/update | jq .
```

**Empty cart:**
```bash
curl --noproxy localhost -X POST http://localhost/wp-json/hotel/v1/cart/clear | jq .
```

### Auth (login, register, logout)

**Login:**
```bash
curl --noproxy localhost -X POST -d "username=aus&password=admin123" http://localhost/wp-json/hotel/v1/auth/login | jq .
```

**Register a new account:**
```bash
curl --noproxy localhost -X POST -d "email=newuser@gmail.com&password=mypassword123" http://localhost/wp-json/hotel/v1/auth/register | jq .
```

**See your current user info (requires API key or login):**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" http://localhost/wp-json/hotel/v1/auth/me | jq .
```

**Logout:**
```bash
curl --noproxy localhost -X POST http://localhost/wp-json/hotel/v1/auth/logout | jq .
```

### Checkout (place an order)

First add items to cart, then:

```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X POST http://localhost/wp-json/hotel/v1/checkout | jq .
```

### Admin (aus only — requires API key)

**How to get an API key:**
1. Go to `http://localhost/hotel-admin/` → **API Keys** tab
2. Type a name like "My Script" → click **Generate**
3. Copy the long code that appears — that's your API key
4. Use it in commands below by replacing `YOUR_API_KEY` with it

**List all users:**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" http://localhost/wp-json/hotel/v1/admin/users | jq .
```

**List all orders:**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" http://localhost/wp-json/hotel/v1/admin/orders | jq .
```

**Site stats:**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" http://localhost/wp-json/hotel/v1/admin/stats | jq .
```

**Block a user (replace 3 with user ID):**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X POST -d "block=1" http://localhost/wp-json/hotel/v1/admin/users/3/block | jq .
```

**Unblock a user:**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X POST -d "block=0" http://localhost/wp-json/hotel/v1/admin/users/3/block | jq .
```

**Create a room:**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X POST -d "name=VIP Suite&price=599&short_description=Luxury room with view" http://localhost/wp-json/hotel/v1/admin/rooms | jq .
```

**Update room price (replace 20 with room ID):**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X PUT -d "price=699" http://localhost/wp-json/hotel/v1/admin/rooms/20 | jq .
```

**Delete a room (replace 99 with room ID):**
```bash
curl --noproxy localhost -u "aus:YOUR_API_KEY" -X DELETE http://localhost/wp-json/hotel/v1/admin/rooms/99 | jq .
```

## No terminal? Use the API Explorer

1. Go to `http://localhost/hotel-api-explorer/` (must be logged in as aus)
2. Pick an endpoint from the dropdown
3. Type a parameter if needed (like a room ID or search term)
4. Click **Send Request**
5. Results appear formatted — no terminal, no commands
