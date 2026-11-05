<?php

namespace Database\Seeders;

use App\Enums\EnquiryStatus;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\City;
use App\Models\ContactEnquiry;
use App\Models\CustomerRequest;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class RequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = Vehicle::published()->take(5)->get();
        $cities = City::take(5)->get();
        $admin = User::where('email', 'admin@wheeldeal.test')->first();

        if ($vehicles->isEmpty()) {
            return;
        }

        $rows = [
            ['sell', 'new', 'Rahul Sharma', '+919811111111', 3800000, 'gorgeous'],
            ['sell', 'contacted', 'Meera Iyer', '+919822222222', 720000, 'good'],
            ['sell', 'approved', 'Arjun Patel', '+919833333333', 1780000, 'excellent'],
            ['buy', 'new', 'Sana Khan', '+919844444444', 1500000, 'good'],
            ['buy', 'new', 'Vikram Rao', '+919855555555', 2500000, 'excellent'],
            ['buy', 'rejected', 'Anita Das', '+919866666666', 900000, 'fair'],
        ];

        foreach ($rows as $index => [$type, $status, $name, $phone, $price, $condition]) {
            $vehicle = $vehicles[$index % $vehicles->count()];
            $city = $cities[$index % $cities->count()];

            $request = CustomerRequest::create([
                'type' => $type,
                'status' => $status,
                'name' => $name,
                'phone' => $phone,
                'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
                'city_id' => $city->id,
                'brand_id' => $type === RequestType::SELL->value ? $vehicle->brand_id : null,
                'vehicle_model_id' => $type === RequestType::SELL->value ? $vehicle->vehicle_model_id : null,
                'year' => $vehicle->year,
                'km_driven' => $vehicle->km_driven,
                'expected_price' => $price,
                'condition' => $condition,
                'colour' => $vehicle->colour,
                'message' => $type === RequestType::SELL->value
                    ? 'I am the first owner. The car has a full service history and is available for inspection this weekend.'
                    : 'Looking for a well-maintained family car with complete service records and a clean title.',
                'consent' => true,
                'otp_verified_at' => now()->subDays($index + 1),
                'admin_notes' => $status === RequestStatus::CONTACTED->value ? 'Called on WhatsApp; requested a viewing slot.' : null,
                'assigned_to' => $admin?->id,
                'source' => 'seed',
                'ip_address' => '127.0.0.1',
            ]);

            if ($status !== RequestStatus::NEW->value) {
                $request->notes()->create([
                    'user_id' => $admin?->id,
                    'note' => 'Status updated to '.$request->status->label().'.',
                    'is_internal' => true,
                ]);
            }
        }

        CustomerRequest::create([
            'type' => 'sell',
            'status' => 'pending_verification',
            'name' => 'Neha Verma',
            'phone' => '+919877777777',
            'email' => 'neha@example.test',
            'city_id' => $cities->first()->id,
            'year' => 2020,
            'km_driven' => 45000,
            'expected_price' => 1100000,
            'condition' => 'good',
            'message' => 'Awaiting OTP verification to submit my car.',
            'consent' => true,
            'source' => 'seed',
            'ip_address' => '127.0.0.1',
        ]);

        foreach ($vehicles->take(4) as $index => $vehicle) {
            $enquiry = Enquiry::create([
                'vehicle_id' => $vehicle->id,
                'name' => ['Karan Mehta', 'Divya S', 'Imran Ali', 'Ritu Joshi'][$index],
                'phone' => '+91990000000'.$index,
                'email' => 'buyer'.$index.'@example.test',
                'message' => 'Is this vehicle available for a test drive this week? Please share the service history.',
                'status' => $index === 0 ? EnquiryStatus::NEW->value : EnquiryStatus::CONTACTED->value,
                'ip_address' => '127.0.0.1',
            ]);

            if ($index === 1) {
                $enquiry->notes()->create([
                    'user_id' => $admin?->id,
                    'note' => 'Shared inspection slot options.',
                ]);
            }
        }

        ContactEnquiry::create([
            'name' => 'Suresh Menon',
            'phone' => '+919988877766',
            'email' => 'suresh@example.test',
            'subject' => 'Selling multiple cars',
            'message' => 'I have three cars to sell after relocating. Can someone help with a bulk valuation?',
            'status' => 'new',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
