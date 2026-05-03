# MediNova Pharma — Hostinger Deployment Guide

## Server: moccasin-chimpanzee-720084.hostingersite.com
## PHP: 8.2+ (Hostinger hPanel → PHP Configuration)

---

## Step 1: Prepare Local Files

```bash
# Switch to production branch
git checkout production

# Build frontend assets
npm run build

# Install PHP deps for production (no dev packages)
composer install --no-dev --optimize-autoloader
```

---

## Step 2: Upload Files to Hostinger

### Option A: ZIP Upload (Recommended)
```bash
# Create ZIP excluding unnecessary files
zip -r medinova.zip . -x "node_modules/*" ".git/*" "storage/logs/*" "storage/framework/cache/*" "storage/framework/views/*" "storage/debugbar/*" "*.md" ".env" "tests/*"
```
1. Login to **hPanel** → **File Manager**
2. Navigate to `public_html/` (or `domains/moccasin-chimpanzee-720084.hostingersite.com/public_html/`)
3. Upload `medinova.zip`
4. Extract it
5. Move all files one level UP so `public/` is at the root (not inside a subfolder)

### Option B: Git Clone (if SSH is enabled)
```bash
# On Hostinger via SSH
cd ~/domains/moccasin-chimpanzee-720084.hostingersite.com/
git clone https://github.com/agussetiawa7n/medinova-pharma.git .
git checkout production
composer install --no-dev --optimize-autoloader
```

---

## Step 3: Configure Document Root

Hostinger defaults to `public_html/`. Laravel needs the document root to point to `public/`.

**Option A — hPanel (Recommended):**
1. hPanel → **Hosting** → your domain
2. Scroll to **Configuration** → change document root
3. Set to: `domains/moccasin-chimpanzee-720084.hostingersite.com/public`

**Option B — .htaccess in root:**
Create `.htaccess` in the root folder (parent of `public/`):
```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

---

## Step 4: Create Database

1. hPanel → **Databases** → **MySQL Databases**
2. Create database: `u123456789_medinova`
3. Create user + password
4. Assign user to database with ALL PRIVILEGES
5. Note down: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

---

## Step 5: Configure .env

```bash
# Copy the template
cp .env.hostinger.example .env

# Edit .env with actual values
nano .env
```

**CRITICAL — Fill these values in .env:**
```env
APP_KEY=                         # Run: php artisan key:generate
APP_URL=https://moccasin-chimpanzee-720084.hostingersite.com
APP_DEBUG=false

DB_DATABASE=u123456789_medinova   # From hPanel
DB_USERNAME=u123456789_admin      # From hPanel
DB_PASSWORD=your-password-here    # From hPanel

# SMTP (use Hostinger email or Gmail SMTP)
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=ssl

# Payment Gateway Keys (get from Razorpay/Stripe/PayPal dashboards)
RAZORPAY_KEY=rzp_live_xxxx
RAZORPAY_SECRET=xxxx
STRIPE_KEY=pk_live_xxxx
STRIPE_SECRET=sk_live_xxxx
PAYPAL_MODE=live
PAYPAL_CLIENT_ID=xxxx
PAYPAL_CLIENT_SECRET=xxxx

# Wallet HMAC Key (generate a random 64-char hex string)
WALLET_SHARED_SECRET=your-random-64-char-hex-string
```

---

## Step 6: Run Laravel Setup Commands

Via hPanel Terminal or SSH:
```bash
# Generate app key
php artisan key:generate

# Create storage symlink
php artisan storage:link

# Run database migrations
php artisan migrate

# Seed admin user + demo data (optional)
php artisan db:seed --class=AdminSeeder

# Cache everything for speed
php artisan optimize
```

---

## Step 7: Set File Permissions

```bash
# Make storage writable
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/logs storage/framework

# If using Hostinger File Manager, right-click → Change Permissions
```

---

## Step 8: Import Local Database

**Export from local:**
```bash
mysqldump -u root -p medinova_pharma > medinova_backup.sql
```

**Import on Hostinger:**
1. hPanel → **Databases** → **phpMyAdmin**
2. Select your database
3. Import → Choose `medinova_backup.sql`
4. Click Go

---

## Step 9: Verify Deployment

| Check | URL |
|-------|-----|
| Homepage | `https://moccasin-chimpanzee-720084.hostingersite.com/` |
| Shop | `/products` |
| Login | `/login` |
| Admin | `/admin` |
| Storage | `/storage/<any-image-path>` should load images |

---

## Step 10: Post-Deployment

- [ ] Test Add to Cart flow
- [ ] Test Checkout with COD
- [ ] Test Wishlist
- [ ] Test Admin panel (upload images, manage orders)
- [ ] Test Email (password reset, order confirmation)
- [ ] Set up Cron Job in hPanel:
  ```
  * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
  ```

---

## Quick Commands Cheat Sheet

```bash
# After code changes:
git pull origin production
composer install --no-dev --optimize-autoloader
npm run build
php artisan optimize

# Clear cache:
php artisan optimize:clear

# View logs:
tail -f storage/logs/laravel.log

# Check Laravel version:
php artisan --version
```
