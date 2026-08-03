# 🔥 Drastha LMS — Shared Hosting SSH Error Reference

> **Target Environment:** Shared Hosting (1 vCPU, 2 GB RAM, 100 GB SSD, 40 PHP Workers)
> **Stack:** Laravel 12 + Inertia.js (React) + MySQL/MariaDB + Redis/Memcached (kalau ada)
> **Tujuan:** Cheat sheet cepat saat SSH ke production & dapat error message.

Format setiap error:
> **ERROR_TEXT** (kutipan persis dari terminal)
> → 🔍 **Penyebab**
> → 🛠️ **Solusi** (command yang bisa langsung di-paste)

---

## 1. 🔑 Permission & Ownership

### 1.1 `The stream or file ".../storage/logs/laravel.log" could not be opened in append mode`
```
The stream or file "/home/u123456/storage/logs/laravel.log" could not be opened in append mode:
Failed to open stream: Permission denied
```
→ 🔍 Web server user (www-data/apache/nobody) tidak bisa tulis ke `storage/` atau `bootstrap/cache/`.
→ 🛠️
```bash
cd ~/public_html   # atau ~/domains/nama-domain.com/public_html
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache
# kalau shared hosting pakai "nobody":
chown -R $USER:nobody storage bootstrap/cache
# verifikasi:
ls -la storage/logs/
```

### 1.2 `Permission denied` saat `php artisan` apa pun
```
file_put_contents(.../bootstrap/cache/services.php): Failed to open stream: Permission denied
```
→ 🛠️
```bash
chmod -R 775 bootstrap/cache
chmod -R 775 storage
php artisan config:clear
php artisan cache:clear
```

### 1.3 `chown: invalid user: 'www-data'` (kalau pakai user lain)
→ 🔍 Di shared hosting cPanel, grup web server bisa `nobody`, `apache`, atau username sendiri.
→ 🛠️ Cari dulu nama grupnya:
```bash
ps aux | grep -E 'apache|nginx|php-fpm' | head -5
id   # lihat group user kamu
groups   # lihat semua group
```
Lalu set ownership pakai group yang ditemukan, misal `chown -R $USER:nobody storage`.

---

## 2. 🧠 PHP Version & Composer

### 2.1 `Composer detected issues in your platform: This package requires PHP >= 8.3`
```
Your Composer dependencies require a PHP version ">= 8.3".
You are using PHP 8.1.x (cli).
```
→ 🛠️
```bash
# Cek versi:
php -v
# Cari PHP 8.3 di server (umumnya MultiPHP Manager cPanel menyediakan ini):
ls /opt/cpanel/ea-php83/root/usr/bin/php 2>/dev/null
ls /usr/local/bin/php83 2>/dev/null
which -a php
# Pakai versi yang benar via alias atau full path:
/opt/cpanel/ea-php83/root/usr/bin/php -v
/opt/cpanel/ea-php83/root/usr/bin/composer install --no-dev --optimize-autoloader
# Opsional: tambah ke ~/.bashrc agar persisten:
echo 'alias php="/opt/cpanel/ea-php83/root/usr/bin/php"' >> ~/.bashrc
source ~/.bashrc
```

### 2.2 `Fatal error: Allowed memory size of 134217728 bytes exhausted`
```
Fatal error: Allowed memory size of 134217728 bytes exhausted (tried to allocate 12582912 bytes)
```
→ 🛠️
```bash
# Tambahkan di .htaccess di root document (front-controller):
echo 'php_value memory_limit 512M' >> .htaccess

# Atau set di user.ini (PHP-FPM/shared hosting biasanya baca ini):
mkdir -p public
echo 'memory_limit = 512M' > public/.user.ini
# tunggu 5 menit (cache TTL user.ini)

# Untuk command artisan, set sementara:
php -d memory_limit=512M artisan optimize
```

### 2.3 `proc_open(): fork failed - Cannot allocate memory`
```
The Process class relies on proc_open, which is not available on your PHP installation.
```
→ 🔍 Hosting mematikan `proc_open` / `exec` di `disable_functions` (biasanya alasan keamanan).
→ 🛠️
1. Buka cPanel → **MultiPHP INI Editor** atau **Select PHP Version → Options**.
2. Hapus `proc_open`, `exec`, `shell_exec` dari **disable_functions**.
3. Atau minta support hosting untuk whitelist-nya.
4. Cek:
```bash
php -r "echo ini_get('disable_functions');"
```

---

## 3. 🗄️ Database (MySQL/MariaDB)

### 3.1 `SQLSTATE[HY000] [2002] No such file or directory` (saat pakai `DB_HOST=localhost`)
```
SQLSTATE[HY000] [2002] No such file or directory
```
→ 🔍 Soket MySQL di shared hosting biasanya di `/var/lib/mysql/mysql.sock`, bukan default PHP.
→ 🛠️ Edit `.env`:
```env
DB_HOST=127.0.0.1   # lebih aman daripada "localhost" di shared hosting
DB_PORT=3306
DB_DATABASE=cpaneluser_drastha
DB_USERNAME=cpaneluser_drastha
DB_PASSWORD=********
```
Lalu:
```bash
php artisan config:clear
php artisan migrate:status
```

### 3.2 `SQLSTATE[HY000] [1045] Access denied for user`
```
SQLSTATE[HY000] [1045] Access denied for user 'cpaneluser_d'@'localhost' (using password: YES)
```
→ 🛠️
1. Reset password DB lewat cPanel → **MySQL Databases**.
2. Copy-paste ulang (bukan ketik manual) ke `.env`.
3. Pastikan user DB sudah di-**Assign** ke database dengan **All Privileges**.
4. Test dari SSH:
```bash
mysql -u cpaneluser_d -p -h 127.0.0.1 cpaneluser_drastha -e "SHOW TABLES;"
```

### 3.3 `SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long`
```
SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 1000 bytes
```
→ 🔍 MariaDB lama di shared hosting masih default `utf8mb3`.
→ 🛠️ Edit `app/Providers/AppServiceProvider.php`:
```php
use Illuminate\Support\Facades\Schema;
public function boot(): void
{
    Schema::defaultStringLength(191); // aman untuk MariaDB < 10.2
}
```
Lalu:
```bash
php artisan migrate:fresh --seed    # ⚠️ drop semua tabel, hanya di local
# di production: tulis migration manual
```

### 3.4 `Base table or view not found: users`
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'cpaneluser_drastha.users' doesn't exist
```
→ 🛠️
```bash
php artisan migrate --force
# atau kalau di-seed:
php artisan db:seed --force
```

---

## 4. 🔐 File `.env` & APP_KEY

### 4.1 `No application encryption key has been specified`
```
RuntimeException: No application encryption key has been specified.
```
→ 🛠️
```bash
cd ~/public_html
ls -la .env                # pastikan file ada
php artisan key:generate --force
php artisan config:clear
```

### 4.2 `The environment file is invalid`
```
Dotenv\Exception\InvalidFileException: Failed to parse dotenv file due to an invalid name
```
→ 🔍 Ada baris `.env` yang di-quote salah, atau nilai mengandung `#` tanpa escape.
→ 🛠️
```bash
cat -A .env | head -50     # cek karakter tersembunyi
# jangan pakai tanda kutip kecuali value mengandung spasi, contoh:
#   APP_NAME="Drastha LMS"        ✅
#   APP_NAME=Drastha # LMS        ❌ (# dianggap komentar)
# Fix:
sed -i 's/^APP_NAME=.*/APP_NAME="Drastha Learning"/' .env
php artisan config:clear
```

### 4.3 `file_get_contents(.../.env): failed to open stream: No such file or directory`
→ 🛠️
```bash
cd ~/public_html
cp .env.example .env       # atau upload .env dari lokal via FTP/cPanel File Manager
nano .env                  # edit kredensial
php artisan key:generate
```

---

## 5. 📦 Composer / Vendor

### 5.1 `Class "Illuminate\..." not found`
```
Class "Illuminate\Foundation\Application" not found
```
→ 🔍 Folder `vendor/` belum ada atau ke-upload via FTP tapi corrupt.
→ 🛠️
```bash
cd ~/public_html
ls vendor/                 # kalau kosong/hilang:
composer install --no-dev --optimize-autoloader
```

### 5.2 `Cannot use ... as ... because the name is already in use`
```
Cannot use Illuminate\Support\Facades\Route as Route;
```
→ 🛠️
```bash
composer dump-autoload -o
php artisan optimize:clear
```

### 5.3 `Failed to download laravel/framework from dist`
```
Your requirements could not be resolved to an installable set of packages.
```
→ 🔍 Versi `composer.json` konflik dengan PHP versi server.
→ 🛠️
```bash
composer install --no-dev --ignore-platform-req=php-8.3 --optimize-autoloader
# atau update Composer:
composer self-update --2
```

### 5.4 Composer timeout / 502 di shared hosting
→ 🛠️ Jalankan **di lokal** lalu upload folder `vendor/` via ZIP:
```bash
# di local:
composer install --no-dev --optimize-autoloader
# zip vendor/, upload via cPanel File Manager, unzip di server.
```

---

## 6. 🛣️ Routes & 404

### 6.1 `404 Not Found` di semua halaman kecuali `/`
→ 🔍 `mod_rewrite` tidak aktif atau `.htaccess` hilang.
→ 🛠️
```bash
cd ~/public_html
ls -la .htaccess           # pastikan ada
# kalau tidak ada, ambil dari repo:
git checkout -- .htaccess
# pastikan Apache pakai AllowOverride All (minta support hosting kalau tidak bisa)
```

### 6.2 `Route [login] not defined`
```
RouteNotFoundException: Route [login] not defined.
```
→ 🛠️
```bash
php artisan route:list | head -20
php artisan route:clear
php artisan optimize:clear
```

### 6.3 `CSRF token mismatch`
```
419 | Page Expired — CSRF token mismatch.
```
→ 🛠️
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
# Hapus cache browser / coba incognito
# Pastikan APP_URL di .env sama dengan domain (pakai https://, bukan http://)
```

---

## 7. 🎨 Frontend (Vite/Inertia/React)

### 7.1 `Vite manifest not found at: public/build/manifest.json`
```
ViteManifestNotFoundException: Vite manifest not found at: /home/.../public_html/public/build/manifest.json
```
→ 🔍 Asset Vite belum di-build atau salah path.
→ 🛠️
```bash
cd ~/public_html
# Build di LOCAL, lalu upload folder public/build/ via FTP:
# di local:
npm ci
npm run build
# zip public/build/ → upload → extract di public_html/public/build/
# cek:
ls public/build/manifest.json
```

### 7.2 `Mix manifest not found` (kalau pakai Laravel Mix)
→ Sama dengan 7.1, jalankan `npm run prod` lalu upload `public/js/` + `public/css/`.

### 7.3 Halaman Inertia stuck loading / blank
→ 🛠️
```bash
# 1. pastikan manifest.json ada
# 2. pastikan APP_URL benar (tanpa trailing slash)
grep APP_URL .env
# 3. clear semua cache Laravel
php artisan optimize:clear
# 4. hard reload browser (Ctrl+Shift+R)
```

---

## 8. 📂 Storage & Uploads

### 8.1 `Unable to write in the "public" directory`
```
League\Flysystem\UnableToWriteFile: Unable to write file
```
→ 🛠️
```bash
cd ~/public_html
# storage harus writable:
chmod -R 775 storage
chown -R $USER:www-data storage
# kalau pakai storage:link:
php artisan storage:link
ls -la public/ | grep storage   # harus ada symlink "storage" -> ../storage/app/public
# kalau symlink tidak bisa (shared hosting), buat manual via cPanel:
#    Symlink: public/storage -> ../storage/app/public
```

### 8.2 `SplFileInfo::getSize(): stat failed for ...`
→ File upload melebihi `upload_max_filesize` atau `post_max_size`.
→ 🛠️
```bash
# cek limit:
php -i | grep -E 'upload_max_filesize|post_max_size'
# naikkan via .user.ini (taruh di public/):
cat > public/.user.ini <<'EOF'
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 512M
max_execution_time = 120
EOF
# tunggu 5 menit agar user.ini ter-cache ulang
```

---

## 9. ⚙️ Queue & Scheduler (jika diaktifkan)

### 9.1 `The "database" queue driver is not configured`
```
InvalidArgumentException: Database driver not configured.
```
→ 🛠️ Edit `.env`:
```env
QUEUE_CONNECTION=database   # atau "sync" untuk uji cepat
# jangan pakai "redis" kalau hosting tidak support
```
Lalu:
```bash
php artisan config:clear
php artisan queue:table            # kalau pakai database
php artisan migrate
```

### 9.2 `Class "Redis" not found`
→ 🔍 Ekstensi PHP Redis tidak tersedia di shared hosting.
→ 🛠️
```env
# di .env, ganti ke:
CACHE_STORE=database     # atau file
SESSION_DRIVER=database
QUEUE_CONNECTION=database
REDIS_CLIENT=php      # pakai phpredis extension kalau ada
# cek:
php -m | grep -i redis
```

---

## 10. 💾 Cache (Redis/Memcached/File)

### 10.1 ❌ `In AbstractConnection.php line 144: Connection refused [tcp://127.0.0.1:6379]`
```
In AbstractConnection.php line 144:
  Connection refused [tcp://127.0.0.1:6379]
```
> 🚨 **Ini error yang paling sering muncul di Hostinger & shared hosting pada umumnya.** Bukan karena `REDIS_HOST` di `.env` salah, tapi karena **Hostinger shared hosting TIDAK menyediakan Redis/Memcached server** sama sekali (kecuali paket Cloud/Managed VPS). Jadi Redis Client (phpredis/predis) di Laravel tetap mencoba konek ke `127.0.0.1:6379` saat boot dan gagal.

**Bukti di screenshot user:** Error muncul di Inertia response **sebelum** controller jalan → artinya Laravel boot gagal. Penyebabnya `SESSION_DRIVER=redis` atau `CACHE_STORE=redis` di `.env`.

→ 🛠️ **Ganti driver ke `database` di `.env` Hostinger** (Laravel sudah include tabel `cache` & `sessions` di migration default, tapi pastikan tabel itu ada):
```env
# ===== Hostinger / Shared Hosting =====
APP_NAME="Drastha Learning"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://drasthalearning.com

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Disable Redis (hosting tidak punya Redis)
# REDIS_CLIENT=phpredis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

Lalu di server:
```bash
cd ~/domains/drasthalearning.com/public_html
# 1. pastikan APP_KEY terisi:
php artisan key:generate --force

# 2. flush semua cache config Laravel (WAJIB, kalau tidak env baru diabaikan):
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/services.php

# 3. pastikan tabel cache & sessions ada (default Laravel sudah include migrasinya):
php artisan migrate --force
# cek:
mysql -u cpaneluser_d -p cpaneluser_drastha -e "SHOW TABLES LIKE '%sessions%';"
mysql -u cpaneluser_d -p cpaneluser_drastha -e "SHOW TABLES LIKE '%cache%';"
# kalau tidak ada, generate manual:
php artisan session:table && php artisan cache:table && php artisan migrate --force

# 4. test:
curl -I https://drasthalearning.com
# harus 200/302, BUKAN 500 Inertia error.
```

> 💡 **Kenapa tidak pakai file driver?** File driver lebih lambat di shared hosting (disk I/O + permission 775 antar folder) → `database` driver lebih cepat untuk session (cocok 100 user + 40 worker).

### 10.2 (alternatif) Cek apakah Hostinger akun kamu mengaktifkan Redis
```bash
# beberapa paket Hostinger Business/Cloud punya Redis internal di socket:
ls /home/$USER/.redis/ 2>/dev/null
# atau:
nc -zv 127.0.0.1 6379 2>&1
# kalau "Connection refused" → tidak ada Redis. Tetap pakai database driver.
```
Kalau **memang** ada Redis (jarang di shared hosting), minta socket path ke support Hostinger lalu:
```env
REDIS_CLIENT=predis
REDIS_HOST=/home/u123456/.redis/redis.sock
REDIS_PORT=0
REDIS_PASSWORD=password_dari_support
```

### 10.3 (versi lama sebelum fix) `Predis\Connection\ConnectionException: Connection refused`
Sama dengan 10.1 — solusinya identik, ganti ke driver `database`.

### 10.4 `No memory left to allocate` saat `php artisan optimize`
→ 🛠️
```bash
# naikkan memory_limit temporer:
php -d memory_limit=1024M artisan optimize
```

---

## 11. 🪪 SSL / HTTPS

### 11.1 Halaman masih `http://` padahal sudah install SSL
→ 🛠️ Tambahkan di `public/.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```
Atau set `APP_URL=https://domain.com` di `.env`.

### 11.2 `cURL error 60: SSL certificate problem`
```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```
→ 🛠️ Tambahkan `CURL_CA_BUNDLE` di `.env`:
```env
CURL_CA_BUNDLE=/etc/pki/tls/certs/ca-bundle.crt
```
Atau download `cacert.pem` dari [curl.se/docs/caextract.html](https://curl.se/docs/caextract.html), taruh di `storage/cacert.pem`, lalu:
```bash
echo 'CURL_CA_BUNDLE='$(realpath storage/cacert.pem) >> .env
```

---

## 12. 🚦 Worker & Concurrency (sesuai PRD: 100 user, 40 worker)

### 12.1 Server lambat, `top` menunjukkan CPU 100%
→ 🔍 Bukan error, tapi bottleneck. Cek:
```bash
top -c        # atau: htop (kalau tersedia)
# lihat proses dominan. Biasanya:
# - MySQL: optimize tabel (lihat 13.1)
# - PHP-FPM: kurangi pm.max_children di .user.ini
# - Composer autoload: jalankan composer dump-autoload -o
```

### 12.2 PHP-FPM mencapai max_children, request antri
→ 🛠️ Turunkan jumlah worker per-FPM tapi naikkan `pm.max_requests`:
```ini
# di public/.user.ini (untuk PHP-FPM):
pm = ondemand
pm.max_children = 10        # shared hosting: 1 vCPU/2GB RAM
pm.max_requests = 200
pm.process_idle_timeout = 60
```
> **Catatan:** Shared hosting cPanel biasanya **tidak memperbolehkan** edit `pm.*`. Sola­sinya pakai **Laravel cache optimally** + **database eager loading** (lihat `.clinerules/AGENTS.md`).

---

## 13. 🧹 Optimasi & Maintenance (WAJIB setelah deploy)

### 13.1 `Specified key was too long` saat migrate (ulangan untuk ingatkan)
→ Sudah dibahas di 3.3.

### 13.2 Pembersihan setelah deploy
```bash
cd ~/public_html
php artisan down --secret="rahasia-deploy-123" || true
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
php artisan up
```

### 13.3 Log membengkak, disk penuh
```bash
du -sh storage/logs/
truncate -s 0 storage/logs/laravel.log   # kosongkan tanpa hapus file
# atau rotasi:
cat > ~/.logrotate.conf <<'EOF'
/home/$USER/storage/logs/*.log {
    daily
    rotate 7
    compress
    missingok
    notifempty
}
EOF
```

### 13.4 Disk 100% penuh
```bash
df -h
du -sh storage/app/public/* | sort -h | tail -10
# hapus backup lama, log lama, cache lama:
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*.php   # akan di-rebuild otomatis
```

---

## 14. 📡 Healthcheck Command (pakai ini tiap pagi)

```bash
# 1. versi PHP
php -v

# 2. composer + ukuran vendor
du -sh vendor/

# 3. storage writable?
[ -w storage/logs ] && echo "OK storage" || echo "FAIL storage"

# 4. database ping
php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"

# 5. cache hit rate
php artisan tinker --execute="Cache::put('healthcheck','ok',60); echo Cache::get('healthcheck');"

# 6. queue
php artisan queue:failed

# 7. response time
time curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" https://yourdomain.com
```

---

## 15. 🆘 Recovery Darurat (kalau situs down total)

```bash
# 1. masuk mode maintenance
cd ~/public_html
php artisan down

# 2. cek error terakhir
tail -50 storage/logs/laravel.log
tail -50 ~/access-logs/$(ls -t ~/access-logs/ | head -1) 2>/dev/null

# 3. rollback cepat (kalau pakai git)
git log --oneline -10
git checkout HEAD~1 -- .
php artisan optimize:clear

# 4. kalau .env rusak
cp .env.backup .env       # kalau ada backup
chmod 640 .env

# 5. clear semua cache
php artisan optimize:clear
rm -rf bootstrap/cache/*.php

# 6. hidupkan kembali
php artisan up
```

---

## 📌 Cheat Sheet (versi TL;DR)

| Error singkat                              | Solusi cepat                                            |
| ------------------------------------------- | ------------------------------------------------------- |
| `Permission denied` storage                 | `chmod -R 775 storage bootstrap/cache`                  |
| `No application encryption key`             | `php artisan key:generate`                              |
| `Vite manifest not found`                   | Upload `public/build/` dari local build                 |
| `404 Not Found` semua route                 | Cek `.htaccess` + `mod_rewrite`                         |
| `Connection refused 6379`                   | Ganti `CACHE_STORE=`, `SESSION_DRIVER=`, `QUEUE=` ke `database` |
| `Base table not found`                      | `php artisan migrate --force`                           |
| `Allowed memory size exhausted`             | `php -d memory_limit=512M` atau `.user.ini`             |
| `Composer require PHP >= 8.3`               | Pakai full path PHP 8.3 cPanel                          |
| `CSRF token mismatch`                       | `php artisan optimize:clear` + cek `APP_URL`            |
| `cURL error 60 SSL`                         | Set `CURL_CA_BUNDLE` di `.env`                          |
| `File upload terlalu besar`                 | Set `upload_max_filesize=50M` di `public/.user.ini`     |

---

> 💡 **Lihat juga:**
> - `.clinerules/AGENTS.md` — aturan performa database (index, eager loading, N+1)
> - `PROJECT_AUDIT_REPORT.md` — hasil audit codebase
> - `REMEDIATION_PLAN_FOR_AI.md` — rencana perbaikan
> - `docs/DEPLOYMENT.md` — panduan deploy (jika ada)