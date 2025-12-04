# PHP Advanced Interview Questions (1–100)

## E. Performance, Scaling & Concurrency (66–80)

### 66. High traffic PHP application এ bottleneck detect করার tools?
- **Blackfire**, **Tideways**, **Xdebug profiler**, **New Relic/APM**
- MySQL slow query log, EXPLAIN
- System tools: top, iostat, strace
- Techniques: profiling, log tracing, load testing

### 67. PHP session locking কীভাবে কাজ করে?
- `session_start()` করলে session file এ lock নেয়।
- Lock release না হওয়া পর্যন্ত অন্য concurrent request block থাকে।
- সমস্যা: slow response, deadlock risk
- Fix: `session_write_close()`, Redis session, stateless request

### 68. Long-running worker এ memory leak detection
- `memory_get_usage()` logging
- Periodic restarts (Supervisor `--max-requests`)
- Generators ব্যবহার করা
- Unset static/global references

### 69. Stateless vs Stateful server
- Stateful → local session, sticky load balancer দরকার
- Stateless → JWT, Redis session, easier scaling

### 70. PHP-FPM pm tuning
- **Static**: best performance, high memory
- **Dynamic**: balanced
- **Ondemand**: low traffic, cold start delay
- Tune: `pm.max_children`, `pm.start_servers`

### 71. Caching strategies
- In-process cache → one request scope
- Redis/Memcached → share across servers
- HTTP Cache/CDN → public GET endpoints

### 72. serialize() vs json_encode()
- Performance: JSON faster
- Security: serialize risky (object injection)
- Portability: JSON universal

### 73. Heavy file logging এর bottleneck reduce
- Buffered logs
- Async log queue
- Syslog
- Log rotation

### 74. PHP Stream API large file pattern
```
$h = fopen('big.csv', 'r');
while(($l = fgets($h)) !== false) process($l);
fclose($h);
```

### 75. foreach এর সাথে heavy array operations optimize
- Avoid `array_merge` inside loops
- Use `foreach` with direct assignments
- Generators

### 76. N+1 query detect & fix
- Tools: Debugbar, Tideways
- Fix: Eager load (`with()`), joins

### 77. array_walk vs array_map vs foreach
- foreach → fastest
- array_map → slower, functional
- array_walk → slowest

### 78. Horizontal scaling design
- Sessions → Redis
- Cache → Redis/Memcached
- Files → S3
- DB read replicas

### 79. Async PHP (Swoole/React) challenges
- Blocking calls break event loop
- Library compatibility
- Debugging harder

### 80. Upload-heavy system architecture
- Nginx/XSendfile handles transfer
- PHP only signs URL
- Store files in S3/GCS

---

## F. Security & Best Practices (81–90)

### 81. Prepared statement সর্বদা ১০০% secure?
- Mostly yes, but edge cases → dynamic column/table names, ORDER BY, LIMIT

### 82. htmlspecialchars() / strip_tags() misuse
- htmlspecialchar → output escaping
- strip_tags → bypassable
- Fix: context-aware escaping

### 83. password_hash() vs custom hashing
- password_hash uses bcrypt/argon2 + auto salt
- MD5/SHA1 predictable → broken

### 84. Manual CSRF protection
- Generate token
- Store in session
- Add hidden input
- Validate on POST

### 85. Secure file upload checklist
- MIME check
- Extension whitelist
- Size limit
- Store outside webroot
- Disable execute permission
- Random filename

### 86. Session fixation & prevention
- On login: `session_regenerate_id(true)`
- HttpOnly + SameSite cookies

### 87. unserialize() object injection
- Dangerous if attacker controls payload
- Fix: `json_encode()`, or deny classes

### 88. Output encoding vs input validation
- Primary defence → Output encoding

### 89. display_errors ON leak risk
- DB credentials, stack traces, paths

### 90. JWT/API token common mistakes
- Long expiry
- Store token in localStorage → XSS risk
- No audience/issuer validation

---

## Testing, Tooling & Ecosystem (91–100)

### 91. Testable design principles
- Dependency Injection
- Pure logic
- Small units
- No static-heavy code

### 92. Test doubles
- Stub → return fixed data
- Mock → interaction expectations
- Spy → call history tracking

### 93. Integration vs Feature vs Unit test
- Unit → isolated class
- Feature → controller + service
- Integration → real DB/services

### 94. CI/CD pipeline steps
- Composer install
- Lint
- Static analysis
- PHPUnit
- Build
- Deploy

### 95. Static analysis tools benefits
- Type safety
- Dead code detection
- Bug prevention

### 96. Coding standards tools
- Consistent code style
- Faster code review
- Fewer conflicts

### 97. Dockerized PHP vs XAMPP/Laragon
- Docker → reproducible, team-friendly
- Local stacks → inconsistent

### 98. Legacy PHP upgrade strategy
- Fix deprecated features
- Add tests
- Gradually enable strict types
- Update dependencies

### 99. Monolith → microservice split
- Benefits: scale, independent deploy
- Risks: distributed bugs, network overhead

### 100. Multi-team governance
- Coding standard
- Branching strategy
- PR templates
- Code review rules
- Static analysis CI

# Laravel Top 100 Interview Questions & Answers
**আপডেট: December 3, 2025**

---



