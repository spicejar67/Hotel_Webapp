#!/bin/bash
# ============================================
# Hotel Metrodata - One-Click Setup
# ============================================
# Run: bash setup.sh (must run inside WSL/Linux)
# Everything is automatic
# ============================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}  Hotel Metrodata  ${NC}"
echo "=============================="

# Detect if running from Windows mount - move to Linux native path
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [[ "$SCRIPT_DIR" == /mnt/* ]]; then
    echo "  Detected Windows path, moving to /var/www/hotel-metrodata..."
    sudo mkdir -p /var/www/hotel-metrodata
    sudo cp -r "$SCRIPT_DIR"/* /var/www/hotel-metrodata/ 2>/dev/null
    sudo chown -R $USER:$USER /var/www/hotel-metrodata
    cd /var/www/hotel-metrodata
    exec bash setup.sh
    exit
fi

SITE_URL="localhost"
DB_NAME="hotel_metrodata"
DB_USER="hotel"
DB_PASS="hotel123"

# 1. Install dependencies
echo -e "\n${GREEN}[1/6] Installing system packages...${NC}"
sudo apt-get update -qq 2>/dev/null
sudo apt-get install -y -qq nginx php8.5-fpm php8.5-mysql php8.5-curl php8.5-gd php8.5-mbstring php8.5-xml php8.5-zip mariadb-server curl 2>/dev/null || {
    # Fallback for non-Ubuntu or different PHP version
    sudo apt-get install -y -qq nginx php-fpm php-mysql php-curl php-gd php-mbstring php-xml php-zip mariadb-server curl
}

# 2. Start services
echo -e "${GREEN}[2/6] Starting nginx, MariaDB, PHP...${NC}"
sudo systemctl start mariadb 2>/dev/null || sudo systemctl start mysql 2>/dev/null || true
sudo systemctl start nginx 2>/dev/null || true
sudo systemctl start php8.5-fpm 2>/dev/null || sudo systemctl start php-fpm 2>/dev/null || true

# 3. Create database
echo -e "${GREEN}[3/6] Creating database...${NC}"
sudo mysql -e "
    CREATE DATABASE IF NOT EXISTS $DB_NAME;
    CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
    GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'localhost';
    FLUSH PRIVILEGES;
" 2>/dev/null || {
    # If root requires sudo password, try without
    sudo mysql -u root -e "
        CREATE DATABASE IF NOT EXISTS $DB_NAME;
        CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
        GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null
}

# 4. Download WordPress core
if [ ! -f "wp-admin/index.php" ]; then
    echo -e "${GREEN}[4/6] Downloading WordPress...${NC}"
    curl -sL https://wordpress.org/latest.tar.gz | tar xz --strip-components=1
fi

# 5. Configure WordPress
echo -e "${GREEN}[5/6] Configuring wp-config.php...${NC}"
if [ -f "wp-config-sample.php" ]; then
    cp -f wp-config-sample.php wp-config.php
    sed -i "s/database_name_here/$DB_NAME/" wp-config.php
    sed -i "s/username_here/$DB_USER/" wp-config.php
    sed -i "s/password_here/$DB_PASS/" wp-config.php
else
    cat > wp-config.php << WPCONFIG
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

# Add security salts if missing
if ! grep -q "AUTH_KEY" wp-config.php 2>/dev/null; then
    curl -s https://api.wordpress.org/secret-key/1.1/salt/ >> wp-config.php
fi

# 6. Import database
echo -e "${GREEN}[6/6] Importing database...${NC}"
if [ -f "database/seed.sql.gz" ]; then
    gunzip -c database/seed.sql.gz | sudo mysql "$DB_NAME" 2>/dev/null
    sudo mysql "$DB_NAME" -e "
        UPDATE wp_options SET option_value='http://$SITE_URL' WHERE option_name IN ('siteurl','home');
        UPDATE wp_posts SET post_content = REPLACE(post_content, 'localhost:8080', '$SITE_URL');
        UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'localhost:8080', '$SITE_URL');
    " 2>/dev/null
fi

# Set permissions
sudo chown -R www-data:www-data wp-content 2>/dev/null || true
sudo chmod -R 755 wp-content 2>/dev/null || true

# Download Storefront theme if missing
if [ ! -f "wp-content/themes/storefront/style.css" ]; then
    echo -e "${GREEN}Downloading Storefront theme...${NC}"
    curl -sL https://downloads.wordpress.org/theme/storefront.latest-stable.zip -o /tmp/storefront.zip
    unzip -oq /tmp/storefront.zip -d wp-content/themes/
    rm /tmp/storefront.zip
fi
# Configure nginx (overwrite any existing config)
sudo tee /etc/nginx/sites-available/hotel > /dev/null << NGINXEOF
server {
    listen 80;
    server_name localhost;
    root $(pwd);
    index index.php index.html;
    client_max_body_size 64M;
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.ht { deny all; }
}
NGINXEOF

sudo ln -sf /etc/nginx/sites-available/hotel /etc/nginx/sites-enabled/hotel
sudo rm -f /etc/nginx/sites-enabled/default

# Restart everything
sudo systemctl restart nginx 2>/dev/null || true
sudo systemctl restart php8.5-fpm 2>/dev/null || sudo systemctl restart php-fpm 2>/dev/null || true

echo ""
echo -e "${GREEN}  Setup complete!${NC}"
echo ""
echo -e "  Site:    ${BLUE}http://localhost${NC}"
echo -e "  Admin:   ${BLUE}http://localhost/wp-admin${NC}"
echo -e "  Login:   ${BLUE}aus${NC} / ${BLUE}admin123${NC}"
echo ""
echo -e "  Run ${BLUE}bash start.sh${NC} to start after reboot"
echo -e "  Run ${BLUE}bash stop.sh${NC}  to stop all services"
