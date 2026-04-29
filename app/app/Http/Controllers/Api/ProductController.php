<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

#[OA\Info(title: 'Product API', version: '1.0.0', description: 'RESTful API for managing products')]
#[OA\SecurityScheme(securityScheme: 'sanctum', type: 'http', scheme: 'bearer', bearerFormat: 'JWT')]
class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/products',
        summary: 'List products',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'enabled', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated list of products')]
    )]
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('enabled')) {
            $query->where('enabled', filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        return ProductResource::collection($query->paginate(10));
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id', 'price', 'stock'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'stock', type: 'integer'),
                    new OA\Property(property: 'enabled', type: 'boolean'),
                ]
            )
        ),
        tags: ['Products'],
        responses: [new OA\Response(response: 201, description: 'Product created')]
    )]
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get a product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Product $product)
    {
        return new ProductResource($product->load('category'));
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update a product',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'stock', type: 'integer'),
                    new OA\Property(property: 'enabled', type: 'boolean'),
                ]
            )
        ),
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Product updated')]
    )]
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Soft delete a product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Product deleted')]
    )]
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    #[OA\Delete(
        path: '/api/products/bulk',
        summary: 'Bulk delete products',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'))]
            )
        ),
        tags: ['Products'],
        responses: [new OA\Response(response: 200, description: 'Products deleted')]
    )]
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Products deleted successfully']);
    }

    #[OA\Get(
        path: '/api/products/export-link',
        summary: 'Get a temporary signed download link for Excel export',
        security: [['sanctum' => []]],
        tags: ['Products'],
        responses: [new OA\Response(response: 200, description: 'Signed download URL')]
    )]
    public function exportLink()
    {
        $url = URL::temporarySignedRoute(
            'products.export.download',
            now()->addMinutes(5)
        );

        $appUrl = rtrim(config('app.url'), '/');
        $parsed = parse_url($url);
        $internalBase = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $url = str_replace($internalBase, $appUrl, $url);

        return response()->json(['url' => $url]);
    }

    #[OA\Get(
        path: '/api/products/export',
        summary: 'Export products to Excel (via signed URL)',
        tags: ['Products'],
        responses: [new OA\Response(response: 200, description: 'Excel file download')]
    )]
    public function export(Request $request)
    {
        return Excel::download(new ProductsExport(), 'products.xlsx');
    }
}
