<?php

namespace Tests\Feature;

use App\Models\Product as Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectPrintWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_browse_shows_approved_public_artwork_only(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('City Lights');
        $response->assertDontSee('Private Draft');
        $response->assertDontSee('Jakarta Poster');
    }

    public function test_owner_can_view_private_artwork_but_guest_cannot(): void
    {
        $this->seed();
        $artwork = Artwork::where('name', 'Private Draft')->firstOrFail();
        $owner = User::findOrFail($artwork->user_id);

        $this->get(route('artworks.show', $artwork->id))->assertForbidden();

        $this->withSession([
            'user_id' => $owner->id,
            'name' => $owner->name,
            'role' => $owner->role,
        ])->get(route('artworks.show.user', ['username' => $owner->slug, 'id' => $artwork->id]))
            ->assertOk()
            ->assertSee('Private Draft');
    }

    public function test_display_only_artwork_cannot_be_added_to_cart(): void
    {
        $this->seed();
        $buyer = User::where('email', 'bobbyhuntrix@gmail.com')->firstOrFail();
        $artwork = Artwork::where('name', 'Botanical Calm')->firstOrFail();

        $this->withSession([
            'user_id' => $buyer->id,
            'name' => $buyer->name,
            'role' => $buyer->role,
        ])->post(route('cart.add', ['username' => $buyer->slug, 'artwork' => $artwork->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('cart_items', ['product_id' => $artwork->id]);
    }

    public function test_checkout_creates_completed_purchase_with_price_snapshot(): void
    {
        $this->seed();
        $buyer = User::where('email', 'john12@gmail.com')->firstOrFail();
        $artwork = Artwork::where('name', 'Forest Morning')->firstOrFail();

        $session = ['user_id' => $buyer->id, 'name' => $buyer->name, 'role' => $buyer->role];
        $this->withSession($session)->post(route('cart.add', ['username' => $buyer->slug, 'artwork' => $artwork->id]));

        $this->withSession($session)->post(route('cart.checkout', ['username' => $buyer->slug]), [
            'payment_method' => 'Demo Payment',
            'confirmation' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'status' => 'completed',
            'payment_status' => 'simulated_paid',
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'product_id' => $artwork->id,
            'creator_price' => $artwork->price,
        ]);
    }

    public function test_upload_stores_original_filename_and_paths(): void
    {
        Storage::fake('local');
        $this->seed();
        $creator = User::where('email', 'john12@gmail.com')->firstOrFail();

        $this->withSession([
            'user_id' => $creator->id,
            'name' => $creator->name,
            'role' => $creator->role,
        ])->post(route('artworks.store', ['username' => $creator->slug]), [
            'title' => 'Path Check',
            'description' => 'Verifies stored image metadata.',
            'image' => UploadedFile::fake()->createWithContent(
                'path-check.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
            ),
            'category_id' => 1,
            'tags' => 'test',
            'visibility' => 'private',
            'is_printable' => '1',
            'creator_price' => 10000,
        ])->assertRedirect();

        $artwork = Artwork::where('name', 'Path Check')->firstOrFail();

        $this->assertSame('path-check.png', $artwork->original_filename);
        $this->assertNotNull($artwork->original_path);
        $this->assertNotNull($artwork->preview_path);
        Storage::disk('local')->assertExists($artwork->original_path);
        Storage::disk('local')->assertExists('public/' . $artwork->preview_path);
    }

    public function test_account_page_is_user_profile_workspace(): void
    {
        $this->seed();
        $user = User::where('email', 'john12@gmail.com')->firstOrFail();

        $this->withSession([
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ])->get(route('account', ['username' => $user->slug]))
            ->assertOk()
            ->assertSee('Upload image')
            ->assertSee('Own images')
            ->assertSee('Transaction history')
            ->assertSee('Bought print files')
            ->assertSee('Account settings');
    }
}
