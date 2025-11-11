<?php

namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public string $title,
        public ?string $bodyHtml,
        public ?string $vendor,
        public ?string $productType,
        /** @var string[]|null */
        public ?array $tags,
        /** @var string[]|null */
        public ?array $options,
        /** @var VariantDTO[] */
        public array $variants
    ) {}
}
