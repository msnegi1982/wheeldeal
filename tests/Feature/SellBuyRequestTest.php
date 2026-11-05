<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Jobs\SendOtpJob;
use App\Models\Brand;
use App\Models\City;
use App\Models\CustomerRequest;
use App\Models\OtpVerification;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SellBuyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_sell_request_is_persisted_and_a_hashed_otp_is_queued(): void
    {
        Queue::fake();

        [$city, $brand, $model] = $this->catalog();

        $response = $this->post(route('sell.store'), [
            'name' => 'Asha Verma',
            'phone' => '98765 43210',
            'email' => 'asha@example.test',
            'city_id' => $city->id,
            'brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'year' => 2019,
            'km_driven' => 48000,
            'expected_price' => 725000,
            'condition' => 'good',
            'colour' => 'White',
            'message' => 'Single owner, full service history.',
            'consent' => '1',
            'channel' => 'sms',
        ]);

        $request = CustomerRequest::query()->sole();

        $response->assertRedirect(route('requests.verify', $request));
        $this->assertSame(RequestType::SELL, $request->type);
        $this->assertSame(RequestStatus::PENDING_VERIFICATION, $request->status);
        $this->assertSame('919876543210', $request->phone);
        $this->assertNull($request->otp_verified_at);

        $otp = OtpVerification::query()->sole();

        $this->assertSame($request->getMorphClass(), $otp->verifiable_type);
        $this->assertSame($request->id, $otp->verifiable_id);
        $this->assertSame('919876543210', $otp->phone);
        $this->assertNull($otp->verified_at);
        $this->assertSame(0, $otp->attempts);
        $this->assertSame(3, $otp->max_attempts);
        $this->assertNotSame($this->queuedCode($otp), $otp->code_hash);

        Queue::assertPushed(SendOtpJob::class);
    }

    public function test_buy_request_does_not_require_vehicle_details(): void
    {
        Queue::fake();

        [$city] = $this->catalog();

        $response = $this->post(route('buy.store'), [
            'name' => 'Rahul Nair',
            'phone' => '+91 99887 76655',
            'city_id' => $city->id,
            'message' => 'Looking for an automatic SUV under 15 lakh.',
            'consent' => '1',
            'channel' => 'whatsapp',
        ]);

        $request = CustomerRequest::query()->sole();

        $response->assertRedirect(route('requests.verify', $request));
        $this->assertSame(RequestType::BUY, $request->type);
        $this->assertNull($request->brand_id);
        $this->assertNull($request->vehicle_model_id);
        $this->assertSame('919988776655', $request->phone);
    }

    public function test_invalid_phone_and_honeypot_are_rejected(): void
    {
        Queue::fake();

        [$city] = $this->catalog();

        $this->post(route('buy.store'), [
            'name' => 'Bot',
            'phone' => '12345',
            'city_id' => $city->id,
            'consent' => '1',
            'website' => 'https://spam.example',
        ])->assertSessionHasErrors(['phone', 'website']);

        $this->assertDatabaseCount('customer_requests', 0);
        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_wrong_otp_counts_attempts_and_correct_code_verifies_the_request(): void
    {
        Queue::fake();

        [$city, $brand, $model] = $this->catalog();

        $this->post(route('sell.store'), [
            'name' => 'Asha Verma',
            'phone' => '9876543210',
            'city_id' => $city->id,
            'brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'year' => 2019,
            'km_driven' => 48000,
            'expected_price' => 725000,
            'consent' => '1',
            'channel' => 'sms',
        ]);

        $request = CustomerRequest::query()->sole();
        $otp = OtpVerification::query()->sole();
        $code = $this->queuedCode($otp);

        $this->get(route('requests.verify', $request))
            ->assertOk()
            ->assertSee('Verify your mobile number');

        $this->post(route('requests.verify.submit', $request), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, $otp->fresh()->attempts);

        $this->post(route('requests.verify.submit', $request), ['code' => $code])
            ->assertRedirect(route('requests.success', $request));

        $this->assertNotNull($request->fresh()->otp_verified_at);
        $this->assertSame(RequestStatus::NEW, $request->fresh()->status);
        $this->assertNotNull($otp->fresh()->verified_at);

        $this->get(route('requests.success', $request))
            ->assertOk()
            ->assertSee('Request received');
    }

    public function test_verification_requires_six_digits(): void
    {
        Queue::fake();

        $request = CustomerRequest::factory()->unverified()->create();

        $this->post(route('requests.verify.submit', $request), ['code' => '12ab'])
            ->assertSessionHasErrors('code');
    }

    public function test_resend_is_throttled_for_thirty_seconds(): void
    {
        Queue::fake();

        $request = CustomerRequest::factory()->unverified()->create([
            'phone' => '9876543210',
        ]);

        $otp = OtpVerification::query()->create([
            'verifiable_type' => $request->getMorphClass(),
            'verifiable_id' => $request->id,
            'phone' => '9876543210',
            'channel' => 'sms',
            'purpose' => 'vehicle_request',
            'code_hash' => bcrypt('123456'),
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        $this->post(route('requests.resend', $request))
            ->assertRedirect()
            ->assertSessionHasErrors('code');

        $this->assertSame($otp->fresh()->code_hash, $otp->code_hash);
    }

    public function test_verified_request_cannot_be_verified_again(): void
    {
        Queue::fake();

        $request = CustomerRequest::factory()->create();

        $this->post(route('requests.verify.submit', $request), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->get(route('requests.verify', $request))
            ->assertRedirect(route('requests.success', $request));
    }

    /**
     * @return array{0: City, 1: Brand, 2: VehicleModel}
     */
    private function catalog(): array
    {
        $city = City::factory()->create(['name' => 'Pune', 'slug' => 'pune']);
        $brand = Brand::factory()->create(['name' => 'Maruti Suzuki', 'slug' => 'maruti-suzuki']);
        $model = VehicleModel::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Baleno',
            'slug' => 'baleno',
        ]);

        return [$city, $brand, $model];
    }

    private function queuedCode(OtpVerification $otp): string
    {
        $code = null;

        Queue::assertPushed(SendOtpJob::class, function (SendOtpJob $job) use ($otp, &$code) {
            if ($job->otp->is($otp)) {
                $code = $job->code;
            }

            return true;
        });

        $this->assertIsString($code);

        return $code;
    }
}
