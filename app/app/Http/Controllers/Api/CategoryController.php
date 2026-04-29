<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/api/categories',
        summary: 'List all categories',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        responses: [new OA\Response(response: 200, description: 'List of categories')]
    )]
    public function index()
    {
        return response()->json([
            'data' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
