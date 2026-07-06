# Drastha LMS - Premium Monolith Learning Management System (LMS)

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vue.js&logoColor=4FC08D)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-9553E8?style=for-the-badge&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-00758F?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)

**Drastha LMS** adalah platform e-learning (*Learning Management System*) kelas enterprise berbasis web monolitik modern yang dirancang untuk performa tinggi, keamanan maksimal, dan ramah SEO. Menggunakan kombinasi **Laravel 11**, **Vue 3 (Inertia.js)**, dan **Tailwind CSS**, platform ini menyajikan pengalaman pengguna sekelas aplikasi native (Single Page Application / SPA) yang sangat responsif, cepat, dan hemat bandwidth.

Platform ini siap pakai (*Production-Ready*) dan dikemas secara komersial untuk kebutuhan instansi pendidikan, perusahaan (*corporate training*), sekolah, maupun kreator kursus independen yang ingin menjual kelas secara online dengan kendali penuh.

---

## 🚀 Keunggulan Utama (Value Propositions)

### 1. Arsitektur Monolitik Modern (Laravel + Vue 3 SPA via Inertia.js)
* **Native App Feeling**: Navigasi halaman super instan tanpa full-reload berkat integrasi Inertia.js.
* **Kemudahan Deployment**: Berbeda dengan arsitektur microservices yang rumit, Drastha LMS menggunakan pendekatan monolitik yang mudah di-maintain dan dideploy di VPS murah maupun Shared Hosting premium (seperti Hostinger).
* **Performa Tinggi**: Optimasi aset dengan Vite untuk kompilasi super cepat dan runtime yang efisien.

### 2. Fitur Anti-Pirasi & Proteksi Konten Tingkat Tinggi (Advanced Anti-Piracy)
Untuk melindungi kekayaan intelektual (video, modul, kuis) dari pembajakan:
* **Anti-DevTools Debugger Loop**: Otomatis mendeteksi jika ada user yang membuka *Inspect Element / Developer Tools* browser dan memblokir tampilan konten seketika untuk mencegah pencurian source code atau download video ilegal.
* **Disable Copy-Paste & Right-Click**: Proteksi klik kanan, seleksi teks, serta shortcut keyboard sensitif (`Ctrl+C`, `Ctrl+U`, `Ctrl+S`, `F12`) untuk mempersulit penyalinan konten pembelajaran secara tidak sah.

### 3. Progressive Web App (PWA) Support
* Dapat diinstal langsung di layar utama smartphone (Android & iOS) dan desktop layaknya aplikasi native.
* Dilengkapi dengan kustomisasi **Service Worker** untuk caching pintar guna mempercepat load page dan menghemat kuota internet pengguna.

### 4. Penuh Optimasi SEO (SEO & Google Search Ready)
* **JSON-LD Schema Markup**: Otomatis menyuntikkan schema data terstruktur `EducationalOrganization` agar website mendapatkan Rich Snippets eksklusif di hasil pencarian Google.
* **Dynamic Meta & Open Graph Tags**: Penanganan meta title, meta description, Facebook OG, dan Twitter Cards secara dinamis untuk setiap kursus demi performa klik media sosial yang maksimal.
* **Auto Sitemap & Robots.txt**: Konfigurasi otomatis untuk pengindeksan mesin pencari yang optimal.

---

## 🛠️ Modul & Fitur Unggulan (Core Modules)

### 👥 Portal Siswa (Student Experience)
* **Interactive Learning Player**: Layar belajar premium dengan pelacakan progres otomatis, sidebar materi yang interaktif, serta tab diskusi dan unduh materi.
* **Kuis & Penilaian Interaktif**: Sistem ujian akhir per bab/modul untuk mengukur pemahaman materi sebelum mendapatkan sertifikat kelulusan.
* **Course Gifting (Fitur Kado Kelas)**: Siswa dapat membeli kursus untuk dihadiahkan kepada kerabat atau rekan kerja menggunakan email penerima.
* **Keranjang Belanja & Checkout Modern**: Proses pembelian kelas yang mulus dengan multi-item cart.

### 👨‍🏫 Portal Instruktur (Instructor Suite)
* **Drag-and-Drop Course Builder**: Modul pembuatan materi yang intuitif untuk menyusun Bab, Sub-bab, Video Pembelajaran (embed atau storage), Artikel, Dokumen Pendukung, dan Kuis.
* **Analitik Pendapatan & Murid**: Dashboard khusus untuk memantau performa penjualan kelas secara real-time.
* **Withdrawal Request System**: Fitur bagi instruktur untuk menarik saldo bagi hasil pendapatan kursus secara transparan.

### 👑 Portal Admin (Central Command Center)
* **Platform Analytics**: Pantau total pendapatan kotor, jumlah murid aktif, jumlah instruktur, dan produk kelas terlaris.
* **Course Approval Flow**: Sistem moderasi kualitas materi sebelum kelas ditayangkan ke publik.
* **User & Role Management**: Kelola data pengguna, konfirmasi pendaftaran instruktur baru, serta atur level akses pengguna secara granular.
* **Blog CMS**: Sistem manajemen konten blog built-in untuk mendukung kampanye artikel SEO.

### 💳 E-commerce & Monetisasi (Payment Gateway)
* Terintegrasi langsung dengan **Midtrans Payment Gateway** (sistem pembayaran terbesar di Indonesia).
* Mendukung otomatisasi verifikasi transaksi via **Webhooks** (Virtual Account Bank, GoPay, ShopeePay, QRIS, Kartu Kredit, Alfamart/Indomaret).
* **Sistem Kupon & Diskon**: Buat kode promo dengan batasan waktu, kuota penggunaan, maupun persentase potongan harga untuk mendongkrak penjualan.

---

## 🔒 Sistem Keamanan (Enterprise-Grade Security)

Keamanan platform telah di-audit dan diperkuat berdasarkan standar pentesting tingkat lanjut:
* **OTP Authentication**: Proses pendaftaran, masuk (*login*), dan setel ulang kata sandi diamankan dengan token OTP (One-Time Password) sekali pakai via email (integrasi SMTP & Brevo API).
* **Google OAuth Safe Merging**: Memungkinkan pengguna masuk instan dengan akun Google dengan sistem integrasi akun yang aman tanpa kebocoran data.
* **Route & Endpoint Shielding**: Menggunakan filter Ziggy Route dinamis yang membatasi metadata rute berdasarkan role user aktif. Endpoint sensitif admin tidak akan diekspos sedikit pun pada sisi client siswa.
* **CORS & Security Headers**: Proteksi header HTTP yang ketat terhadap serangan *Clickjacking*, *XSS*, *MIME-sniffing*, serta restriksi CORS asal origin yang aman (hanya dari `APP_URL`).
* **Rate Limiting**: Batasan frekuensi request pada rute sensitif (Login, Register, OTP) guna mencegah serangan *Brute Force* dan *DDoS*.
* **IDOR/BOLA Prevention**: Enkripsi & validasi kepemilikan data di sisi server untuk memastikan user tidak dapat memodifikasi kuis atau mengedit materi kursus milik orang lain.

---

## 💻 Tech Stack & Kebutuhan Sistem

* **Backend**: PHP 8.2 / 8.3 (Laravel 11.x)
* **Frontend**: Vue 3.x (Composition API), Inertia.js Vue 3 Adapter
* **Build Tool**: Vite
* **Styling**: Tailwind CSS
* **Database**: MySQL 8.0+
* **Mail Service**: Mailer SMTP / Brevo API Integration
* **Payment Gateway**: Midtrans (Sandbox / Production API)

---

## ⚙️ Cara Memulai & Instalasi Lokal

### 1. Klon Repositori & Install Dependensi
```bash
# Klon repositori
git clone https://github.com/Aqilsalimm/lms_dl_customcode.git
cd lms_dl_customcode

# Install dependensi PHP
composer install --no-dev --optimize-autoloader

# Install dependensi Node.js
npm install
```

### 2. Konfigurasi Environment File
Salin file `.env.example` menjadi `.env` dan lengkapi konfigurasi database, mailer, google oauth, dan midtrans:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrasi Database & Seeder
Jalankan migrasi tabel database beserta data awal bawaan:
```bash
php artisan migrate --seed
```

### 4. Build Aset Frontend
Lakukan kompilasi aset Vue 3 dan CSS ke folder public:
```bash
npm run build
```

### 5. Jalankan Local Server
```bash
php artisan serve
```
*Platform siap diakses secara lokal pada alamat: `http://127.0.0.1:8000`*

---

## 🚢 Alur Deployment Server (Production)

Proyek ini telah dilengkapi dengan script deployment otomatis `deploy.sh` yang aman. Untuk melakukan deploy di server Hostinger/cPanel VPS Anda:

```bash
# Hubungkan ke SSH server Anda
cd /path-to-your-project/drastha-lms

# Tarik perubahan kode terbaru dari branch production/main
git pull origin main

# Jalankan script deploy untuk auto-clear cache & reload config
./deploy.sh
```

---
*Fully Created by : Aqilsalimm*
