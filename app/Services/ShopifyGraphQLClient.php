<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Exceptions\ShopifyApiException;

class ShopifyGraphQLClient
{
    public function __construct(
        protected ?Client $http = null
    ) {
        $this->http = $http ?: new Client();
    }

    /**
     * @throws ShopifyApiException
     */
    public function query(string $shop, string $token, string $query, array $variables = []): array
    {
        $version = config('shopify.version', '2025-07');
        // create url for shopify graphql
        $url = "https://{$shop}/admin/api/{$version}/graphql.json";

        try {
            // request query for execute
            $res = $this->http->post($url, [
                'headers' => [
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'query' => $query,
                    'variables' => $variables,
                ],
                'timeout' => (float) config('shopify.timeout', 20),
                'connect_timeout' => (float) config('shopify.connect_timeout', 5),
            ]);
        } catch (GuzzleException $e) {
            throw new ShopifyApiException("Shopify request failed: {$e->getMessage()}", null, 503);
        }

        $json = json_decode((string) $res->getBody(), true);

        if (isset($json['errors'])) {
            throw new ShopifyApiException('GraphQL errors from Shopify', $json['errors'], 502);
        }

        return $json;
    }
}
