# Advanced PHP Interview Questions (OOP, Design Patterns, Security, Performance)

Below is the complete **Markdown (.md)** version containing **Questions + Answers + Explanations**.

---

# **1. OOP এবং ডিজাইন প্যাটার্ন (OOP and Design Patterns)**

## **Traits কী এবং কখন ব্যবহার করবেন?**
**Answer:**
Traits হলো PHP-তে horizontal code reuse করার মেকানিজম, যা multiple classes-এ common method set inject করতে সাহায্য করে। এটি সিঙ্গেল ইনহেরিট্যান্স সীমাবদ্ধতা দূর করে।

**Explain:**
- একটি ক্লাস শুধু একটিই parent class extend করতে পারে, কিন্তু অনেক Trait ব্যবহার করতে পারে।
- Logging, Authorization, Caching-এর মতো shared functionality বিভিন্ন unrelated class-এ দরকার হলে trait খুবই কার্যকর।

---

## **Abstract Class এবং Interface-এর মধ্যে পার্থক্য কী?**
**Answer:**
- **Interface:** শুধুমাত্র method signatures থাকে, কোনো implementation বা property থাকে না।
- **Abstract Class:** আংশিক implement করা class, যেখানে abstract + concrete method এবং properties থাকতে পারে।

**Explain:**
- Interface একটি contract; class-কে methods implement করতে বাধ্য করে।
- Abstract class একটি base class; shared logic define করা যায়।
- PHP-তে multiple interface implement করা যায়, কিন্তু একটিই abstract class extend করা যায়।

---

## **PHP-তে `final` কীওয়ার্ডের ব্যবহার**
**Answer:**
- `final class`: ক্লাস extend করা যাবে না।
- `final method`: চাইল্ড ক্লাস override করতে পারবে না।

**Explain:**
যেখানে behavior overriding রোধ করতে চান, যেমন security বা সুনির্দিষ্ট behaviour maintain করা দরকার।

---

## **SOLID Principles ব্যাখ্যা করুন।**
**Answer & Explain:**
### **S – Single Responsibility Principle**
একটি class-এর পরিবর্তনের শুধুমাত্র একটি reason থাকা উচিত।

### **O – Open/Closed Principle**
Class extension-এর জন্য open, modification-এর জন্য closed হওয়া উচিত।

### **L – Liskov Substitution Principle**
Child class parent class-এর মতো আচরণ করতে পারতে হবে।

### **I – Interface Segregation Principle**
Client-কে এমন interface implement করতে বাধ্য করা যাবে না যা সে ব্যবহার করে না।

### **D – Dependency Inversion Principle**
High-level module abstraction-এর ওপর depend করবে, concrete implementation-এর ওপর নয়।

---

## **Dependency Injection (DI) কী? কেন ব্যবহার করা হয়?**
**Answer:**
Dependency Injection হলো design pattern যেখানে object dependency বাইরে থেকে প্রদান করা হয় (constructor/setter/inversion container)।

**Explain:**
- কোড loosely coupled হয়
- Unit testing সহজ হয়
- DIP (Dependency Inversion Principle) follow হয়

---

# **2. Modern PHP এবং Performance**

## **Composer কী এবং কেন গুরুত্বপূর্ণ?**
**Answer:**
Composer হলো PHP dependency manager, যেটি project-এর libraries manage করে এবং autoloading সক্ষম করে।

**Explain:**
- PSR-4 autoloading
- Version conflict management
- Modern PHP ecosystem (Laravel, Symfony ইত্যাদি) Composer-এর ওপর চলে

---

## **PHP Namespaces কী? কেন দরকার?**
**Answer:**
Namespaces কোডকে logical group-এ ভাগ করে name collision প্রতিরোধ করে।

**Explain:**
বড় project-এ একই নামের class থাকতে পারে — namespace তাদের আলাদা করে।

---

## **Autoloading কীভাবে কাজ করে?**
**Answer:**
PHP class first-time use হলে autoloader সেই class-এর namespace অনুযায়ী ফাইল খুঁজে load করে।

**Explain:**
- `composer.json`-এ namespace → folder map
- PSR-4 standard
- manual require/include-এর প্রয়োজন হয় না

---

## **PHP Performance optimization techniques**
**Answer:**
- OPcache enable করা
- Efficient SQL queries + indexing
- Redis/Memcached cache
- Gzip compression + minified assets
- Profiling tools (Blackfire, Xdebug)

**Explain:**
Performance tuning মূলত CPU, Database, Memory এবং IO optimization নিয়ে কাজ করে।

---

## **Generators কী? কেন memory-efficient?**
**Answer:**
Generators yield ব্যবহার করে একে একে value প্রদান করে।

**Explain:**
- পুরো dataset মেমরিতে লোড না করে lazily values produce করে
- বড় CSV, log file বা API result iterate করলে memory বাঁচে

---

# **3. সিকিউরিটি এবং ডেটাবেস**

## **SQL Injection প্রতিরোধ**
**Answer:**
Prepared statement (PDO/MySQLi) ব্যবহার করতে হবে।

**Explain:**
User input SQL থেকে আলাদা থাকে, তাই malicious code execute হয় না।

---

## **XSS প্রতিরোধ**
**Answer:**
Output escaping (`htmlspecialchars`) এবং CSP policy ব্যবহার করা।

**Explain:**
Input script HTML হিসেবে execute হওয়া রোধ করে।

---

## **Password Hashing — Best Function**
**Answer:**
`password_hash()` + `password_verify()`

**Explain:**
- Bcrypt/Argon2 automatic
- Unique salt
- Upgradeable hashing cost

---

## **PDO কী এবং MySQLi থেকে ভালো কেন?**
**Answer:**
PDO হলো database abstraction layer।

**Explain:**
- Same code → different databases
- Secure prepared statement default
- OOP structured API

---

## **CSRF কী? কিভাবে প্রতিরোধ করবেন?**
**Answer:**
Cross-site request forgery token ব্যবহার করতে হবে।

**Explain:**
Server-generated token form submission-এর সময় verify করে, unauthorized request প্রতিরোধ করে।

---

# **4. Advanced Features & Concepts**

## **Closure (Anonymous Function) কী?**
**Answer:**
Name-less function যা outer scope-এর variable access করতে পারে।

**Explain:**
Sorting, filtering, callback—এসব operation-এ বেশি ব্যবহার হয়।

---

## **Magic Methods কী?**
**Answer:**
`__construct`, `__get`, `__set`, `__call`, `__toString`—এগুলো special events-এ auto call হয়।

**Explain:**
- `__construct()`: object তৈরি হলে
- `__toString()`: object কে string হিসেবে print করলে

---

## **PHP Request-Response Cycle**
**Answer & Explain:**
1. Client browser HTTP request পাঠায়
2. Web server (Apache/Nginx) request PHP engine-এ পাঠায়
3. PHP script parse → compile → opcode execute
4. Database query execute
5. Output (HTML/JSON) generate
6. Server response client-এ পাঠায়

---

# B. OOP, Design & Architecture (16–35)

## 17. PHP তে Late Static Binding — `self::` vs `static::`
### **Late Static Binding কী?**
Inheritance‑এ child class থেকে parent‑এর static method/property reference করলে `self::` parent কন্টেক্সট ধরে, কিন্তু `static::` runtime‑এ যে class call করছে তার context ধরে।

### **উদাহরণ**
```php
class A {
    public static function who() {
        echo __CLASS__;
    }

    public static function test() {
        self::who();     // A
        static::who();   // Late static binding → C
    }
}

class B extends A {
    public static function who() {
        echo __CLASS__;
    }
}

class C extends B {}

C::test();
```
### **Output**
- `self::who()` → **A** (parent context)
- `static::who()` → **C** (runtime context)

### **Conclusion**
- `self::` = compile time binding
- `static::` = runtime (late-static) binding

---

## 18. Trait — সুবিধা ও সমস্যা
### **Trait কী?**
Multiple inheritance না থাকা PHP‑তে horizontal code reuse দেয়।
```php
trait Logger {
    public function log($msg) { echo $msg; }
}
```

### **Problems created by Traits**
- Hidden dependency — কোন class কি দিচ্ছে বোঝা কঠিন
- Method conflict / name collision
- Behavior scattering → SRP ভেঙে যেতে পারে
- Harder to test, harder to mock
- Architecture‑এ consistency কমে

---

## 19. Abstract Class vs Interface — কখন কোনটা
### **Abstract Class**
- Partial implementation থাকে
- Shared properties + methods
- Use when: common base behavior দরকার

### **Interface**
- Only contract
- No property (PHP 8.1-এ readonly property allowed via `public readonly`)
- Multiple inheritance possible

### **Real Example**
- Abstract `PaymentGateway` → shared validation, HTTP client
- Interface `Payable` → যে class payment নেবে সে শুধু contract follow করবে

---

## 20. SOLID — SRP এবং DIP
### **Single Responsibility Principle**
```php
class InvoicePrinter {
   public function print($invoice) {}
}

class InvoiceRepository {
   public function save($invoice) {}
}
```
একটি class একটিই responsibility নেবে।

### **Dependency Inversion Principle**
```php
interface Mailer {
   public function send($msg);
}

class SmtpMailer implements Mailer {}

class NotificationService {
   public function __construct(private Mailer $mailer){}
}
```
High-level code depends on abstraction.

---

## 21. Magic methods overuse করার সমস্যা
- Debugging কঠিন হয়
- Performance কমে (dynamic call)
- Predictability নষ্ট হয়: কে call হচ্ছে বোঝা যায় না
- IDE autocomplete কাজ কম করে

---

## 22. Service Container / IoC Container কীভাবে কাজ করে
**Basic idea:** abstraction অনুযায়ী object resolve করা (automatic dependency injection)।

### **Generic Example**
```php
$container->bind(Mailer::class, SmtpMailer::class);
$mailer = $container->make(Mailer::class);
```

Benefits:
- decoupling
- unit testing friendly
- central lifecycle management

---

## 23. Repository vs Service Layer
### **Repository**
- Database interaction abstraction
- Focus: persistence

### **Service Layer**
- Business logic orchestrate করা
- Repository + Domain logic merge করে outcome তৈরি করা

Use both when project becomes large.

---

## 24. Value Object vs Entity (DDD)
### **Entity** — identity‐based
```php
class User {
    public function __construct(public int $id, public string $name){}
}
```

### **Value Object** — value‑based
```php
class Money {
    public function __construct(private int $amount){}
}
```
Value object immutable ও equality by value.

---

## 25. Immutability in PHP
- Properties private
- No setters
- `withX()` method new instance return

```php
class Address {
    public function __construct(private string $city){}

    public function withCity($new) {
        return new self($new);
    }
}
```

---

## 26. Static Utility Class vs Proper OOP
### Utility Class Problem
- No polymorphism
- Hard to test
- Global‑state like behavior

### Proper OOP
- Strategies, dependency injection, testability

---

## 27. Strategy vs State Pattern
### Strategy
- Algorithm interchangeable
```php
interface SortStrategy { public function sort($data); }
```

### State
- Object behavior changes depending on internal state
```php
class Order {
    private $state;
    public function setState(State $state){ $this->state = $state; }
}
```

---

## 28. Adapter vs Decorator vs Proxy
### Adapter
- Interface পরিবর্তন করে অন্য system compatible করা

### Decorator
- Behavior add/remove dynamically

### Proxy
- Access control (lazy load, cache)

---

## 29. Circular Dependency Detection/Solution
- Static analysis tools (PHPStan, Psalm)
- Refactor → interfaces, events, domain separation
- Break dependency graph via ports/adapters

---

## 30. Aggregate Root (DDD)
Aggregate root = entity controlling invariants inside an aggregate.
```php
class Order { // root
    private array $items;
    public function addItem(Product $p){}
}
```

---

## 31. Object Hydration (without ORM)
- Reflection
- Named constructors
- Mapper class
```php
class UserHydrator {
   public function fromArray($data) {}
}
```

---

## 32. PHP Cloning — deep vs shallow
```php
class A {
    public $obj;
    public function __clone() {
        $this->obj = clone $this->obj; // deep copy
    }
}
```

---

## 33. Exception Hierarchy Design
- DomainException
- ValidationException
- InfrastructureException
- RepositoryException

Use granular classes.

---

## 34. Domain Events / Event Sourcing
- Event classes (OrderPlaced)
- Event dispatcher
- Projectors to rebuild state

---

## 35. Fat Model vs Thin Model vs Service‑Heavy
### Fat Model
+ easy start
− messy later

### Thin Model + Services
+ scalable, testable
− many classes

### Service‑Heavy
+ domain logic isolated

---

## 36. Modular/Hexagonal Migration Strategy
- Break monolith into modules
- Introduce ports & adapters
- Extract domain logic
- Replace direct calls with interfaces
- Gradually separate infrastructure


# PHP Namespaces, Autoloading, Composer & PSR Standards (Q&A with Explanations)

## 36. PHP namespaces কীভাবে কাজ করে? Global namespace vs sub-namespace example
### **Answer**
Namespace হলো PHP-তে class/function/constant গুলিকে logical ভাবে group করা এবং নামের সংঘর্ষ (name collision) প্রতিরোধ করার একটি mechanism।

- **Global namespace**: যেখানে কোনো namespace ঘোষণা নেই।
- **Sub-namespace**: Nested structure যেখানে `App\Controllers`, `App\Models` ইত্যাদি থাকে।

### **Example**
```php
// global namespace
class User {}

// sub namespace
namespace App\Models;
class User {}
```
এখন `App\Models\User` এবং `\User` আলাদা entity।

---

## 37. Composer এর PSR-4 autoloading কীভাবে কাজ করে? (Internal map creation)
### **Answer**
PSR‑4 autoloading class name থেকে namespace prefix + directory map ব্যবহার করে ফাইল locate করে।

### **Step-by-step (“police tracing the suspect”) ব্যাখ্যা**
1. Composer `composer.json` থেকে `autoload.psr-4` পড়ে
2. Namespace prefix → folder map তৈরি করে
3. যখন code এ `new App\Controllers\HomeController` কল হয়:
   - Composer prefix match করে: `App\\` → `app/`
   - বাকি class path যোগ করে: `Controllers/HomeController.php`
   - সম্পূর্ণ path: `app/Controllers/HomeController.php`
4. Autoloaded class map cache (`vendor/composer/autoload_static.php`) তৈরি হয়
5. Composer file load করে class define করে

---

## 38. PSR-1, PSR-2/PSR-12 coding standard এর মূল原则
### **PSR‑1 (Basic Coding Standard)**
- Files MUST use UTF‑8
- Classes must follow **StudlyCase**
- Methods must follow **camelCase**
- Constants must be **UPPER_CASE**

### **PSR‑12 (extended PSR‑2)**
- 4 spaces indent
- Opening braces on the next line
- One class per file
- Line length ideally ≤ 120
- Strict namespace + use ordering

---

## 39. PSR-3 (Logger Interface) কিভাবে reusable logging solution design করতে সাহায্য করে?
### **Answer**
PSR‑3 একটি standard logger interface দেয়: `Psr\Log\LoggerInterface`।

এটির মাধ্যমে:
- Framework পরিবর্তন করলেও logging code একই থাকে
- `error()`, `info()`, `debug()` ইত্যাদি common method থাকে
- Different logger (Monolog, custom logger) সহজেই swap করা যায়

**Benefits:** decoupled, testable, reusable logging system।

---

## 40. PSR‑4 vs Classmap autoloading (performance/use case)
### **PSR‑4**
- Namespace → directory mapping
- Development friendly
- File scan করে runtime map তৈরি করে
- Large projects = slower

### **Classmap**
- Composer পুরো codebase scan করে exact map বানায়
- Fastest autoloading
- Production build এর জন্য best

### **Use case**
- PSR‑4 → development
- Classmap → production optimization

---

## 41. Composer dump-autoload কমান্ড কী করে? -o flag মানে কী?
### **dump-autoload**
- Autoload map regenerate
- নতুন class যোগ করলে Composer কে update করে

### **`-o` (optimize)**
- PSR-4 scan করে classmap তৈরি করে
- Faster production autoload

---

## 42. composer.lock ফাইলের গুরুত্ব
### **Answer**
- সমস্ত dependency এর exact version lock করে
- Production environment এ same version ensure করে
- Deployment stability ও reproducibility বজায় রাখে

**মোট কথা:** commit না করলে production এ আলাদা version চলে যাওয়ার ঝুঁকি।

---

## 43. Version constraint: ^1.2, ~1.2, 1.*, >=1.2 <2.0
### **Meaning**
- `^1.2` → `>=1.2 && <2.0` (backwards compatible updates)
- `~1.2` → `>=1.2 && <2.0` কিন্তু patch-level বেশি flexible
- `1.*` → সব `1.x`
- `>=1.2 <2.0` → manually defined constraint

---

## 44. Private packages (Git repo, Satis, Packagist alternatives) Composer দিয়ে manage
### **Methods**
1. **Private Git Repo** (GitHub/GitLab/Bitbucket)
   ```json
   "repositories": [{
       "type": "vcs",
       "url": "git@github.com:company/package.git"
   }]
   ```

2. **Satis** (self-hosted Packagist clone)
3. **Private Packagist** service

Private auth token ব্যবহার করে Composer download করে।

---

## 45. Large monorepo তে multiple Composer package manage (path repo/workspaces)
### **Approaches**
- **Path repositories**
   ```json
   "repositories": [{
       "type": "path",
       "url": "packages/*"
   }]
   ```

- **Composer workspaces/monorepo tools**
  - Symfony Flex
  - Laravel "workbench" (Laravel 11)
  - Yarn/npm-style workspace structure

- **Advantages**
  - Local development → instant update
  - Version sync সহজ

---

## 46. Namespaced function এবং constant declare করা যায় – কিভাবে?
### **Example**
```php
namespace App\Helpers;

function clean_text($str) {
    return trim($str);
}

const APP_VERSION = '1.0.0';
```

**কারণ:** global helper pollution এড়াতে এবং autoloadable function তৈরি করতে।

---

## 47. Autoloading order bug তৈরি করতে পারে – কোন scenario তে?
### **Scenarios**
- একই নামের class দুই স্থানে থাকলে
- Classmap override করে PSR‑4 path
- Vendor autoload → manual autoload → multiple autoloader conflict

---

## 48. Composer এর autoload-dev এর ব্যবহার
### **Answer**
- Development dependencies (PHPUnit, Faker, Tools, Seeders)
- Production autoload এর মধ্যে অন্তর্ভুক্ত হয় না
- Deployment package ছোট হয়

---

## 49. Multiple autoloaders থাকলে order কীভাবে কাজ করে?
### **Answer**
- PHP SPL autoload stack এ FIFO order
- প্রথম autoloader class resolve করলে বাকি গুলো call হয় না
- ভুল order → class not found অথবা wrong class load

---

## 50. Namespaced global helper function design best practice
- Conflict এড়াতে proper namespace ব্যবহার
- Single-responsibility function
- Composer autoload ব্যবহার
- Avoid global `function_exists()` hacks
- Clear naming: `Text\clean()`, `Arr\flatten()` এর মতো

---

**End of Document**

