<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['cloudinary.cloud_name' => null, 'cloudinary.storage' => 'auto']);
    }

    public function test_public_pages_and_search_render(): void
    {
        $product = Product::factory()->create(['title' => 'Linen shirt']);
        Product::factory()->create(['title' => 'Canvas tote', 'description' => 'Everyday bag']);
        $this->get('/')->assertOk()->assertSee('Linen shirt');
        $this->get('/products?search=Linen')->assertOk()->assertSee('Linen shirt')->assertDontSee('Canvas tote');
        $this->get('/products?search=nomatches')->assertOk()->assertSee('No matches just yet');
        $this->get(route('products.show', $product))->assertOk()->assertSee('Delivery details');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/products/999')->assertNotFound();
    }

    public function test_registration_login_and_logout(): void
    {
        $this->post('/register', ['name' => 'Jane Doe', 'username' => 'jane', 'phone' => '9800000000', 'password' => 'Password@123', 'password_confirmation' => 'Password@123', 'role' => 'admin'])->assertRedirect('/');
        $user = User::where('username', 'jane')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check('Password@123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->post('/login', ['username' => 'jane', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'jane', 'password' => 'Password@123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_collection_preview_shows_edit_controls_instead_of_checkout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['sizes' => ['M'], 'colors' => ['Blue']]);

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee('YOUR STOREFRONT')
            ->assertSee('Store administrator')
            ->assertDontSee('Order in a few simple steps');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Edit product')
            ->assertSee(route('admin.products.edit', $product))
            ->assertSee('Blue')
            ->assertDontSee('Delivery details')
            ->assertDontSee('Place order')
            ->assertDontSee(route('orders.store', $product));
    }

    public function test_customer_product_page_keeps_checkout_and_member_profile(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Member')
            ->assertSee('Delivery details')
            ->assertSee('Place order')
            ->assertDontSee('Edit product');
    }

    #[DataProvider('weakRegistrationPasswords')]
    public function test_registration_rejects_weak_passwords(string $password): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe', 'username' => 'jane', 'phone' => '9800000000',
            'password' => $password, 'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public static function weakRegistrationPasswords(): array
    {
        return [
            'only six characters' => ['Aa1@bc'],
            'missing uppercase' => ['password@123'],
            'missing lowercase' => ['PASSWORD@123'],
            'missing number' => ['Password@abc'],
            'missing symbol' => ['Password123'],
        ];
    }

    public function test_registration_accepts_seven_characters_with_all_required_character_types(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe', 'username' => 'jane', 'phone' => '9800000000',
            'password' => 'Aa1@bcd', 'password_confirmation' => 'Aa1@bcd',
        ])->assertSessionHasNoErrors()->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('Aa1@bcd', User::firstOrFail()->password));
    }

    public function test_registration_rejects_mismatched_password_confirmation(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe', 'username' => 'jane', 'phone' => '9800000000',
            'password' => 'Password@123', 'password_confirmation' => 'Different@123',
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_order_uses_server_price_and_optional_variants(): void
    {
        $product = Product::factory()->create(['price' => '1250.50']);
        $this->post(route('orders.store', $product), [...$this->orderData(), 'quantity' => 3, 'total_price' => 1, 'unit_price' => 1])
            ->assertRedirect(route('products.show', $product))->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['product_id' => $product->id, 'total_price' => 3751.5, 'unit_price' => 1250.5, 'quantity' => 3, 'size' => null, 'color' => null, 'user_id' => null]);
    }

    public function test_invalid_and_missing_variants_and_quantity_are_rejected(): void
    {
        $product = Product::factory()->create(['sizes' => ['M'], 'colors' => ['Blue']]);
        $this->post(route('orders.store', $product), $this->orderData())->assertSessionHasErrors(['size', 'color']);
        $this->post(route('orders.store', $product), [...$this->orderData(), 'size' => 'XL', 'color' => 'Red', 'quantity' => 21])->assertSessionHasErrors(['size', 'color', 'quantity']);
        $this->assertDatabaseCount('orders', 0);
        $this->post(route('orders.store', $product), [...$this->orderData(), 'size' => 'M', 'color' => 'Blue'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_admin_pages_are_protected_and_render_for_admins(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/orders')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create();
        foreach (['/admin/dashboard', '/admin/products', '/admin/products/create', '/admin/products/'.$product->id.'/edit', '/admin/orders'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_can_create_edit_and_delete_product_with_local_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/products', [...$this->productData(), 'images' => [$this->image()]])->assertRedirect(route('admin.products.index'));
        $product = Product::firstOrFail();
        $oldPath = substr($product->images()->first()->image_public_id, 6);
        Storage::disk('public')->assertExists($oldPath);
        $this->put(route('admin.products.update', $product), [...$this->productData(), 'title' => 'Updated product'])->assertSessionHasNoErrors();
        $this->assertSame($oldPath, substr($product->images()->first()->image_public_id, 6));
        $this->put(route('admin.products.update', $product), [...$this->productData(), 'images' => [$this->image()]])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($oldPath);
        $newPath = substr($product->images()->first()->image_public_id, 6);
        Storage::disk('public')->assertExists($newPath);
        $this->delete(route('admin.products.destroy', $product))->assertSessionHas('success');
        $this->assertDatabaseCount('products', 0);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_product_validation_and_failed_upload_preserve_existing_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create(['title' => 'Original']);
        $product->images()->create(['image_url' => '/storage/original.png', 'image_public_id' => 'local:original.png']);
        $this->put(route('admin.products.update', $product), [...$this->productData(), 'price' => -10])->assertSessionHasErrors('price');
        $this->mock(CloudinaryService::class)->shouldReceive('upload')->once()->andThrow(new \RuntimeException('Storage unavailable'));
        $this->put(route('admin.products.update', $product), [...$this->productData(), 'images' => [$this->image()]])->assertSessionHasErrors('images');
        $this->assertSame('Original', $product->fresh()->title);
        $this->assertDatabaseHas('product_images', ['image_public_id' => 'local:original.png']);
    }

    public function test_orders_can_be_filtered_and_status_updated_without_deleting_history(): void
    {
        $product = Product::factory()->create();
        $this->post(route('orders.store', $product), $this->orderData());
        $order = Order::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/orders?search=Jane&status=pending')->assertOk()->assertSee('Jane Doe');
        $this->patch(route('admin.orders.status', $order), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->patch(route('admin.orders.status', $order), ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
        $this->get('/admin/dashboard')->assertOk()->assertSee('1,250.50');
        $this->delete(route('admin.products.destroy', $product))->assertSessionHasErrors('product');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_cloudinary_requests_are_signed(): void
    {
        config(['cloudinary.cloud_name' => 'test-cloud', 'cloudinary.api_key' => 'test-key', 'cloudinary.api_secret' => 'test-secret']);
        Http::preventStrayRequests();
        Http::fake([
            '*/image/upload' => Http::response(['secure_url' => 'https://example.com/image.png', 'public_id' => 'ecommerce/products/image']),
            '*/image/destroy' => Http::response(['result' => 'ok']),
        ]);
        $service = new CloudinaryService;
        $result = $service->upload($this->image());
        $this->assertSame('ecommerce/products/image', $result['public_id']);
        $service->delete($result['public_id']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/destroy') && $request['signature'] === sha1('invalidate=true&public_id=ecommerce/products/image&timestamp='.$request['timestamp'].'test-secret'));
        Http::assertSentCount(2);
    }

    public function test_local_storage_can_be_selected_when_cloud_credentials_are_invalid(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        config(['cloudinary.storage' => 'local', 'cloudinary.cloud_name' => 'configured-cloud', 'cloudinary.api_secret' => 'placeholder']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/admin/products', [...$this->productData(), 'images' => [$this->image()]])
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $image = Product::firstOrFail()->images()->firstOrFail();
        $this->assertStringStartsWith('local:', $image->image_public_id);
        $this->assertStringStartsWith('/storage/products/', $image->image_url);
        Storage::disk('public')->assertExists(substr($image->image_public_id, 6));
        Http::assertNothingSent();
    }

    public function test_edit_with_an_empty_browser_upload_keeps_existing_images(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create();
        $image = $product->images()->create(['image_url' => '/storage/products/existing.png', 'image_public_id' => 'local:products/existing.png']);
        $this->mock(CloudinaryService::class)->shouldNotReceive('upload');

        $this->call('POST', route('admin.products.update', $product), [
            ...$this->productData(), '_method' => 'PUT', 'title' => 'Edited without an image',
        ], [], ['images' => ['name' => [''], 'type' => [''], 'tmp_name' => [''], 'error' => [UPLOAD_ERR_NO_FILE], 'size' => [0]]])
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $this->assertSame('Edited without an image', $product->fresh()->title);
        $this->assertTrue($product->images()->sole()->is($image));
    }

    public function test_edit_replaces_images_locally_even_with_invalid_cloud_credentials(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        config(['cloudinary.storage' => 'local', 'cloudinary.cloud_name' => 'configured-cloud', 'cloudinary.api_key' => 'invalid', 'cloudinary.api_secret' => 'invalid']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create();
        Storage::disk('public')->put('products/old.png', 'old image');
        $product->images()->create(['image_url' => '/storage/products/old.png', 'image_public_id' => 'local:products/old.png']);

        $this->post(route('admin.products.update', $product), [
            ...$this->productData(), '_method' => 'PUT', 'images' => [$this->image()],
        ])->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $image = $product->images()->sole();
        Storage::disk('public')->assertExists(substr($image->image_public_id, 6));
        Storage::disk('public')->assertMissing('products/old.png');
        Http::assertNothingSent();
    }

    public function test_rejected_cloud_upload_explains_credentials_and_preserves_product(): void
    {
        config(['cloudinary.storage' => 'cloudinary', 'cloudinary.cloud_name' => 'test-cloud', 'cloudinary.api_key' => 'invalid', 'cloudinary.api_secret' => 'invalid']);
        Http::preventStrayRequests();
        Http::fake(['*/image/upload' => Http::response(['error' => ['message' => 'Invalid Signature']], 401)]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = Product::factory()->create(['title' => 'Original product']);
        $image = $product->images()->create(['image_url' => '/storage/products/original.png', 'image_public_id' => 'local:products/original.png']);

        $this->from(route('admin.products.edit', $product))->post(route('admin.products.update', $product), [
            ...$this->productData(), '_method' => 'PUT', 'images' => [$this->image()],
        ])->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors(['images' => 'Image storage rejected the upload. Correct the Cloudinary credentials or select local image storage, then select your images again. No product changes were saved.']);

        $this->assertSame('Original product', $product->fresh()->title);
        $this->assertTrue($product->images()->sole()->is($image));
        Http::assertSentCount(1);
    }

    private function orderData(): array
    {
        return ['name' => 'Jane Doe', 'phone' => '9800000000', 'location' => 'Kathmandu', 'quantity' => 1];
    }

    private function productData(): array
    {
        return ['title' => 'Everyday shirt', 'price' => '1499.50', 'description' => 'A comfortable everyday essential.', 'sizes' => ['M'], 'colors' => ['Blue']];
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('product.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9b8AAAAASUVORK5CYII='));
    }
}
