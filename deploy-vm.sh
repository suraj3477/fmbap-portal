#!/usr/bin/env bash
set -e

echo "========================================================="
echo "   FMBAP PORTAL - AUTOMATED UBUNTU 24.04 VM DEPLOYMENT   "
echo "========================================================="

# 1. Update system packages
echo "[1/8] Updating apt packages..."
sudo apt-get update -y
sudo apt-get install -y git curl unzip zip nginx ufw

# 2. Install PHP 8.3 & required extensions
echo "[2/8] Installing PHP 8.3 and extensions..."
sudo apt-get install -y php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-mysql \
                        php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
                        php8.3-gd php8.3-bcmath php8.3-intl

# 3. Install Composer
echo "[3/8] Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

# 4. Install Node.js 20 LTS & NPM
echo "[4/8] Installing Node.js 20 LTS..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

# 5. Setup Project Directory in /var/www/fmbap-portal
echo "[5/8] Setting up repository in /var/www/fmbap-portal..."
sudo mkdir -p /var/www
if [ ! -d "/var/www/fmbap-portal/.git" ]; then
    sudo git clone https://github.com/suraj3477/fmbap-portal.git /var/www/fmbap-portal
fi

cd /var/www/fmbap-portal
sudo git fetch origin
sudo git reset --hard origin/main

# Configure .env
if [ ! -f ".env" ]; then
    sudo cp .env.example .env
fi
sudo sed -i 's|^APP_ENV=.*|APP_ENV=production|' .env
sudo sed -i 's|^APP_DEBUG=.*|APP_DEBUG=false|' .env
sudo sed -i 's|^APP_URL=.*|APP_URL=http://10.103.79.44|' .env
sudo sed -i 's|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|' .env

# Install backend dependencies
echo "[6/8] Installing PHP and JS dependencies..."
sudo composer install --no-dev --optimize-autoloader
sudo php artisan key:generate --force

# Setup SQLite Database
sudo touch database/database.sqlite
sudo php artisan migrate --seed --force
sudo php artisan storage:link || true

# Build frontend production assets
sudo npm ci
sudo npm run build
sudo rm -f public/hot

# File Permissions
sudo chown -R www-data:www-data /var/www/fmbap-portal
sudo chmod -R 775 storage bootstrap/cache database
sudo chmod 664 database/database.sqlite

# 6. Configure Nginx
echo "[7/8] Configuring Nginx..."
sudo tee /etc/nginx/sites-available/fmbap > /dev/null << 'EOF'
server {
    listen 80;
    server_name 10.103.79.44;

    root /var/www/fmbap-portal/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/fmbap /etc/nginx/sites-enabled/fmbap
sudo rm -f /etc/nginx/sites-enabled/default

# Restart Services
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm

# Firewall
echo "[8/8] Configuring Firewall..."
sudo ufw allow 22/tcp || true
sudo ufw allow 80/tcp || true

echo "========================================================="
echo "   DEPLOYMENT SUCCESSFUL!                                "
echo "   Access Portal: http://10.103.79.44                    "
echo "========================================================="
