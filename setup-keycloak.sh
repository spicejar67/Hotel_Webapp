#!/bin/bash
# ============================================
# Hotel Metrodata - Keycloak Setup (Optional)
# ============================================
# Run: bash setup-keycloak.sh
# Only run this AFTER the main setup.sh completes
# ============================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}  Hotel Metrodata - Keycloak SSO  ${NC}"
echo "==================================="

# 1. Install Java
echo -e "\n${GREEN}[1/4] Installing Java 17...${NC}"
sudo apt-get install -y openjdk-17-jre

# 2. Download Keycloak
if [ ! -d "$HOME/keycloak-26.2.0" ]; then
    echo -e "${GREEN}[2/4] Downloading Keycloak (150MB)...${NC}"
    curl -sL https://github.com/keycloak/keycloak/releases/download/26.2.0/keycloak-26.2.0.zip -o /tmp/keycloak.zip
    unzip -q /tmp/keycloak.zip -d $HOME
    rm /tmp/keycloak.zip
fi

# 3. Free port 8080 and start Keycloak
echo -e "${GREEN}[3/4] Starting Keycloak on port 8080...${NC}"
sudo rm -f /etc/nginx/sites-enabled/hotel-8080 2>/dev/null
sudo systemctl restart nginx 2>/dev/null

cd $HOME/keycloak-26.2.0
nohup bash bin/kc.sh start-dev --http-port=8080 > /tmp/keycloak.log 2>&1 &
echo "  Keycloak starting (wait 30 seconds)..."

# 4. Install WordPress bridge plugin
echo -e "${GREEN}[4/4] Installing WordPress bridge...${NC}"
sleep 30

if curl -s -o /dev/null -w "%{http_code}" --noproxy localhost http://localhost:8080 | grep -q 200; then
    echo "  Keycloak is running at http://localhost:8080"
    echo "  Create admin account on first visit"
else
    echo "  Keycloak may still be starting. Check: curl http://localhost:8080"
fi

echo ""
echo -e "${GREEN}  Keycloak setup complete!${NC}"
echo ""
echo "  Next steps:"
echo "  1. Open http://localhost:8080 and create admin account"
echo "  2. Create a realm called 'hotel'"
echo "  3. Create a client called 'hotel-webapp'"
echo "  4. Set redirect URI: http://localhost/*"
echo "  5. Generate a client secret under Credentials tab"
echo "  6. Update the plugin at /var/www/hotel-metrodata/wp-content/plugins/hotel-keycloak.php"
echo "     with your client secret"
echo ""
echo "  Stop Keycloak:  pkill -f keycloak"
echo "  Logs:            tail -f /tmp/keycloak.log"
