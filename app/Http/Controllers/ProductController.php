<?php

namespace App\Http\Controllers;

use App\Exceptions\ShopifyApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopifyProductRequest;
use App\Repositories\ShopifyProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        try {
            $res = $this->repo->createProductWithVariantsAndImages($shop, $token, $data);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'response' => $res,
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

    public function index(Request $request): JsonResponse
    {
        $shop = $request->header('X-Shopify-Shop-Domain');
        $token = $request->header('X-Shopify-Access-Token');

        if (!$shop || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Missing Shopify headers.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $query = <<<'GQL'
                query {
                    shop {
                        products(first: 20) {
                            nodes {
                                id
                                title
                            }
                        }
                    }
                }
                GQL;

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json',
            ])->post("https://{$shop}/admin/api/2023-10/graphql.json", [
                'query' => $query
            ]);


            return response()->json([
                'success' => true,
                'products' => $response['data']['shop']['products']['nodes'] ?? [],
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
