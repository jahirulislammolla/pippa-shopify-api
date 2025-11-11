<?php

namespace App\DTOs;

class VariantDTO
{
    public function __construct(
        public string $sku,
        public string $price,
        /** @var string[] */
        public array $optionValues,
        public ?int $inventoryQuantity = null,
        public ?ImageDTO $image = null
    ) {}
}
