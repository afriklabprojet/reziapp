<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPrivateFileTest extends TestCase
{
    use RefreshDatabase;

    private const PRIVATE_DOCUMENT_PATH = 'documents/kyc.txt';

    public function test_admin_can_stream_private_file(): void
    {
        Storage::fake('private');

        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => 'admin']);
        Storage::disk('private')->put(self::PRIVATE_DOCUMENT_PATH, 'sensitive-content');

        $response = $this->actingAs($admin)
            ->get(route('admin.private-file', ['path' => self::PRIVATE_DOCUMENT_PATH]));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertStreamedContent('sensitive-content');
    }

    public function test_non_admin_cannot_access_private_file(): void
    {
        Storage::fake('private');

        /** @var User $user */
        $user = User::factory()->createOne(['role' => 'user']);
        Storage::disk('private')->put(self::PRIVATE_DOCUMENT_PATH, 'sensitive-content');

        $this->actingAs($user)
            ->get(route('admin.private-file', ['path' => self::PRIVATE_DOCUMENT_PATH]))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_private_file_route(): void
    {
        $this->get(route('admin.private-file', ['path' => self::PRIVATE_DOCUMENT_PATH]))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_traverse_outside_private_disk(): void
    {
        Storage::fake('private');

        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/files/private/%2E%2E%2F.env')
            ->assertNotFound();
    }
}
