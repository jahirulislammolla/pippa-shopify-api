<?php

namespace Tests\Feature;

use App\DTOs\ProductDTO;
use App\DTOs\VariantDTO;
use App\Repositories\ShopifyProductRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopifyProductTest extends TestCase
{
    public function test_can_create_product(): void
    {
        // Bind a fake repository to avoid real Shopify calls
        $this->app->bind(ShopifyProductRepositoryInterface::class, function () {
            return new class implements ShopifyProductRepositoryInterface {
                public function createProductWithVariantsAndImages(ProductDTO $product, string $shop, string $token): array
                {
                    return [
                        'product_id' => 'gid://shopify/Product/1234567890',
                        'handle' => 't-shirt-pro',
                    ];
                }
            };
        });

        $payload = [
            'title' => 'Test Product',
            'options' => ['Size'],
            'variants' => [
                [
                    'sku' => 'SKU-1',
                    'price' => '9.99',
                    'inventory_quantity' => 5,
                    'option_values' => ['M'],
                    'image' => ['src' => 'https://example.com/img.jpg', 'alt' => 'img'],
                ]
            ]
        ];

        $resp = $this->postJson('/api/shopify/products', $payload, [
            'X-Shopify-Shop-Domain' => 'demo.myshopify.com',
            'X-Shopify-Access-Token' => 'fake',
        ]);

        $resp->assertOk()
             ->assertJson([
                 'success' => true,
                 'product_id' => 'gid://shopify/Product/1234567890',
                 'handle' => 't-shirt-pro',
             ]);
    }
}
