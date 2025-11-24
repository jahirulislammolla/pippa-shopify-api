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
