# Drastha LMS - High-Performance Developer Guidelines

## Core Performance Rules (Big O Notation & Database Efficiency)

To maintain response latency below **<100ms** and prevent server crashes on Shared Hosting (1 vCPU, 2GB RAM, 40 PHP Workers) during concurrent usage (e.g. 66+ simultaneous participants):

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
