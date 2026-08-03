/**
 * ============================================================
 *  DRASTHA LMS — k6 Load Test (Event Offline Edition)
 *  Skenario 100 orang, 40 PHP Worker, ~3 req per worker
 *  Target: <100ms p(95) pada endpoint cacheable; <300ms p(95) pada dynamic.
 *
 *  Usage:    k6 run tests/load/k6-load-test.js
 *  Custom:   k6 run -e BASE_URL=https://lms.drastha.com tests/load/k6-load-test.js
 * ============================================================
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

// Default localhost:8000 (artisan serve), override via env var BASE_URL
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// 5 skenario perilaku: 80% browse (cache-friendly), 15% course detail (DB), 5% login attempt (write)
const BROWSE = 0.80;
const DETAIL = 0.15;
const LOGIN  = 0.05;

// Threshold target: Tailwind 1vCPU/2GB/40 worker hosting
export const options = {
    scenarios: {
        event_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '15s', target: 50 },   // early arrivals
                { duration: '30s', target: 100 },  // event start — semua peserta login
                { duration: '60s', target: 100 },  // stabil — browsing selama acara
                { duration: '20s', target: 30 },   // setelah acara: tinggal yang ngisi assessment
                { duration: '10s', target: 0 },    // selesai
            ],
            gracefulRampDown: '10s',
        },
    },
    thresholds: {
        // Cache-friendly endpoint harus <100ms
        'http_req_duration{name:homepage}':        ['p(95)<100'],
        'http_req_duration{name:courses_index}':   ['p(95)<150'],
        'http_req_duration{name:api_search}':      ['p(95)<200'],
        // Dynamic DB-backed boleh lebih lambat
        'http_req_duration{name:course_detail}':   ['p(95)<300'],
        'http_req_duration{name:live_link}':       ['p(95)<300'],
        // Global fallback
        http_req_duration: ['p(95)<500'],
        http_req_failed:   ['rate<0.01'],
    },
    // Hindari error SSL di CI
    insecureSkipTLSVerify: true,
};

// Helper: random pick untuk simulasi 100 user
function pickScenario() {
    const r = Math.random();
    if (r < BROWSE) return 'browse';
    if (r < BROWSE + DETAIL) return 'detail';
    return 'login';
}

function browseFlow() {
    // 1) Homepage (cache 1 jam) — harus super cepat
    let res = http.get(`${BASE_URL}/`, { name: 'homepage' });
    check(res, {
        'homepage 200':           (r) => r.status === 200,
        'homepage <100ms':        (r) => r.timings.duration < 100,
    });
    sleep(0.5);

    // 2) Courses index (cached 1 jam)
    res = http.get(`${BASE_URL}/courses`, { name: 'courses_index' });
    check(res, {
        'courses_index 200':      (r) => r.status === 200,
        'courses_index <200ms':   (r) => r.timings.duration < 200,
    });
    sleep(0.3);

    // 3) Live class listing (cached 30 menit via WarmupCache)
    res = http.get(`${BASE_URL}/live-classes`, { name: 'live_classes' });
    check(res, {
        'live_classes 200':       (r) => r.status === 200,
    });
    sleep(0.5);

    // 4) AJAX course search (filter) — cache friendly karena key by query string
    const cat = Math.floor(Math.random() * 5) + 1;
    res = http.get(`${BASE_URL}/api/courses/search?category_id=${cat}&q=laravel`, {
        name: 'api_search',
    });
    check(res, {
        'api_search 200':         (r) => r.status === 200,
        'api_search <200ms':      (r) => r.timings.duration < 200,
    });
    sleep(0.5);
}

function detailFlow() {
    // Course detail (dynamic DB). Pakai slug statis 'web-development-bootcamp'
    // — pastikan kursus ini di-publish sebelum load test.
    const slugs = [
        'web-development-bootcamp',
        'laravel-12-mastery',
        'react-fundamentals',
        'data-science-101',
    ];
    const slug = slugs[Math.floor(Math.random() * slugs.length)];

    let res = http.get(`${BASE_URL}/courses/${slug}`, { name: 'course_detail' });
    check(res, {
        'course_detail 200':      (r) => r.status === 200,
        'course_detail <500ms':   (r) => r.timings.duration < 500,
    });
    sleep(1);

    // Live meeting link endpoint (saat peserta mau join Zoom/Google Meet)
    res = http.get(`${BASE_URL}/courses/${slug}/live-link`, { name: 'live_link' });
    check(res, {
        'live_link <500ms':       (r) => r.status === 200 && r.timings.duration < 500,
    });
    sleep(0.5);
}

function loginFlow() {
    // 5% VU mencoba login. Ini yang paling berat — POST + password_hash + session write.
    const email = `user${__VU}@drastha.test`;
    const payload = JSON.stringify({ email: email, password: 'password123' });
    const params = {
        headers: { 'Content-Type': 'application/json' },
        name: 'login',
    };
    const res = http.post(`${BASE_URL}/login`, payload, params);
    // 422 (validation) atau 302 (redirect) tetap dianggap "request sukses" di level transport.
    check(res, {
        'login handled (302/422)': (r) => r.status === 302 || r.status === 200 || r.status === 422,
        'login <800ms':            (r) => r.timings.duration < 800,
    });
    sleep(2);
}

export default function () {
    const scenario = pickScenario();
    if (scenario === 'browse') {
        browseFlow();
    } else if (scenario === 'detail') {
        detailFlow();
    } else {
        loginFlow();
    }
}