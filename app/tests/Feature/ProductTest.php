<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function auth()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_with_wrong_credentials_returns_422(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(422);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $this->auth()
            ->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_can_filter_products_by_category(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $cat1->id]);
        Product::factory()->count(3)->create(['category_id' => $cat2->id]);

        $this->auth()
            ->getJson("/api/products?category_id={$cat1->id}")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_products_by_enabled_status(): void
    {
        Product::factory()->count(2)->create(['enabled' => true]);
        Product::factory()->count(3)->create(['enabled' => false]);

        $this->auth()
            ->getJson('/api/products?enabled=true')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create();

        $this->auth()
            ->postJson('/api/products', [
                'name'        => 'Test Product',
                'category_id' => $category->id,
                'description' => 'A description',
                'price'       => 19.99,
                'stock'       => 10,
                'enabled'     => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Product');

        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_create_product_validates_required_fields(): void
    {
        $this->auth()
            ->postJson('/api/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock']);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $this->auth()
            ->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_show_returns_404_for_missing_product(): void
    {
        $this->auth()
            ->getJson('/api/products/9999')
            ->assertStatus(404);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();

        $this->auth()
            ->putJson("/api/products/{$product->id}", [
                'name'        => 'Updated Name',
                'category_id' => $product->category_id,
                'price'       => 29.99,
                'stock'       => 5,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_update_validates_required_fields(): void
    {
        $product = Product::factory()->create();

        $this->auth()
            ->putJson("/api/products/{$product->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_soft_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->auth()
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted successfully');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // ── Bulk Delete ───────────────────────────────────────────────────────────

    public function test_can_bulk_delete_products(): void
    {
        $products = Product::factory()->count(3)->create();
        $ids = $products->pluck('id')->toArray();

        $this->auth()
            ->deleteJson('/api/products/bulk', ['ids' => $ids])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Products deleted successfully');

        foreach ($ids as $id) {
            $this->assertSoftDeleted('products', ['id' => $id]);
        }
    }

    public function test_bulk_delete_validates_ids(): void
    {
        $this->auth()
            ->deleteJson('/api/products/bulk', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }
}
