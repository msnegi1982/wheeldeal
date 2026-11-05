<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\UserRole;
use App\Models\ContactEnquiry;
use App\Models\CustomerRequest;
use App\Models\Enquiry;
use App\Models\ImportBatch;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private Vehicle $vehicle;

    private CustomerRequest $request;

    private Enquiry $enquiry;

    private ContactEnquiry $contactEnquiry;

    private ImportBatch $importBatch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'is_active' => true,
        ]);
        $this->admin->assignRole(UserRole::SUPER_ADMIN->label());

        $this->editor = User::factory()->create([
            'name' => 'Editor',
            'email' => 'editor@example.test',
            'is_active' => true,
        ]);
        $this->editor->assignRole(UserRole::EDITOR->label());

        $this->vehicle = Vehicle::factory()->create();
        $this->request = CustomerRequest::factory()->create();
        $this->enquiry = Enquiry::factory()->create();
        $this->contactEnquiry = ContactEnquiry::create([
            'name' => 'Meera Shah',
            'phone' => '+919876543210',
            'email' => 'meera@example.test',
            'subject' => 'Test drive',
            'message' => 'Could I see the car this weekend?',
            'status' => 'new',
            'ip_address' => '127.0.0.1',
        ]);
        $this->importBatch = ImportBatch::create([
            'user_id' => $this->admin->id,
            'original_name' => 'vehicles.csv',
            'stored_path' => 'imports/vehicles.csv',
            'disk' => 'local',
            'status' => ImportStatus::PENDING,
        ]);
    }

    public function test_super_admin_dashboard_and_every_index_page_render(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk();

        $pages = [
            '/admin/vehicles',
            '/admin/vehicles/create',
            '/admin/vehicle-models',
            '/admin/vehicle-models/create',
            '/admin/vehicle-variants',
            '/admin/vehicle-variants/create',
            '/admin/brands',
            '/admin/brands/create',
            '/admin/categories',
            '/admin/categories/create',
            '/admin/cities',
            '/admin/cities/create',
            '/admin/fuel-types',
            '/admin/fuel-types/create',
            '/admin/features',
            '/admin/features/create',
            '/admin/customer-requests',
            '/admin/enquiries',
            '/admin/contact-enquiries',
            '/admin/import-batches',
            '/admin/import-batches/create',
            '/admin/seo-pages',
            '/admin/seo-pages/create',
            '/admin/redirects',
            '/admin/redirects/create',
            '/admin/users',
            '/admin/users/create',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($this->admin)->get($page);
            $this->assertSame(200, $response->status(), 'Admin page failed: '.$page);
        }
    }

    public function test_record_pages_render_for_core_workflows(): void
    {
        $pages = [
            '/admin/vehicles/'.$this->vehicle->slug,
            '/admin/vehicles/'.$this->vehicle->slug.'/edit',
            '/admin/customer-requests/'.$this->request->uuid,
            '/admin/customer-requests/'.$this->request->uuid.'/edit',
            '/admin/enquiries/'.$this->enquiry->uuid,
            '/admin/enquiries/'.$this->enquiry->uuid.'/edit',
            '/admin/contact-enquiries/'.$this->contactEnquiry->uuid,
            '/admin/contact-enquiries/'.$this->contactEnquiry->uuid.'/edit',
            '/admin/import-batches/'.$this->importBatch->uuid,
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($this->admin)->get($page);
            $this->assertSame(200, $response->status(), 'Admin record page failed: '.$page);
        }
    }

    public function test_editor_cannot_manage_users_or_delete_imports(): void
    {
        $this->actingAs($this->editor)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($this->editor)
            ->get('/admin/import-batches')
            ->assertOk();

        $this->assertTrue($this->editor->can('view_any_imports'));
        $this->assertFalse($this->editor->can('delete_imports'));
        $this->assertFalse($this->editor->can('view_any_users'));
        $this->assertTrue($this->admin->can('delete_imports'));
    }

    public function test_inactive_user_cannot_access_the_admin_panel(): void
    {
        $this->admin->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertForbidden();
    }
}
