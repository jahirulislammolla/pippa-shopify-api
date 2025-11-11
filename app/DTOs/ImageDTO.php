<?php

namespace App\DTOs;

class ImageDTO
{
    public function __construct(
        public string $src,
        public ?string $alt = null
    ) {}
}
