import http from 'k6/http';
import { check, sleep } from 'k6';

// 1. Konfigurasi Skenario Uji (66 Virtual Users)
export const options = {
  stages: [
    { duration: '10s', target: 30 },  // Ramp-up: 10 detik pertama naik ke 30 user
    { duration: '15s', target: 66 },  // Naik lagi sampai 66 user (target peserta)
    { duration: '1m',  target: 66 },  // Tahan di 66 user aktif bersamaan selama 1 menit
    { duration: '10s', target: 0 },   // Ramp-down: Turunkan trafik secara perlahan
  ],
  thresholds: {
    // Kriteria Lulus Uji:
    'http_req_failed': ['rate<0.01'],    // Gagal/Error wajib di bawah 1%
    'http_req_duration': ['p(95)<500'],  // 95% request wajib di bawah 500ms
  },
};

// 2. Logika Utama Pengujian
export default function () {
  const params = {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
      'Accept-Language': 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
      'Cache-Control': 'no-cache',
    },
  };

  // Uji URL utama
  const res = http.get('https://drasthalearning.com/', params);

  // Jika status bukan 200, cetak pesan singkat untuk diagnosa penyebab (misal 403/429/503/301)
  if (res.status !== 200) {
    console.log(`[HTTP ${res.status}] ${res.status_text} | URL: ${res.url}`);
  }

  // Validasi: Pastikan Server menjawab HTTP Status 200 (atau 304 Not Modified jika ada caching)
  check(res, {
    'status is 200': (r) => r.status === 200 || r.status === 304,
  });

  // Jeda alami 1 detik antar request
  sleep(1);
}
