<?php

namespace App\Http\Controllers;

use App\DTOs\ImageDTO;
use App\DTOs\ProductDTO;
use App\DTOs\VariantDTO;
use App\Exceptions\ShopifyApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopifyProductRequest;
use App\Repositories\ShopifyProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(private ShopifyProductRepository $repo) {}

    public function store(StoreShopifyProductRequest $request): JsonResponse
    {
        $shop = $request->header('X-Shopify-Shop-Domain');
        $token = $request->header('X-Shopify-Access-Token');

        if (!$shop || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Missing Shopify headers.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validated();

        $variants = [];
        foreach ($data['variants'] as $v) {
            $img = null;
            if (!empty($v['image']['src'])) {
                $img = new ImageDTO($v['image']['src'], $v['image']['alt'] ?? null);
            }

            $variants[] = new VariantDTO(
                sku: $v['sku'],
                price: (string) $v['price'],
                optionValues: $v['option_values'],
                inventoryQuantity: $v['inventory_quantity'] ?? null,
                image: $img
            );
        }

        $dto = new ProductDTO(
            title: $data['title'],
            bodyHtml: $data['body_html'] ?? null,
            vendor: $data['vendor'] ?? null,
            productType: $data['product_type'] ?? null,
            tags: $data['tags'] ?? null,
            options: $data['options'] ?? null,
            variants: $variants
        );

        try {
            $res = $this->repo->createProductWithVariantsAndImages($dto, $shop, $token);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'product_id' => $res['product_id'],
                'handle' => $res['handle'],
            ]);

        } catch (ShopifyApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], $e->status);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unexpected server error.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
