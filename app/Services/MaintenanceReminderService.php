<?php

namespace App\Services;

use App\ServicePrediction;
use App\Utils\SmsUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Entities\SmsLog;

class MaintenanceReminderService
{
    /**
     * Process all pending reminders for all businesses.
     *
     * @return array Summary of sent reminders
     */
    public function processAll(): array
    {
        $summary = ['sent' => 0, 'skipped' => 0, 'errors' => 0, 'schedules_created' => 0];

        $businessIds = DB::table('business')->pluck('id');

        foreach ($businessIds as $businessId) {
            $result = $this->processForBusiness($businessId);
            $summary['sent'] += $result['sent'];
            $summary['skipped'] += $result['skipped'];
            $summary['errors'] += $result['errors'];
            $summary['schedules_created'] += $result['schedules_created'];
        }

        return $summary;
    }

    /**
     * Process reminders for a specific business.
     */
    public function processForBusiness(int $businessId): array
    {
        $summary = ['sent' => 0, 'skipped' => 0, 'errors' => 0, 'schedules_created' => 0];

        // Get predictions that need action
        $predictions = ServicePrediction::where('business_id', $businessId)
            ->whereIn('status', ['due', 'overdue'])
            ->with(['contact', 'device', 'device.deviceCategory', 'device.deviceModel', 'serviceCategory'])
            ->get();

        foreach ($predictions as $prediction) {
            try {
                $result = $this->processReminder($prediction, $businessId);
                if ($result === 'sent') {
                    $summary['sent']++;
                } elseif ($result === 'schedule_created') {
                    $summary['schedules_created']++;
                } else {
                    $summary['skipped']++;
                }
            } catch (\Exception $e) {
                $summary['errors']++;
                Log::error('Maintenance reminder failed', [
                    'prediction_id' => $prediction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    /**
     * Process a single prediction reminder.
     *
     * @return string 'sent', 'skipped', or 'schedule_created'
     */
    protected function processReminder(ServicePrediction $prediction, int $businessId): string
    {
        $requiredLevel = $this->determineReminderLevel($prediction);

        // Skip if already sent at this level
        if ($prediction->reminder_level >= $requiredLevel && !empty($prediction->reminder_sent_at)) {
            // Check if we already sent this level recently (within 7 days)
            if ($prediction->reminder_sent_at->diffInDays(Carbon::now()) < 7) {
                return 'skipped';
            }
        }

        $contact = $prediction->contact;
        if (empty($contact) || empty($contact->mobile)) {
            return 'skipped';
        }

        // Build message
        $message = $this->buildMessage($prediction, $requiredLevel);

        // Level 4 = create CRM schedule for call center instead of SMS
        if ($requiredLevel >= 4) {
            $this->createFollowUpSchedule($prediction, $businessId);
            $prediction->update([
                'reminder_level' => $requiredLevel,
                'reminder_sent_at' => Carbon::now(),
            ]);
            return 'schedule_created';
        }

        // Send SMS using SmsUtil (same pattern as Sms module)
        $smsResult = SmsUtil::sendEpusheg($contact->mobile, $message);
        $smsSent = is_array($smsResult) ? ($smsResult['success'] ?? false) : $smsResult;
        $providerBalance = is_array($smsResult) ? ($smsResult['balance'] ?? null) : null;

        $logBaseData = [
            'sms_message_id' => null,
            'contact_id' => $contact->id,
            'transaction_id' => null,
            'job_sheet_id' => null,
            'mobile' => $contact->mobile,
            'message_content' => $message,
            'sent_at' => now(),
            'provider_balance' => $providerBalance,
        ];

        if ($smsSent) {
            SmsLog::create(array_merge($logBaseData, [
                'status' => 'sent',
                'error_message' => null,
            ]));

            $prediction->update([
                'reminder_level' => $requiredLevel,
                'reminder_sent_at' => Carbon::now(),
            ]);

            Log::info('Maintenance reminder sent', [
                'prediction_id' => $prediction->id,
                'contact_id' => $prediction->contact_id,
                'mobile' => $contact->mobile,
                'level' => $requiredLevel,
            ]);

            return 'sent';
        } else {
            SmsLog::create(array_merge($logBaseData, [
                'status' => 'failed',
                'error_message' => 'Maintenance reminder SMS failed - provider error',
            ]));

            Log::warning('Maintenance reminder SMS failed', [
                'prediction_id' => $prediction->id,
                'contact_id' => $prediction->contact_id,
                'mobile' => $contact->mobile,
            ]);

            return 'skipped';
        }
    }

    /**
     * Determine what reminder level is needed based on status and overdue months.
     */
    protected function determineReminderLevel(ServicePrediction $prediction): int
    {
        if ($prediction->status === 'due') {
            return 1; // Gentle reminder
        }

        if ($prediction->status === 'overdue') {
            if ($prediction->overdue_months >= 3) {
                return 4; // Call center
            } elseif ($prediction->overdue_months >= 2) {
                return 3; // Discount offer
            } else {
                return 2; // Strong reminder
            }
        }

        return 0;
    }

    /**
     * Build the SMS message based on reminder level.
     */
    protected function buildMessage(ServicePrediction $prediction, int $level): string
    {
        $carInfo = $this->getCarDescription($prediction);
        $serviceType = optional($prediction->serviceCategory)->name ?? 'صيانة';

        switch ($level) {
            case 1:
                return "عزيزي العميل، موعد {$serviceType} لسيارتك {$carInfo} هذا الشهر. احجز الآن للحفاظ على أداء سيارتك.";

            case 2:
                return "تنبيه: سيارتك {$carInfo} تأخرت عن موعد {$serviceType}. ننصحك بالحجز في أقرب وقت للحفاظ على سلامة سيارتك.";

            case 3:
                return "عرض خاص! سيارتك {$carInfo} متأخرة عن موعد {$serviceType}. احجز الآن واستفد بخصم خاص. اتصل بنا لمزيد من التفاصيل.";

            case 4:
                return "سيارتك {$carInfo} متأخرة {$prediction->overdue_months} شهور عن موعد {$serviceType}. يرجى التواصل معنا فوراً.";

            default:
                return "تذكير: سيارتك {$carInfo} تحتاج {$serviceType}.";
        }
    }

    /**
     * Get a human-readable car description.
     */
    protected function getCarDescription(ServicePrediction $prediction): string
    {
        $parts = [];

        if ($prediction->device) {
            $brand = optional($prediction->device->deviceCategory)->name;
            $model = optional($prediction->device->deviceModel)->name;
            $plate = $prediction->device->plate_number;

            if ($brand) $parts[] = $brand;
            if ($model) $parts[] = $model;
            if ($plate) $parts[] = "({$plate})";
        }

        return !empty($parts) ? implode(' ', $parts) : 'سيارتك';
    }

    /**
     * Create a CRM follow-up schedule for call center action.
     */
    protected function createFollowUpSchedule(ServicePrediction $prediction, int $businessId): void
    {
        $carInfo = $this->getCarDescription($prediction);
        $serviceType = optional($prediction->serviceCategory)->name ?? 'صيانة';

        DB::table('crm_schedules')->insert([
            'business_id' => $businessId,
            'contact_id' => $prediction->contact_id,
            'title' => "متابعة صيانة متأخرة - {$serviceType} - {$carInfo}",
            'description' => "العميل متأخر {$prediction->overdue_months} شهور عن موعد {$serviceType} لسيارة {$carInfo}. آخر صيانة: {$prediction->last_service_date}. يرجى الاتصال بالعميل.",
            'schedule_type' => 'follow_up',
            'status' => 'open',
            'start_datetime' => Carbon::now()->toDateTimeString(),
            'end_datetime' => Carbon::now()->addDays(3)->toDateTimeString(),
            'follow_up_type' => 'call',
            'created_by' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
