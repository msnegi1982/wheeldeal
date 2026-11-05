<?php

namespace Tests\Feature;

use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\FuelType;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    private Brand $maruti;

    private Brand $hyundai;

    private Category $suv;

    private City $pune;

    private City $mumbai;

    private FuelType $petrol;

    private FuelType $diesel;

    /** @var array<string, Vehicle> */
    private array $vehicles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->maruti = Brand::factory()->create(['name' => 'Maruti Suzuki', 'slug' => 'maruti-suzuki']);
        $this->hyundai = Brand::factory()->create(['name' => 'Hyundai', 'slug' => 'hyundai']);
        $this->suv = Category::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
        $this->pune = City::factory()->create(['name' => 'Pune', 'slug' => 'pune']);
        $this->mumbai = City::factory()->create(['name' => 'Mumbai', 'slug' => 'mumbai']);
        $this->petrol = FuelType::factory()->create(['name' => 'Petrol', 'slug' => 'petrol']);
        $this->diesel = FuelType::factory()->create(['name' => 'Diesel', 'slug' => 'diesel']);

        $this->vehicles['budget'] = $this->vehicle('Maruti Suzuki Baleno', [
            'brand' => $this->maruti,
            'price' => 500000,
            'year' => 2018,
            'km' => 60000,
            'city' => $this->pune,
            'fuel' => $this->petrol,
            'transmission' => Transmission::MANUAL,
            'featured' => false,
        ]);

        $this->vehicles['mid'] = $this->vehicle('Maruti Suzuki Brezza', [
            'brand' => $this->maruti,
            'price' => 900000,
            'year' => 2021,
            'km' => 30000,
            'city' => $this->mumbai,
            'fuel' => $this->diesel,
            'transmission' => Transmission::AUTOMATIC,
            'featured' => true,
        ]);

        $this->vehicles['premium'] = $this->vehicle('Hyundai Creta', [
            'brand' => $this->hyundai,
            'price' => 1500000,
            'year' => 2023,
            'km' => 12000,
            'city' => $this->pune,
            'fuel' => $this->petrol,
            'transmission' => Transmission::AUTOMATIC,
            'featured' => false,
        ]);

        $this->vehicle('Hyundai Venue', [
            'brand' => $this->hyundai,
            'price' => 1100000,
            'year' => 2022,
            'km' => 20000,
            'city' => $this->mumbai,
            'fuel' => $this->petrol,
            'transmission' => Transmission::MANUAL,
            'status' => VehicleStatus::DRAFT,
        ]);

        cache()->flush();
    }

    public function test_catalog_page_lists_only_published_vehicles(): void
    {
        $response = $this->get(route('vehicles.index'));

        $response->assertOk();
        $response->assertSee($this->vehicles['budget']->title);
        $response->assertSee($this->vehicles['premium']->title);
        $response->assertDontSee('Hyundai Venue');
        $response->assertSee('<meta name="robots" content="index,follow">', false);
        $response->assertSee('rel="canonical" href="'.route('vehicles.index').'"', false);
    }

    public function test_brand_filter_narrows_results_and_marks_the_page_noindex(): void
    {
        $response = $this->get(route('vehicles.index', ['brand' => 'maruti-suzuki']));

        $response->assertOk();
        $response->assertSee($this->vehicles['budget']->title);
        $response->assertSee($this->vehicles['mid']->title);
        $response->assertDontSee($this->vehicles['premium']->title);
        $response->assertSee('<meta name="robots" content="noindex,follow">', false);
        $response->assertSee('rel="canonical" href="'.route('vehicles.index').'"', false);
    }

    public function test_price_and_city_filters_combine(): void
    {
        $response = $this->get(route('vehicles.index', [
            'min_price' => 800000,
            'city' => 'pune',
        ]));

        $response->assertOk();
        $response->assertSee($this->vehicles['premium']->title);
        $response->assertDontSee($this->vehicles['budget']->title);
        $response->assertDontSee($this->vehicles['mid']->title);
    }

    public function test_fuel_and_transmission_filters_are_respected(): void
    {
        $response = $this->get(route('vehicles.index', [
            'fuel' => 'diesel',
            'transmission' => 'automatic',
        ]));

        $response->assertOk();
        $response->assertSee($this->vehicles['mid']->title);
        $response->assertDontSee($this->vehicles['premium']->title);
    }

    public function test_sort_price_low_orders_cheapest_first(): void
    {
        $html = $this->get(route('vehicles.index', ['sort' => 'price_low']))->getContent();

        $budgetPosition = strpos($html, $this->vehicles['budget']->title);
        $premiumPosition = strpos($html, $this->vehicles['premium']->title);

        $this->assertNotFalse($budgetPosition);
        $this->assertNotFalse($premiumPosition);
        $this->assertLessThan($premiumPosition, $budgetPosition);
    }

    public function test_invalid_filter_values_are_ignored_instead_of_erroring(): void
    {
        $response = $this->get(route('vehicles.index', [
            'min_year' => 'not-a-number',
            'sort' => 'drop-table',
            'transmission' => 'rocket',
            'per_page' => 999,
        ]));

        $response->assertOk();
        $response->assertSee($this->vehicles['budget']->title);
        $response->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_ajax_filter_request_returns_json_partial_with_count_and_url(): void
    {
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('vehicles.index', ['brand' => 'maruti-suzuki']));

        $response->assertOk()
            ->assertJsonStructure(['html', 'count', 'url'])
            ->assertJsonPath('count', 2);

        $this->assertStringContainsString('brand=maruti-suzuki', $response->json('url'));
        $this->assertStringContainsString($this->vehicles['mid']->title, $response->json('html'));
        $this->assertStringNotContainsString($this->vehicles['premium']->title, $response->json('html'));
    }

    public function test_empty_filter_result_shows_empty_state_and_noindex(): void
    {
        $response = $this->get(route('vehicles.index', ['max_price' => 1000]));

        $response->assertOk();
        $response->assertSee('No cars match those filters');
        $response->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_category_landing_page_locks_the_category_filter(): void
    {
        $response = $this->get(route('categories.show', 'suv'));

        $response->assertOk();
        $response->assertSee('Used SUV cars for sale');
        $response->assertSee($this->vehicles['premium']->title);
        $response->assertSee('value="suv"', false);
    }

    public function test_category_brand_landing_page_filters_to_both(): void
    {
        $response = $this->get(route('catalog.category-brand', ['suv', 'maruti-suzuki']));

        $response->assertOk();
        $response->assertSee($this->vehicles['budget']->title);
        $response->assertDontSee($this->vehicles['premium']->title);
    }

    public function test_unknown_category_or_brand_returns_404(): void
    {
        $this->get(route('categories.show', 'spaceship'))->assertNotFound();
        $this->get(route('brands.show', 'nope'))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function vehicle(string $title, array $overrides): Vehicle
    {
        $brand = $overrides['brand'];
        $city = $overrides['city'];
        $fuel = $overrides['fuel'];

        $model = VehicleModel::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $this->suv->id,
            'name' => $title,
            'slug' => Str::slug($title),
        ]);

        $variant = VehicleVariant::factory()->create([
            'vehicle_model_id' => $model->id,
            'fuel_type_id' => $fuel->id,
            'name' => 'VXi',
        ]);

        return Vehicle::factory()->create([
            'title' => $title,
            'slug' => Str::slug($title),
            'brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'vehicle_variant_id' => $variant->id,
            'category_id' => $this->suv->id,
            'fuel_type_id' => $fuel->id,
            'city_id' => $city->id,
            'year' => $overrides['year'],
            'price' => $overrides['price'],
            'km_driven' => $overrides['km'],
            'transmission' => $overrides['transmission']->value,
            'is_featured' => $overrides['featured'] ?? false,
            'status' => $overrides['status'] ?? VehicleStatus::PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }
}
