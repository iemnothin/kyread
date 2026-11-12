#!/usr/bin/env bash
# Dijalankan DI SERVER oleh deploy.sh setelah rilis baru diekstrak ke $BASE/current.
# Idempotent: aman dijalankan ulang di setiap rilis.
set -euo pipefail

BASE=/var/www/kyread-staging
APP=$BASE/current
SHARED=$BASE/shared
ETC=/etc/kyread-staging
DOMAIN=kyread-staging.skyvora.id
DB=kyread_staging

mkdir -p "$ETC" "$SHARED/storage"/{app/private,app/public,framework/{cache/data,sessions,views,testing},logs}
chmod 700 "$ETC"

# --- Database (sekali) -------------------------------------------------------
if [ ! -f "$ETC/db.pass" ]; then
  openssl rand -hex 24 > "$ETC/db.pass" && chmod 600 "$ETC/db.pass"
fi
DB_PASS=$(cat "$ETC/db.pass")
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB\`.* TO '$DB'@'localhost';
SQL

# --- .env (sekali; rilis berikutnya memakai ulang) -------------------------
FIRST_RUN=0
if [ ! -f "$SHARED/.env" ]; then
  FIRST_RUN=1
  cp "$APP/.env.example" "$SHARED/.env"
  sed -i \
    -e 's|^APP_ENV=.*|APP_ENV=staging|' \
    -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
    -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" \
    -e 's|^LOG_LEVEL=.*|LOG_LEVEL=warning|' \
    -e "s|^DB_DATABASE=.*|DB_DATABASE=$DB|" \
    -e "s|^DB_USERNAME=.*|DB_USERNAME=$DB|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" \
    "$SHARED/.env"
  grep -q '^SESSION_SECURE_COOKIE=' "$SHARED/.env" || echo 'SESSION_SECURE_COOKIE=true' >> "$SHARED/.env"
fi

ln -sfn "$SHARED/.env" "$APP/.env"
rm -rf "$APP/storage" && ln -sfn "$SHARED/storage" "$APP/storage"

cd "$APP"
# Staging memakai dev dependencies karena seeder demo butuh Faker.
COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-interaction --no-progress --quiet
[ "$FIRST_RUN" = 0 ] || php artisan key:generate --force

php artisan storage:link --force
php artisan migrate --force
# Data demo hanya diisi saat database masih kosong.
if [ "$(mysql -N -e "SELECT COUNT(*) FROM \`$DB\`.categories")" = "0" ]; then
  php artisan db:seed --force
fi

php artisan optimize

chown -R www-data:www-data "$SHARED/storage" "$APP/bootstrap/cache"
chown root:www-data "$SHARED/.env" && chmod 640 "$SHARED/.env"

# --- Scheduler (auto-batal pesanan belum dibayar) ----------------------------
cat > /etc/cron.d/kyread-staging <<CRON
* * * * * www-data cd $BASE/current && php artisan schedule:run >> /dev/null 2>&1
CRON
chmod 644 /etc/cron.d/kyread-staging

# --- Nginx + HTTPS (sekali) --------------------------------------------------
CONF=/etc/nginx/sites-available/$DOMAIN.conf
if [ ! -f "$CONF" ]; then
  cp "$APP/deploy/nginx/$DOMAIN.conf" "$CONF"
  ln -sfn "$CONF" "/etc/nginx/sites-enabled/$DOMAIN.conf"
  nginx -t && systemctl reload nginx
  certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect --register-unsafely-without-email \
    || certbot --nginx -d "$DOMAIN" --non-interactive --redirect
fi

echo "kyread staging siap: $(cat "$APP/REVISION" 2>/dev/null)"
