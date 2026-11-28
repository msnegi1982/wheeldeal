<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\SiteSettings as SiteSettingsPage;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        SiteSettings::flush();

        parent::tearDown();
    }

    public function test_super_admin_can_update_site_identity_and_contact_details(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'is_active' => true,
        ]);
        $admin->assignRole(UserRole::SUPER_ADMIN->label());

        $this->actingAs($admin)->get('/admin/site-settings')->assertOk();

        Livewire::actingAs($admin)
            ->test(SiteSettingsPage::class)
            ->set('data.site_name', 'Test Motors')
            ->set('data.phone', '+91 11111 22222')
            ->set('data.whatsapp', '+91 91111 22222')
            ->set('data.email', 'hello@testmotors.test')
            ->set('data.address', '12 Test Road, Bengaluru')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Test Motors', Setting::get('site_name'));
        $this->assertSame('+91 11111 22222', Setting::get('phone'));
        $this->assertSame('919111122222', Setting::get('whatsapp'));
        $this->assertSame('hello@testmotors.test', Setting::get('email'));
        $this->assertSame('12 Test Road, Bengaluru', Setting::get('address'));

        $this->assertSame('Test Motors', config('seo.site_name'));
        $this->assertSame('Test Motors', config('marketplace.brand.name'));
        $this->assertSame('+91 11111 22222', config('marketplace.brand.phone'));
        $this->assertSame('919111122222', config('marketplace.brand.whatsapp'));
        $this->assertSame('hello@testmotors.test', config('marketplace.brand.email'));
        $this->assertSame('12 Test Road, Bengaluru', config('marketplace.brand.address'));
    }

    public function test_editor_cannot_manage_site_settings_by_default(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = User::factory()->create([
            'email' => 'editor@example.test',
            'is_active' => true,
        ]);
        $editor->assignRole(UserRole::EDITOR->label());

        $this->actingAs($editor)
            ->get('/admin/site-settings')
            ->assertForbidden();
    }

    public function test_logo_setting_is_exposed_as_a_public_url(): void
    {
        Storage::fake('public');

        $path = 'branding/test-logo.png';
        Storage::disk('public')->put($path, 'fake-image');

        Setting::set('logo', $path);
        SiteSettings::reload();

        $this->assertSame(Storage::disk('public')->url($path), site_logo_url());
    }
}
