<x-mail::message>
# Halo, {{ $name }}!

Selamat datang di **Drastha Learning**! Akun Anda telah dibuat oleh Administrator kami dengan role **{{ ucfirst($role) }}**.

Untuk mengaktifkan akun dan membuat password pribadi, gunakan tautan aman berikut:

- **Email:** {{ $email }}

<x-mail::button :url="$setupUrl">
Aktifkan Akun
</x-mail::button>

> **Catatan keamanan:** tautan ini hanya dapat digunakan satu kali dan berlaku selama 60 menit. Jangan membagikan tautan ini kepada siapa pun.

Terima kasih,<br>
Tim Administrator {{ config('app.name') }}
</x-mail::message>
