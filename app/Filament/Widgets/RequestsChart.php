<?php

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Enums\RequestStatus;
use App\Models\CustomerRequest;
use App\Models\Enquiry;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class RequestsChart extends ChartWidget
{
    protected static ?string $heading = 'Requests and enquiries — last 14 days';

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->subDays(13);
        $labels = [];
        $keys = [];

        for ($day = 0; $day < 14; $day++) {
            $date = $start->addDays($day);
            $labels[] = $date->format('d M');
            $keys[] = $date->toDateString();
        }

        $requests = CustomerRequest::query()
            ->where('created_at', '>=', $start)
            ->whereIn('status', [
                RequestStatus::NEW->value,
                RequestStatus::CONTACTED->value,
                RequestStatus::APPROVED->value,
                RequestStatus::REJECTED->value,
            ])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $enquiries = Enquiry::query()
            ->where('created_at', '>=', $start)
            ->where('status', '!=', EnquiryStatus::CLOSED->value)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        return [
            'datasets' => [
                [
                    'label' => 'Sell / buy requests',
                    'data' => array_map(fn (string $key) => (int) ($requests[$key] ?? 0), $keys),
                    'borderColor' => '#111111',
                    'backgroundColor' => 'rgba(17, 17, 17, 0.08)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Vehicle enquiries',
                    'data' => array_map(fn (string $key) => (int) ($enquiries[$key] ?? 0), $keys),
                    'borderColor' => '#FFC107',
                    'backgroundColor' => 'rgba(255, 193, 7, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'labels' => [
                        'color' => '#52525b',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_requests') ?? false;
    }
}
