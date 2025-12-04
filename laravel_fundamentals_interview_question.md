## A. Laravel Fundamentals (1–20)

### 1. Laravel কী এবং এর মূল সুবিধা কী?

**উত্তর:**
Laravel হল PHP এর একটি modern, elegant web framework যা MVC architecture অনুসরণ করে। এটি Taylor Otwell দ্বারা তৈরি এবং বর্তমানে industry এর সবচেয়ে জনপ্রিয় framework।

**মূল সুবিধা:**
- Eloquent ORM (Beautiful database query builder)
- Blade Templating Engine
- Built-in Authentication & Authorization
- Migration & Seeding System
- Service Container & Dependency Injection
- Artisan CLI Tool
- Queue & Event System
- Testing Infrastructure

**উদাহরণ:**
````php
// Traditional PHP
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Laravel
$user = User::where('email', $email)->first();<?php
````
### 2. Laravel Service Container কী এবং কেন important?
**উত্তর:**

Service Container হল Laravel এর core component যা IoC (Inversion of Control) এবং Dependency Injection implement করে।

**গুরুত্ব:**

Loose coupling maintain করা
Testing এর জন্য dependencies mock করা সহজ
Code reusability বৃদ্ধি
Application এর scalability improve হয়

কীভাবে কাজ করে:
````php
// Service Container registration
app()->bind('PaymentGateway', function($app) {
    return new StripePaymentGateway();
});

// Resolving from container
$gateway = app('PaymentGateway');

// Or with dependency injection
public function processPayment(PaymentGateway $gateway) {
    $gateway->charge($amount);
}
````


### 3. Laravel এর Service Provider কী?
**উত্তর:**
Service Provider হল Laravel application এর "bootstrapping" center। এখানে services, bindings, event listeners register করা হয়।

কখন register vs boot ব্যবহার হয়:

register() → Container bindings
boot() → Other services এর উপর নির্ভর করে

**Structure:**

````php

<?php
// app/Providers/MyServiceProvider.php
class MyServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register bindings to container
        $this->app->bind('EmailService', function($app) {
            return new EmailService(
                config('mail.driver'),
                config('mail.from')
            );
        });
    }

    public function boot()
    {
        // Application already booted, register listeners
        Event::listen('user.registered', function($event) {
            Mail::send(new UserWelcomeMail($event->user));
        });
    }
}
````
### 4. Laravel এ Routing কীভাবে কাজ করে?
উত্তর:
Routes HTTP requests কে controllers এ map করে।

Route resolution process:

Request আসে → Routes load হয়
Matching route খোঁজা হয়
Controller + Method call হয়
Response return হয়

বিভিন্ন routing pattern:
````php
<?php
// Basic route
Route::get('/users', [UserController::class, 'index']);

// Route parameter
Route::get('/users/{id}', [UserController::class, 'show']);

// Named route
Route::get('/profile', [UserController::class, 'profile'])->name('profile');

// Route group with middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Resource routes (REST)
Route::resource('posts', PostController::class);
// Generates: GET/POST/PUT/DELETE/PATCH automatically

// API routes
Route::apiResource('api/products', ProductController::class);
````

### 5. Laravel Controller এবং Request lifecycle?
উত্তর:
Controller হল business logic holder এবং request handling করে।
Request Lifecycle:

public/index.php → Entry point
Service providers register
Request HTTP kernel এ যায়
Middleware stack execute
Route matching
Controller method call
Response generate
Middleware এর return phase
Response send
````php
<?php
// app/Http/Controllers/PostController.php
class PostController extends Controller
{
    public function store(StorePostRequest $request)
    {
        // Request validation automatically happens in StorePostRequest
        
        $post = Post::create([
            'title' => $request->validated()['title'],
            'content' => $request->validated()['content'],
            'user_id' => auth()->id()
        ]);

        return redirect()->route('posts.show', $post)->with('success', 'Post created!');
    }
}

// app/Http/Requests/StorePostRequest.php
class StorePostRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10'
        ];
    }
}
````

### 6. Laravel এ Middleware কী?
উত্তর:
Middleware হল HTTP request/response filtration layer।

Custom Middleware তৈরি:

````php
<?php
// app/Http/Middleware/CheckAdmin.php
class CheckAdmin
{
    public function handle($request, Closure $next)
    {
        // Before request processing
        if (!auth()->user()->is_admin) {
            return redirect('/')->with('error', 'Unauthorized');
        }

        $response = $next($request);

        // After request processing
        $response->header('X-Admin-Check', 'passed');

        return $response;
    }
}

// app/Http/Kernel.php
protected $routeMiddleware = [
    'admin' => \App\Http\Middleware\CheckAdmin::class,
];

// [web.php](http://_vscodecontentref_/0)
Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
});
````

Middleware execution order:
````javascript
Request → Global Middleware → Route Middleware → Controller
Response ← Global Middleware ← Route Middleware ← Controller
````


### 7. Laravel Eloquent ORM কী?
উত্তর:
Eloquent হল Laravel এর ActiveRecord implementation যা DB interactions কে object-oriented করে।

Query Builder vs Eloquent:

Query Builder → SQL queries
Eloquent → Object-oriented, relationships

মডেল তৈরি:
````php
<?php
// app/Models/User.php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password']; // JSON response থেকে hide
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Eloquent query
$user = User::find(1);
$user->update(['name' => 'John Doe']);

$users = User::where('active', true)->get();
$users = User::where('age', '>=', 18)->limit(10)->get();

// Relationship loading
$user->posts()->get(); // All posts
$user->posts; // Lazy loading (not recommended)
$users = User::with('posts')->get(); // Eager loading ✓
````

### 8. Laravel এ Relationships কী কী?
উত্তর:
Eloquent relationships হল model এর মধ্যে associations।

Four main relationships:
````php
<?php
// 1. One-To-Many (Post -> Comments)
class Post extends Model {
    public function comments() {
        return $this->hasMany(Comment::class);
    }
}

// Usage:
$post = Post::with('comments')->find(1);
$post->comments; // All comments

// 2. Many-To-Many (User <-> Role)
class User extends Model {
    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}

$user->roles()->attach([1, 2, 3]); // Add roles
$user->roles()->detach([1]); // Remove role

// 3. One-To-One (User -> Profile)
class User extends Model {
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}

// 4. Polymorphic (Post/Comment can have like from User)
class Like extends Model {
    public function likeable() {
        return $this->morphTo();
    }
}

class Post extends Model {
    public function likes() {
        return $this->morphMany(Like::class, 'likeable');
    }
}


### N+1 Query Problem & Solution:

<?php
// ❌ Bad - N+1 queries
foreach(Post::all() as $post) {
    echo $post->user->name; // Query হবে প্রতিটি post এর জন্য
}

// ✓ Good - Eager loading
$posts = Post::with('user')->get(); // 2 queries শুধু
foreach($posts as $post) {
    echo $post->user->name;
}

````

### 9. Laravel Migration এবং Schema কী?
উত্তর:
Migration হল version control for database schema।

Migration তৈরি:

````php
php artisan make:migration create_posts_table
php artisan make:migration add_status_to_posts_table

<?php
// database/migrations/2025_12_03_create_posts_table.php
class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('published_at')->nullable();
            $table->timestamps(); // created_at, updated_at
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
}


php artisan migrate              # Run migrations
php artisan migrate:rollback     # Undo last batch
php artisan migrate:refresh      # Reset & run all
php artisan migrate:reset        # Undo all
````

### 10. Laravel Validation কীভাবে কাজ করে?
উত্তর:
Validation ensure করে যে user input সঠিক format এ আছে।


Common validation rules:

required, nullable, filled
email, url, ip
min:value, max:value, between:min,max
unique:table,column
regex:/pattern/
confirmed (password_confirmation field থাকতে হবে)
custom rules বানানো যায়

তিনটি উপায়:
````php
<?php
// 1. Request validation
class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'age' => 'required|integer|between:18,99',
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Email ইতিমধ্যে registered আছে।',
        ];
    }
}

// 2. Manual validation
$validated = $request->validate([
    'name' => 'required|string',
    'email' => 'required|email',
]);

// 3. Validator facade
$validator = Validator::make($request->all(), [
    'name' => 'required',
]);

if ($validator->fails()) {
    return back()->withErrors($validator)->withInput();
}

````

### 11. Laravel এ Authentication কীভাবে implement করা হয়?
উত্তর:
Laravel built-in authentication system provide করে।

# Scaffolding generate
````php
composer require laravel/breeze --dev
php artisan breeze:install
````

**Manual authentication:**
````php
// Login route
Route::post('/login', function(Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'Invalid credentials.',
    ]);
});

// Check authentication
if (Auth::check()) {
    $user = Auth::user(); // Current user
}

// Logout
Auth::logout();
$request->session()->invalidate();


Authorization (Policy):

<?php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function update(User $user, Post $post)
    {
        return $user->id === $post->user_id;
    }
}

// In controller
$this->authorize('update', $post); // Policy check

// In blade
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan
````

### 12. Laravel Blade Templating Engine?
উত্তর:
Blade হল Laravel এর powerful templating engine যা PHP কে সহজ করে।

Blade security:

````php
{{ }} → auto escaping করে (XSS protection)
{!! !!} → raw output (careful!)

<!-- resources/views/posts/index.blade.php -->

@extends('layouts.app')

@section('content')
    <h1>Posts</h1>

    @forelse($posts as $post)
        <div class="post">
            <h2>{{ $post->title }}</h2>
            <p>{{ $post->excerpt }}</p>
            
            @if($post->is_published)
                <span class="badge">Published</span>
            @else
                <span class="badge">Draft</span>
            @endif

            @foreach($post->tags as $tag)
                <span class="tag">{{ $tag->name }}</span>
            @endforeach
        </div>
    @empty
        <p>No posts found.</p>
    @endforelse

    <!-- Conditions -->
    @unless($user->is_admin) <!-- @unless = opposite of @if -->
        <p>You don't have admin access.</p>
    @endunless

    <!-- Component -->
    <x-alert type="success">{{ $message }}</x-alert>

    <!-- Raw PHP -->
    @php
        $total = $posts->count();
    @endphp
@endsection
```

### 13. Laravel Service Container এ binding এর different উপায়?
উত্তর:
Service Container এ services register করার বিভিন্ন উপায় আছে।
Binding vs Singleton:

Binding → নতুন instance প্রতিবার
Singleton → একই instance সর্বদা

````php
<?php
// 1. Bind (Simple binding)
$this->app->bind('PaymentGateway', function($app) {
    return new StripePaymentGateway();
});

// Usage: $gateway = app('PaymentGateway');

// 2. Singleton (Create once, reuse)
$this->app->singleton('Logger', function($app) {
    return new MonoLogger();
});

// Usage: same instance every time

// 3. Instance (Already created object)
$logger = new Logger();
$this->app->instance('Logger', $logger);

// 4. Contextual binding (Different implementations)
$this->app->bind('Repository', function($app) {
    return new MySQLRepository();
});

$this->app->when(Controller::class)
    ->needs('Repository')
    ->give(function() {
        return new CacheRepository();
    });

// 5. Auto-wiring (Type hints)
class UserController
{
    public function __construct(UserRepository $repo) 
    {
        // Auto-resolved from container
    }
}
````
### 14. Laravel এ Events এবং Listeners?
উত্তর:
Events হল application এর বিভিন্ন অংশের মধ্যে loose coupling।

Events benefit:

Loose coupling
Single Responsibility
Testable
Async processing possible

Event তৈরি:

````php
<?php
// app/Events/UserRegistered.php
class UserRegistered
{
    public function __construct(public User $user) {}
}

// app/Listeners/SendWelcomeEmail.php
class SendWelcomeEmail
{
    public function handle(UserRegistered $event)
    {
        Mail::send(new UserWelcomeMail($event->user));
    }
}

// app/Providers/EventServiceProvider.php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeEmail::class,
        NotifyAdminAboutNewUser::class,
    ],
];

// Dispatch event
event(new UserRegistered($user)); // Or Event::dispatch(...)
````

### 15. Laravel Queue System কী?
উত্তর:
Queue হল time-consuming tasks asynchronously handle করার system।

php artisan queue:table
php artisan migrate
```

**Job তৈরি:**
````bash
php artisan make:job SendEmailNotification
```

````php
// app/Jobs/SendEmailNotification.php
class SendEmailNotification implements ShouldQueue
{
    public function __construct(public User $user) {}

    public function handle()
    {
        Mail::send(new NotificationMail($this->user));
    }

    // Retry logic
    public function retryAfter()
    {
        return now()->addMinutes(5);
    }

    public function maxExceptions()
    {
        return 3; // Max retry
    }
}

// Dispatch job
SendEmailNotification::dispatch($user); // Background
SendEmailNotification::dispatchSync($user); // Immediate

// Process queue
php artisan queue:work
```

**Queue drivers:**
- **sync** → Synchronous (testing)
- **database** → DB তে store
- **redis** → Fast, distributed
- **sqs** → AWS

---
````
### 16. Laravel에서 Caching কীভাবে use করা হয়?

**উত্তর:**
Caching performance improve করে expensive operations avoid করে।

Cache drivers:

file → Filesystem (development)
redis → Fast, distributed
memcached → High-performance
database → DB (slow)

````php
// Simple caching
$posts = Cache::remember('all_posts', 3600, function() {
    return Post::all();
});

// Get or forever
$value = Cache::rememberForever('key', function() {
    return 'value';
});

// Manual cache
if (Cache::has('users')) {
    $users = Cache::get('users');
} else {
    $users = User::all();
    Cache::put('users', $users, now()->addHours(24));
}

// Forget cache
Cache::forget('users');
Cache::flush(); // Clear all

// Increment/Decrement
Cache::increment('views'); // views++
Cache::decrement('remaining', 5); // remaining -= 5
````
### 17. Laravel এ Testing কীভাবে করা হয়?
উত্তর:
Laravel PHPUnit based testing provide করে।

php artisan make:test UserControllerTest
php artisan make:test UserControllerTest --unit


````php
// tests/Feature/UserControllerTest.php
class UserControllerTest extends TestCase
{
    #[Test]
    public function test_can_view_users()
    {
        $response = $this->get('/users');
        
        $response->assertStatus(200)
                 ->assertViewHas('users');
    }

    #[Test]
    public function test_can_create_user()
    {
        $response = $this->post('/users', [
            'name' => 'John',
            'email' => 'john@test.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@test.com',
        ]);
    }

    #[Test]
    public function test_authenticated_user_can_delete_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->delete("/posts/{$post->id}")
             ->assertRedirect();

        $this->assertSoftDeleted($post);
    }
}

// Unit test example
class CalculatorTest extends TestCase
{
    #[Test]
    public function test_addition()
    {
        $calc = new Calculator();
        $result = $calc->add(2, 2);
        
        $this->assertEquals(4, $result);
    }
}
````

**Test running:**
````bash
php artisan test
php artisan test --filter=UserControllerTest
php artisan test --profile # Performance
```

---

### 18. Laravel Factories এবং Seeders কী?

**উত্তর:**
Factories এবং Seeders testing এবং development এর জন্য fake data generate করে।

**Factory:**
````bash
php artisan make:factory PostFactory
```

````php
// database/factories/PostFactory.php
class PostFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(5),
            'user_id' => User::factory(),
            'published_at' => now(),
        ];
    }

    public function unpublished()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => null,
            ];
        });
    }
}

// Usage
$post = Post::factory()->create(); // Create in DB
$post = Post::factory()->unpublished()->create();
$posts = Post::factory()->count(50)->create();
````

**Seeder:**
````bash
php artisan make:seeder UserSeeder
```

````php
// database/seeders/UserSeeder.php
class UserSeeder extends Seeder
{
    public function run()
    {
        User::factory()->count(100)->create();
    }
}

// database/seeders/DatabaseSeeder.php
public function run()
{
    $this->call([
        UserSeeder::class,
        PostSeeder::class,
    ]);
}

// Seed করা
php artisan db:seed
php artisan db:seed --class=UserSeeder
php artisan migrate:fresh --seed
````

---

### 19. Laravel Model Scopes কী?

**উত্তর:**
Scopes হল reusable query constraints যা model কে clean রাখে।

Local vs Global Scope:

Local → Optional, explicitly called
Global → Always applied (soft deletes example)

````php
// app/Models/Post.php
class Post extends Model
{
    // Local scope
    public function scopePublished($query)
    {
        return $query->where('published_at', '<', now());
    }

    public function scopeByAuthor($query, $author)
    {
        return $query->where('user_id', $author->id);
    }

    // Global scope
    protected static function booted()
    {
        static::addGlobalScope('active', function($query) {
            $query->where('is_active', true);
        });
    }
}

// Usage
Post::published()->get();
Post::published()->byAuthor($user)->get();

// Multiple scopes
Post::published()
    ->byAuthor($user)
    ->wherePivot('featured', true)
    ->get();

````
### 20. Laravel Mutators এবং Accessors?
উত্তর:
Mutators এবং Accessors model properties auto-transform করে।

Casts:

string, integer, float, boolean
array, collection, json
datetime, date, time
encrypted
Custom casts

````php
<?php
// Laravel 9+ approach (recommended)
class User extends Model
{
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'age' => 'integer',
    ];

    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
            set: fn ($value) => strtolower($value),
        );
    }
}

// Laravel 8 and before
class User extends Model
{
    public function getFirstNameAttribute($value)
    {
        return ucfirst($value);
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = strtolower($value);
    }
}

// Usage
$user->first_name = 'JOHN'; // Auto lowercased
echo $user->first_name; // Outputs: John
````
