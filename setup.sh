#!/bin/bash
# ============================================
# Hotel Metrodata - WordPress Setup Script
# ============================================
# Run this on any Ubuntu/Debian/WSL machine
# Usage: bash setup.sh
# ============================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🏨 Hotel Metrodata - Setup${NC}"
echo "================================="

# 1. Ask for config
read -p "Domain or IP (default: localhost): " SITE_URL
SITE_URL=${SITE_URL:-localhost}

read -p "Database name (default: hotel_metrodata): " DB_NAME
DB_NAME=${DB_NAME:-hotel_metrodata}

read -p "Database user (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Database password: " DB_PASS
echo ""

read -p "WordPress admin email: " ADMIN_EMAIL

# 2. Install dependencies if needed
echo -e "\n${GREEN}Checking dependencies...${NC}"
if ! command -v nginx &> /dev/null; then
    echo "Installing nginx..."
    sudo apt-get update && sudo apt-get install -y nginx
fi
if ! command -v php &> /dev/null; then
    echo "Installing PHP..."
    sudo apt-get install -y php8.5-fpm php8.5-mysql php8.5-curl php8.5-gd php8.5-mbstring php8.5-xml php8.5-zip
fi
if ! command -v mysql &> /dev/null; then
    echo "Installing MariaDB..."
    sudo apt-get install -y mariadb-server
    sudo systemctl start mariadb
fi

# 3. Create database
echo -e "\n${GREEN}Setting up database...${NC}"
sudo mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null || {
    echo "Trying without password..."
    sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
}
echo "Database $DB_NAME ready"

# 4. Download WordPress if not present
if [ ! -f "wp-admin/index.php" ]; then
    echo -e "\n${GREEN}Downloading WordPress...${NC}"
    curl -sL https://wordpress.org/latest.tar.gz | tar xz --strip-components=1
fi

# 5. Configure wp-config.php
echo -e "\n${GREEN}Configuring WordPress...${NC}"
cp wp-config-sample.php wp-config.php
sed -i "s/database_name_here/$DB_NAME/" wp-config.php
sed -i "s/username_here/$DB_USER/" wp-config.php
sed -i "s/password_here/$DB_PASS/" wp-config.php

# Add salts
SALTS=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/)
sed -i "/AUTH_KEY/d; /SECURE_AUTH_KEY/d; /LOGGED_IN_KEY/d; /NONCE_KEY/d; /AUTH_SALT/d; /SECURE_AUTH_SALT/d; /LOGGED_IN_SALT/d; /NONCE_SALT/d" wp-config.php
echo "$SALTS" >> wp-config.php

# 6. Import database
echo -e "\n${GREEN}Importing database...${NC}"
if [ -f "database/seed.sql.gz" ]; then
    gunzip -c database/seed.sql.gz | sudo mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null || {
        gunzip -c database/seed.sql.gz | sudo mysql -u root "$DB_NAME"
    }
    echo "Database imported"
fi

# 7. Update site URLs in database
sudo mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    UPDATE wp_options SET option_value='http://$SITE_URL' WHERE option_name='siteurl' OR option_name='home';
    UPDATE wp_posts SET post_content = REPLACE(post_content, 'http://localhost', 'http://$SITE_URL');
    UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'http://localhost', 'http://$SITE_URL');
    UPDATE wp_options SET option_value=REPLACE(option_value, 'http://localhost', 'http://$SITE_URL') WHERE option_name LIKE '%upload%';
" 2>/dev/null || {
    sudo mysql -u root "$DB_NAME" -e "
        UPDATE wp_options SET option_value='http://$SITE_URL' WHERE option_name='siteurl' OR option_name='home';
        UPDATE wp_posts SET post_content = REPLACE(post_content, 'http://localhost', 'http://$SITE_URL');
    "
}

# 8. Set permissions
echo -e "\n${GREEN}Setting permissions...${NC}"
sudo chown -R www-data:www-data wp-content/uploads
sudo chmod -R 755 wp-content

# 9. Configure nginx
NGINX_CONF="/etc/nginx/sites-available/hotel"
sudo tee "$NGINX_CONF" > /dev/null << NGINXEOF
server {
    listen 80;
    server_name $SITE_URL;
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

sudo ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/hotel 2>/dev/null || true
sudo rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
sudo systemctl restart nginx php8.5-fpm 2>/dev/null || true

# 10. Create admin account
sudo mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    UPDATE wp_users SET user_email='$ADMIN_EMAIL' WHERE ID=1;
" 2>/dev/null || {
    sudo mysql -u root "$DB_NAME" -e "UPDATE wp_users SET user_email='$ADMIN_EMAIL' WHERE ID=1;"
}

echo -e "\n${GREEN}✅ Setup complete!${NC}"
echo -e "Site: ${BLUE}http://$SITE_URL${NC}"
echo -e "Admin: ${BLUE}http://$SITE_URL/wp-admin${NC}"
echo -e "Login: username 'aus', password 'admin123'"
echo ""
echo "Change the admin password after first login!"
