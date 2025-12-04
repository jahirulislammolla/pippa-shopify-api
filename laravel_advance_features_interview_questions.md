# Laravel Advanced Features Interview Questions (21–40)

---

## 21. Laravel এ Eloquent Eager Loading কীভাবে N+1 Query সমস্যা solve করে?

### উত্তর:

**সমস্যা - N+1 Query:**

```php
<?php
// ❌ BAD - N+1 Query Problem
$posts = Post::all();  // 1 query

foreach ($posts as $post) {
    echo $post->user->name;  // +1 query per post
}
// Total queries: 1 + n (n = number of posts)
// If 100 posts: 101 queries!
```

**সমাধান - Eager Loading:**

```php
<?php
// ✓ GOOD - Eager loading with()
$posts = Post::with('user')->get();  // 2 queries

foreach ($posts as $post) {
    echo $post->user->name;  // No additional queries
}
// Total queries: 2 (fixed, regardless of post count)
```

**কীভাবে কাজ করে:**

```php
<?php
// Query 1: Get all posts
SELECT * FROM posts;

// Query 2: Get all users for those posts (single query!)
SELECT * FROM users WHERE id IN (1, 2, 3, 4, 5...);

// PHP merges results in memory
```

**Multiple relationships:**

```php
<?php
// Load multiple relationships
$posts = Post::with(['user', 'comments', 'tags'])->get();

// Nested relationships
$posts = Post::with('user.profile', 'comments.author')->get();

// Conditional loading
$posts = Post::with(['user' => function($query) {
    $query->select('id', 'name', 'email');
}])->get();
```

**Advanced eager loading patterns:**

```php
<?php
// Pattern 1: Load with count
$posts = Post::withCount('comments')->get();
// Adds comments_count property

// Pattern 2: Load with sum/avg
$orders = Order::withSum('items', 'price')->get();
// Adds items_sum_price property

// Pattern 3: Load with custom column
$posts = Post::with(['comments' => function($query) {
    $query->where('approved', true)
          ->orderBy('created_at', 'desc')
          ->limit(5);
}])->get();

// Pattern 4: Late eager loading
$posts = Post::all();
if ($need_users) {
    $posts->load('user');  // Load after retrieval
}
```

**Query Builder level:**

```php
<?php
// Direct with() on QueryBuilder
Post::query()
    ->with('user')
    ->where('published_at', '<', now())
    ->paginate(15);

// Using load() on collection (already fetched)
$posts = Post::limit(10)->get();
$posts->load('user', 'comments');  // Load after fetch
```

**Performance comparison:**

```
Without Eager Loading:
- 100 posts → 101 queries
- 1000 posts → 1001 queries
- Time: ~2-5 seconds

With Eager Loading:
- 100 posts → 2 queries
- 1000 posts → 2 queries
- Time: ~50-100ms
```

---

## 22. Laravel Repository Pattern কী এবং কেন use করা হয়?

### উত্তর:

Repository Pattern হল data access logic কে abstraction layer এ রাখা।

**ছাড়া Repository (tightly coupled):**

```php
<?php
// UserController.php
class UserController extends Controller
{
    public function index()
    {
        // DB logic directly in controller
        $users = User::where('active', true)
                     ->with('profile')
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);
        
        return view('users.index', ['users' => $users]);
    }
    
    public function store(Request $request)
    {
        // DB logic directly
        $user = User::create($request->validated());
        $user->profile()->create([...]);
        
        return redirect()->route('users.show', $user);
    }
}

// সমস্যা:
// - Controller জটিল হয়ে যাচ্ছে
// - Testing কঠিন
// - DB switching করা মুশকিল
```

**Repository Pattern (loosely coupled):**

```php
<?php
// app/Contracts/UserRepository.php
interface UserRepository
{
    public function all();
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getActive();
}

// app/Repositories/EloquentUserRepository.php
class EloquentUserRepository implements UserRepository
{
    protected $model;
    
    public function __construct(User $model)
    {
        $this->model = $model;
    }
    
    public function all()
    {
        return $this->model->paginate(15);
    }
    
    public function getActive()
    {
        return $this->model->where('active', true)
                          ->with('profile')
                          ->orderBy('created_at', 'desc')
                          ->get();
    }
    
    public function create(array $data)
    {
        return $this->model->create($data);
    }
    
    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }
    
    public function update($id, array $data)
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user;
    }
    
    public function delete($id)
    {
        return $this->findById($id)->delete();
    }
}

// app/Http/Controllers/UserController.php
class UserController extends Controller
{
    public function __construct(private UserRepository $userRepo) {}
    
    public function index()
    {
        // Clean, readable controller
        $users = $this->userRepo->all();
        return view('users.index', ['users' => $users]);
    }
    
    public function store(Request $request)
    {
        $user = $this->userRepo->create($request->validated());
        return redirect()->route('users.show', $user);
    }
}

// app/Providers/RepositoryServiceProvider.php
class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class
        );
    }
}
```

**Repository এর সুবিধা:**

```php
<?php
// 1. Testing এ mock করা সহজ
$mockRepo = Mockery::mock(UserRepository::class);
$mockRepo->shouldReceive('all')->andReturn([...]);

$controller = new UserController($mockRepo);
$response = $controller->index();

// 2. DB switching সহজ
// MySQL থেকে MongoDB এ shift করতে:
class MongoUserRepository implements UserRepository { ... }

// Service container update করুন
$this->app->bind(UserRepository::class, MongoUserRepository::class);
// Controller code unchanged!

// 3. Complex queries reusable
interface UserRepository {
    public function getActiveWithOrders();
    public function getByEmailDomain($domain);
}
```

**Real-world repository:**

```php
<?php
class PostRepository
{
    public function __construct(private Post $model) {}
    
    public function getPublished()
    {
        return $this->model->where('published_at', '<', now())
                          ->with('author', 'comments')
                          ->orderBy('published_at', 'desc')
                          ->get();
    }
    
    public function getFeatured()
    {
        return $this->getPublished()
                   ->where('featured', true)
                   ->limit(5);
    }
    
    public function search($term)
    {
        return $this->model->where('title', 'like', "%$term%")
                          ->orWhere('content', 'like', "%$term%")
                          ->paginate(20);
    }
    
    public function withCommentCount()
    {
        return $this->model->withCount('comments');
    }
}

// Usage in service
class PublishPostService
{
    public function __construct(
        private PostRepository $postRepo,
        private NotificationService $notifier
    ) {}
    
    public function publish(Post $post)
    {
        $post->published_at = now();
        $post->save();
        
        // এখানে PostRepository use করতে পারি
        $featured = $this->postRepo->getFeatured();
        
        // Notify subscribers
        $this->notifier->notifySubscribers($post);
        
        return $post;
    }
}
```

---

## 23. Laravel Service Layer Pattern কী?

### উত্তর:

Service Layer হল business logic holder যা controller এবং repository এর মধ্যে থাকে।

**Layer architecture:**

```
Controller → Service → Repository → Model → Database
    ↑            ↓
    ← Response ←
```

**Service Layer না থাকলে:**

```php
<?php
class OrderController extends Controller
{
    public function checkout(CheckoutRequest $request)
    {
        // Business logic in controller (bad!)
        
        $order = Order::create($request->validated());
        
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $product->decrement('stock', $item['quantity']);
            
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
        }
        
        $user = auth()->user();
        Mail::send(new OrderConfirmation($order, $user));
        
        if ($request->notify_warehouse) {
            // Notify warehouse
        }
        
        return response()->json(['order_id' => $order->id]);
    }
}
```

**Service Layer সহ (clean):**

```php
<?php
// app/Services/OrderService.php
class OrderService
{
    public function __construct(
        private OrderRepository $orderRepo,
        private ProductRepository $productRepo,
        private NotificationService $notifier,
        private WarehouseService $warehouse
    ) {}
    
    public function createOrder(User $user, array $items, bool $notifyWarehouse = true)
    {
        // Validate stock
        foreach ($items as $item) {
            $product = $this->productRepo->findById($item['product_id']);
            if ($product->stock < $item['quantity']) {
                throw new InsufficientStockException($product);
            }
        }
        
        // Create order
        $order = $this->orderRepo->create([
            'user_id' => $user->id,
            'total' => $this->calculateTotal($items),
            'status' => 'pending',
        ]);
        
        // Add items এবং update stock
        foreach ($items as $item) {
            $product = $this->productRepo->findById($item['product_id']);
            
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
            
            $this->productRepo->decrementStock($product->id, $item['quantity']);
        }
        
        // Send notifications
        $this->notifier->sendOrderConfirmation($order, $user);
        
        if ($notifyWarehouse) {
            $this->warehouse->notifyNewOrder($order);
        }
        
        return $order;
    }
    
    private function calculateTotal(array $items): float
    {
        return collect($items)->sum(function($item) {
            $product = $this->productRepo->findById($item['product_id']);
            return $product->price * $item['quantity'];
        });
    }
}

// app/Http/Controllers/OrderController.php
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}
    
    public function checkout(CheckoutRequest $request)
    {
        try {
            $order = $this->orderService->createOrder(
                auth()->user(),
                $request->items,
                $request->notify_warehouse ?? true
            );
            
            return response()->json(['order_id' => $order->id], 201);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => 'Out of stock'], 400);
        }
    }
}

// Service registration
class OrderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(OrderService::class, function($app) {
            return new OrderService(
                $app->make(OrderRepository::class),
                $app->make(ProductRepository::class),
                $app->make(NotificationService::class),
                $app->make(WarehouseService::class),
            );
        });
    }
}
```

**Service layer সুবিধা:**

```php
<?php
// 1. Reusable business logic
class OrderService {
    public function createOrder(...) { ... }
    public function cancelOrder(...) { ... }
    public function refundOrder(...) { ... }
}

// API থেকে ও background job থেকে use করা যায়
class OrderController {
    public function store() {
        $this->orderService->createOrder(...);
    }
}

class ProcessRefundJob {
    public function handle(OrderService $service) {
        $service->refundOrder(...);
    }
}

// 2. Complex logic testable
class OrderServiceTest extends TestCase {
    public function test_creates_order_with_correct_total() {
        $service = new OrderService(
            Mockery::mock(OrderRepository::class),
            Mockery::mock(ProductRepository::class),
            // ...
        );
        
        $order = $service->createOrder(...);
        $this->assertEquals(100, $order->total);
    }
}
```

---

## 24. Laravel Policy এবং Authorization কীভাবে implement করা হয়?

### উত्तर:

Policies হল resource-based authorization logic।

**Policy তৈরি:**

```bash
php artisan make:policy PostPolicy --model=Post
```

**Policy implementation:**

```php
<?php
// app/Policies/PostPolicy.php
class PostPolicy
{
    use HandlesAuthorization;
    
    // ব্যবহারকারী যেকোনো post দেখতে পারে
    public function viewAny(User $user)
    {
        return true;
    }
    
    // নির্দিষ্ট post দেখতে পারে
    public function view(User $user, Post $post)
    {
        return true;  // Anyone can view
    }
    
    // নতুন post তৈরি করতে পারে (authenticated users)
    public function create(User $user)
    {
        return $user->email_verified_at !== null;
    }
    
    // নিজের post edit করতে পারে
    public function update(User $user, Post $post)
    {
        return $user->id === $post->user_id;
    }
    
    // নিজের post delete করতে পারে
    public function delete(User $user, Post $post)
    {
        return $user->id === $post->user_id || $user->is_admin;
    }
    
    // Admin সব কিছু করতে পারে
    public function before(User $user, $ability)
    {
        if ($user->is_admin) {
            return true;  // Allow all abilities for admin
        }
    }
}

// app/Providers/AuthServiceProvider.php
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Post::class => PostPolicy::class,
        Comment::class => CommentPolicy::class,
    ];
    
    public function boot()
    {
        $this->registerPolicies();
    }
}
```

**Controller এ use করা:**

```php
<?php
class PostController extends Controller
{
    public function edit(Post $post)
    {
        // Authorize করা (Policy use)
        $this->authorize('update', $post);
        
        return view('posts.edit', ['post' => $post]);
    }
    
    public function update(Request $request, Post $post)
    {
        // Authorize করা
        if (!auth()->user()->can('update', $post)) {
            abort(403, 'Unauthorized');
        }
        
        $post->update($request->validated());
        return redirect()->route('posts.show', $post);
    }
    
    // Route model binding এর সাথে
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return redirect()->route('posts.index');
    }
}
```

**Blade template এ:**

```blade
<!-- resources/views/posts/show.blade.php -->

<div class="post">
    <h1>{{ $post->title }}</h1>
    <p>{{ $post->content }}</p>
    
    <!-- Authorize check in view -->
    @can('update', $post)
        <a href="{{ route('posts.edit', $post) }}" class="btn">Edit</a>
    @endcan
    
    @can('delete', $post)
        <form action="{{ route('posts.destroy', $post) }}" method="POST">
            @csrf
            @method('DELETE')
            <button class="btn-danger">Delete</button>
        </form>
    @endcan
    
    <!-- Or use @cannot -->
    @cannot('update', $post)
        <p>You cannot edit this post.</p>
    @endcannot
</div>
```

**Advanced policy:**

```php
<?php
class PostPolicy
{
    // Condition based authorization
    public function update(User $user, Post $post)
    {
        // User can update only if:
        // 1. User is owner AND
        // 2. Post not locked AND
        // 3. Less than 24 hours old
        
        return $user->id === $post->user_id
            && !$post->is_locked
            && $post->created_at->diffInHours(now()) < 24;
    }
    
    // With guest user (nullable)
    public function view(?User $user, Post $post)
    {
        if ($post->is_published) {
            return true;
        }
        
        return $user && $user->id === $post->user_id;
    }
}
```

---

## 25. Laravel Events এবং Listeners architecture?

### উत्तर:

Events হল application এর বিভিন্ন অংশের মধ্যে loose coupling তৈরি করে।

**Event তৈরি:**

```bash
php artisan make:event UserRegistered
php artisan make:listener SendWelcomeEmail --event=UserRegistered
```

**Event class:**

```php
<?php
// app/Events/UserRegistered.php
class UserRegistered
{
    // Use Dispatchable trait for dispatch helper
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(public User $user)
    {
        // Public property automatically available to listeners
    }
}

// Event variants
class PostPublished {
    public function __construct(public Post $post) {}
}

class CommentCreated {
    public function __construct(public Comment $comment) {}
}

class PaymentProcessed {
    public function __construct(public Order $order, public float $amount) {}
}
```

**Listener class:**

```php
<?php
// app/Listeners/SendWelcomeEmail.php
class SendWelcomeEmail
{
    // Optional: Queued listener
    use ShouldQueue;
    
    public function __construct(private MailService $mail) {}
    
    public function handle(UserRegistered $event)
    {
        $this->mail->send(new UserWelcomeMail($event->user));
    }
}

// Another listener for same event
class NotifyAdminNewUser
{
    use ShouldQueue;
    public $delay = 60;  // Delay 60 seconds
    
    public function handle(UserRegistered $event)
    {
        Notification::route('mail', 'admin@app.com')
                     ->notify(new NewUserRegistration($event->user));
    }
}

class UpdateUserStatistics
{
    // Not queued, instant execution
    public function handle(UserRegistered $event)
    {
        Cache::increment('total_users');
        Cache::increment('users_registered_today');
    }
}
```

**Event registration:**

```php
<?php
// app/Providers/EventServiceProvider.php
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendWelcomeEmail::class,
            NotifyAdminNewUser::class,
            UpdateUserStatistics::class,
        ],
        
        PostPublished::class => [
            NotifyFollowers::class,
            IndexPostForSearch::class,
            ClearPostCache::class,
        ],
        
        PaymentProcessed::class => [
            UpdateInventory::class,
            SendShippingNotification::class,
        ],
    ];
    
    public function boot()
    {
        // Listen to events dynamically
        Event::listen(CommentCreated::class, function(CommentCreated $event) {
            // Handle event
        });
    }
}
```

**Event dispatch করা:**

```php
<?php
// In controller
class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->validated());
        
        // Dispatch event (listeners run after response)
        event(new UserRegistered($user));
        // or
        UserRegistered::dispatch($user);
        
        return redirect()->route('users.show', $user);
    }
}

// In model observer
class User extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::created(function($user) {
            event(new UserRegistered($user));
        });
    }
}
```

**Queued listeners:**

```php
<?php
// Event listeners are queued if they implement ShouldQueue
class SendWelcomeEmail implements ShouldQueue
{
    // Will be queued instead of immediate execution
    // Requires queue table to be migrated
    
    public function handle(UserRegistered $event)
    {
        // This runs in background job
        Mail::send(new UserWelcomeMail($event->user));
    }
}

// Artisan to process queue
php artisan queue:work redis
```

**Event-driven architecture example:**

```php
<?php
// Order flow using events
class OrderController
{
    public function checkout(Request $request, OrderService $service)
    {
        $order = $service->createOrder(
            auth()->user(),
            $request->items
        );
        
        // Events dispatched internally
        event(new OrderCreated($order));
        
        return response()->json(['order_id' => $order->id]);
    }
}

// Listeners handle all side effects
class OrderCreated extends Event { ... }

// Listeners:
// 1. SendOrderConfirmationEmail
// 2. NotifyWarehouse
// 3. UpdateInventory
// 4. RecordAnalytics
// 5. AddCustomerPoints

// All decoupled and can be modified independently
```
---

## 26. Laravel Caching System: Strategy এবং Implementation?

### উত्तर:

Laravel caching multiple strategies support করে - application level, query level, route level।

**Caching drivers:**

```php
<?php
// config/cache.php
'default' => env('CACHE_DRIVER', 'file'),

'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
    
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'laravel_cache:',
    ],
    
    'memcached' => [
        'driver' => 'memcached',
        'servers' => [
            ['host' => '127.0.0.1', 'port' => 11211],
        ],
    ],
    
    'dynamodb' => [
        'driver' => 'dynamodb',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'table' => env('DYNAMODB_CACHE_TABLE', 'laravel_cache'),
    ],
],
```

**Basic caching operations:**

```php
<?php
// Simple cache
Cache::put('user_' . $user->id, $user->data, now()->addHours(24));

// Get from cache
$data = Cache::get('user_' . $user->id);  // null if not found
$data = Cache::get('user_' . $user->id, 'default');  // default value

// Check existence
if (Cache::has('user_' . $user->id)) {
    // exists
}

// Forget
Cache::forget('user_' . $user->id);

// Flush all
Cache::flush();
```

**Remember pattern (most common):**

```php
<?php
// If not cached, execute callback and cache result
$posts = Cache::remember('all_posts', 3600, function() {
    return Post::with('author', 'comments')->get();
});

// Remember forever
$config = Cache::rememberForever('app_config', function() {
    return config('app');
});

// Remember with TTL
$users = Cache::remember(
    'active_users',
    now()->addMinutes(30),
    function() {
        return User::where('is_active', true)->get();
    }
);
```

**Pull pattern (get and forget):**

```php
<?php
// Get value and immediately forget
$value = Cache::pull('temporary_key');  // null if not found

// Useful for one-time operations
$token = Cache::pull('email_verification_token_' . $email);
if ($token && $token === $user_token) {
    // Valid token, auto-forgotten
}
```

**Atomic operations:**

```php
<?php
// Increment
Cache::increment('page_views');
Cache::increment('user_score', 5);

// Decrement
Cache::decrement('remaining_quota');
Cache::decrement('available_seats', 2);

// Store forever
Cache::forever('license_key', 'ABC123');
```

**Cache tags (organize cache):**

```php
<?php
// Store with tags
Cache::tags(['users', 'user:' . $user->id])->put(
    'user_profile_' . $user->id,
    $user->profile,
    3600
);

// Flush by tag
Cache::tags('users')->flush();  // Clear all user caches

// Retrieve tagged cache
$profile = Cache::tags('users')->get('user_profile_' . $user->id);

// Real-world example
class UserService {
    public function updateProfile(User $user, array $data) {
        $user->update($data);
        
        // Clear user-related caches
        Cache::tags(['user:' . $user->id])->flush();
    }
}
```

**Query result caching:**

```php
<?php
// Cache query results
class PostRepository {
    public function getPublished() {
        return Cache::remember(
            'published_posts',
            now()->addHours(24),
            function() {
                return Post::where('published', true)
                          ->with('author')
                          ->orderBy('created_at', 'desc')
                          ->get();
            }
        );
    }
    
    public function publishPost(Post $post) {
        $post->update(['published' => true]);
        
        // Invalidate cache
        Cache::forget('published_posts');
        Cache::tags('posts')->flush();
    }
}
```

**Route caching:**

```php
<?php
// In routes/web.php
Route::get('/trending-posts', function() {
    $posts = Cache::remember('trending_posts', 3600, function() {
        return Post::orderBy('views', 'desc')->limit(10)->get();
    });
    
    return view('trending', ['posts' => $posts]);
});

// Or use middleware
Route::middleware('cache:3600')->group(function() {
    Route::get('/api/trending', [PostController::class, 'trending']);
});
```

**Cache invalidation strategy:**

```php
<?php
class Post extends Model {
    protected static function boot() {
        parent::boot();
        
        static::updated(function($post) {
            // Invalidate caches on update
            Cache::forget('all_posts');
            Cache::forget('published_posts');
            Cache::tags(['post:' . $post->id])->flush();
        });
        
        static::deleted(function($post) {
            // Invalidate on delete
            Cache::flush();
        });
    }
}
```

---

## 27. Laravel Database Optimization: Indexes এবং Query Optimization?

### उत्तर:

Database performance এর জন্য proper indexing এবং query optimization critical।

**Index types এবং usage:**

```php
<?php
// Migration with indexes
Schema::create('posts', function(Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->unsignedBigInteger('user_id');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    
    // Single column index
    $table->index('user_id');
    $table->index('published_at');
    
    // Unique index
    $table->unique('slug');
    $table->unique('email');
    
    // Compound index (multiple columns)
    $table->index(['user_id', 'published_at']);
    
    // Full text index
    $table->fullText(['title', 'content']);
    
    // Foreign key
    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade');
});

// Check indexes
php artisan migrate --pretend
```

**Query optimization patterns:**

```php
<?php
// Bad: Multiple queries
$posts = Post::all();
foreach ($posts as $post) {
    $author = User::find($post->user_id);  // Query per post!
}

// Good: Single query with join
$posts = Post::with('author')->get();

// Or with join
$posts = Post::join('users', 'posts.user_id', '=', 'users.id')
             ->select('posts.*', 'users.name as author_name')
             ->get();
```

**EXPLAIN query analysis:**

```php
<?php
// Check query performance
$posts = Post::where('published_at', '<', now())
             ->where('user_id', 1)
             ->orderBy('created_at', 'desc')
             ->limit(10);

// Get EXPLAIN output
$explains = DB::select(DB::raw('EXPLAIN ' . $posts->toSql()), $posts->getBindings());
dd($explains);

// Output shows:
// - type: ALL (full table scan) ❌ or range/ref ✓
// - rows: Estimated rows scanned
// - key: Index used (or NULL if no index)
// - filtered: Percentage of rows filtered by WHERE
```

**Optimization techniques:**

```php
<?php
// Technique 1: Selective columns
$users = User::select('id', 'name', 'email')  // Not all columns
             ->where('active', true)
             ->get();

// Technique 2: Limit result
$recent_posts = Post::orderBy('created_at', 'desc')
                    ->limit(10)  // Not all rows
                    ->get();

// Technique 3: Pagination
$posts = Post::paginate(20);  // Better than limit

// Technique 4: Database-level filtering
// Bad:
$users = User::all();
$admins = $users->filter(fn($u) => $u->is_admin);

// Good:
$admins = User::where('is_admin', true)->get();

// Technique 5: Use exists() instead of count()
// Bad:
if (Post::where('user_id', $user->id)->count() > 0) { ... }

// Good:
if (Post::where('user_id', $user->id)->exists()) { ... }

// Technique 6: Chunking for large result
User::where('active', true)->chunk(100, function($users) {
    foreach ($users as $user) {
        // Process in batches
    }
});

// Technique 7: Lazy loading for memory
User::lazy()->each(function($user) {
    // Process one at a time (memory efficient)
});
```

**Slow query log:**

```sql
-- /etc/mysql/mysql.conf.d/mysqld.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 0.5  # Log queries taking > 0.5 seconds
log_queries_not_using_indexes = 1
```

**Query builder debugging:**

```php
<?php
// Enable query logging
DB::enableQueryLog();

$users = User::where('active', true)->get();
$posts = Post::where('user_id', 1)->get();

// View executed queries
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    echo $query['query'];
    echo $query['bindings'];
    echo $query['time'];
}

// Output:
// SELECT * FROM users WHERE active = ? [1] (0.5ms)
// SELECT * FROM posts WHERE user_id = ? [1] (0.2ms)
```

**Real-world optimization example:**

```php
<?php
// Before: Slow
class PostController {
    public function index() {
        $posts = Post::all();
        $featured = collect($posts)->where('featured', true)->take(5);
        
        return view('index', ['posts' => $posts, 'featured' => $featured]);
    }
}

// After: Optimized
class PostController {
    public function index() {
        $posts = Post::where('published_at', '<', now())
                     ->select('id', 'title', 'content', 'user_id', 'featured')
                     ->with('author:id,name')
                     ->orderBy('created_at', 'desc')
                     ->paginate(20);
        
        $featured = Post::where('featured', true)
                       ->where('published_at', '<', now())
                       ->limit(5)
                       ->get();
        
        return view('index', ['posts' => $posts, 'featured' => $featured]);
    }
}
```

---

## 28. Laravel Broadcasting System: Real-time communication?

### उत्तर:

Broadcasting এর মাধ্যমে real-time updates client এ push করা যায়।

**Broadcasting drivers:**

```php
<?php
// config/broadcasting.php
'default' => env('BROADCAST_DRIVER', 'pusher'),

'connections' => [
    'pusher' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ],
    ],
    
    'ably' => [
        'driver' => 'ably',
        'key' => env('ABLY_KEY'),
    ],
    
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
],
```

**Event broadcasting:**

```php
<?php
// app/Events/PostPublished.php
use BroadcastableModelEvent;

class PostPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(public Post $post) {}
    
    // Who can listen to this event
    public function broadcastOn()
    {
        return new Channel('posts');  // Public channel
        // or
        // return new PrivateChannel('post.' . $this->post->id);
    }
    
    // What data to broadcast
    public function broadcastWith()
    {
        return [
            'id' => $this->post->id,
            'title' => $this->post->title,
            'author' => $this->post->author->name,
        ];
    }
    
    // Channel name
    public function broadcastAs()
    {
        return 'post.published';
    }
}

// Trigger broadcast
class PublishPostController {
    public function store(Request $request) {
        $post = Post::create($request->validated());
        
        // Broadcast to all listeners
        broadcast(new PostPublished($post))->toOthers();
        
        return response()->json($post, 201);
    }
}
```

**Listening to broadcasts (frontend):**

```javascript
// resources/js/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Listen to public channel
Echo.channel('posts')
    .listen('PostPublished', (data) => {
        console.log('New post published:', data);
        // Update UI
    });

// Listen to private channel
Echo.private('post.' + postId)
    .listen('PostUpdated', (data) => {
        console.log('Post updated:', data);
    });

// Listen to presence channel
Echo.join('online-users')
    .here((users) => {
        console.log('Online users:', users);
    })
    .joining((user) => {
        console.log('User joined:', user.name);
    })
    .leaving((user) => {
        console.log('User left:', user.name);
    });
```

**Private channel authorization:**

```php
<?php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('post.{id}', function($user, $id) {
    $post = Post::find($id);
    // User can listen only if post owner or admin
    return $user->id === $post->user_id || $user->is_admin;
});

Broadcast::private('message.{userId}', function($user, $userId) {
    // Private message between users
    return $user->id === (int) $userId;
});
```

**Real-world broadcasting example:**

```php
<?php
// Real-time notifications
class UserCommentedEvent implements ShouldBroadcast {
    public function __construct(public Comment $comment) {}
    
    public function broadcastOn() {
        // Notify post owner
        return new PrivateChannel('user.' . $this->comment->post->user_id);
    }
    
    public function broadcastWith() {
        return [
            'message' => $this->comment->user->name . ' commented on your post',
            'comment' => $this->comment,
        ];
    }
}

// Trigger
class CommentController {
    public function store(Request $request, Post $post) {
        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);
        
        // Broadcast to post owner
        broadcast(new UserCommentedEvent($comment));
        
        return response()->json($comment, 201);
    }
}
```

---

## 29. Laravel Queue System: Background Jobs এবং Processing?

### उत्तर:

Queue system long-running tasks background এ handle করে।

**Queue setup:**

```bash
# Database queue
php artisan queue:table
php artisan migrate

# Redis queue (recommended)
# Install redis-server first
php artisan queue:work redis

# SQS queue
composer require aws/aws-sdk-php
```

**Job তৈরি:**

```bash
php artisan make:job SendWelcomeEmail
php artisan make:job ProcessVideo --queued
```

**Job implementation:**

```php
<?php
// app/Jobs/SendWelcomeEmail.php
class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(public User $user) {}
    
    // Main job logic
    public function handle(MailService $mail)
    {
        $mail->send(new UserWelcomeMail($this->user));
    }
    
    // Handle failures
    public function failed(Throwable $exception)
    {
        Log::error('Welcome email failed: ' . $exception->getMessage());
        
        // Notify admin
        Notification::route('mail', 'admin@app.com')
                     ->notify(new JobFailedNotification());
    }
    
    // Retry logic
    public $tries = 3;        // Retry 3 times
    public $timeout = 60;     // Timeout after 60 seconds
    public $backoff = [10, 30, 60]; // Retry after 10, 30, 60 seconds
    
    // Rate limiting
    public function middleware()
    {
        return [new RateLimited('emails')];
    }
}
```

**Dispatch jobs:**

```php
<?php
// In controller
class RegisterController {
    public function store(Request $request) {
        $user = User::create($request->validated());
        
        // Dispatch job to queue
        SendWelcomeEmail::dispatch($user);
        
        // Dispatch with delay
        SendWelcomeEmail::dispatch($user)->delay(now()->addMinutes(5));
        
        // Dispatch to specific queue
        SendWelcomeEmail::dispatch($user)->onQueue('emails');
        
        // Dispatch and wait for result
        $result = SendWelcomeEmail::dispatchSync($user);
        
        return redirect()->route('users.show', $user);
    }
}

// In model
class User extends Model {
    protected static function boot() {
        parent::boot();
        
        static::created(function($user) {
            SendWelcomeEmail::dispatch($user);
        });
    }
}

// Batch jobs
Bus::batch([
    new ProcessVideo($video1),
    new ProcessVideo($video2),
    new ProcessVideo($video3),
])
->then(function(Batch $batch) {
    // All jobs completed
    Log::info('Videos processed');
})
->catch(function(Batch $batch, Throwable $e) {
    // Job failed
    Log::error('Video processing failed');
})
->finally(function(Batch $batch) {
    // Always runs
    Log::info('Batch complete');
})
->dispatch();
```

**Queue worker processing:**

```bash
# Start queue worker
php artisan queue:work

# Work specific queue
php artisan queue:work --queue=emails,default

# Process limited jobs
php artisan queue:work --max-jobs=100

# Timeout per job
php artisan queue:work --timeout=120

# In production with Supervisor
sudo vim /etc/supervisor/conf.d/laravel-worker.conf
```

**Supervisor configuration (production):**

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/app/artisan queue:work redis --queue=default,emails --sleep=3 --tries=3 --max-jobs=1000
autostart=true
autorestart=true
numprocs=8
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-worker.log
```

**Advanced job features:**

```php
<?php
class ProcessDataJob implements ShouldQueue {
    use InteractsWithQueue, Queueable;
    
    public $timeout = 300;  // 5 minutes
    public $tries = 3;
    
    public function __construct(public array $data) {}
    
    public function handle() {
        try {
            // Process data
            $this->processLargeDataset();
        } catch (Exception $e) {
            // Release back to queue
            $this->release(60);  // Retry after 60 seconds
            
            // or fail
            throw $e;
        }
    }
    
    public function failed(Throwable $exception) {
        // Log failure
        Log::error('Job failed: ' . $exception->getMessage());
        
        // Notify
        Notification::route('mail', 'admin@app.com')
                     ->notify(new JobFailedNotification($exception));
    }
}
```

---

## 30. Laravel File Storage System: Local এবং Cloud storage?

### उत्तर:

Laravel flexible file storage system provide করে - local filesystem, S3, Azure, GCS।

**Storage configuration:**

```php
<?php
// config/filesystems.php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'url' => env('APP_URL') . '/storage',
        'visibility' => 'private',
    ],
    
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL') . '/storage',
        'visibility' => 'public',
    ],
    
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => false,
    ],
    
    'gcs' => [
        'driver' => 'gcs',
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'key_file' => env('GOOGLE_CLOUD_KEY_FILE'),
        'bucket' => env('GOOGLE_CLOUD_BUCKET'),
    ],
],
```

**File operations:**

```php
<?php
// Store file
$path = Storage::disk('s3')->put(
    'uploads/documents',
    $request->file('document'),
    'public'
);

// Store with custom name
$path = Storage::disk('s3')->putAs(
    'uploads/documents',
    $request->file('document'),
    'custom-name.pdf'
);

// Store with metadata
$path = Storage::disk('s3')->put(
    'uploads/documents/my-doc.pdf',
    $file,
    [
        'visibility' => 'public',
        'metadata' => ['user_id' => auth()->id()],
    ]
);

// Get file
$contents = Storage::disk('s3')->get('path/to/file.pdf');

// Check existence
if (Storage::disk('s3')->exists('path/to/file.pdf')) {
    // exists
}

// Delete file
Storage::disk('s3')->delete('path/to/file.pdf');

// Copy file
Storage::disk('s3')->copy('path/to/file.pdf', 'path/to/copy.pdf');

// Move file
Storage::disk('s3')->move('path/to/file.pdf', 'new/path/file.pdf');

// Get URL
$url = Storage::disk('s3')->url('path/to/file.pdf');

// Temporary URL (for private files)
$url = Storage::disk('s3')->temporaryUrl(
    'path/to/file.pdf',
    now()->addMinutes(30)
);

// Get file size
$size = Storage::disk('s3')->size('path/to/file.pdf');

// Get MIME type
$mime = Storage::disk('s3')->mimeType('path/to/file.pdf');

// Last modified
$modified = Storage::disk('s3')->lastModified('path/to/file.pdf');
```

**Upload with validation:**

```php
<?php
class DocumentController {
    public function store(Request $request) {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240', // 10MB
            'title' => 'required|string|max:255',
        ]);
        
        // Store file
        $path = $request->file('document')->store(
            'documents/' . auth()->id(),
            's3'
        );
        
        // Save record
        Document::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'path' => $path,
            'mime_type' => $request->file('document')->getMimeType(),
            'size' => $request->file('document')->getSize(),
        ]);
        
        return response()->json(['success' => true]);
    }
}

// Download file
public function download(Document $document) {
    return Storage::disk('s3')
                  ->download($document->path, $document->title . '.pdf');
}
```

**Organized storage structure:**

```php
<?php
class FileService {
    public function storeUserDocument(User $user, $file, $category = 'documents') {
        $filename = Str::random(40) . '.' . $file->extension();
        
        $path = sprintf(
            'users/%d/%s/%s',
            $user->id,
            $category,
            $filename
        );
        
        return Storage::disk('s3')->putFile($path, $file, 'public');
    }
    
    public function getUserDocuments(User $user) {
        return collect(
            Storage::disk('s3')->files('users/' . $user->id)
        );
    }
    
    public function deleteUserFile(User $user, $path) {
        // Security: verify ownership
        if (!Str::startsWith($path, 'users/' . $user->id)) {
            throw new UnauthorizedHttpException('Unauthorized');
        }
        
        return Storage::disk('s3')->delete($path);
    }
}
```

---

## 31. Laravel Request Lifecycle: Request থেকে Response পর্যন্ত?

### उत्तर:

Lifecycle বুঝা debugging এবং optimization এর জন্য important।

**Request lifecycle flow:**

```
1. public/index.php entry point
   ↓
2. Bootstrap autoloader (vendor/autoload.php)
   ↓
3. Application instance create
   ↓
4. HTTP Request create
   ↓
5. Service Providers boot (config এর providers থেকে)
   ↓
6. Bootstrap/HTTP Kernel
   ↓
7. Global middleware
   ↓
8. Route matching
   ↓
9. Route middleware
   ↓
10. Controller/Action execution
    ↓
11. Response create
    ↓
12. Middleware terminate
    ↓
13. Response send
```

**Detailed execution:**

```php
<?php
// 1. public/index.php
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$response->send();
$kernel->terminate($request, $response);

// 2. Service Provider registration
// Registered in config/app.php 'providers'
class MyServiceProvider extends ServiceProvider {
    public function register() {
        // Register bindings (before app boots)
        $this->app->singleton('myservice', function() {
            return new MyService();
        });
    }
    
    public function boot() {
        // Called when app booting (dependencies resolved)
        // Can use other services
    }
}

// 3. HTTP Kernel middleware
class HttpKernel extends Kernel {
    protected $middleware = [
        // Global middleware - every request
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];
    
    protected $middlewareGroups = [
        'web' => [
            // Web request middleware
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        
        'api' => [
            // API request middleware
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
}

// 4. Route definition
Route::get('/posts', [PostController::class, 'index'])
     ->middleware(['auth', 'verified'])
     ->name('posts.index');

// 5. Controller execution
class PostController extends Controller {
    public function index() {
        // Business logic
        $posts = Post::all();
        
        // Response
        return view('posts.index', ['posts' => $posts]);
    }
}

// 6. Response send
// Middleware terminate
// Response headers and body sent
```

**Request lifecycle timing:**

```php
<?php
// Add debugging middleware
class LogRequestTiming {
    public function handle($request, Closure $next) {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        Log::info("Request {$request->path()} took {$duration}s");
        
        return $response;
    }
}

// Add to routes
Route::middleware('log-timing')->group(function() {
    // Routes
});
```

**Service provider lifecycle:**

```php
<?php
class DebugServiceProvider extends ServiceProvider {
    public function register() {
        Log::info('register() called - bindings registered');
    }
    
    public function boot() {
        Log::info('boot() called - services ready');
    }
}

// Output:
// [timestamp] local.DEBUG: register() called
// [timestamp] local.DEBUG: boot() called
// Request executed
```

---

## 32. Laravel Middleware: Custom Middleware এবং execution flow?

### उत्तर:

Middleware HTTP request/response filtering layer।

**Middleware তৈরি:**

```bash
php artisan make:middleware CheckAdmin
php artisan make:middleware LogRequests
```

**Middleware types:**

```php
<?php
// Type 1: Before middleware (request processing)
class CheckAdmin {
    public function handle($request, Closure $next) {
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect('/');
        }
        
        return $next($request);
    }
}

// Type 2: After middleware (response processing)
class LogResponse {
    public function handle($request, Closure $next) {
        $response = $next($request);
        
        Log::info('Response status: ' . $response->getStatusCode());
        
        return $response;
    }
}

// Type 3: Conditional middleware
class RateLimitMiddleware {
    public function handle($request, Closure $next) {
        $key = 'rate_limit_' . auth()->id();
        
        if (Cache::has($key)) {
            return response('Too many requests', 429);
        }
        
        Cache::put($key, true, now()->addMinutes(1));
        
        return $next($request);
    }
}
```

**Middleware registration:**

```php
<?php
// app/Http/Kernel.php
class HttpKernel extends Kernel {
    // Global middleware - every request
    protected $middleware = [
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
    ];
    
    // Middleware groups
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            StartSession::class,
            VerifyCsrfToken::class,
        ],
        'api' => [
            ThrottleRequests::class,
            SubstituteBindings::class,
        ],
    ];
    
    // Route middleware (alias)
    protected $routeMiddleware = [
        'admin' => CheckAdmin::class,
        'verified' => VerifyEmail::class,
        'throttle' => ThrottleRequests::class,
    ];
}
```

**Middleware usage in routes:**

```php
<?php
// Single middleware
Route::get('/admin', [AdminController::class, 'index'])
     ->middleware('admin');

// Multiple middleware
Route::post('/posts', [PostController::class, 'store'])
     ->middleware(['auth', 'verified']);

// Middleware group
Route::middleware('api')->group(function() {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
});

// Exclude middleware
Route::post('/login', [AuthController::class, 'login'])
     ->withoutMiddleware('csrf');

// Middleware with parameters
Route::get('/posts/{id}', [PostController::class, 'show'])
     ->middleware('can:view,id');
```

**Real-world middleware examples:**

```php
<?php
// Middleware 1: API key validation
class ApiKeyValidation {
    public function handle($request, Closure $next) {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey || !$this->isValidKey($apiKey)) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }
        
        return $next($request);
    }
}

// Middleware 2: Request logging
class LogRequests {
    public function handle($request, Closure $next) {
        $start = microtime(true);
        $response = $next($request);
        
        Log::info(
            "Method: {$request->method()}, "
            . "Path: {$request->path()}, "
            . "Status: {$response->getStatusCode()}, "
            . "Time: " . (microtime(true) - $start) . "s"
        );
        
        return $response;
    }
}

// Middleware 3: CORS handling
class CorsMiddleware {
    public function handle($request, Closure $next) {
        return $next($request)
                   ->header('Access-Control-Allow-Origin', '*')
                   ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
                   ->header('Access-Control-Allow-Headers', 'Content-Type');
    }
}
```

---

## 33. Laravel Model Relationships: Polymorphic, Many-to-Many Advanced?

### उत्तर:

Advanced relationship patterns complex data structures handle করে।

**Polymorphic relationships:**

```php
<?php
// Multiple models can have comments/likes
// Post, Video, Image dapat di-comment

// Models
class Post extends Model {
    public function comments() {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Video extends Model {
    public function comments() {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Comment extends Model {
    public function commentable() {
        return $this->morphTo();
    }
}

// Usage
$post = Post::find(1);
$post->comments()->create(['text' => 'Nice post!']);

$video = Video::find(1);
$video->comments()->create(['text' => 'Great video!']);

// Get all comments with their parent
$comments = Comment::with('commentable')->get();
foreach ($comments as $comment) {
    echo get_class($comment->commentable);  // Post or Video
}
```

**Many-to-many advanced:**

```php
<?php
// Pivot table with additional columns
class Student extends Model {
    public function courses() {
        return $this->belongsToMany(Course::class, 'course_student')
                   ->withPivot('grade', 'attended_at', 'completed_at')
                   ->withTimestamps();
    }
}

// Usage
$student = Student::find(1);

// Attach with pivot data
$student->courses()->attach(1, [
    'grade' => 'A',
    'attended_at' => now(),
]);

// Update pivot
$student->courses()->updateExistingPivot(1, [
    'completed_at' => now(),
]);

// Get with pivot
$courses = $student->courses()->where('grade', 'A')->get();

foreach ($student->courses as $course) {
    echo $course->pivot->grade;  // Access pivot
}
```

**Many-to-many through:**

```php
<?php
// Author -> Books -> Publishers (through books)
class Author extends Model {
    public function publishers() {
        return $this->hasManyThrough(
            Publisher::class,
            Book::class,
            'author_id',  // Foreign key on books
            'id',         // Foreign key on publishers
            'id',         // Local key on authors
            'publisher_id' // Local key on books
        );
    }
}

// Usage
$author = Author::find(1);
$publishers = $author->publishers()->get();  // All publishers of author's books
```

**Advanced relationships with constraints:**

```php
<?php
class Post extends Model {
    // Only published comments
    public function publishedComments() {
        return $this->hasMany(Comment::class)
                   ->where('published', true)
                   ->orderBy('created_at', 'desc');
    }
    
    // Comments with author
    public function commentsWithAuthor() {
        return $this->hasMany(Comment::class)
                   ->with('author:id,name,email');
    }
}

// Usage
$post = Post::with('commentsWithAuthor')->find(1);
```

---

## 34. Laravel Form Request Validation: Custom Rules এবং Messages?

### उत्तर:

Form Request validation clean এবং reusable।

**Form Request তৈরি:**

```bash
php artisan make:request StorePostRequest
php artisan make:request UpdatePostRequest
```

**Form Request implementation:**

```php
<?php
// app/Http/Requests/StorePostRequest.php
class StorePostRequest extends FormRequest
{
    // Authorization check
    public function authorize()
    {
        return auth()->check();
    }
    
    // Validation rules
    public function rules()
    {
        return [
            'title' => 'required|string|max:255|unique:posts',
            'content' => 'required|string|min:10',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array|max:5',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|mimes:jpeg,png|max:2048',
        ];
    }
    
    // Custom messages
    public function messages()
    {
        return [
            'title.required' => 'Post title is required.',
            'title.unique' => 'A post with this title already exists.',
            'content.min' => 'Content must be at least 10 characters.',
            'featured_image.mimes' => 'Image must be JPEG or PNG.',
        ];
    }
    
    // Attribute names
    public function attributes()
    {
        return [
            'title' => 'post title',
            'content' => 'post content',
            'tags' => 'post tags',
        ];
    }
    
    // Prepare input data
    public function prepareForValidation()
    {
        $this->merge([
            'slug' => Str::slug($this->title),
            'user_id' => auth()->id(),
        ]);
    }
    
    // Validated data
    public function validated()
    {
        return array_merge(parent::validated(), [
            'user_id' => auth()->id(),
        ]);
    }
}

// Usage in controller
class PostController {
    public function store(StorePostRequest $request) {
        // Data automatically validated
        $post = Post::create($request->validated());
        
        return redirect()->route('posts.show', $post);
    }
}
```

**Custom validation rules:**

```php
<?php
// Create custom rule
php artisan make:rule GreaterThanField

// app/Rules/GreaterThanField.php
class GreaterThanField implements ValidationRule
{
    public function __construct(private string $field) {}
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value <= data_get(request()->all(), $this->field)) {
            $fail("The {$attribute} must be greater than {$this->field}.");
        }
    }
}

// Usage in rules
public function rules()
{
    return [
        'start_date' => 'required|date',
        'end_date' => ['required', 'date', new GreaterThanField('start_date')],
    ];
}

// Or inline rule
public function rules()
{
    return [
        'price' => [
            'required',
            'numeric',
            function($attribute, $value, $fail) {
                if ($value < 0) {
                    $fail("Price cannot be negative.");
                }
            },
        ],
    ];
}
```

**Conditional validation:**

```php
<?php
public function rules()
{
    return [
        'type' => 'required|in:personal,company',
        'name' => 'required|string',
        
        // Conditional rules
        'company_name' => Rule::requiredIf($this->type === 'company'),
        'personal_id' => Rule::requiredIf($this->type === 'personal'),
        
        // Or with when
        'tax_id' => [
            Rule::when(
                $this->type === 'company',
                'required|regex:/^[0-9]{10}$/'
            ),
        ],
    ];
}
```

---

## 35. Laravel Eloquent Accessors & Mutators (Deep)

### उत्तर:

Modern approach (PHP 8+) attributes use করে।

**Mutators (setters):**

```php
<?php
class User extends Model
{
    // New syntax (PHP 8+)
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,  // Getter
            set: fn ($value) => Hash::make($value),  // Setter
        );
    }
    
    // Usage
    $user = new User();
    $user->password = 'plain_password';
    $user->save();
    // Password automatically hashed
}
```

**Accessors (getters):**

```php
<?php
class User extends Model
{
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
        );
    }
    
    // Usage
    echo $user->first_name;  // Returns: John (not: john)
}
```

**Computed properties:**

```php
<?php
class User extends Model
{
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
    
    // Usage
    echo $user->full_name;  // John Doe (computed on-the-fly)
}
```

**Attribute casts:**

```php
<?php
class Post extends Model
{
    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'is_featured' => 'boolean',
        'metadata' => 'json',
        'tags' => 'array',
        'published_date' => 'immutable_datetime',
        'options' => AsCollection::class,  // Custom cast
    ];
}

// Usage
$post = Post::find(1);
echo $post->published_at->format('Y-m-d');  // Carbon instance
echo $post->metadata['color'];  // Array access
```

---

## 36. Laravel Notifications System: Mail, SMS, Database?

### उत्तर:

Multi-channel notifications flexibly।

**Notification তৈরি:**

```bash
php artisan make:notification OrderShipped
```

**Notification class:**

```php
<?php
class OrderShipped extends Notification
{
    use Queueable;
    
    public function __construct(public Order $order) {}
    
    // Channels to send through
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }
    
    // Mail channel
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->greeting('Order Shipped!')
                    ->line('Order #' . $this->order->id . ' has been shipped.')
                    ->action('Track Order', route('orders.show', $this->order))
                    ->line('Thank you for your purchase!');
    }
    
    // Database channel
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => 'shipped',
            'tracking_number' => $this->order->tracking_number,
        ];
    }
    
    // SMS channel
    public function toNexmo(object $notifiable): NexmoMessage
    {
        return (new NexmoMessage())
                    ->content("Your order #{$this->order->id} has shipped!");
    }
}

// Send notification
$user->notify(new OrderShipped($order));
// Or queue
$user->notifyLater(now()->addMinutes(5), new OrderShipped($order));
```

**Queued notifications:**

```php
<?php
class OrderShipped extends Notification implements ShouldQueue
{
    public $queue = 'notifications';
    
    public $timeout = 300;  // 5 minutes
}

// Send (will be queued)
$user->notify(new OrderShipped($order));
```

**Notification channels:**

```php
<?php
// Custom channel
class SlackChannel
{
    public function send($notifiable, Notification $notification)
    {
        $slack = new SlackClient(config('services.slack.token'));
        
        $slack->post('https://hooks.slack.com/...', [
            'text' => $notification->toSlack(),
        ]);
    }
}

// Register in NotificationChannels
'slack' => SlackChannel::class,

// Use in notification
public function via() {
    return ['slack'];
}

public function toSlack() {
    return "Order #{$this->order->id} shipped!";
}
```

---

## 37. Laravel Observers: Model events এবং callbacks?

### उत्तर:

Observers model lifecycle manage করে।

**Observer তৈরি:**

```bash
php artisan make:observer PostObserver --model=Post
```

**Observer implementation:**

```php
<?php
class PostObserver
{
    public function creating(Post $post) {
        // Before create
        $post->slug = Str::slug($post->title);
    }
    
    public function created(Post $post) {
        // After create
        Log::info("Post {$post->id} created");
    }
    
    public function updating(Post $post) {
        // Before update
        if ($post->isDirty('title')) {
            $post->slug = Str::slug($post->title);
        }
    }
    
    public function updated(Post $post) {
        // After update
        Cache::forget('all_posts');
    }
    
    public function deleting(Post $post) {
        // Before delete
    }
    
    public function deleted(Post $post) {
        // After delete
        Log::info("Post {$post->id} deleted");
    }
    
    public function restoring(Post $post) {
        // Before restore (soft delete)
    }
    
    public function restored(Post $post) {
        // After restore
    }
    
    public function forceDeleting(Post $post) {
        // Before force delete
    }
    
    public function forceDeleted(Post $post) {
        // After force delete
    }
}

// Register observer
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Post::observe(PostObserver::class);
        
        // Or observe multiple
        Comment::observe([CommentObserver::class]);
    }
}
```

---

## 38. Laravel Scopes: Query constraints reusable?

### উत्তর:

Scopes query কে readable এবং reusable রাখে।

**Local scopes:**

```php
<?php
class Post extends Model
{
    // Simple scope
    public function scopePublished($query)
    {
        return $query->where('published_at', '<', now());
    }
    
    // Scope with parameter
    public function scopeByAuthor($query, User $author)
    {
        return $query->where('user_id', $author->id);
    }
    
    // Scope with multiple conditions
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('deleted_at', null);
    }
}

// Usage
$posts = Post::published()->byAuthor($user)->get();
$active = Post::active()->paginate();
```

**Global scopes:**

```php
<?php
class Post extends Model
{
    protected static function booted()
    {
        // Always apply this scope
        static::addGlobalScope('published', function($query) {
            $query->where('published_at', '<', now());
        });
    }
}

// Usage
Post::all();  // Only published posts

// Bypass global scope
Post::withoutGlobalScopes()->get();  // All posts
Post::withoutGlobalScope('published')->get();
```

---

## 39. Laravel Route Model Binding: Implicit এবং Explicit?

### उत्तर:

Route model binding automatic model resolution করে।

**Implicit binding:**

```php
<?php
// Route
Route::get('/posts/{post}', [PostController::class, 'show']);

// Controller
class PostController {
    public function show(Post $post) {
        // $post automatically resolved from {post} parameter
        return view('posts.show', ['post' => $post]);
    }
}

// URL: /posts/1
// Laravel finds Post with id=1
```

**Custom key binding:**

```php
<?php
// Model
class Post extends Model {
    public function getRouteKeyName() {
        return 'slug';  // Use slug instead of id
    }
}

// Route
Route::get('/posts/{post}', [PostController::class, 'show']);

// URL: /posts/my-post-title
// Laravel finds Post with slug='my-post-title'
```

**Scoped binding:**

```php
<?php
// Only find post belonging to user
Route::get('/users/{user}/posts/{post}', function(User $user, Post $post) {
    // Ensure post belongs to user
    return $post->where('user_id', $user->id)->firstOrFail();
});

// Or with custom binding
public function scopeByUser($query, User $user) {
    return $query->where('user_id', $user->id);
}
```

---

## 40. Laravel Artisan Commands: Custom Command তৈরি?

### उत्তर:

Custom commands automatable tasks handle করে।

**Command তৈরি:**

```bash
php artisan make:command SendDailyEmails
php artisan make:command ClearOldLogs
```

**Command implementation:**

```php
<?php
class SendDailyEmails extends Command
{
    protected $signature = 'email:daily {--user=}';
    protected $description = 'Send daily emails to users';
    
    public function handle()
    {
        $userId = $this->option('user');
        
        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::where('send_daily_email', true)->get();
        }
        
        foreach ($users as $user) {
            Mail::send(new DailyEmailMail($user));
            $this->info("Email sent to {$user->email}");
        }
        
        return Command::SUCCESS;
    }
}

// Run command
php artisan email:daily
php artisan email:daily --user=1
```

**Scheduled commands:**

```php
<?php
// app/Console/Kernel.php
class Kernel extends ConsoleKernel
{
    protected $commands = [
        SendDailyEmails::class,
    ];
    
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('email:daily')->dailyAt('09:00');
        $schedule->command('backup:run')->weekly();
        $schedule->command('queue:work --max-jobs=1000')
                 ->everyMinute()
                 ->withoutOverlapping();
    }
}

// Run scheduler (cron)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

*Laravel Advanced Features Complete (21-40)* ✅

*সব topics covered: Caching, Database, Broadcasting, Queue, Storage, Lifecycle, Middleware, Relationships, Validation, Observers, Scopes, Binding, Commands*


