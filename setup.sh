#!/bin/bash
# ============================================
# Hotel Metrodata - One-Click Setup
# ============================================
# Run: bash setup.sh
# Everything is automatic - no prompts, no config
# ============================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}  Hotel Metrodata  ${NC}"
echo "=============================="

# Configure
SITE_URL="localhost"
DB_NAME="hotel_metrodata"
DB_USER="hotel"
DB_PASS="hotel123"

# 1. Install dependencies
echo -e "\n${GREEN}[1/6] Installing dependencies...${NC}"
sudo apt-get update -qq
sudo apt-get install -y -qq nginx php8.5-fpm php8.5-mysql php8.5-curl php8.5-gd php8.5-mbstring php8.5-xml php8.5-zip mariadb-server curl 2>/dev/null || {
    sudo apt-get install -y -qq nginx php-fpm php-mysql php-curl php-gd php-mbstring php-xml php-zip mariadb-server curl
}

# 2. Start services
echo -e "${GREEN}[2/6] Starting services...${NC}"
sudo systemctl start mariadb nginx php8.5-fpm 2>/dev/null || {
    sudo systemctl start mysql nginx php-fpm
}

# 3. Create database
echo -e "${GREEN}[3/6] Creating database...${NC}"
sudo mysql -e "
    CREATE DATABASE IF NOT EXISTS $DB_NAME;
    CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
    GRANT ALL ON $DB_NAME.* TO '$DB_USER'@'localhost';
    FLUSH PRIVILEGES;
" 2>/dev/null

# 4. Download WordPress core
if [ ! -f "wp-admin/index.php" ]; then
    echo -e "${GREEN}[4/6] Downloading WordPress...${NC}"
    curl -sL https://wordpress.org/latest.tar.gz | tar xz --strip-components=1
fi

# 5. Configure WordPress
echo -e "${GREEN}[5/6] Configuring WordPress...${NC}"
cp -f wp-config-sample.php wp-config.php 2>/dev/null || {
    # Create wp-config if sample doesn't exist
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
}

sed -i "s/database_name_here/$DB_NAME/" wp-config.php 2>/dev/null
sed -i "s/username_here/$DB_USER/" wp-config.php 2>/dev/null
sed -i "s/password_here/$DB_PASS/" wp-config.php 2>/dev/null

# Add salts
if ! grep -q "AUTH_KEY" wp-config.php 2>/dev/null; then
    curl -s https://api.wordpress.org/secret-key/1.1/salt/ >> wp-config.php
fi

# 6. Import database
echo -e "${GREEN}[6/6] Importing database...${NC}"
if [ -f "database/seed.sql.gz" ]; then
    gunzip -c database/seed.sql.gz | sudo mysql "$DB_NAME" 2>/dev/null
    # Update URLs
    sudo mysql "$DB_NAME" -e "
        UPDATE wp_options SET option_value='http://$SITE_URL' WHERE option_name IN ('siteurl','home');
        UPDATE wp_posts SET post_content = REPLACE(post_content, 'http://localhost', 'http://$SITE_URL');
    " 2>/dev/null
    echo "  Database imported successfully"
fi

# 7. Set permissions
sudo chown -R www-data:www-data wp-content 2>/dev/null
sudo chmod -R 755 wp-content 2>/dev/null

# 8. Configure nginx
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
sudo systemctl restart nginx php8.5-fpm 2>/dev/null || {
    sudo systemctl restart nginx php-fpm
}

echo ""
echo -e "${GREEN}  Setup complete!${NC}"
echo "  Site:    ${BLUE}http://localhost${NC}"
echo "  Admin:   ${BLUE}http://localhost/wp-admin${NC}"
echo "  Login:   username: ${BLUE}aus${NC}  password: ${BLUE}admin123${NC}"
echo ""
echo "  Next time: bash start.sh"
