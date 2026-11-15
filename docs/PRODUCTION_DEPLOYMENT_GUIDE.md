# VEGGITABLE SAAS: PRODUCTION DEPLOYMENT & LAUNCH GUIDE

## 1. System Overview & Technology Stack
- **Framework**: Laravel 12 / 13 on PHP 8.3+
- **Application Architecture**: Multi-Tenant Vegetable & Fruit Shop Management SaaS (Single Database with strict `tenant_id` partitioning)
- **Database Engine**: MySQL 8.4 Community Server (InnoDB, `utf8mb4`, `utf8mb4_unicode_ci`)
- **Web Server**: Nginx (HTTP/2 with TLS 1.3) or Apache 2.4+
- **Frontend Stack**: Bootstrap 5.3 + EasyAdmin Pro + Blade + Livewire 3 + Noto Nastaliq Urdu Typography (No React/Vue/Tailwind)
- **Reporting & Hardware**: DomPDF, Maatwebsite Excel, ESC/POS Thermal Printing, Scale Barcode Decoders

---

## 2. Server Prerequisites & PHP 8.3 Extensions
### Ubuntu 24.04 LTS / Debian 12:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip nginx supervisor mysql-server-8.0

# Add PHP 8.3 PPA
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 & Extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-bcmath php8.3-intl php8.3-curl php8.3-zip \
    php8.3-gd php8.3-opcache
```

---

## 3. Application Deployment & Environment Setup
```bash
# 1. Clone repository to web root
cd /var/www
git clone https://github.com/organization/veggitable.git veggitable
cd /var/www/veggitable

# 2. Install production composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Setup environment file
cp .env.example .env
php artisan key:generate

# 4. Set directory ownership and permissions
sudo chown -R www-data:www-data /var/www/veggitable
sudo chmod -R 775 /var/www/veggitable/storage
sudo chmod -R 775 /var/www/veggitable/bootstrap/cache
```

### Production `.env` Critical Settings
```ini
APP_NAME="Veggitable SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://veggitable.pk

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=veggitable_saas
DB_USERNAME=veggitable_user
DB_PASSWORD="STRONG_DB_PASSWORD"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database
CACHE_STORE=database
```

---

## 4. Database Migrations & Initial Seeding
```bash
# Run database migrations
php artisan migrate --force

# Seed master roles, permissions and default subscription plans
php artisan db:seed --class=RoleAndPermissionSeeder --force
php artisan db:seed --class=SubscriptionPlanSeeder --force
```

---

## 5. Production Optimization Commands
Before launching, execute Laravel's production caching engine:
```bash
# Cache configuration, routes, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Verify launch readiness via CLI diagnostic
php artisan veggitable:launch-readiness
```

---

## 6. Nginx Virtual Host Configuration (with SSL)
Create `/etc/nginx/sites-available/veggitable.conf`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name veggitable.pk *.veggitable.pk;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name veggitable.pk *.veggitable.pk;
    root /var/www/veggitable/public;

    # SSL Certificates (Let's Encrypt Certbot)
    ssl_certificate /etc/letsencrypt/live/veggitable.pk/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/veggitable.pk/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline' 'unsafe-eval';" always;

    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # FastCGI PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 7. PHP 8.3 OPCache & FPM Tuning
In `/etc/php/8.3/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1

memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 60
```

---

## 8. Cron Scheduler & Supervisor Queue Workers

### System Cron (`crontab -e -u www-data`)
```bash
* * * * * cd /var/www/veggitable && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor Worker (`/etc/supervisor/conf.d/veggitable-worker.conf`)
```ini
[program:veggitable-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/veggitable/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/veggitable/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 9. Automated Backups & Disaster Recovery
- Nightly automated database backups with SHA-256 checksums and 30-day retention:
```bash
php artisan veggitable:backup --clean --keep=30
```
- Restoring from backup:
```bash
gunzip < /var/www/veggitable/storage/app/backups/veggitable_backup_YYYY-MM-DD_HHmmss.sql.gz | mysql -u veggitable_user -p veggitable_saas
```

---

## 10. Launch Verification Checklist
- [x] Run `php artisan veggitable:launch-readiness` (Verify 100% score)
- [x] Super Admin System Health Dashboard accessible at `/superadmin/system-health`
- [x] Verify multi-tenant isolation audit across 25 database tables
- [x] Test ESC/POS 80mm thermal receipt printing in English & Urdu Nastaliq
- [x] Verify electronic produce scale weight-embedded barcode scanner (`20AAAAAWWWWWX`)
- [x] Test daily register cash reconciliation Z-Report
- [x] Verify automated daily summaries command (`php artisan veggitable:daily-summaries`)
- [x] Verify subscription billing cycle checks (`php artisan veggitable:check-subscriptions`)
- [x] Full automated test suite passing (**154+ tests, 973+ assertions**)

