<?php

namespace App\Repositories;

use App\DTOs\ProductDTO;

interface ShopifyProductRepositoryInterface
{
    /**
     * @return array{product_id:string, handle:string}
     */
    public function createProductWithVariantsAndImages(ProductDTO $product, string $shop, string $token): array;
}
