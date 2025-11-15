<?php

namespace App\Services;

use App\Exceptions\ShopifyApiException;
use App\Models\ShopifyLocation;

class ShopifyProductService
{
    public function __construct(private ShopifyGraphQLClient $client) {}

    /** productCreate
     *  https://shopify.dev/docs/api/admin-graphql/2025-07/mutations/productcreate
     */

    public function createProduct(string $shop, string $token, array $product): array
    {
        // product create mutation query
        $productCreateMutation = <<<'GQL'
        mutation productCreate($product: ProductCreateInput!) {
            productCreate(product: $product) {
                product {
                    id
                    title
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

        // format productOptions
        $productOptions = [];
        foreach ($product['options'] as $opt) {
            $single_formatted_values = [];
            foreach( $opt['values'] as $val ) {
                $single_formatted_values[] = ['name' => $val ];
            }

            $productOptions[] = [
                'name' => $opt['name'] ?? '',
                'values' => $single_formatted_values ?? [],
            ];
        }

        // create product mutation
        $json = $this->client->query($shop, $token, $productCreateMutation, [
            'product' =>  [
                'title'           => $product['title'] ?? '',
                'descriptionHtml' => $product['description'] ?? '',
                'vendor'          => $product['vendor'] ?? '',
                'productType'     => $product['product_type'] ?? '',
                'productOptions'  => $productOptions,
            ],
        ]);

        $errors = $json['data']['productCreate']['userErrors'] ?? [];
        // if product create
        if (!empty($errors)) {
            throw new ShopifyApiException('Shopify userErrors on productCreate', $errors, 422);
        }

        return $json['data']['productCreate']['product'] ?? [];
    }


    /**
     *  productVariantsBulkCreate
     *  https://shopify.dev/docs/api/admin-graphql/2025-07/mutations/productvariantsbulkcreate
     */
    public function createBulkVariants(string $shop, string $token, string $productId, array $productVariantInput, array $media): array
    {
        // mutation query productVariantsBulkCreate
        $productVariantsBulkMutation = <<<'GQL'
        mutation productVariantUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!, $media: [CreateMediaInput!]) {
            productVariantsBulkCreate(productId: $productId, variants: $variants, media: $media) {
                productVariants {
                    id
                    title
                    inventoryItem {
                        id
                        sku
                    }
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

        // create productVariantsBulkCreate
       $json = $this->client->query($shop, $token, $productVariantsBulkMutation, [
            'productId' => $productId,
            'variants' => $productVariantInput,
            'media' => $media,
        ]);

        // occour any error
        $errors = $json['data']['productVariantsBulkCreate']['userErrors'] ?? [];

        if (!empty($errors)) {
            return $errors;
        }

        // success result
        return $json['data']['productVariantsBulkCreate']['productVariants'] ?? [];
    }


    /**
     *  locationAdd
     *  https://shopify.dev/docs/api/admin-graphql/2025-07/mutations/locationadd
     */
    public function getOrCreateLocation(string $shop, string $token, $location): array
    {
        // Get location from database
        $get_location = ShopifyLocation::where('shop_domain', $shop)->first();

        // if location not exist...then create new location
        if(!$get_location)
        {
            // location create mutation query
            $location_create_query = <<<'GQL'
            mutation  productLocationAdd($input: LocationAddInput!) {
                locationAdd(input: $input) {
                    location {
                        id
                        name
                    }
                }
            }
            GQL;

            // create location
            $res = $this->client->query($shop, $token, $location_create_query, [
                'input' => $location,
            ]);

            $locationId = $res['data']['locationAdd']['location']['id'] ?? null;

            if($locationId)
            {
                //create location save database for further use
                $get_location = ShopifyLocation::updateOrCreate(
                    [
                        'shop_domain'         => $shop,
                        'shopify_location_id' => $locationId,
                    ],
                    [
                        'name'          => $location['name'],
                        'address1'      => $location['address']['address1'] ?? null,
                        'city'          => $location['address']['city'] ?? null,
                        'province_code' => $location['address']['provinceCode'] ?? null,
                        'country_code'  => $location['address']['countryCode'] ?? null,
                        'zip'           => $location['address']['zip'] ?? null,
                    ]
                );
            }
        }

        return [
            'locationId' => $get_location->shopify_location_id ?? null,
            'location' => $get_location,
        ];
    }
}
