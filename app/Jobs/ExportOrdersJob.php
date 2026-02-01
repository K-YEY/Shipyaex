<?php

namespace App\Jobs;

use App\Exports\OrdersExport;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?array $orderIds = null,
        public ?array $filters = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $filename = 'orders-export-' . now()->format('Y-m-d-His') . '.xlsx';
            $path = 'exports/' . $filename;

            // Generate Excel file
            Excel::store(
                new OrdersExport($this->startDate, $this->endDate, $this->orderIds, $this->filters),
                $path,
                'public'
            );

            // Get file size for notification
            $fileSize = Storage::disk('public')->size($path);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            // Notify user of success
            Notification::make()
                ->title('✅ Export Ready!')
                ->body("Your export is ready ({$fileSizeMB} MB). Click to download.")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Download')
                        ->url(Storage::url($path))
                        ->openUrlInNewTab(),
                    \Filament\Notifications\Actions\Action::make('dismiss')
                        ->label('Dismiss')
                        ->close()
                ])
                ->persistent()
                ->sendToDatabase($this->user);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Order export failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger failed() method
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Notification::make()
            ->title('❌ Export Failed')
            ->body('An error occurred while exporting orders. Please try again or contact support.')
            ->danger()
            ->persistent()
            ->sendToDatabase($this->user);
    }
}
