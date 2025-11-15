<?php

namespace App\Repositories;

use App\Exceptions\ShopifyApiException;
use App\Services\ShopifyProductService;

class ShopifyProductRepository implements ShopifyProductRepositoryInterface
{
    public function __construct(private ShopifyProductService $service) {}

    public function createProductWithVariantsAndImages( string $shop, string $token, array $product): array
    {
        // 1) create product
        $created = $this->service->createProduct($shop, $token, $product);

        $productId = $created['id'] ?? null;

        if (!$productId) {
            throw new ShopifyApiException('Product ID missing in response', null, 500);
        }

        $location = [
            'name' => 'New York Shop',
            'address' => [
                'address1' => '101 Liberty Street',
                'city' => 'New York',
                'provinceCode' => 'NY',
                'countryCode' => 'US',
                'zip' => '10006',
            ],
        ];
        // create or get location call
        $location = $this->service->getOrCreateLocation($shop, $token, $location);

        $locationId = $location['locationId'] ?? null;
        $created_options = $created['options'] ?? [];

        #create option name to id map
        $optionNammeToIdMap  = [];
        foreach ( $created_options as $opt ) {
            $optionNammeToIdMap[ $opt['name'] ] = $opt['id'];
        }

        $variantInput = [];
        // formatted variants
        foreach( $product['variants'] as $variant ) {
            $variantPayload = [
                'optionValues' => [],
                'price' => $variant['price'] ?? '0.00',
                'inventoryItem' => [
                    'sku' => $variant['sku'] ?? '',
                    'cost' => '0.00'
                ],
                'inventoryQuantities' => [
                    'availableQuantity' => $variant['inventory_quantity'] ?? '',
                    'locationId' => $locationId ?? ''
                ],
            ];


            // input option upore based kore loop values
            foreach ( $variant['option_values'] as  $opt ) {
                $optValue = $opt;
                $optName = null;
                // find option name from product options
                foreach ( $product['options'] as $productOpt ) {
                    if ( in_array($optValue, $productOpt['values'] ?? [] )) {
                        $optName = $productOpt['name'] ?? null;
                        break;
                    }
                }
                // get option id from map
                $optId = $optionNammeToIdMap[ $optName ] ?? null;
                if ($optValue && $optName && $optId) {
                    $variantPayload['optionValues'][] = [
                        'name' => $optValue,
                        'optionId' => $optId,
                    ];
                }
            }

            $variantInput[] = $variantPayload;
        }

        // formatted media data
        $mediaList =  [];
        foreach( $product['images'] as $img ) {
                $mediaList[] =
                    [
                        'alt' => $img['alt'] ?? null,
                        'mediaContentType' => 'IMAGE',
                        'originalSource' => $img['src'] ?? null,
                    ];
        }

        // return $variantInput;
        if(!empty($variantInput)){
            // create bulk variants
            $created_variants = $this->service->createBulkVariants($shop, $token, $productId, $variantInput, $mediaList);
        }

        return [
            'product_id' => $productId,
            'product' => $created,
            'options' => $created_options,
            'variants' => $created_variants ?? [],
            'images' => $mediaList ?? [],
            'inventory_set' => $is_created_inventories ?? false,
        ];
    }
}
