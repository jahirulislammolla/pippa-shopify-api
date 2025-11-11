# Laravel Shopify Product API (GraphQL 2025-07)

Shopify Admin GraphQL (2025-07) ব্যবহার করে **Product + Variants + Images** তৈরি করার Laravel REST API।
**Repository Pattern + DI**, **Form Request Validation**, **Guzzle error handling**, এবং **PHPUnit test** অন্তর্ভুক্ত।

## ✨ Features

-   POST `/api/shopify/products` → এক কলেই **product + variants** তৈরি
-   প্রতি ভ্যারিয়েন্টে **ইমেজ অ্যাসাইন** (3-step flow: `productCreate` → `productCreateMedia` → `productVariantUpdate`)
-   Headers থেকে **Shop domain** ও **Admin API token** গ্রহণ (multi-store ready)
-   **FormRequest** দিয়ে কঠোর validation
-   **Repository pattern** + পরিষ্কার সার্ভিস লেয়ার
-   **Guzzle** exception ও Shopify **userErrors** হ্যান্ডলিং
-   **Feature test** (mocked repository)

---

## 🧰 Requirements

-   PHP 8.2+ / 8.3, Composer
-   Laravel 11/12
-   Shopify **store** (live বা development)
-   Custom app এর **Admin API access token**
-   স্টোর ডোমেইন: `your-store.myshopify.com`
-   App scopes (minimum): `write_products`
    (ইমেজ URL থেকে মিডিয়া করলে সাধারণত `write_files`/`read_files`ও লাগতে পারে)

---

## 🚀 Quick Start (Local)

```bash
git clone https://github.com/jahirulislammolla/pippa-shopify-api.git
cd pippa-shopify-api
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
# http://127.0.0.1:8000
```

> এই প্রজেক্টে Shopify token/domain `.env` এ নয়—**headers** থেকেই নেয়া হয়।

---

## 🛣️ Endpoint

**POST** `/api/shopify/products`

### Required Headers

-   `Accept: application/json`
-   `Content-Type: application/json`
-   `X-Shopify-Access-Token: <your_admin_api_token>`
-   `X-Shopify-Shop-Domain: your-store.myshopify.com`

### Request Body (sample)

```json
{
  "title": "Cotton T-Shirt Premium",
  "description": "<p>High quality premium cotton t-shirt</p>",
  "vendor": "My Brand",
  "product_type": "Apparel",
  "options": [
     { "name": "Size", "values": ["Small", "Medium", "Large"] },
     { "name": "Color", "values": ["Red", "Blue"] }
  ],
  "variants": [
    { "sku": "TSHIRT-SM-RED",  "price": "19.99", "inventory_quantity": 100, "option_values": ["Small", "Red"] },
    { "sku": "TSHIRT-SM-BLUE", "price": "19.99", "inventory_quantity": 50,  "option_values": ["Small", "Blue"] },
    { "sku": "TSHIRT-MD-RED",  "price": "21.99", "inventory_quantity": 75,  "option_values": ["Medium", "Red"] },
    { "sku": "TSHIRT-MD-BLUE", "price": "21.99", "inventory_quantity": 60,  "option_values": ["Medium", "Blue"] },
    { "sku": "TSHIRT-LG-RED",  "price": "23.99", "inventory_quantity": 40,  "option_values": ["Large", "Red"] },
    { "sku": "TSHIRT-LG-BLUE", "price": "23.99", "inventory_quantity": 30,  "option_values": ["Large", "Blue"] }
  ],
  "images": [
    {
      "src": "https://cdn.shopify.com/s/files/1/0533/2089/files/placeholder-images-image_large.png",
      "alt": "T-Shirt"
    }
  ]
}
```

### Success (200)

```json
{
    "success": true,
    "message": "Product created successfully.",
    "product_id": "gid://shopify/Product/1234567890",
    "handle": "t-shirt-pro"
}
```

### Errors

-   **422**: Validation / Shopify `userErrors`
-   **401/403**: Missing/invalid headers
-   **5xx**: Upstream/Unexpected

Error shape:

```json
{
    "success": false,
    "message": "Shopify userErrors on productCreate",
    "errors": [
        { "field": ["variants", "0", "price"], "message": "Invalid price" }
    ]
}
```

---

## 🧪 Testing

```bash
php artisan test
```

Feature test mocked repository ব্যবহার করে endpoint এর happy path যাচাই করে।

---

## 🧱 Project Structure

```
app/
  DTOs/ (ImageDTO, ProductDTO, VariantDTO)
  Exceptions/ShopifyApiException.php
  Http/
    Controllers/ProductController.php
    Requests/StoreShopifyProductRequest.php
  Providers/AppServiceProvider.php
  Repositories/
    ShopifyProductRepositoryInterface.php
    ShopifyProductRepository.php
  Services/
    ShopifyGraphQLClient.php
    ShopifyProductService.php
config/shopify.php
routes/api.php
tests/Feature/CreateShopifyProductTest.php
```

---

## 🧩 Implementation Notes (How it works)

**3-step GraphQL orchestration:**

1. `productCreate` → product + variants তৈরি
    - রেসপন্স থেকে `product.id`, `variant.id` (sku দিয়ে ম্যাপ করুন)
2. `productCreateMedia` → ইমেজ URL থেকে product media তৈরি
    - রেসপন্স থেকে `media.id` / `image.id`
3. `productVariantUpdate` → প্রতিটি variant এ `imageId` সেট

> কিছু API ভার্সনে এক ধাপে ইমেজসহ ভ্যারিয়েন্ট দেয়া সম্ভব—তবু এই ৩-ধাপ পদ্ধতি স্থিতিশীল ও স্পষ্ট।

**Validation highlights**

-   `title`: required|string|max:255
-   `options`: array<string>
-   `variants.*.option_values` length == `options` length
-   `variants.*.sku`: required
-   `variants.*.price`: `/^\d+(\.\d{1,2})?$/`

---

## 🧷 Config

`config/shopify.php`

```php
return [
  'version' => '2025-07',
  'timeout' => 20,
  'connect_timeout' => 5,
];
```

DI binding: `App\Providers\RepositoryServiceProvider`
`config/app.php` → providers এ যোগ করুন।

---

## 🧑‍💻 Postman Quick Test (cURL)

```bash
curl -X POST http://127.0.0.1:8000/api/shopify/products   -H "Accept: application/json"   -H "Content-Type: application/json"   -H "X-Shopify-Access-Token: <YOUR_ADMIN_API_TOKEN>"   -H "X-Shopify-Shop-Domain: your-store.myshopify.com"   -d '{
    "title": "T-Shirt Pro",
    "options": ["Size","Color"],
    "variants": [
      {
        "sku": "TSHIRT-PRO-S-BLK",
        "price": "29.99",
        "inventory_quantity": 25,
        "option_values": ["S","Black"],
        "image": {"src":"https://example.com/images/black-s.jpg","alt":"Black Small"}
      }
    ]
  }'
```

---

## ☁️ Production Deploy (Ubuntu 22.04 + Nginx + PHP-FPM 8.3)

```bash
sudo apt update && sudo apt -y upgrade
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt -y install php8.3 php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath unzip git nginx
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

sudo mkdir -p /var/www/shopify-api && sudo chown -R $USER:www-data /var/www/shopify-api
cd /var/www/shopify-api
# git clone <repo> .
# composer install --no-dev --optimize-autoloader
# cp .env.example .env && php artisan key:generate

sudo tee /etc/nginx/sites-available/shopify-api.conf >/dev/null <<'NGINX'
server {
    listen 80;
    server_name YOUR_DOMAIN_OR_IP;
    root /var/www/shopify-api/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~* \.(log|env)$ { deny all; }

    client_max_body_size 20M;
    sendfile on;
}
NGINX

sudo ln -s /etc/nginx/sites-available/shopify-api.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php artisan config:cache && php artisan route:cache
```

> ডোমেইন না থাকলে `server_name _;` বা সার্ভার IP দিন। SSL চাইলে পরে Certbot ব্যবহার করুন।

---

## 🧯 Troubleshooting

-   **401/403**: Headers ঠিক আছে তো? `X-Shopify-Access-Token`/`X-Shopify-Shop-Domain` সঠিক কিনা চেক করুন।
-   **422 userErrors**: `options` ও `variants.*.option_values` length mismatch, invalid price/sku ইত্যাদি।
-   **Images fail**: ইমেজ URL পাবলিকলি অ্যাক্সেসেবল কিনা নিশ্চিত করুন।
-   **Rate limiting**: Shopify throttleStatus দেখে interval দিন।

---
