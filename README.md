# Shopify Product API (GraphQL 2025-07)

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
# Create DB Name: pippa-shopify-db for store Shopify Location and reuse it
php artisan migrate
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
  "title": "T-Shirt Premium TT",
  "description": "<p>High quality premium cotton t-shirt</p>",
  "vendor": "My Brand",
  "product_type": "Apparel",
  "options": [
     { "name": "Size", "values": ["Small", "Medium", "Large"] },
     { "name": "Color", "values": ["Red", "Blue"] }
  ],
  "variants": [
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
    "response": {
        "product_id": "gid://shopify/Product/8505346982085",
        "product": {
            "id": "gid://shopify/Product/8505346982085",
            "title": "T-Shirt Premium TT",
            "options": [
                {
                    "id": "gid://shopify/ProductOption/10729882648773",
                    "name": "Size"
                },
                {
                    "id": "gid://shopify/ProductOption/10729882681541",
                    "name": "Color"
                }
            ]
        },
        "options": [
            {
                "id": "gid://shopify/ProductOption/10729882648773",
                "name": "Size"
            },
            {
                "id": "gid://shopify/ProductOption/10729882681541",
                "name": "Color"
            }
        ],
        "variants": [
            {
                "id": "gid://shopify/ProductVariant/45343083921605",
                "title": "Small / Blue",
                "inventoryItem": {
                    "id": "gid://shopify/InventoryItem/47477671821509",
                    "sku": "TSHIRT-SM-BLUE"
                },
                "inventoryQuantity": 50,
                "selectedOptions": [
                    {
                        "name": "Size",
                        "value": "Small"
                    },
                    {
                        "name": "Color",
                        "value": "Blue"
                    }
                ]
            },
            {
                "id": "gid://shopify/ProductVariant/45343083954373",
                "title": "Medium / Red",
                "inventoryItem": {
                    "id": "gid://shopify/InventoryItem/47477671854277",
                    "sku": "TSHIRT-MD-RED"
                },
                "inventoryQuantity": 75,
                "selectedOptions": [
                    {
                        "name": "Size",
                        "value": "Medium"
                    },
                    {
                        "name": "Color",
                        "value": "Red"
                    }
                ]
            },
            {
                "id": "gid://shopify/ProductVariant/45343083987141",
                "title": "Medium / Blue",
                "inventoryItem": {
                    "id": "gid://shopify/InventoryItem/47477671887045",
                    "sku": "TSHIRT-MD-BLUE"
                },
                "inventoryQuantity": 60,
                "selectedOptions": [
                    {
                        "name": "Size",
                        "value": "Medium"
                    },
                    {
                        "name": "Color",
                        "value": "Blue"
                    }
                ]
            },
            {
                "id": "gid://shopify/ProductVariant/45343084019909",
                "title": "Large / Red",
                "inventoryItem": {
                    "id": "gid://shopify/InventoryItem/47477671919813",
                    "sku": "TSHIRT-LG-RED"
                },
                "inventoryQuantity": 40,
                "selectedOptions": [
                    {
                        "name": "Size",
                        "value": "Large"
                    },
                    {
                        "name": "Color",
                        "value": "Red"
                    }
                ]
            },
            {
                "id": "gid://shopify/ProductVariant/45343084052677",
                "title": "Large / Blue",
                "inventoryItem": {
                    "id": "gid://shopify/InventoryItem/47477671952581",
                    "sku": "TSHIRT-LG-BLUE"
                },
                "inventoryQuantity": 30,
                "selectedOptions": [
                    {
                        "name": "Size",
                        "value": "Large"
                    },
                    {
                        "name": "Color",
                        "value": "Blue"
                    }
                ]
            }
        ],
        "images": [
            {
                "alt": "T-Shirt",
                "mediaContentType": "IMAGE",
                "originalSource": "https://cdn.shopify.com/s/files/1/0533/2089/files/placeholder-images-image_large.png"
            }
        ],
        "inventory_set": false
    }
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
php artisan test --filter=ShopifyProductRepositoryTest
```

Feature test mocked repository ব্যবহার করে endpoint এর happy path যাচাই করে।

---

## 🧱 Project Structure

```
app/
  Exceptions/ShopifyApiException.php
  Http/
    Controllers/ProductController.php
    Requests/StoreShopifyProductRequest.php
  Models/
    ShopifyLocation.php
  Providers/AppServiceProvider.php
  Repositories/
    ShopifyProductRepositoryInterface.php
    ShopifyProductRepository.php
  Services/
    ShopifyGraphQLClient.php
    ShopifyProductService.php
database/migrations/2025_11_14_091552_create_shopify_locations_table.php
config/shopify.php
routes/api.php
tests/Feature/ShopifyProductRepositoryTest.php
```

---

## 🧩 Implementation Notes (How it works)

**3-step GraphQL orchestration:**

1. `productCreate` → product + options তৈরি
    - রেসপন্স থেকে `product` inforamion
2. `productvariantsbulkcreate` →  variants + ইমেজ URL থেকে product media তৈরি
    - রেসপন্স থেকে `variants` / `media`

> কিছু API ভার্সনে এক ধাপে ইমেজসহ ভ্যারিয়েন্ট দেয়া সম্ভব—তবু এই ৩-ধাপ পদ্ধতি স্থিতিশীল ও স্পষ্ট।

**Validation highlights**

-   `title`: required|string|max:255
-   `options`: array<string>
-   `description` : string
-   `vendor` : string
-   `product_type` : string
-   `options` : array
-   `variants.*.option_values` length == `options` length
-   `variants.*.sku`: required
-   `variants.*.inventory_quantity`: integer
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

## 📁 Postman Quick Test Root Folder Provide File Upload Your Postman

```
    📄 Shopify.postman_collection.json
```

## 🧯 Troubleshooting

-   **401/403**: Headers ঠিক আছে তো? `X-Shopify-Access-Token`/`X-Shopify-Shop-Domain` সঠিক কিনা চেক করুন।
-   **422 userErrors**: `options` ও `variants.*.option_values` length mismatch, invalid price/sku ইত্যাদি।
-   **Images fail**: ইমেজ URL পাবলিকলি অ্যাক্সেসেবল কিনা নিশ্চিত করুন।
-   **Rate limiting**: Shopify throttleStatus দেখে interval দিন।

---
