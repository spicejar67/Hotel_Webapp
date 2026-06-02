#!/bin/bash
# ============================================
# Hotel Metrodata - One-Click Setup (macOS)
# ============================================
# Run: bash setup-mac.sh
# Requires Homebrew: /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
# ============================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}  Hotel Metrodata - macOS Setup  ${NC}"
echo "==================================="

# Auto-install Homebrew if missing
if ! command -v brew &> /dev/null; then
    echo -e "${GREEN}Installing Homebrew...${NC}"
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)" </dev/null
    eval "$(/opt/homebrew/bin/brew shellenv 2>/dev/null || /usr/local/bin/brew shellenv)"
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SITE_URL="localhost"
DB_NAME="hotel_metrodata"
DB_USER="root"
DB_PASS=""

# 1. Install packages
echo -e "\n${GREEN}[1/6] Installing packages via Homebrew...${NC}"
brew install nginx php mariadb curl 2>/dev/null || true
brew services start mariadb 2>/dev/null || true
brew services start nginx 2>/dev/null || true
brew services start php 2>/dev/null || true

# 2. Create database
echo -e "${GREEN}[2/6] Creating database...${NC}"
mysql -u root -e "
    CREATE DATABASE IF NOT EXISTS $DB_NAME;
    FLUSH PRIVILEGES;
" 2>/dev/null

# 3. Download WordPress core
if [ ! -f "$SCRIPT_DIR/wp-admin/index.php" ]; then
    echo -e "${GREEN}[3/6] Downloading WordPress...${NC}"
    curl -sL https://wordpress.org/latest.tar.gz | tar xz --strip-components=1 -C "$SCRIPT_DIR"
fi

# 4. Configure WordPress
echo -e "${GREEN}[4/6] Configuring wp-config.php...${NC}"
if [ -f "$SCRIPT_DIR/wp-config-sample.php" ]; then
    cp -f "$SCRIPT_DIR/wp-config-sample.php" "$SCRIPT_DIR/wp-config.php"
    sed -i '' "s/database_name_here/$DB_NAME/" "$SCRIPT_DIR/wp-config.php"
    sed -i '' "s/username_here/$DB_USER/" "$SCRIPT_DIR/wp-config.php"
    sed -i '' "s/password_here/$DB_PASS/" "$SCRIPT_DIR/wp-config.php"
else
    cat > "$SCRIPT_DIR/wp-config.php" << WPCONFIG
<?php
define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASS', '$DB_PASS' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
\$table_prefix = 'wp_';
define( 'WP_DEBUG', false );
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );
require_once ABSPATH . 'wp-settings.php';
WPCONFIG
fi

if ! grep -q "AUTH_KEY" "$SCRIPT_DIR/wp-config.php" 2>/dev/null; then
    curl -s https://api.wordpress.org/secret-key/1.1/salt/ >> "$SCRIPT_DIR/wp-config.php"
fi

# 5. Import database
echo -e "${GREEN}[5/6] Importing database...${NC}"
if [ -f "$SCRIPT_DIR/database/seed.sql.gz" ]; then
    gunzip -c "$SCRIPT_DIR/database/seed.sql.gz" | mysql -u root "$DB_NAME" 2>/dev/null
    mysql -u root "$DB_NAME" -e "
        UPDATE wp_options SET option_value='http://$SITE_URL' WHERE option_name IN ('siteurl','home');
    " 2>/dev/null
fi

# 6. Configure nginx
echo -e "${GREEN}[6/6] Configuring nginx...${NC}"
NGINX_CONF=$(brew --prefix)/etc/nginx/servers/hotel.conf
mkdir -p "$(dirname "$NGINX_CONF")"

sudo tee "$NGINX_CONF" > /dev/null << NGINXEOF
server {
    listen 80;
    server_name localhost;
    root $SCRIPT_DIR;
    index index.php index.html;
    client_max_body_size 64M;
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    location ~ \.php\$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include $(brew --prefix)/etc/nginx/fastcgi_params;
    }
    location ~ /\.ht { deny all; }
}
NGINXEOF

# Set permissions
chmod -R 755 "$SCRIPT_DIR/wp-content" 2>/dev/null || true

# Restart nginx
brew services restart nginx 2>/dev/null || true

echo ""
echo -e "${GREEN}  Setup complete!${NC}"
echo ""
echo -e "  Site:    ${BLUE}http://localhost${NC}"
echo -e "  Admin:   ${BLUE}http://localhost/wp-admin${NC}"
echo -e "  Login:   ${BLUE}aus${NC} / ${BLUE}admin123${NC}"
echo ""
echo -e "  Start:   ${BLUE}brew services start nginx mariadb php${NC}"
echo -e "  Stop:    ${BLUE}brew services stop nginx mariadb php${NC}"
