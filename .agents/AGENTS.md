# Drastha LMS - High-Performance Developer Guidelines

## Core Performance Rules (Big O Notation & Database Efficiency)

To maintain response latency below **<100ms** and prevent server crashes on Shared Hosting (2 vCPU, 3GB RAM, 60 PHP Workers) during concurrent usage (e.g. 80+ simultaneous participants):

### 1. Database Indexing & Query Efficiency ($O(\log N)$ Lookup)

- **Targeted Composite Indexes Only:** Always create composite indexes on multi-column `WHERE` / `JOIN` patterns (e.g., `[user_id, course_id, status]`, `[attempt_id, question_id]`).
- **Avoid Single-Column Over-Indexing:** Do not add redundant single-column indexes on low-cardinality fields (e.g., boolean flags) without context.
- **Foreign Key Indexing:** Ensure every foreign key referenced in high-frequency queries is indexed.

### 2. Elimination of N+1 Queries ($O(1)$ Query Complexity)

- **Strict Eager Loading:** Always eager load relationships using `.with()` or `.withCount()` when accessing relations inside loops or returning Inertia props (e.g., `Course::with(['modules.lessons', 'modules.assessments'])`).
- **No DB Queries Inside Loops:** Never execute Eloquent/Query Builder calls inside `foreach`, `Collection::each()`, or `array_map()`.
- **Batch Pre-fetching:** Pre-fetch user progress, enrollments, or attempt records in batch maps (e.g., `getCompletedModuleAssessmentMap()`) before rendering views.

### 3. Application-Level Setting & Data Caching

- **Cached Settings:** Use `Setting::getValue($key, $default)` instead of querying `Setting::where('key', ...)->value('value')` directly.
- **Cache Invalidation:** Always tie cache keys to model lifecycle events (`saved`, `deleted`, `updated`) so data invalidates automatically when edited in Admin/Instructor panels.

### 4. Asset & Payload Optimization

- Keep JSON payloads delivered via Inertia light by using `.makeHidden()` or selecting only required attributes for student views.

### 5. Check the Rules

- After build a Code, please check the git diff for more contextual information about changing the code.
- Check on Folders `/.agents/workflows/` for more information about rules, prd, erd, and architecture.
- About Role on this project, please check on Folders `/.agents/skills/` for more information about role. Then, you can decide which role to use for build a code.
- **Ziggy Route Registry:** Refer to `/.agents/ziggy_routes_registry.md` for a complete list of named routes available to the frontend. Use this file to quickly find route URIs and names without needing to audit the Laravel router again.
- After adding a new feature or editing code that result in changes to registered Ziggy Routes, documentation for the new route must be added to the `/.agents/ziggy_routes_registry.md` to facilitate project management maintenance.

### 6. Testing & Database Safety

- **Unit/Feature Testing Isolation:** Selalu pastikan bahwa perintah pengujian (unit testing) terisolasi (misalnya menggunakan environment `testing` atau database tersendiri).
- **Dilarang Keras Menghapus Data Dummy:** Saat menjalankan test, hindari _trigger_ `RefreshDatabase` pada database utama pengembangan. Jangan pernah menghapus akun _dummy_ lokal (Admin, Instructor, Student) akibat kelalaian eksekusi test. Selalu periksa konfigurasi `.env` atau `phpunit.xml` sebelum menjalankan test di environment lokal.

### 7. AI Role and Model Routing

This repository uses a role-based multi-model engineering workflow. Before modifying any code, the AI MUST classify the requested task and select the appropriate role from `/.agents/skills/`. The AI MUST read the corresponding SKILL.md before implementing or auditing the task.

- **Frontend Engineering:** role as a Frontend Engineer with following required model ('Gemini 3.6 Flash or Gemini 3.7 Flash or Gemini 38 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/frontend_engineer/SKILL.md`. Use This role for: ('UI, UX implementation, React / Vue / Blade frontend, JavaScript / TypeScript, CSS / Tailwind, Components, Pages, Layouts, Client-side state, Frontend validation, Frontend API integration, Responsive behavior, and Accessibility')
- **Backend Engineering:** role as a Backend Engineer with following required model ('Gemini 3.1 Pro or Gemini 3.8 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/backend_engineer/SKILL.md`. Use This role for: ('Controllers, Services, Repositories, Business logic, APIs, Authentication, Authorization, Backend validation, Laravel/PHP logic, Integrations, Queues, Jobs, Events, Listeners, and Ziggy Route')
- **QA Performance:** role as a QA Performance Implementation with following required model ('Gemini 3.1 Pro or Gemini 3.8 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/qa_performance/SKILL.md`. Use This role for: ('Performance optimization, Profiling, Query optimization, Performance instrumentation, Caching implementation, and Performance regression fixes')
- **Security Auditing:** role as a Security Auditor with following required model on Cline ('Deepseek V4 Pro 0813 or Kimi K3') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/security_auditor/SKILL.md`. Use this role for: ('Security review, Authentication audit, Authorization audit, IDOR, Privilege escalation, Injection vulnerabilities, XSS, CSRF, SSRF, SQL injection, Command injection, Path traversal, Sensitive data exposure, Security regression, and etc')
- **Database Engineering:** role as a Database Engineering with following required model ('Gemini 3.1 Pro or Gemini 3.8 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/database_engineer/SKILL.md`. Use this role for: ('Database review, Database audit, Database implementation, Database migration, Database seeder, Database model, Database relationship, Database constraint, Database index, Database trigger, Database view, Database function, Database procedure, Database constraint, Database validation, Database performance, Database security, Database regression, and etc').
- **Security Implementation:** role as a Security Implementator with following required model ('Gemini 3.1 Pro or Gemini 3.8 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/security_implementation/SKILL.md`. Use this role for: ('Authentication implementation, Authorization implementation, Access control, Rate limiting, Input validation, CSRF protection, Secure headers, Encryption, Security middleware, Security hardening, Ziggy Route Registry').
- **QA Performance Testing:** role as a QA Performance Testing with following required model ('Gemini 3.1 Pro or Gemini 3.8 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/qa_performance_testing/SKILL.md`. Use this role for: ('Performance testing, Performance benchmarking, Load testing, Stress testing, Endurance testing, Performance analysis, Performance reporting, Performance regression testing'). For More information, on k6 grafana testing should be running if the project has suceeded push to github and pull command on SSH Live Server (this option, should be based on manual instruction, please confirmation first to me before execute this test), this is URL for Live Server: ('https://www.drasthalearning.com').
- **QA Performance Auditing:** role as a QA Performance Auditing with following required model ('Gemini 3.1 Pro or Gemini 3.8 Flash') with 'High Reasoning Mode'. The skill location at: `/.agents/skills/qa_performance_auditing/SKILL.md`. Use this role for: ('Performance auditing, Performance regression detection, Performance bottleneck identification, Performance optimization recommendations').

### 8. Mandatory Model Validation

Before executing any implementation or audit task, determine: (TASK, ROLE, CURRENT MODEL, REQUIRED MODEL). The current model MUST match the model assigned to the selected role.

- **Model Mismatch Policy:** If the current model is NOT authorized for the selected role: (STOP immediately, Do not modify any project files, Do not create partial implementation, Do not attempt to compensate using another model, Inform the user of the model mismatch, Specify the required model, Require the user to switch to the correct model, Resume only after the correct model is active). Example: MODEL MISMATCH -> Requested task: (Build the frontend dashboard) -> Selected role: (frontend_engineer) -> Current model: (Gemini 3.1 Pro or Gemini 3.8 Flash) -> Required model: (Gemini 3.6 Flash OR Gemini 3.7 Flash High Reasoning) -> No files will be modified. -> Please switch to the required model before continuing.

### 9. Task Classification Rules

The AI MUST classify the task before implementation.

- **Frontend Task:** Implementation or modification of UI, components, pages, JavaScript, TypeScript, CSS, Tailwind, client-side state, frontend behavior. -> ROLE: frontend_engineer
- **Backend Task:** Implementation or modification of PHP, Controllers, Services, Repositories, API, business logic, queues, jobs, backend integrations, Ziggy Routes. -> ROLE: backend_engineer
- **QA Performance Task:** Optimization, profiling, query tuning, or caching improvements. -> ROLE: qa_performance
- **Security Audit Task:** Security audit, vulnerability fixes, or security hardening. -> ROLE: security_auditor
- **Security Implementation Task:** Security implementation, authentication implementation, authorization implementation, access control, rate limiting, input validation, CSRF protection, secure headers, encryption, security middleware, security hardening. -> ROLE: security_implementation
- **QA Performance Testing Task:** Performance testing, performance benchmarking, load testing, stress testing, endurance testing, performance analysis, performance reporting, performance regression testing. -> ROLE: qa_performance_testing
- **QA Performance Auditing Task:** Performance auditing, performance regression detection, performance bottleneck identification, performance optimization recommendations. -> ROLE: qa_performance_auditing
- **Database Engineering Task:** Database review, database audit, database implementation, database migration, database seeder, database model, database relationship, database constraint, database index, database trigger, database view, database function, database procedure, database constraint, database validation, database performance, database security, database regression, and etc. -> ROLE: database_engineer

### 10. Multi-role Tasks

A task may require multiple roles. The AI MUST decompose the task instead of forcing one model to perform every responsibility. Example: ('Create a course management system. Frontend → frontend_engineer → Gemini 3.6 Flash / Gemini 3.7 Flash / Gemini 3.8 Flash High Reasoning Backend → backend_engineer → Gemini 3.1 Pro or Gemini 3.8 Flash with High Reasoning Mode Database → database_engineer → Gemini 3.1 Pro or Gemini 3.8 Flash with High Reasoning Mode Security → security_implementator → Gemini 3.1 Pro or Gemini 3.8 Flash with High Reasoning Mode Performance Testing → qa_performance_testing → Gemini 3.1 Pro or Gemini 3.8 Flash with High Reasoning Mode Security Audit → security_auditor → DeepSeek V4 Pro 0813 OR Kimi K3 Performance Audit → qa_performance_auditor → DeepSeek V4 Pro 0813 OR Kimi K3'). Each role MUST follow its own `SKILL.md`.

### 11. Implementation and Audit Independence

The implementation model SHOULD NOT be the sole auditor of its own work. Preferred workflow: (Implementation -> Automated Testing -> Independent Audit -> Finding -> Fix -> Testing -> Re-Audit). Security audits MUST use: (security_auditor), Performance audits MUST use: (qa_performance_auditor). The auditor MUST not assume that passing tests means the implementation is defect-free.

### 12. AI QA Workflow

For feature-level changes, use: (Requirement -> Task Classification -> Role Selection -> Model Validation -> Implementation -> PHPUnit / Playwright / Relevant Tests -> AI Audit -> Finding Classification -> Fix -> Retest -> Re-Audit -> PASS). Maximum automated fix/retest cycles: 3. If the system fails to reach a clean audit result after 3 cycles: (HUMAN_REVIEW_REQUIRED). The AI MUST NOT enter an infinite repair loop.
