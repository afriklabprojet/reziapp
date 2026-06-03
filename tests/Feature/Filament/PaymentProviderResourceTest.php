<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\PaymentProviderResource;
use App\Filament\Resources\PaymentProviderResource\Pages\CreatePaymentProvider;
use App\Filament\Resources\PaymentProviderResource\Pages\EditPaymentProvider;
use App\Filament\Resources\PaymentProviderResource\Pages\ListPaymentProviders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProviderResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_page_classes_exist(): void
    {
        $this->assertTrue(class_exists(ListPaymentProviders::class));
        $this->assertTrue(class_exists(CreatePaymentProvider::class));
        $this->assertTrue(class_exists(EditPaymentProvider::class));
    }

    public function test_resource_getpages_returns_all_three_routes(): void
    {
        $pages = PaymentProviderResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    public function test_list_page_loads_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('filament.admin.resources.payment-providers.index'))
            ->assertOk();
    }
}
