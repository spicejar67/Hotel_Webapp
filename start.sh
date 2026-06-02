#!/bin/bash
# Start Hotel Metrodata services
sudo systemctl start mariadb nginx php8.5-fpm 2>/dev/null || sudo systemctl start mysql nginx php-fpm
echo "Hotel Metrodata running at http://localhost"
