<?php

namespace Tests\Feature;

use App\Exceptions\ShopifyApiException;
use App\Repositories\ShopifyProductRepository;
use App\Services\ShopifyProductService;
use PHPUnit\Framework\TestCase;

class ShopifyProductRepositoryTest extends TestCase
{
    private ShopifyProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Pure PHPUnit mock, Mockery না
        $this->service = $this->createMock(ShopifyProductService::class);
    }

    private function makeSampleProductPayload(): array
    {
        return [
            'title'        => 'Special T-Shirt Premium',
            'description'  => '<p>High quality premium cotton t-shirt</p>',
            'vendor'       => 'My Brand',
            'product_type' => 'Apparel',
            'options'      => [
                [ 'name' => 'Size',  'values' => ['Small', 'Medium', 'Large'] ],
                [ 'name' => 'Color', 'values' => ['Red', 'Blue'] ],
            ],
            'variants'     => [
                [ 'sku' => 'TSHIRT-SM-RED',  'price' => '19.99', 'inventory_quantity' => 100, 'option_values' => ['Small', 'Red'] ],
                [ 'sku' => 'TSHIRT-SM-BLUE', 'price' => '19.99', 'inventory_quantity' => 50,  'option_values' => ['Small', 'Blue'] ],
                [ 'sku' => 'TSHIRT-MD-RED',  'price' => '21.99', 'inventory_quantity' => 75,  'option_values' => ['Medium', 'Red'] ],
                [ 'sku' => 'TSHIRT-MD-BLUE', 'price' => '21.99', 'inventory_quantity' => 60,  'option_values' => ['Medium', 'Blue'] ],
                [ 'sku' => 'TSHIRT-LG-RED',  'price' => '23.99', 'inventory_quantity' => 40,  'option_values' => ['Large', 'Red'] ],
                [ 'sku' => 'TSHIRT-LG-BLUE', 'price' => '23.99', 'inventory_quantity' => 30,  'option_values' => ['Large', 'Blue'] ],
            ],
            'images' => [
                [
                    'src' => 'https://cdn.shopify.com/s/files/1/0533/2089/files/placeholder-images-image_large.png',
                    'alt' => 'T-Shirt',
                ],
            ],
        ];
    }

    public function test_it_creates_product_with_variants_and_images_successfully(): void
    {
        $shop  = 'test-shop.myshopify.com';
        $token = 'shpat_test_token';

        $payload = $this->makeSampleProductPayload();

        $productId  = 'gid://shopify/Product/1234567890';
        $locationId = 'gid://shopify/Location/111';

        // createProduct() থেকে যেটা আসবে
        $createdOptions = [
            [ 'id' => 'gid://shopify/ProductOption/size',  'name' => 'Size' ],
            [ 'id' => 'gid://shopify/ProductOption/color', 'name' => 'Color' ],
        ];

        $createProductResponse = [
            'id'      => $productId,
            'title'   => $payload['title'],
            'options' => $createdOptions,
        ];

        $this->service
            ->expects($this->once())
            ->method('createProduct')
            ->with(
                $this->equalTo($shop),
                $this->equalTo($token),
                $this->equalTo($payload)
            )
            ->willReturn($createProductResponse);

        // getOrCreateLocation() expectation
        $locationResponse = [
            'locationId' => $locationId,
            'location'   => ['id' => 111],
        ];

        $this->service
            ->expects($this->once())
            ->method('getOrCreateLocation')
            ->with(
                $this->equalTo($shop),
                $this->equalTo($token),
                $this->callback(function ($locationArg) {
                    // repo-র ভিতরের hard-coded location ঠিকঠাক গেছে কিনা
                    $this->assertIsArray($locationArg);
                    $this->assertSame('New York Shop', $locationArg['name']);
                    $this->assertArrayHasKey('address', $locationArg);

                    return true;
                })
            )
            ->willReturn($locationResponse);

        // createBulkVariants() থেকে যা রিটার্ন করবে
        $createdVariantsResponse = [
            ['id' => 'gid://shopify/ProductVariant/1', 'sku' => 'TSHIRT-SM-RED'],
            ['id' => 'gid://shopify/ProductVariant/2', 'sku' => 'TSHIRT-SM-BLUE'],
        ];

        $this->service
            ->expects($this->once())
            ->method('createBulkVariants')
            ->with(
                $this->equalTo($shop),
                $this->equalTo($token),
                $this->equalTo($productId),

                // 4th argument: $variantInput
                $this->callback(function (array $variantInput) use ($payload, $locationId, $createdOptions) {
                    // সব variants পাঠানো হয়েছে কিনা
                    $this->assertCount(count($payload['variants']), $variantInput);

                    $first = $variantInput[0];

                    // basic fields
                    $this->assertSame('19.99', $first['price']);
                    $this->assertSame('TSHIRT-SM-RED', $first['inventoryItem']['sku']);
                    $this->assertSame(100, $first['inventoryQuantities']['availableQuantity']);
                    $this->assertSame($locationId, $first['inventoryQuantities']['locationId']);

                    // optionValues mapping ঠিক আছে কিনা
                    $this->assertCount(2, $first['optionValues']);

                    $names     = array_column($first['optionValues'], 'name');
                    $optionIds = array_column($first['optionValues'], 'optionId');

                    $this->assertContains('Small', $names);
                    $this->assertContains('Red', $names);

                    // Size/Color এর optionId গুলো map থেকে এসেছে কিনা
                    $this->assertContains($createdOptions[0]['id'], $optionIds); // Size id
                    $this->assertContains($createdOptions[1]['id'], $optionIds); // Color id

                    return true;
                }),

                // 5th argument: $mediaList
                $this->callback(function (array $mediaList) use ($payload) {
                    $this->assertCount(count($payload['images']), $mediaList);

                    $firstImage = $mediaList[0];
                    $this->assertSame('IMAGE', $firstImage['mediaContentType']);
                    $this->assertSame($payload['images'][0]['src'], $firstImage['originalSource']);
                    $this->assertSame($payload['images'][0]['alt'], $firstImage['alt']);

                    return true;
                })
            )
            ->willReturn($createdVariantsResponse);

        // Act
        $repo   = new ShopifyProductRepository($this->service);
        $result = $repo->createProductWithVariantsAndImages($shop, $token, $payload);

        // Assert
        $this->assertIsArray($result);
        $this->assertSame($productId, $result['product_id']);
        $this->assertSame($productId, $result['product']['id']);

        $this->assertSame($createdOptions, $result['options']);
        $this->assertSame($createdVariantsResponse, $result['variants']);

        $this->assertIsArray($result['images']);
        $this->assertCount(count($payload['images']), $result['images']);

        $this->assertArrayHasKey('inventory_set', $result);
        $this->assertIsBool($result['inventory_set']);
    }

    public function test_it_throws_exception_when_product_id_missing(): void
    {
        $shop    = 'test-shop.myshopify.com';
        $token   = 'shpat_test_token';
        $payload = $this->makeSampleProductPayload();

        // createProduct() থেকে id ছাড়া response
        $this->service
            ->expects($this->once())
            ->method('createProduct')
            ->with(
                $this->equalTo($shop),
                $this->equalTo($token),
                $this->equalTo($payload)
            )
            ->willReturn([
                'title' => $payload['title'],
                // 'id' নাই — এখানে exception আশা করছি
            ]);

        $repo = new ShopifyProductRepository($this->service);

        $this->expectException(ShopifyApiException::class);
        $this->expectExceptionMessage('Product ID missing in response');

        $repo->createProductWithVariantsAndImages($shop, $token, $payload);
    }
}
