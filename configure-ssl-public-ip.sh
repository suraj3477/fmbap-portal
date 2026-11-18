#!/usr/bin/env bash
set -e

echo "========================================================="
echo "   CONFIGURING FMBAP FOR PUBLIC IP: 103.162.247.169 (PORT 443)"
echo "========================================================="

PUBLIC_IP="103.162.247.169"
PRIVATE_IP="10.103.79.44"

# 1. Create SSL Directory and Self-Signed Certificate
echo "[1/5] Generating SSL Certificate for $PUBLIC_IP..."
sudo mkdir -p /etc/nginx/ssl
if [ ! -f "/etc/nginx/ssl/fmbap.crt" ]; then
    sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
      -keyout /etc/nginx/ssl/fmbap.key \
      -out /etc/nginx/ssl/fmbap.crt \
      -subj "/C=IN/ST=Assam/L=Guwahati/O=Brahmaputra Board/OU=IT/CN=$PUBLIC_IP"
fi

# 2. Update Nginx to listen on Port 443 (SSL) and Port 80
echo "[2/5] Updating Nginx Configuration for Port 443..."
sudo tee /etc/nginx/sites-available/fmbap > /dev/null << EOF
server {
    listen 80;
    listen 443 ssl default_server;
    server_name $PUBLIC_IP $PRIVATE_IP localhost;

    ssl_certificate /etc/nginx/ssl/fmbap.crt;
    ssl_certificate_key /etc/nginx/ssl/fmbap.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/fmbap-portal/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 50M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# 3. Allow Port 443 in Local Firewall
echo "[3/5] Opening Port 443 in UFW..."
sudo ufw allow 443/tcp || true

# 4. Update .env APP_URL to https://103.162.247.169
echo "[4/5] Updating Laravel Environment..."
cd /var/www/fmbap-portal
sudo sed -i "s|^APP_URL=.*|APP_URL=https://$PUBLIC_IP|" .env
sudo php artisan optimize:clear

# 5. Test and Reload Nginx
echo "[5/5] Reloading Nginx Web Server..."
sudo nginx -t
sudo systemctl reload nginx

echo "========================================================="
echo "   SUCCESS! FMBAP PORTAL IS NOW LIVE OVER PUBLIC HTTPS!  "
echo "   Public URL: https://$PUBLIC_IP                         "
echo "========================================================="
