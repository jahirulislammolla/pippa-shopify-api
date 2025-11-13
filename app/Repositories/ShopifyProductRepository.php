<?php

namespace App\Repositories;

use App\Exceptions\ShopifyApiException;
use App\Services\ShopifyProductService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ShopifyProductRepository
{
    public function __construct(private ShopifyProductService $service) {}

    public function createProductWithVariantsAndImages( $product, string $shop, string $token): array
    {
        // 1) create product + variants
        $created = $this->service->createProduct($shop, $token, $product);
        $productId = $created['id'] ?? null;
        $vendor = $created['vendor'] ?? null;
        $handle = $created['handle'] ?? null;
        $created_options = $created['options'] ?? [];
        #create option name to id map
        $optionNammeToIdMap  = [];
        foreach ( $created_options as $opt ) {
            $optionNammeToIdMap[ $opt['name'] ] = $opt['id'];
        }

        if (!$productId) {
            throw new ShopifyApiException('Product ID missing in response', null, 500);
        }

        $variantInput = [];
        foreach( $product['variants'] as $variant ) {
            $variantPayload = [
                'optionValues' => [],
                'price' => $variant['price'] ?? '0.00',
                'sku' => $variant['sku'] ?? '',
            ];


            // input option upore based kore loop values
            foreach ( $variant['option_values'] as $optIndex => $opt ) {
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
                        'name' => $optName,
                        'optionId' => $optId,
                    ];
                }
            }

            $variantInput[] = $variantPayload;
        }

        if(!empty($variantInput)){

            $created_variant = $this->service->createBulkVariants($shop, $token, $productId, $variantInput);

            if (!empty($created_variant)) {
                $inventoryQuantities = [];
                foreach ($created_variant as $cv) {
                    $inventoryItemId = $cv['inventoryItem']['id'] ?? null;
                    $sku = $cv['sku'] ?? null;

                    // find matching input variant by sku
                    $inputVariant = null;
                    foreach ($product['variants'] as $v) {
                        if (($v['sku'] ?? null) === $sku) {
                            $inputVariant = $v;
                            break;
                        }
                    }

                    if ($inventoryItemId && $inputVariant) {
                        $inventoryQuantity = $inputVariant['inventory_quantity'] ?? null;
                        if (is_int($inventoryQuantity)) {
                            $inventoryQuantities[$inventoryItemId] =  $inventoryQuantity;
                        }
                    }
                }
                //set variant inventories
                if (!empty($inventoryQuantities)) {
                    $is_created_inventories = $this->service->setInventories($shop, $token, $inventoryQuantities);
                }
            }

        }

        // collect image list from request variants
        $imageRequests =  [];
        foreach( $product['images'] as $img ) {
                $imageRequests[] =
                    [
                        'alt' => $img['alt'] ?? null,
                        'mediaContentType' => 'IMAGE',
                        'originalSource' => $img['src'] ?? null,
                    ];
        }

        if (!empty($imageRequests)) {
            $mediaList = $this->service->attachImages($shop, $token, $productId, $imageRequests);
        }

        return [
            'product_id' => $productId,
            'vendor' => $vendor,
            'options' => $created_options,
            'variants' => $created_variant ?? [],
            'images' => $mediaList ?? [],
            'inventory_set' => $is_created_inventories ?? false,
        ];
    }
}
