<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );

        $categories = ['Electronics', 'Clothing', 'Food & Beverage', 'Books', 'Sports'];

        foreach ($categories as $name) {
            $category = Category::firstOrCreate(['name' => $name]);
            Product::factory()->count(5)->create(['category_id' => $category->id]);
        }
    }
}
