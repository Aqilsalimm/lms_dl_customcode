import http from 'k6/http';
import { check, sleep } from 'k6';

// 1. Konfigurasi Skenario Uji Beban Puncak (120 Virtual Users, Jeda 5 Detik)
export const options = {
  stages: [
    { duration: '20s', target: 30 },   // Ramp-up 1: 40 user awal
    { duration: '35s', target: 60 },   // Ramp-up 2: 80 user
    { duration: '45s', target: 90 },  // Beban Puncak: 120 user aktif bersamaan
    { duration: '1m',  target: 90 },  // Tahan di 120 user selama 1 menit
    { duration: '20s', target: 0 },    // Ramp-down: Selesai
  ],
  // Mengabaikan error validasi handshake SSL sementara jika ada perbedaan sertifikat SNI
  insecureSkipTLSVerify: true,
  thresholds: {
    'http_req_failed': ['rate<0.05'],    // Toleransi error di bawah 5%
    'http_req_duration': ['p(95)<1000'], // 95% request di bawah 1 detik
  },
};

// 2. Logika Pengujian Skenario Peserta Besok (Jeda 10 Detik Pacing)
export default function () {
  const params = {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
      'Accept-Language': 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
      'Accept-Encoding': 'gzip, deflate, br',
      'Cache-Control': 'max-age=0',
      'Sec-Ch-Ua': '"Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
      'Sec-Ch-Ua-Mobile': '?0',
      'Sec-Ch-Ua-Platform': '"Windows"',
      'Sec-Fetch-Dest': 'document',
      'Sec-Fetch-Mode': 'navigate',
      'Sec-Fetch-Site': 'none',
      'Sec-Fetch-User': '?1',
      'Upgrade-Insecure-Requests': '1',
    },
  };

  // Uji URL utama HTTPS
  const res = http.get('https://drasthalearning.com/', params);

  // Diagnosa status jika gagal
  if (res.status !== 200 && res.status !== 304) {
    console.log(`[HTTP ${res.status}] ${res.status_text} | URL: ${res.url}`);
  }

  // Validasi respon sukses
  check(res, {
    'status is 200 or 304': (r) => r.status === 200 || r.status === 304,
  });

  // Jeda realistis peserta besok membaca/mengerjakan soal (10 detik)
  sleep(5);
}
