<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Services\Import\VehicleImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class ProcessImportBatch implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public ImportBatch $batch
    ) {}

    public function handle(VehicleImportService $service): void
    {
        $service->process($this->batch);
    }

    public function failed(Throwable $exception): void
    {
        $this->batch->refresh();

        if ($this->batch->isFinished()) {
            return;
        }

        ImportError::create([
            'import_batch_id' => $this->batch->id,
            'row_number' => 0,
            'column' => null,
            'message' => 'Import failed: '.Str::limit($exception->getMessage(), 1000),
            'row_data' => null,
        ]);

        $this->batch->forceFill([
            'status' => ImportStatus::FAILED,
            'error_rows' => max(1, $this->batch->error_rows),
            'finished_at' => now(),
        ])->save();
    }
}
