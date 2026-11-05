<?php

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Enums\RequestStatus;
use App\Enums\VehicleStatus;
use App\Models\ContactEnquiry;
use App\Models\CustomerRequest;
use App\Models\Enquiry;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketplaceStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $published = Vehicle::query()->where('status', VehicleStatus::PUBLISHED->value)->count();
        $drafts = Vehicle::query()->where('status', VehicleStatus::DRAFT->value)->count();
        $newRequests = CustomerRequest::query()
            ->whereIn('status', [RequestStatus::NEW->value, RequestStatus::PENDING_VERIFICATION->value])
            ->count();
        $newEnquiries = Enquiry::query()->where('status', EnquiryStatus::NEW->value)->count()
            + ContactEnquiry::query()->where('status', EnquiryStatus::NEW->value)->count();

        $publishedThisWeek = Vehicle::query()
            ->where('status', VehicleStatus::PUBLISHED->value)
            ->where('published_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Published vehicles', number_format($published))
                ->description($publishedThisWeek.' published in the last 7 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Draft listings', number_format($drafts))
                ->description('Waiting for photos, pricing or approval')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),
            Stat::make('Open sell / buy requests', number_format($newRequests))
                ->description('New or awaiting OTP verification')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('info'),
            Stat::make('Unanswered enquiries', number_format($newEnquiries))
                ->description('Vehicle and contact form messages')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($newEnquiries > 0 ? 'danger' : 'gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_vehicles') ?? false;
    }
}
