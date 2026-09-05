/**
 * ============================================================
 *  DRASTHA LMS — k6 Real-User Scenario Load Test Script
 *  Infrastructure: 2 vCPU | 3GB RAM | 200GB SSD NVMe | 60 PHP Workers
 *  Target Concurrency: 150 - 180 Virtual Users (VUs)
 *
 *  Usage:
 *    k6 run tests/load/k6-load-test.js
 *    k6 run -e BASE_URL=https://drasthalearning.com tests/load/k6-load-test.js
 * ============================================================
 */
import http from 'k6/http';
import { check, group, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'https://drasthalearning.com';

const BASE_HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language': 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    'Accept-Encoding': 'gzip, deflate, br',
    'Sec-Ch-Ua': '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
    'Sec-Ch-Ua-Mobile': '?0',
    'Sec-Ch-Ua-Platform': '"Windows"',
    'Sec-Fetch-Dest': 'document',
    'Sec-Fetch-Mode': 'navigate',
    'Sec-Fetch-Site': 'same-origin',
    'Upgrade-Insecure-Requests': '1',
};

function getCsrfTokenFromCookie(jar, url) {
    const cookies = jar.cookiesForURL(url);
    if (cookies && cookies['XSRF-TOKEN'] && cookies['XSRF-TOKEN'].length > 0) {
        return decodeURIComponent(cookies['XSRF-TOKEN'][0]);
    }
    return '';
}

export const options = {
    scenarios: {
        human_event_journey: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '20s', target: 40 },
                { duration: '40s', target: 120 },
                { duration: '2m',  target: 150 },
                { duration: '30s', target: 40 },
                { duration: '15s', target: 0 },
            ],
            gracefulRampDown: '10s',
        },
    },
    thresholds: {
        'http_req_duration{name:01_homepage}':       ['p(95)<150'],
        'http_req_duration{name:02_courses_catalog}':['p(95)<250'],
        'http_req_duration{name:03_course_search}':  ['p(95)<250'],
        'http_req_duration{name:04_course_detail}':  ['p(95)<350'],
        'http_req_duration{name:05_login_attempt}':  ['p(95)<700'],

        http_req_duration: ['p(95)<500'],
        http_req_failed:   ['rate<0.02'],
    },
    insecureSkipTLSVerify: true,
    http2: false,
    noConnectionReuse: false,
    batchPerHost: 30,
    timeout: '10s',
};

export default function () {
    const jar = http.cookieJar();

    // LANGKAH 1: Homepage Visit
    group('01_Homepage_Visit', function () {
        const res = http.get(`${BASE_URL}/`, {
            headers: BASE_HEADERS,
            tags: { name: '01_homepage' },
        });

        check(res, {
            'beranda status 200/304': (r) => r.status === 200 || r.status === 304,
        });

        sleep(3 + Math.random() * 2);
    });

    // LANGKAH 2: Catalog Browsing via Inertia
    group('02_Courses_Catalog_Browsing', function () {
        const inertiaHeaders = Object.assign({}, BASE_HEADERS, {
            'X-Inertia': 'true',
            'X-Requested-With': 'XMLHttpRequest',
        });

        const res = http.get(`${BASE_URL}/courses`, {
            headers: inertiaHeaders,
            tags: { name: '02_courses_catalog' },
        });

        check(res, {
            'katalog kelas status 200/304': (r) => r.status === 200 || r.status === 304,
        });

        sleep(3 + Math.random());
    });

    // LANGKAH 3: Search / Filter
    group('03_Course_Search_Filter', function () {
        const categoryId = Math.floor(Math.random() * 4) + 1;
        const apiHeaders = Object.assign({}, BASE_HEADERS, {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        });

        const res = http.get(`${BASE_URL}/api/courses/search?category_id=${categoryId}&search=laravel`, {
            headers: apiHeaders,
            tags: { name: '03_course_search' },
        });

        check(res, {
            'search status 200': (r) => r.status === 200,
            'has pagination object': (r) => r.json('pagination') !== undefined,
        });

        // Test cursor navigation if has_more_pages
        if (res.status === 200) {
            const body = res.json();
            if (body.pagination && body.pagination.has_more_pages && body.pagination.next_cursor) {
                const nextRes = http.get(`${BASE_URL}/api/courses/search?category_id=${categoryId}&search=laravel&cursor=${body.pagination.next_cursor}`, {
                    headers: apiHeaders,
                    tags: { name: '03_course_search_next' },
                });

                check(nextRes, {
                    'cursor next status 200': (r) => r.status === 200,
                    'is different cursor': (r) => r.json('pagination.prev_cursor') !== null,
                });
            }
        }

        sleep(2 + Math.random());
    });

    // LANGKAH 4: Course Detail
    group('04_Course_Detail_View', function () {
        const validSlugs = [
            'web-development-bootcamp',
            'laravel-13-mastery',
            'react-fundamentals',
            'data-science-101',
        ];
        const targetSlug = validSlugs[Math.floor(Math.random() * validSlugs.length)];

        const res = http.get(`${BASE_URL}/courses/${targetSlug}`, {
            headers: BASE_HEADERS,
            tags: { name: '04_course_detail' },
        });

        check(res, {
            'detail kelas 200/304/404': (r) => r.status === 200 || r.status === 304 || r.status === 404,
        });

        sleep(4 + Math.random() * 2);
    });

    // LANGKAH 5: Login Flow with CSRF Cookie
    group('05_Auth_Login_Flow', function () {
        const loginPageRes = http.get(`${BASE_URL}/login`, {
            headers: BASE_HEADERS,
            tags: { name: '05_login_page' },
        });

        check(loginPageRes, {
            'halaman login status 200/304': (r) => r.status === 200 || r.status === 304,
        });

        const csrfToken = getCsrfTokenFromCookie(jar, BASE_URL);

        const loginPayload = JSON.stringify({
            email: `student_${__VU}@drastha.test`,
            password: 'password',
            remember: false,
        });

        const postHeaders = Object.assign({}, BASE_HEADERS, {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken,
        });

        const loginPostRes = http.post(`${BASE_URL}/login`, loginPayload, {
            headers: postHeaders,
            tags: { name: '05_login_attempt' },
        });

        check(loginPostRes, {
            'login response (200/302/422/403)': (r) => r.status === 200 || r.status === 302 || r.status === 422 || r.status === 403,
        });

        sleep(3);
    });
}