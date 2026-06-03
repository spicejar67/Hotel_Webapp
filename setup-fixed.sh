#!/bin/bash
# ============================================
# Hotel Metrodata - One-Click Setup (FIXED v2)
# ============================================
# Fixed for Linux Mint 22.3 with PHP 8.3
# Original issues: hardcoded php8.5, wrong socket path, sudo without TTY,
#   wp-content owned by www-data so plugin unzips need sudo
# ============================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}  Hotel Metrodata  ${NC}"
echo "=============================="

# Detect PHP version
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null)
PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
PHP_SERVICE="php${PHP_VER}-fpm"

echo -e "  Detected PHP ${GREEN}${PHP_VER}${NC}"
echo -e "  Socket: ${PHP_SOCK}"

# Verify socket exists
if [ ! -S "$PHP_SOCK" ]; then
    echo -e "  ${RED}Socket not found: $PHP_SOCK${NC}"
    echo "  Falling back to /run/php/php-fpm.sock"
    PHP_SOCK="/run/php/php-fpm.sock"
fi

SITE_URL="localhost"
DB_NAME="hotel_metrodata"
DB_USER="hotel"
DB_PASS="hotel123"

# =====================================
# STEP 1: Install packages (skip if installed)
# =====================================
echo -e "\n${GREEN}[1/7] Checking system packages...${NC}"
MISSING=""
for pkg in nginx php${PHP_VER}-fpm php${PHP_VER}-mysql php${PHP_VER}-curl php${PHP_VER}-gd php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-zip mariadb-server curl unzip; do
    if ! dpkg -s "$pkg" >/dev/null 2>&1; then
        MISSING="$MISSING $pkg"
    fi
done
if [ -n "$MISSING" ]; then
    echo "  Installing missing:$MISSING"
    sudo apt-get update -qq
    sudo apt-get install -y -qq $MISSING
else
    echo "  All packages already installed."
fi

# =====================================
# STEP 2: Start services
# =====================================
echo -e "${GREEN}[2/7] Starting services...${NC}"
sudo systemctl start mariadb 2>/dev/null || sudo systemctl start mysql 2>/dev/null || true
sudo systemctl start nginx 2>/dev/null || true
sudo systemctl start ${PHP_SERVICE} 2>/dev/null || sudo systemctl start php-fpm 2>/dev/null || true
echo "  nginx, MariaDB, PHP-FPM are running."

# =====================================
# STEP 3: Create database
# =====================================
echo -e "${GREEN}[3/7] Creating database...${NC}"
sudo mysql -e "
    CREATE DATABASE IF NOT EXISTS $DB_NAME;
    CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
    GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'localhost';
    FLUSH PRIVILEGES;
" 2>&1 || {
    echo -e "  ${RED}Database creation failed. Trying with -u root...${NC}"
    sudo mysql -u root -e "
        CREATE DATABASE IF NOT EXISTS $DB_NAME;
        CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
        GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'localhost';
        FLUSH PRIVILEGES;
    " 2>&1
}
echo "  Database '$DB_NAME' ready."

# =====================================
# STEP 4: Download WordPress core
# =====================================
if [ ! -f "wp-admin/index.php" ]; then
    echo -e "${GREEN}[4/7] Downloading WordPress...${NC}"
    curl -sL https://wordpress.org/latest.tar.gz | tar xz --strip-components=1
    echo "  WordPress core downloaded."
else
    echo -e "${GREEN}[4/7] WordPress already present.${NC}"
fi

# =====================================
# STEP 5: Configure wp-config.php
# =====================================
echo -e "${GREEN}[5/7] Configuring wp-config.php...${NC}"
if [ -f "wp-config-sample.php" ]; then
    cp -f wp-config-sample.php wp-config.php
    sed -i "s/database_name_here/$DB_NAME/" wp-config.php
    sed -i "s/username_here/$DB_USER/" wp-config.php
    sed -i "s/password_here/$DB_PASS/" wp-config.php
fi

# Ensure bare minimum config exists
if ! grep -q "DB_NAME" wp-config.php 2>/dev/null; then
    cat > wp-config.php << 'WPCONFIG'
<?php
define( 'DB_NAME', 'hotel_metrodata' );
define( 'DB_USER', 'hotel' );
define( 'DB_PASS', 'hotel123' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
$table_prefix = 'wp_';
define( 'WP_DEBUG', false );
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );
require_once ABSPATH . 'wp-settings.php';
WPCONFIG
fi

# Add security salts
if ! grep -q "AUTH_KEY" wp-config.php 2>/dev/null; then
    curl -s https://api.wordpress.org/secret-key/1.1/salt/ >> wp-config.php
    echo "  Security salts added."
fi
echo "  wp-config.php configured."

# =====================================
# STEP 6: Import database + download plugins/theme
# =====================================
echo -e "${GREEN}[6/7] Importing database and plugins...${NC}"

# Import seed database
if [ -f "database/seed.sql.gz" ]; then
    echo "  Importing seed database..."
    gunzip -c database/seed.sql.gz | sudo mysql "$DB_NAME" 2>&1
    sudo mysql "$DB_NAME" -e "
        UPDATE wp_options SET option_value='http://$SITE_URL' WHERE option_name IN ('siteurl','home');
        UPDATE wp_posts SET post_content = REPLACE(post_content, 'localhost:8080', '$SITE_URL');
        UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'localhost:8080', '$SITE_URL');
    " 2>&1
    echo "  Database imported and URLs updated."
else
    echo "  No seed.sql.gz found — skipping DB import."
fi

# ---------- PLUGINS (needs sudo — wp-content is www-data owned) ----------
PLUGIN_DIR="wp-content/plugins"
THEME_DIR="wp-content/themes"

for plugin in woocommerce wp-mail-smtp; do
    if [ ! -d "$PLUGIN_DIR/$plugin" ]; then
        echo "  Downloading $plugin..."
        curl -sL "https://downloads.wordpress.org/plugin/$plugin.latest-stable.zip" -o "/tmp/$plugin.zip"
        sudo unzip -oq "/tmp/$plugin.zip" -d "$PLUGIN_DIR/"
        sudo chown -R www-data:www-data "$PLUGIN_DIR/$plugin"
        rm "/tmp/$plugin.zip"
        echo "    $plugin installed."
    else
        echo "  $plugin already present."
    fi
done

# Storefront theme
if [ ! -f "$THEME_DIR/storefront/style.css" ]; then
    echo "  Downloading Storefront theme..."
    curl -sL https://downloads.wordpress.org/theme/storefront.latest-stable.zip -o /tmp/storefront.zip
    sudo unzip -oq /tmp/storefront.zip -d "$THEME_DIR/"
    sudo chown -R www-data:www-data "$THEME_DIR/storefront"
    rm /tmp/storefront.zip
    echo "    Storefront theme installed."
else
    echo "  Storefront theme already present."
fi

# Final permission sweep
sudo chown -R www-data:www-data wp-content 2>/dev/null || true
sudo chmod -R 755 wp-content 2>/dev/null || true

# =====================================
# STEP 7: Configure nginx
# =====================================
echo -e "${GREEN}[7/7] Configuring nginx...${NC}"
PROJECT_DIR="$(pwd)"

sudo tee /etc/nginx/sites-available/hotel > /dev/null << NGINXEOF
server {
    listen 80;
    server_name localhost;
    root ${PROJECT_DIR};
    index index.php index.html;
    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
}
NGINXEOF

sudo ln -sf /etc/nginx/sites-available/hotel /etc/nginx/sites-enabled/hotel

# Test and restart nginx
sudo nginx -t && sudo systemctl restart nginx
echo "  nginx configured and reloaded."

# =====================================
# DONE
# =====================================
echo ""
echo -e "${GREEN}  Setup complete!${NC}"
echo ""
echo -e "  Site:    ${BLUE}http://localhost${NC}"
echo -e "  Admin:   ${BLUE}http://localhost/wp-admin${NC}"
echo -e "  Login:   ${BLUE}aus${NC} / ${BLUE}admin123${NC}"
echo ""
echo -e "  Start:  ${BLUE}bash start.sh${NC}"
echo -e "  Stop:   ${BLUE}bash stop.sh${NC}"
