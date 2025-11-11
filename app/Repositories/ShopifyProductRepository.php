<?php

namespace App\Repositories;

use App\Exceptions\ShopifyApiException;
use App\Services\ShopifyProductService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ShopifyProductRepository implements ShopifyProductRepositoryInterface
{
    public function __construct(private ShopifyProductService $service) {}

    public function createProductWithVariantsAndImages( $product, string $shop, string $token): array
    {
        // 1) create product + variants
        $created = $this->service->createProduct($shop, $token, $product);
        $productId = $created['id'] ?? null;
        $handle = $created['handle'] ?? null;
        $variantEdges = $created['variants']['edges'] ?? [];

        if (!$productId) {
            throw new ShopifyApiException('Product ID missing in response', null, 500);
        }

        // map sku -> variantId
        $variantMap = collect($variantEdges)->mapWithKeys(function ($edge) {
            $node = $edge['node'];
            return [$node['sku'] => $node['id']];
        });

        // collect image list from request variants
        $imageRequests = $product['images'] ?? [];


        // 2) attach images (optional)
        $imageIdMap = collect();
        if (!empty($imageRequests)) {
            $mediaList = $this->service->attachImages($shop, $token, $productId, $imageRequests);
            // naive map by order: imageRequests[i] -> mediaList[i]
            foreach ($mediaList as $i => $m) {
                if (isset($m['id'])) {
                    $imageIdMap->put($i, $m['id']);
                }
            }
        }



        return [
            'product_id' => $productId,
            'handle' => $handle,
        ];
    }
}
