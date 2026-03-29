<?php

namespace App\Console\Commands;

use App\Services\MaintenancePredictionService;
use Illuminate\Console\Command;

class RecalculateServicePredictions extends Command
{
    protected $signature = 'predictions:recalculate {--business_id= : Recalculate for a specific business only}';

    protected $description = 'Recalculate service maintenance predictions for all customers and vehicles';

    public function handle()
    {
        $this->info('Starting service prediction recalculation...');

        $service = new MaintenancePredictionService();

        $businessId = $this->option('business_id');

        if ($businessId) {
            $count = $service->recalculateForBusiness((int) $businessId);
            $this->info("Recalculated {$count} predictions for business #{$businessId}.");
        } else {
            $count = $service->recalculateAll();
            $this->info("Recalculated {$count} predictions across all businesses.");
        }

        return 0;
    }
}
