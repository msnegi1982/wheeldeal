<?php

namespace Tests\Feature;

use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\FuelType;
use App\Models\Redirect;
use App\Models\SeoPage;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private Category $suv;

    private Category $sedan;

    private FuelType $petrol;

    private City $pune;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create([
            'name' => 'Maruti Suzuki',
            'slug' => 'maruti-suzuki',
            'is_active' => true,
        ]);

        $this->suv = Category::factory()->create([
            'name' => 'SUV',
            'slug' => 'suv',
            'is_active' => true,
        ]);

        $this->sedan = Category::factory()->create([
            'name' => 'Sedan',
            'slug' => 'sedan',
            'is_active' => true,
        ]);

        $this->petrol = FuelType::factory()->create([
            'name' => 'Petrol',
            'slug' => 'petrol',
            'is_active' => true,
        ]);

        $this->pune = City::factory()->create([
            'name' => 'Pune',
            'slug' => 'pune',
            'is_active' => true,
        ]);
    }

    public function test_sitemap_lists_only_public_indexable_urls(): void
    {
        $published = $this->vehicle('Maruti Suzuki Brezza', VehicleStatus::PUBLISHED);
        $draft = $this->vehicle('Maruti Suzuki Baleno', VehicleStatus::DRAFT, $this->sedan);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<urlset', false);
        $response->assertSee(route('home'), false);
        $response->assertSee(route('vehicles.index'), false);
        $response->assertSee(route('categories.show', 'suv'), false);
        $response->assertSee(route('brands.show', 'maruti-suzuki'), false);
        $response->assertSee(route('catalog.category-brand', ['suv', 'maruti-suzuki']), false);
        $response->assertSee($published->public_url, false);
        $response->assertDontSee($draft->public_url, false);
        $response->assertDontSee(route('categories.show', 'sedan'), false);
        $response->assertDontSee(route('catalog.category-brand', ['sedan', 'maruti-suzuki']), false);
    }

    public function test_sitemap_omits_empty_brand_landing_pages(): void
    {
        $this->vehicle('Maruti Suzuki Brezza', VehicleStatus::PUBLISHED);

        Brand::factory()->create([
            'name' => 'Honda',
            'slug' => 'honda',
            'is_active' => true,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(route('brands.show', 'honda'), false);
    }

    public function test_robots_txt_references_the_sitemap_and_blocks_private_areas(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Disallow: /api');
        $response->assertSee('Disallow: /verify-otp');
        $response->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_slug_redirect_returns_301_and_records_the_hit(): void
    {
        $redirect = Redirect::query()->create([
            'old_path' => '/cars/old-slug',
            'new_path' => '/cars/new-slug',
            'status_code' => 301,
        ]);

        $this->get('/cars/old-slug')
            ->assertStatus(301)
            ->assertRedirect(url('/cars/new-slug'));

        $this->assertSame(1, $redirect->fresh()->hits);
        $this->assertNotNull($redirect->fresh()->last_hit_at);
    }

    public function test_redirects_are_not_applied_to_non_get_requests(): void
    {
        $redirect = Redirect::query()->create([
            'old_path' => '/cars/old-slug',
            'new_path' => '/cars/new-slug',
            'status_code' => 301,
        ]);

        // The route only accepts GET, but the important assertion is that the
        // redirect middleware never issues a 301 for a write request.
        $this->post('/cars/old-slug')->assertStatus(405);
        $this->assertSame(0, $redirect->fresh()->hits);
    }

    public function test_changing_a_vehicle_slug_creates_a_301_redirect(): void
    {
        $vehicle = $this->vehicle('Maruti Suzuki Brezza', VehicleStatus::PUBLISHED);
        $oldPath = parse_url($vehicle->public_url, PHP_URL_PATH);

        $vehicle->update(['slug' => 'maruti-suzuki-brezza-2022']);

        $newUrl = $vehicle->fresh()->public_url;
        $redirect = Redirect::query()->where('old_path', $oldPath)->first();

        $this->assertNotNull($redirect);
        $this->assertSame(parse_url($newUrl, PHP_URL_PATH), $redirect->new_path);

        $this->get($oldPath)->assertStatus(301)->assertRedirect($newUrl);
    }

    public function test_vehicle_detail_has_canonical_offer_and_breadcrumb_schema(): void
    {
        $vehicle = $this->vehicle('Maruti Suzuki Brezza', VehicleStatus::PUBLISHED);

        $response = $this->get($vehicle->public_url);

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$vehicle->public_url.'"', false);
        $response->assertSee('<meta name="robots" content="index,follow">', false);
        $response->assertSee('"@type":"Car"', false);
        $response->assertSee('"@type":"Offer"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"priceCurrency":"INR"', false);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_home_page_emits_organization_and_website_schema(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('<link rel="canonical" href="'.route('home').'"', false);
    }

    public function test_landing_page_uses_seo_override_copy(): void
    {
        $this->vehicle('Maruti Suzuki Brezza', VehicleStatus::PUBLISHED);

        SeoPage::query()->create([
            'category_id' => $this->suv->id,
            'brand_id' => $this->brand->id,
            'slug' => 'suv-maruti-suzuki',
            'h1' => 'Inspected Maruti Suzuki SUVs',
            'meta_title' => 'Maruti Suzuki SUV cars for sale',
            'meta_description' => 'Hand-picked Maruti Suzuki SUVs with verified ownership history.',
            'intro_copy' => 'Every SUV in this collection has been checked by our team.',
            'is_active' => true,
        ]);

        $response = $this->get(route('catalog.category-brand', ['suv', 'maruti-suzuki']));

        $response->assertOk();
        $response->assertSee('<title>Maruti Suzuki SUV cars for sale</title>', false);
        $response->assertSee('Hand-picked Maruti Suzuki SUVs with verified ownership history.');
        $response->assertSee('Inspected Maruti Suzuki SUVs');
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_sitemap_generate_command_writes_an_xml_file(): void
    {
        $path = storage_path('framework/testing/sitemap.xml');

        $this->vehicle('Maruti Suzuki Brezza', VehicleStatus::PUBLISHED);

        $this->artisan('sitemap:generate', ['--path' => $path, '--fresh' => true])
            ->assertSuccessful();

        $this->assertFileExists($path);
        $this->assertStringContainsString('<urlset', (string) file_get_contents($path));

        File::delete($path);
    }

    private function vehicle(
        string $title,
        VehicleStatus $status,
        ?Category $category = null
    ): Vehicle {
        $category ??= $this->suv;

        $model = VehicleModel::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $category->id,
            'name' => $title,
            'slug' => Str::slug($title),
            'is_active' => true,
        ]);

        $variant = VehicleVariant::factory()->create([
            'vehicle_model_id' => $model->id,
            'fuel_type_id' => $this->petrol->id,
            'name' => 'VXi',
            'slug' => Str::slug($title).'-vxi',
        ]);

        return Vehicle::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'brand_id' => $this->brand->id,
            'vehicle_model_id' => $model->id,
            'vehicle_variant_id' => $variant->id,
            'category_id' => $category->id,
            'fuel_type_id' => $this->petrol->id,
            'city_id' => $this->pune->id,
            'year' => 2022,
            'price' => 1200000,
            'km_driven' => 18000,
            'owner_count' => 1,
            'transmission' => Transmission::AUTOMATIC->value,
            'seats' => 5,
            'colour' => 'Pearl White',
            'description' => 'A well-maintained example with complete service history.',
            'status' => $status->value,
            'published_at' => $status === VehicleStatus::PUBLISHED ? now()->subDay() : null,
        ]);
    }
}
