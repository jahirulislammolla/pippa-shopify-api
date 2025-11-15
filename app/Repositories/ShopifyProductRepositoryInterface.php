<?php

namespace App\Repositories;

interface ShopifyProductRepositoryInterface
{
    /**
     * @return array{product_id:string, handle:string}
     */
    public function createProductWithVariantsAndImages( string $shop, string $token, array $product): array;
}
