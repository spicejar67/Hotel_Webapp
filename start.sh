#!/bin/bash
# Start Hotel Metrodata services
PHP_FPM=$(ls /usr/lib/systemd/system/php*-fpm.service 2>/dev/null | head -1 | xargs basename)
sudo systemctl start mariadb nginx ${PHP_FPM:-php-fpm} 2>/dev/null || sudo systemctl start mysql nginx php-fpm
echo "Hotel Metrodata running at http://localhost"
