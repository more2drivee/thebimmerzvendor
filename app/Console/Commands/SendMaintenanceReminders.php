<?php

namespace App\Console\Commands;

use App\Services\MaintenanceReminderService;
use Illuminate\Console\Command;

class SendMaintenanceReminders extends Command
{
    protected $signature = 'reminders:send-maintenance {--business_id= : Send for a specific business only}';

    protected $description = 'Send maintenance reminder notifications based on service predictions';

    public function handle()
    {
        $this->info('Processing maintenance reminders...');

        $service = new MaintenanceReminderService();

        $businessId = $this->option('business_id');

        if ($businessId) {
            $summary = $service->processForBusiness((int) $businessId);
        } else {
            $summary = $service->processAll();
        }

        $this->info("Reminders sent: {$summary['sent']}");
        $this->info("Skipped: {$summary['skipped']}");
        $this->info("Schedules created: {$summary['schedules_created']}");

        if ($summary['errors'] > 0) {
            $this->warn("Errors: {$summary['errors']} (check logs for details)");
        }

        return 0;
    }
}
