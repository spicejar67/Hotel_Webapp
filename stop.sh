#!/bin/bash
# Stop Hotel Metrodata services
sudo systemctl stop mariadb nginx php8.5-fpm 2>/dev/null || sudo systemctl stop mysql nginx php-fpm
echo "Hotel Metrodata stopped"
