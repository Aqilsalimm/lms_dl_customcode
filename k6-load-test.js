import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 20 }, // Ramp up to 20 users over 30s
    { duration: '1m', target: 100 }, // Ramp up to 100 users over 1 minute
    { duration: '2m', target: 100 }, // Stay at 100 users for 2 minutes
    { duration: '30s', target: 0 },  // Ramp down to 0 users
  ],
  thresholds: {
    // 95% of requests must complete below 200ms
    http_req_duration: ['p(95)<200'], 
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // 1. Visit homepage
  let res = http.get(`${BASE_URL}/`);
  
  check(res, {
    'homepage status is 200': (r) => r.status === 200,
  });
  
  // Simulate user reading the page
  sleep(1);

  // 2. Visit login page
  let loginRes = http.get(`${BASE_URL}/login`);
  
  check(loginRes, {
    'login page status is 200': (r) => r.status === 200,
  });

  // Simulate user typing credentials
  sleep(5);
}
