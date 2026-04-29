<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Product::with('category');
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Category', 'Description', 'Price', 'Stock', 'Enabled', 'Created At'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->category->name ?? '',
            $product->description,
            $product->price,
            $product->stock,
            $product->enabled ? 'Yes' : 'No',
            $product->created_at->toDateTimeString(),
        ];
    }
}
