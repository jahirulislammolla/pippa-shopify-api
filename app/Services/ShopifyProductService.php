<?php

namespace App\Services;

use App\Exceptions\ShopifyApiException;

class ShopifyProductService
{
    public function __construct(private ShopifyGraphQLClient $client) {}

    /** productCreate
     *  https://shopify.dev/docs/api/admin-graphql/2025-07/mutations/productcreate
     */

    public function createProduct(string $shop, string $token, $product): array
    {
        $mutation = <<<'GQL'
        mutation productCreate($product: ProductCreateInput!) {
            productCreate($product: $product) {
                product {
                    id
                    title
                    vendor
                    options {
                    id
                    name
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $productPayload = [
            'title'           => $product->title,
            'descriptionHtml'     => $product->description ?? '',
            'vendor'          => $product->vendor ?? '',
            'productType'     => $product->productType ?? '',
            'productOptions'  => $$product->options ?? [],  // <-- এখন values সহ
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


    /** productCreateMedia (images)
     * https://shopify.dev/docs/api/admin-graphql/2025-07/mutations/productcreatemedia
     */
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


        $json = $this->client->query($shop, $token, $mutation, [
            'productId' => $productId,
            'media' => $images,
        ]);

        $mediaErrors = $json['data']['productCreateMedia']['mediaUserErrors'] ?? [];
        if (!empty($mediaErrors)) {
            throw new ShopifyApiException('Shopify mediaUserErrors on productCreateMedia', $mediaErrors, 422);
        }

        return $json['data']['productCreateMedia']['media'] ?? [];
    }

    /** productVariantUpdate
     *  https://shopify.dev/docs/api/admin-graphql/latest/mutations/productVariantsBulkCreate
     */
    public function createBulkVariants(string $shop, string $token, string $productId, array $productVariantInput): array
    {
        $mutation = <<<'GQL'
        mutation productVariantUpdate($productId: ID!, $variants: [CreateInputVariants!]!) {
            productVariantsBulkCreate(productId: $productId, variants: $variants) {
                productVariants {
                    id
                    title
                    inventoryItem
                    inventoryQuantity
                    selectedOptions {
                    name
                    value
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $json = $this->client->query($shop, $token, $mutation, [
            'productId' => $productId,
            'variants' => $productVariantInput,
        ]);

        $errors = $json['data']['productVariantUpdate']['userErrors'] ?? [];
        if (!empty($errors)) {
            throw new ShopifyApiException('Shopify userErrors on productVariantUpdate', $errors, 422);
        }

        return $json['data']['productVariantUpdate']['productVariant'] ?? [];
    }

    public function setInventories(string $shop, string $token, array $inventoryMap): bool
    {
        $q = <<<'GQL'
        { shop { primaryLocation { id name } } }
        GQL;

        $res = $this->client->query($shop, $token, $q);
        $locationId = $res['data']['shop']['primaryLocation']['id'] ?? null;
        if (!empty($locationId))
        {
            foreach ($inventoryMap as $inventoryItemId => $availableQuantity) {
                $this->setInventory($shop, $token, $inventoryItemId, $locationId, $availableQuantity);
            }
            return true;
        }
        return false;
    }
      /** inventoryAdjustQuantity
     *  https://shopify.dev/docs/api/admin-graphql/2025-07/mutations/inventoryactivate
     */
    private function setInventory(string $shop, string $token, string $inventoryItemId, string $locationId, int $availableQuantity): array
    {
        $mutation = <<<'GQL'
        mutation inventoryAdjustQuantity($inventoryItemId: ID!, $locationId: ID!, $available: Int!) {
            ActivateInventoryItem(inventoryItemId: $inventoryItemId, locationId: $locationId, available: $available) {
                inventoryLevel {
                    id
                    available
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $json = $this->client->query($shop, $token, $mutation, [
            'inventoryItemId' => $inventoryItemId,
            'locationId' => $locationId,
            'available' => $availableQuantity,
        ]);

        $errors = $json['data']['inventoryActivate']['userErrors'] ?? [];
        if (!empty($errors)) {
            throw new ShopifyApiException('Shopify userErrors on inventoryActivate', $errors, 422);
        }

        return $json['data']['inventoryActivate']['inventoryLevel'] ?? [];
    }
}
