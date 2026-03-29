<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Entities\SmsMessage;
use Modules\Sms\Entities\SmsLog;
use Modules\Repair\Entities\JobSheet;
use App\Utils\SmsUtil;
use App\Contact;

class SmsController extends Controller
{
    /**
     * Get all available SMS messages
     *
     * Optionally accepts a job_sheet_id to indicate which templates
     * have already been sent for that job sheet (based on sms_logs).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request)
    {
        try {
            $user = auth()->user();
            $userRoleIds = $user ? $user->roles->pluck('id')->toArray() : [];

            $jobSheetId = $request->input('job_sheet_id') ?? $request->input('job_sheet_id_');
            $sentMessageIds = [];
            $sentLogs = [];

            Log::info('SmsController getMessages called', [
                'job_sheet_id' => $jobSheetId,
                'request_all' => $request->all(),
            ]);

            if (!empty($jobSheetId)) {
                Log::info('Processing job_sheet_id: ' . $jobSheetId);
                
                $logsQuery = SmsLog::query()
                    ->where('job_sheet_id', (int) $jobSheetId)
                    ->where('status', 'sent');

                Log::info('Query SQL: ' . $logsQuery->toSql());
                Log::info('Query bindings: ' . json_encode($logsQuery->getBindings()));

                $allLogs = $logsQuery
                    ->orderByDesc('sent_at')
                    ->get(['id', 'sms_message_id', 'contact_id', 'transaction_id', 'job_sheet_id', 'mobile', 'message_content', 'status', 'sent_at']);

                Log::info('Logs found: ' . count($allLogs));
                Log::info('Logs data: ' . json_encode($allLogs->toArray()));

                $sentMessageIds = $allLogs
                    ->pluck('sms_message_id')
                    ->filter(fn ($id) => !is_null($id))
                    ->unique()
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->toArray();

                Log::info('Sent message IDs: ' . json_encode($sentMessageIds));

                $sentLogs = $allLogs
                    ->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'sms_message_id' => $log->sms_message_id !== null ? (int) $log->sms_message_id : null,
                            'contact_id' => $log->contact_id ? (int) $log->contact_id : null,
                            'transaction_id' => $log->transaction_id ? (int) $log->transaction_id : null,
                            'job_sheet_id' => $log->job_sheet_id ? (int) $log->job_sheet_id : null,
                            'mobile' => $log->mobile,
                            'message_content' => $log->message_content,
                            'status' => $log->status,
                            'sent_at' => $log->sent_at,
                        ];
                    })
                    ->toArray();

                Log::info('Sent logs: ' . json_encode($sentLogs));
            }

            $messages = SmsMessage::where('status', true)
                ->get()
                ->filter(function ($message) use ($userRoleIds) {
                    $roleIds = is_array($message->roles) ? $message->roles : [];
                    // If no roles assigned, allow all users
                    if (empty($roleIds)) return true;
                    // Only allow if user has at least one matching role
                    return count(array_intersect($roleIds, $userRoleIds)) > 0;
                })
                ->map(function ($message) use ($sentMessageIds) {
                    return [
                        'id' => $message->id,
                        'name' => $message->name,
                        'message_template' => $message->message_template,
                        'description' => $message->description,
                        'sent_for_job_sheet' => in_array($message->id, $sentMessageIds, true),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $messages,
                'sent_message_ids' => $sentMessageIds,
                'sent_logs' => $sentLogs,
                'message' => 'SMS messages retrieved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching SMS messages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching SMS messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get job sheet details by ID
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobSheet(Request $request)
    {
        try {
            $validated = $request->validate([
                'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id',
            ]);

            $jobSheet = JobSheet::with([
                'booking',
                'booking.contact',
                'booking.device',
                'status',
            ])->findOrFail($validated['job_sheet_id']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $jobSheet->id,
                    'job_sheet_no' => $jobSheet->job_sheet_no,
                    'status' => $jobSheet->status->name ?? 'Unknown',
                    'booking' => [
                        'id' => $jobSheet->booking->id ?? null,
                        'booking_no' => $jobSheet->booking->booking_no ?? null,
                    ],
                    'contact' => [
                        'id' => $jobSheet->booking->contact->id ?? null,
                        'name' => $jobSheet->booking->contact->name ?? null,
                        'mobile' => $jobSheet->booking->contact->mobile ?? null,
                        'email' => $jobSheet->booking->contact->email ?? null,
                    ],
                    'device' => [
                        'id' => $jobSheet->booking->device->id ?? null,
                        'device_name' => $jobSheet->booking->device->device_name ?? null,
                        'model_name' => $jobSheet->booking->device->model_name ?? null,
                    ],
                    'created_at' => $jobSheet->created_at,
                    'updated_at' => $jobSheet->updated_at,
                ],
                'message' => 'Job sheet retrieved successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching job sheet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching job sheet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send SMS to a contact
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSms(Request $request)
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'required|integer|exists:contacts,id',
                'message_id' => 'required|integer|exists:sms_messages,id',
                'job_sheet_id' => 'nullable|integer|exists:repair_job_sheets,id',
                'transaction_id' => 'nullable|integer|exists:transactions,id',
            ]);

            // Get contact
            $contact = Contact::findOrFail($validated['contact_id']);
            if (empty($contact->mobile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact does not have a mobile number',
                ], 422);
            }

            // Get SMS message template
            $smsMessage = SmsMessage::findOrFail($validated['message_id']);
            if (!$smsMessage->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS message is inactive',
                ], 422);
            }

            // Use message template
            $messageContent = $smsMessage->message_template;

            // If job_sheet_id provided, fetch and replace job sheet variables
            if (!empty($validated['job_sheet_id'])) {
                $jobSheet = JobSheet::with('booking.contact', 'booking.device')->findOrFail($validated['job_sheet_id']);

                $defaultVariables = [
                    'job_sheet_no' => $jobSheet->job_sheet_no,
                    'booking_no' => $jobSheet->booking->booking_no ?? '',
                    'customer_name' => $jobSheet->booking->contact->first_name ?? $jobSheet->booking->contact->name ?? '',
                    'device_name' => $jobSheet->booking->device->device_name ?? '',
                    'model_name' => $jobSheet->booking->device->model_name ?? '',
                    'status' => $jobSheet->status->name ?? '',
                ];

                foreach ($defaultVariables as $key => $value) {
                    $messageContent = str_replace('{{' . $key . '}}', $value, $messageContent);
                }
            }

            // Remove any unreplaced variables
            $messageContent = preg_replace('/\{\{[^}]+\}\}/', '', $messageContent);

            // Send SMS using SmsUtil
            $smsResult = SmsUtil::sendEpusheg($contact->mobile, $messageContent);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
            $providerBalance = is_array($smsResult) ? $smsResult['balance'] : null;

            $logBaseData = [
                'sms_message_id' => $smsMessage->id,
                'contact_id' => $contact->id,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'job_sheet_id' => $validated['job_sheet_id'] ?? null,
                'mobile' => $contact->mobile,
                'message_content' => $messageContent,
                'sent_at' => now(),
                'provider_balance' => $providerBalance,
            ];

            if ($smsSent) {
                SmsLog::create(array_merge($logBaseData, [
                    'status' => 'sent',
                    'error_message' => null,
                ]));

                Log::info('SMS sent successfully', [
                    'contact_id' => $contact->id,
                    'contact_mobile' => $contact->mobile,
                    'message_id' => $smsMessage->id,
                    'job_sheet_id' => $validated['job_sheet_id'] ?? null,
                    'transaction_id' => $validated['transaction_id'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => [
                        'contact_id' => $contact->id,
                        'contact_name' => $contact->name,
                        'contact_mobile' => $contact->mobile,
                        'message_id' => $smsMessage->id,
                        'message_name' => $smsMessage->name,
                        'message_content' => $messageContent,
                        'job_sheet_id' => $validated['job_sheet_id'] ?? null,
                        'transaction_id' => $validated['transaction_id'] ?? null,
                        'sent_at' => $logBaseData['sent_at'],
                    ],
                ]);
            } else {
                SmsLog::create(array_merge($logBaseData, [
                    'status' => 'failed',
                    'error_message' => 'SMS provider error or configuration issue',
                ]));

                Log::warning('SMS sending failed', [
                    'contact_id' => $contact->id,
                    'contact_mobile' => $contact->mobile,
                    'message_id' => $smsMessage->id,
                    'job_sheet_id' => $validated['job_sheet_id'] ?? null,
                    'transaction_id' => $validated['transaction_id'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS. Please check SMS settings.',
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error sending SMS',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send SMS to multiple contacts
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBulkSms(Request $request)
    {
        try {
            $validated = $request->validate([
                'contact_ids' => 'required|array|min:1',
                'contact_ids.*' => 'integer|exists:contacts,id',
                'message_id' => 'required|integer|exists:sms_messages,id',
                'job_sheet_id' => 'nullable|integer|exists:repair_job_sheets,id',
                'transaction_id' => 'nullable|integer|exists:transactions,id',
            ]);

            $smsMessage = SmsMessage::findOrFail($validated['message_id']);
            if (!$smsMessage->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS message is inactive',
                ], 422);
            }

            $results = [
                'sent' => 0,
                'failed' => 0,
                'details' => [],
            ];

            foreach ($validated['contact_ids'] as $contactId) {
                try {
                    $contact = Contact::findOrFail($contactId);

                    if (empty($contact->mobile)) {
                        $results['failed']++;
                        $results['details'][] = [
                            'contact_id' => $contactId,
                            'status' => 'failed',
                            'reason' => 'No mobile number',
                        ];
                        continue;
                    }

                    $messageContent = $smsMessage->message_template;

                    if (!empty($validated['job_sheet_id'])) {
                        $jobSheet = JobSheet::with('booking.contact', 'booking.device')->findOrFail($validated['job_sheet_id']);

                        $defaultVariables = [
                            'job_sheet_no' => $jobSheet->job_sheet_no,
                            'booking_no' => $jobSheet->booking->booking_no ?? '',
                            'customer_name' => $jobSheet->booking->contact->name ?? '',
                            'device_name' => $jobSheet->booking->device->device_name ?? '',
                            'model_name' => $jobSheet->booking->device->model_name ?? '',
                            'status' => $jobSheet->status->name ?? '',
                        ];

                        foreach ($defaultVariables as $key => $value) {
                            $messageContent = str_replace('{{' . $key . '}}', $value, $messageContent);
                        }
                    }

                    $messageContent = preg_replace('/\{\{[^}]+\}\}/', '', $messageContent);

                    $smsResult = SmsUtil::sendEpusheg($contact->mobile, $messageContent);
                    $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
                    $providerBalance = is_array($smsResult) ? $smsResult['balance'] : null;

                    $logBaseData = [
                        'sms_message_id' => $smsMessage->id,
                        'contact_id' => $contact->id,
                        'transaction_id' => $validated['transaction_id'] ?? null,
                        'job_sheet_id' => $validated['job_sheet_id'] ?? null,
                        'mobile' => $contact->mobile,
                        'message_content' => $messageContent,
                        'sent_at' => now(),
                        'provider_balance' => $providerBalance,
                    ];

                    if ($smsSent) {
                        SmsLog::create(array_merge($logBaseData, [
                            'status' => 'sent',
                            'error_message' => null,
                        ]));

                        $results['sent']++;
                        $results['details'][] = [
                            'contact_id' => $contactId,
                            'contact_name' => $contact->name,
                            'contact_mobile' => $contact->mobile,
                            'status' => 'sent',
                        ];
                    } else {
                        SmsLog::create(array_merge($logBaseData, [
                            'status' => 'failed',
                            'error_message' => 'SMS provider error or configuration issue',
                        ]));

                        $results['failed']++;
                        $results['details'][] = [
                            'contact_id' => $contactId,
                            'contact_name' => $contact->name,
                            'status' => 'failed',
                            'reason' => 'SMS provider error',
                        ];
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'contact_id' => $contactId,
                        'status' => 'failed',
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Bulk SMS sending completed', [
                'sent' => $results['sent'],
                'failed' => $results['failed'],
                'message_id' => $smsMessage->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk SMS sending completed',
                'data' => $results,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending bulk SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error sending bulk SMS',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get SMS message template by ID
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessageTemplate(Request $request)
    {
        try {
            $validated = $request->validate([
                'message_id' => 'required|integer|exists:sms_messages,id',
            ]);

            $message = SmsMessage::findOrFail($validated['message_id']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $message->id,
                    'name' => $message->name,
                    'template' => $message->message_template,
                    'description' => $message->description,
                    'status' => $message->status,
                ],
                'message' => 'Message template retrieved successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching message template', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching message template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
