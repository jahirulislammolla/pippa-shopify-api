<?php

namespace App\Services;

use App\Exceptions\ShopifyApiException;

class ShopifyProductService
{
    public function __construct(private ShopifyGraphQLClient $client) {}

    /** productCreate */

    public function createProduct(string $shop, string $token, $product): array
    {
        $mutation = <<<'GQL'
        mutation productCreate($product: ProductCreateInput!, $media: [CreateMediaInput!]) {
        productCreate(product: $product, media: $media) {
            product {
            id
            handle
            options { id name optionValues { id name } }
            variants(first: 1) { nodes { id title } } # শুধু ডিফল্ট ভ্যারিয়েন্ট থাকবে
            }
            userErrors { field message }
        }
        }
        GQL;

        // productOptions ==> [{ name, values: [{name}, ...] }]
        $productOptions = [];
        $optionNames = $product->options ?? [];                // e.g. ["Size","Color"]

        // variants থেকে প্রতি অপশনের ইউনিক ভ্যালু বের করা
        $optionValuesByIndex = [];
        foreach ($product->variants as $v) {                   // $v->optionValues e.g. ["S","Black"]
            foreach ($v->optionValues as $idx => $val) {
                $optionValuesByIndex[$idx][$val] = true;       // set-like
            }
        }

        foreach ($optionNames as $idx => $name) {
            $vals = array_keys($optionValuesByIndex[$idx] ?? []);
            // Shopify নতুন স্কিমায় value অবজেক্ট লাগে: { name: "S" }
            $valueObjs = array_map(fn($x) => ['name' => (string)$x], $vals);
            // যদি একেবারে কোনো ভ্যারিয়েন্ট না থাকে, অন্তত ১টা ডিফল্ট ভ্যালু দিন
            if (empty($valueObjs)) {
                $valueObjs = [['name' => 'Default']];
            }
            $productOptions[] = [
                'name' => $name,
                'values' => $valueObjs,
            ];
        }

        $productPayload = [
            'title'           => $product->title,
            'description'     => $product->description,
            'vendor'          => $product->vendor,
            'productType'     => $product->productType,
            'productOptions'  => $productOptions,  // <-- এখন values সহ
        ];


        $json = $this->client->query($shop, $token, $mutation, [
            'product' => $productPayload,
        ]);

        $errors = $json['data']['productCreate']['userErrors'] ?? [];
        if (!empty($errors)) {
            throw new ShopifyApiException('Shopify userErrors on productCreate', $errors, 422);
        }

        return $json['data']['productCreate']['product'] ?? [];
    }


    /** productCreateMedia (images) */
    public function attachImages(string $shop, string $token, string $productId, array $images): array
    {
        if (empty($images)) {
            return [];
        }

        $mutation = <<<'GQL'
        mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
        productCreateMedia(productId: $productId, media: $media) {
            media { id alt status }
            mediaUserErrors { field message }
        }
        }
        GQL;

        $media = collect($images)->map(fn ($img) => [
            'mediaContentType' => 'IMAGE',
            'originalSource' => $img['src'],
            'alt' => $img['alt'] ?? null,
        ])->values()->all();

        $json = $this->client->query($shop, $token, $mutation, [
            'productId' => $productId,
            'media' => $media,
        ]);

        $mediaErrors = $json['data']['productCreateMedia']['mediaUserErrors'] ?? [];
        if (!empty($mediaErrors)) {
            throw new ShopifyApiException('Shopify mediaUserErrors on productCreateMedia', $mediaErrors, 422);
        }

        return $json['data']['productCreateMedia']['media'] ?? [];
    }

    /** productVariantUpdate (link imageId) */
    public function setVariantImage(string $shop, string $token, string $variantId, string $imageId): array
    {
        $mutation = <<<'GQL'
mutation productVariantUpdate($input: ProductVariantInput!) {
  productVariantUpdate(input: $input) {
    productVariant { id image { id alt } }
    userErrors { field message }
  }
}
GQL;

        $json = $this->client->query($shop, $token, $mutation, [
            'input' => ['id' => $variantId, 'imageId' => $imageId],
        ]);

        $errors = $json['data']['productVariantUpdate']['userErrors'] ?? [];
        if (!empty($errors)) {
            throw new ShopifyApiException('Shopify userErrors on productVariantUpdate', $errors, 422);
        }

        return $json['data']['productVariantUpdate']['productVariant'] ?? [];
    }
}
