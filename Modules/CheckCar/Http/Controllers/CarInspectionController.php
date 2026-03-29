<?php

namespace Modules\CheckCar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\BusinessLocation;
use App\Transaction;
use App\Utils\Util;
use App\Utils\UrlShortener;
use App\Utils\SmsUtil;
use Modules\Sms\Entities\SmsMessage;
use Modules\Sms\Entities\SmsLog;
use App\Contact;
use Modules\CheckCar\Entities\CarInspection;
use Modules\CheckCar\Entities\CheckCarElement;
use Modules\CheckCar\Entities\CheckCarElementOption;
use Modules\CheckCar\Entities\CheckCarInspectionItem;
use Modules\CheckCar\Entities\CheckCarPhraseTemplate;
use Modules\CheckCar\Entities\CheckCarQuestionCategory;
use Modules\CheckCar\Entities\CheckCarQuestionSubcategory;
use Modules\CheckCar\Entities\OBDCode;
use Modules\CheckCar\Entities\CheckCarServiceSetting;
use Modules\Repair\Entities\JobSheet;
use Modules\Repair\Entities\ContactDevice;
use Yajra\DataTables\Facades\DataTables;

class CarInspectionController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    public function index()
    {
        $inspections = collect();
        $locationId = optional(auth()->user())->location_id;

        if (Schema::hasTable('checkcar_inspections')) {
            $inspections = CarInspection::where(function ($q) use ($locationId) {
                    if (!empty($locationId)) {
                        $q->where('location_id', $locationId);
                    }
                })
                ->with([
                    'creator',
                    'buyerContact',
                    'sellerContact',
                    'jobSheet',
                    'booking.device.deviceModel',
                    'booking.device.category',
                    'documents'
                ])
                ->orderByDesc('created_at')
                ->paginate(20);

            // Map car data from related booking/device so the index view can display it
            $inspections->getCollection()->transform(function (CarInspection $inspection) {
                $booking = $inspection->booking;
                $device = $booking ? $booking->device : null;

                $inspection->car_brand = $device && $device->category ? $device->category->name : null;
                $inspection->car_model = $device && $device->deviceModel ? $device->deviceModel->name : null;
                $inspection->car_year = $device ? $device->manufacturing_year : null;
                $inspection->car_plate_number = $device ? $device->plate_number : null;
                $inspection->car_kilometers = $inspection->jobSheet ? $inspection->jobSheet->km : null;

                return $inspection;
            });
        }

        return view('checkcar::index', compact('inspections'));
    }

    public function getInspectionsDatatables(Request $request)
    {
        if (!Schema::hasTable('checkcar_inspections')) {
            return datatables()->of(collect())->make(true);
        }

        $user = auth()->user();
        $business_id = $user ? $user->business_id : null;
        $locationId = optional($user)->location_id;
        $isAdmin = $user && $business_id && $this->commonUtil->is_admin($user, $business_id);

        $query = CarInspection::where(function ($q) use ($locationId, $isAdmin) {
                if (!empty($locationId) && !$isAdmin) {
                    $q->where('location_id', $locationId);
                }
            })
            ->with([
            'creator',
            'buyerContact',
            'sellerContact',
            'jobSheet',
            'booking.device.deviceModel',
            'booking.device.category',
            'documents',
            'location',
        ])
            ->orderByDesc('created_at');

        return DataTables::eloquent($query)
            ->editColumn('id', function ($inspection) {
                return '<strong>#' . $inspection->id . '</strong>';
            })
            ->addColumn('car_info', function ($inspection) {
                $device = $inspection->booking ? $inspection->booking->device : null;
                $carBrand = $device && $device->category ? $device->category->name : null;
                $carModel = $device && $device->deviceModel ? $device->deviceModel->name : null;
                $carYear = $device ? $device->manufacturing_year : null;
                $carPlate = $device ? $device->plate_number : null;
                $carKm = $inspection->jobSheet ? $inspection->jobSheet->km : null;

                $html = '<strong>' . htmlspecialchars($carBrand ?? '-') . ' ' . htmlspecialchars($carModel ?? '') . '</strong>';
                if ($carYear) {
                    $html .= ' <span class="text-muted">(' . htmlspecialchars($carYear) . ')</span>';
                }
                if ($carPlate) {
                    $html .= '<br><small class="text-muted"><i class="fa fa-id-card"></i> ' . htmlspecialchars($carPlate) . '</small>';
                }
                if ($carKm) {
                    $html .= '<br><small class="text-muted"><i class="fa fa-tachometer"></i> ' . number_format($carKm) . ' km</small>';
                }
                return $html;
            })
            ->addColumn('location', function ($inspection) {
                return $inspection->location ? e($inspection->location->name) : '<span class="text-muted">-</span>';
            })
            ->addColumn('buyer', function ($inspection) {
                $html = '';
                
                // Try to get buyer contact name
                $buyerName = null;
                if ($inspection->buyerContact) {
                    $nameParts = array_filter([
                        $inspection->buyerContact->first_name ?? null,
                        $inspection->buyerContact->middle_name ?? null,
                        $inspection->buyerContact->last_name ?? null,
                    ]);
                    $buyerName = trim(implode(' ', $nameParts));
                    if (empty($buyerName) && !empty($inspection->buyerContact->name)) {
                        $buyerName = $inspection->buyerContact->name;
                    }
                }
                
                // Fallback to buyer_full_name if no contact
                if (empty($buyerName) && !empty($inspection->buyer_full_name)) {
                    $buyerName = $inspection->buyer_full_name;
                }
                
                if (!empty($buyerName)) {
                    $html = '<strong>' . htmlspecialchars($buyerName) . '</strong>';
                    if ($inspection->buyer_phone) {
                        $html .= '<br><small><i class="fa fa-phone"></i> ' . htmlspecialchars($inspection->buyer_phone) . '</small>';
                    }
                } else {
                    $html = '<span class="text-muted">-</span>';
                }
                
                return $html;
            })
            ->addColumn('seller', function ($inspection) {
                $html = '';
                
                // Try to get seller contact name
                $sellerName = null;
                if ($inspection->sellerContact) {
                    $nameParts = array_filter([
                        $inspection->sellerContact->first_name ?? null,
                        $inspection->sellerContact->middle_name ?? null,
                        $inspection->sellerContact->last_name ?? null,
                    ]);
                    $sellerName = trim(implode(' ', $nameParts));
                    if (empty($sellerName) && !empty($inspection->sellerContact->name)) {
                        $sellerName = $inspection->sellerContact->name;
                    }
                }
                
                // Fallback to seller_full_name if no contact
                if (empty($sellerName) && !empty($inspection->seller_full_name)) {
                    $sellerName = $inspection->seller_full_name;
                }
                
                if (!empty($sellerName)) {
                    $html = '<strong>' . htmlspecialchars($sellerName) . '</strong>';
                    if ($inspection->seller_phone) {
                        $html .= '<br><small><i class="fa fa-phone"></i> ' . htmlspecialchars($inspection->seller_phone) . '</small>';
                    }
                } else {
                    $html = '<span class="text-muted">-</span>';
                }
                
                return $html;
            })
            ->addColumn('rating', function ($inspection) {
                if ($inspection->overall_rating) {
                    $color = $inspection->overall_rating >= 4 ? 'success' : ($inspection->overall_rating >= 3 ? 'warning' : 'danger');
                    return '<span class="label label-' . $color . '">' . $inspection->overall_rating . '/5</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('status', function ($inspection) {
                $statusColors = [
                    'draft' => 'default',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $color = $statusColors[$inspection->status] ?? 'default';
                return '<span class="label label-' . $color . '">' . ucfirst(str_replace('_', ' ', $inspection->status)) . '</span>';
            })
            ->addColumn('created_at', function ($inspection) {
                return '<small>' . $inspection->created_at->format('d/m/Y') . '</small><br><small class="text-muted">' . $inspection->created_at->format('H:i') . '</small>';
            })
            ->addColumn('job_sheet_no', function ($inspection) {
                if ($inspection->jobSheet && !empty($inspection->jobSheet->job_sheet_no)) {
                    return htmlspecialchars($inspection->jobSheet->job_sheet_no);
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($inspection) {
                $actions = '';
                
                // Documents button if documents exist
                if ($inspection->documents && $inspection->documents->count() > 0) {
                    $actions .= '<button type="button" 
                            class="btn btn-info btn-xs js-view-documents" 
                            title="' . __('checkcar::lang.view_documents') . '"
                            data-inspection-id="' . $inspection->id . '"
                            data-inspection-documents="' . $inspection->documents->count() . '"
                            data-documents-url="' . route('checkcar.inspections.documents', ['inspection' => $inspection->id]) . '">
                        <i class="fa fa-file"></i>
                        <span class="badge">' . $inspection->documents->count() . '</span>
                    </button> ';
                }
                
                // Send SMS notifications button
                $actions .= ' <button type="button"
                            class="btn btn-primary btn-xs js-send-sms"
                            title="' . e(__('messages.send')) . ' SMS"
                            data-inspection-id="' . $inspection->id . '">
                            <i class="fa fa-paper-plane"></i>
                        </button>';

                // Change car owner button (switch contact_device.contact_id and transaction.contact_id)
                // if ($inspection->buyer_contact_id || $inspection->seller_contact_id) {
                //     $actions .= ' <button type="button"
                //             class="btn btn-warning btn-xs js-change-car-owner"
                //             title="' . e(__('checkcar::lang.change_car_owner')) . '"
                //             data-url="' . e(route('checkcar.inspections.change_car_owner_modal', ['inspection' => $inspection->id])) . '">
                //             <i class="fa fa-random"></i>
                //         </button>';
                // }
                
                // Share button if token exists
                if ($inspection->share_token) {
                    $actions .= ' <a href="' . route('checkcar.inspections.public.show', ['inspection' => $inspection->id, 'token' => $inspection->share_token]) . '" 
                                   target="_blank"
                                   class="btn btn-success btn-xs" title="' . __('checkcar::lang.public_view') . '">
                                    <i class="fa fa-share-alt"></i>
                                </a>';
                }
                return $actions;
            })
            ->filterColumn('location', function ($query, $keyword) {
                $query->whereHas('location', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('car_info', function ($query, $keyword) {
                $query->whereHas('booking.device.category', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                })
                ->orWhereHas('booking.device.deviceModel', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                })
                ->orWhereHas('booking.device', function ($q) use ($keyword) {
                    $q->where('plate_number', 'like', "%{$keyword}%")
                      ->orWhere('manufacturing_year', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('buyer', function ($query, $keyword) {
                $query->whereHas('buyerContact', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('mobile', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('seller', function ($query, $keyword) {
                $query->whereHas('sellerContact', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('mobile', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('created_at', function ($query, $order) {
                $query->orderBy('created_at', $order);
            })
            ->orderColumn('status', function ($query, $order) {
                $query->orderBy('status', $order);
            })
            ->rawColumns(['id', 'car_info', 'buyer', 'seller', 'rating', 'status', 'created_at', 'job_sheet_no', 'location', 'actions'])
            ->make(true);
    }

    /**
     * Send SMS notifications to buyer and seller for an inspection (web endpoint).
     */
    public function sendSms(Request $request, $inspectionId)
    {
        $inspection = \Modules\CheckCar\Entities\CarInspection::findOrFail($inspectionId);
        
        // Get recipient selection from request
        $recipient = $request->input('recipient', 'both'); // Default to both for backward compatibility

        // Ensure share token exists so share URL is stable
        if (empty($inspection->share_token) && method_exists($inspection, 'generateShareToken')) {
            $inspection->generateShareToken();
            $inspection->refresh();
        }

        // Resolve buyer & seller contacts
        $buyerContactModel = null;
        if (!empty($inspection->buyer_contact_id)) {
            $buyerContactModel = Contact::find($inspection->buyer_contact_id);
        }

        $sellerContactModel = null;
        if (!empty($inspection->seller_contact_id)) {
            $sellerContactModel = Contact::find($inspection->seller_contact_id);
        }

        $buyerContact = (object) [
            'id' => $buyerContactModel ? $buyerContactModel->id : null,
            'first_name' => $buyerContactModel ? $buyerContactModel->first_name : null,
            'name' => $buyerContactModel ? $buyerContactModel->name : $inspection->buyer_full_name,
            'mobile' => $buyerContactModel ? $buyerContactModel->mobile : $inspection->buyer_phone,
        ];

        $sellerContact = (object) [
            'id' => $sellerContactModel ? $sellerContactModel->id : null,
            'first_name' => $sellerContactModel ? $sellerContactModel->first_name : null,
            'name' => $sellerContactModel ? $sellerContactModel->name : $inspection->seller_full_name,
            'mobile' => $sellerContactModel ? $sellerContactModel->mobile : $inspection->seller_phone,
        ];

        try {
            $this->sendInspectionSmsNotifications($inspection, $buyerContact, $sellerContact, $recipient);

            return response()->json([
                'success' => true,
                'message' => __('messages.success'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send car inspection SMS via web endpoint', [
                'inspection_id' => $inspection->id,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Send SMS notifications to buyer and seller using single template
     * (mirrors Connector API behavior)
     */
    private function sendInspectionSmsNotifications($inspection, $buyerContact, $sellerContact, $recipient = 'both')
    {
        // Get single SMS template for car inspection
        $smsMessage = SmsMessage::where('name', 'car_inspection')
            ->where('status', 1)
            ->first();

        if (!$smsMessage) {
            Log::warning('Car inspection SMS template not found', [
                'template_name' => 'car_inspection'
            ]);
            return;
        }

        // Send to buyer if selected
        if (($recipient === 'buyer' || $recipient === 'both') && !empty($buyerContact->mobile)) {
            $buyerMessageContent = $this->replaceSmsVariables($smsMessage->message_template, $inspection, $buyerContact, 'buyer');
            $smsSent = SmsUtil::sendEpusheg($buyerContact->mobile, $buyerMessageContent);
            SmsLog::create([
                'sms_message_id' => $smsMessage->id,
                'contact_id' => $buyerContact->id,
                'transaction_id' => null,
                'job_sheet_id' => $inspection->job_sheet_id,
                'mobile' => $buyerContact->mobile,
                'message_content' => $buyerMessageContent,
                'status' => $smsSent ? 'sent' : 'failed',
                'error_message' => $smsSent ? null : 'Failed to send SMS',
                'provider_balance' => SmsUtil::getLastNetBalance(),
                'sent_at' => $smsSent ? now() : null,
            ]);
        } else if (($recipient === 'buyer' || $recipient === 'both') && empty($buyerContact->mobile)) {
            Log::warning('Skipping car inspection SMS for buyer: no mobile', [
                'inspection_id' => $inspection->id,
                'recipient' => $recipient,
            ]);
        }

        // Send to seller if selected
        if (($recipient === 'seller' || $recipient === 'both') && !empty($sellerContact->mobile)) {
            $sellerMessageContent = $this->replaceSmsVariables($smsMessage->message_template, $inspection, $sellerContact, 'seller');
            $smsSent = SmsUtil::sendEpusheg($sellerContact->mobile, $sellerMessageContent);
            SmsLog::create([
                'sms_message_id' => $smsMessage->id,
                'contact_id' => $sellerContact->id,
                'transaction_id' => null,
                'job_sheet_id' => $inspection->job_sheet_id,
                'mobile' => $sellerContact->mobile,
                'message_content' => $sellerMessageContent,
                'status' => $smsSent ? 'sent' : 'failed',
                'error_message' => $smsSent ? null : 'Failed to send SMS',
                'provider_balance' => SmsUtil::getLastNetBalance(),
                'sent_at' => $smsSent ? now() : null,
            ]);
        } else if (($recipient === 'seller' || $recipient === 'both') && empty($sellerContact->mobile)) {
            Log::warning('Skipping car inspection SMS for seller: no mobile', [
                'inspection_id' => $inspection->id,
                'recipient' => $recipient,
            ]);
        }
    }

    /**
     * Replace SMS template variables (shortens share URL if possible)
     */
    private function replaceSmsVariables($template, $inspection, $contact, $contactType)
    {
        $originalShareUrl = !empty($inspection->share_token) && method_exists($inspection, 'getShareUrl')
            ? $inspection->getShareUrl()
            : '';
        $shortShareUrl = '';
        if (!empty($originalShareUrl)) {
            try {
                $urlShortener = new UrlShortener();
                $shortShareUrl = $urlShortener->shorten($originalShareUrl) ?: $originalShareUrl;
            } catch (\Throwable $e) {
                $shortShareUrl = $originalShareUrl;
            }
        }

        $variables = [
            '{{inspection_id}}' => $inspection->id,
            '{{customer_name}}' => $contact->first_name ?? $contact->name ?? '',
            '{{customer_full_name}}' => $contact->name ?? '',
            '{{customer_mobile}}' => $contact->mobile ?? '',
            '{{car_brand}}' => $inspection->car_brand ?? '',
            '{{car_model}}' => $inspection->car_model ?? '',
            '{{car_year}}' => $inspection->car_year ?? '',
            '{{car_color}}' => $inspection->car_color ?? '',
            '{{car_chassis_number}}' => $inspection->car_chassis_number ?? '',
            '{{car_plate_number}}' => $inspection->car_plate_number ?? '',
            '{{car_kilometers}}' => $inspection->car_kilometers ?? 0,
            '{{inspection_status}}' => $inspection->status ?? '',
            '{{contact_type}}' => $contactType,
            '{{inspection_date}}' => $inspection->created_at ? $inspection->created_at->format('Y-m-d H:i') : '',
            '{{share_url}}' => $shortShareUrl,
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    /**
     * Render modal for changing car owner (switch between buyer and seller).
     */
    public function changeCarOwnerModal($inspectionId)
    {
        $inspection = CarInspection::with(['buyerContact', 'sellerContact', 'booking.device'])->findOrFail($inspectionId);

        $currentOwner = null;
        $deviceOwnerId = null;

        if (!empty($inspection->contact_device_id) && class_exists(\Modules\Repair\Entities\ContactDevice::class)) {
            $device = \Modules\Repair\Entities\ContactDevice::find($inspection->contact_device_id);
            if ($device) {
                $deviceOwnerId = $device->contact_id;
            }
        }

        if ($deviceOwnerId && $deviceOwnerId == $inspection->buyer_contact_id) {
            $currentOwner = 'buyer';
        } elseif ($deviceOwnerId && $deviceOwnerId == $inspection->seller_contact_id) {
            $currentOwner = 'seller';
        }

        return view('checkcar::inspections.partials.change_car_owner_modal', compact('inspection', 'currentOwner'));
    }

    /**
     * Persist car owner change: only update contact_device.contact_id and repair Transaction.contact_id.
     */
    public function updateCarOwner(Request $request, $inspectionId)
    {
        $inspection = CarInspection::findOrFail($inspectionId);

        $data = $request->validate([
            'new_owner' => 'required|in:buyer,seller',
        ]);

        if ($data['new_owner'] === 'buyer' && empty($inspection->buyer_contact_id)) {
            return response()->json([
                'success' => false,
                'message' => __('checkcar::lang.buyer_not_available'),
            ], 422);
        }

        if ($data['new_owner'] === 'seller' && empty($inspection->seller_contact_id)) {
            return response()->json([
                'success' => false,
                'message' => __('checkcar::lang.seller_not_available'),
            ], 422);
        }

        $newContactId = $data['new_owner'] === 'buyer'
            ? $inspection->buyer_contact_id
            : $inspection->seller_contact_id;

        if (empty($newContactId)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 422);
        }

        DB::transaction(function () use ($inspection, $newContactId) {
            // Update contact_device contact
            if (!empty($inspection->contact_device_id) && class_exists(\Modules\Repair\Entities\ContactDevice::class)) {
                $device = \Modules\Repair\Entities\ContactDevice::find($inspection->contact_device_id);
                if ($device) {
                    $device->contact_id = $newContactId;
                    $device->save();
                }
            }

            // Update related repair sell transaction contact
            if (!empty($inspection->job_sheet_id)) {
                Transaction::where('repair_job_sheet_id', $inspection->job_sheet_id)
                    ->where('type', 'sell')
                    ->where('sub_type', 'repair')
                    ->update(['contact_id' => $newContactId]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => __('checkcar::lang.car_owner_updated'),
        ]);
    }

    public function create()
    {
        $categories = collect();
        $subcategories = collect();
        $elements = collect();
        $elementOptions = collect();
        $phraseTemplates = collect();

        if (Schema::hasTable('checkcar_question_categories')) {
            $categories = CheckCarQuestionCategory::active()->ordered()->get();
        }

        if (Schema::hasTable('checkcar_question_subcategories')) {
            $subcategories = CheckCarQuestionSubcategory::with('category')
                ->active()
                ->ordered()
                ->get();
        }

        if (Schema::hasTable('checkcar_elements')) {
            $elements = CheckCarElement::with(['category', 'subcategory', 'options'])
                ->active()
                ->ordered()
                ->get();
        }

        if (Schema::hasTable('checkcar_element_options')) {
            $elementOptions = CheckCarElementOption::ordered()->get()->groupBy('element_id');
        }

        if (Schema::hasTable('checkcar_phrase_templates')) {
            $phraseTemplates = CheckCarPhraseTemplate::with('element')->get()->groupBy('element_id');
        }

        $obdCodes = [];
        if (Schema::hasTable('obd_codes')) {
            $obdCodes = OBDCode::orderBy('code')->get();
        }

        return view('checkcar::inspections.create', compact(
            'categories',
            'subcategories', 
            'elements',
            'elementOptions',
            'phraseTemplates',
            'obdCodes'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // Step 1 - customers
            'buyer_full_name' => 'nullable|string|max:255',
            'buyer_phone' => 'nullable|string|max:255',
            'buyer_id_number' => 'nullable|string|max:255',
            'seller_full_name' => 'nullable|string|max:255',
            'seller_phone' => 'nullable|string|max:255',
            'seller_id_number' => 'nullable|string|max:255',

            // Step 2 - car
            'car_brand' => 'nullable|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_year' => 'nullable|string|max:10',
            'car_color' => 'nullable|string|max:255',
            'car_chassis_number' => 'nullable|string|max:255',
            'car_plate_number' => 'nullable|string|max:255',
            'car_kilometers' => 'nullable|integer|min:0',

            // Team
            'inspection_team' => 'nullable|array',

            // Items (element responses)
            'items' => 'nullable|array',
            'items.*.element_id' => 'required|integer|exists:checkcar_elements,id',
            'items.*.option_ids' => 'nullable|array',
            'items.*.option_ids.*' => 'integer|exists:checkcar_element_options,id',
            'items.*.note' => 'nullable|string',

            // Final
            'final_summary' => 'nullable|string',
            'overall_rating' => 'nullable|integer|min:1|max:5',
        ]);

        $data['created_by'] = auth()->id();
        $data['location_id'] = optional(auth()->user())->location_id;
        $data['status'] = 'draft';

        // Extract items before creating inspection
        $items = $data['items'] ?? [];
        unset($data['items']);

        $inspection = CarInspection::create($data);

        // Save inspection items
        foreach ($items as $itemData) {
            $element = CheckCarElement::with(['category', 'subcategory'])->find($itemData['element_id']);
            if (!$element) continue;

            // Build selected options array
            $selectedOptions = [];
            if (!empty($itemData['option_ids'])) {
                $options = CheckCarElementOption::whereIn('id', $itemData['option_ids'])->get();
                foreach ($options as $option) {
                    $selectedOptions[] = [
                        'id' => $option->id,
                        'label' => $option->label,
                        'value' => $option->value ?? $option->label
                    ];
                }
            }

            CheckCarInspectionItem::create([
                'inspection_id' => $inspection->id,
                'element_id' => $element->id,
                'selected_options' => $selectedOptions,
                'note' => $itemData['note'] ?? null,
            ]);
        }

        return redirect()
            ->route('checkcar.inspections.show', $inspection->id)
            ->with('status', __('checkcar::lang.saved_successfully'));
    }

    public function show($id)
    {
        $inspection = CarInspection::with(['items.category', 'items.subcategory', 'creator', 'documents'])->findOrFail($id);

        // Group items by category for display
        $itemsByCategory = $inspection->items->groupBy(function ($item) {
            return $item->category ? $item->category->name : 'Uncategorized';
        });

        // Group documents by party (buyer/seller) for easier rendering
        $documentsByParty = $inspection->documents->groupBy('party');

        $obdCodes = [];
        if (Schema::hasTable('obd_codes')) {
            $obdCodes = OBDCode::all();
        }

        return view('checkcar::inspections.show', compact('inspection', 'itemsByCategory', 'documentsByParty', 'obdCodes'));
    }

    public function getDocuments($id)
    {
        try {
            $inspection = CarInspection::with(['documents'])->findOrFail($id);
            
            $documents = $inspection->documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'party' => $document->party,
                    'document_type' => $document->document_type,
                    'file_path' => $document->file_path,
                    'url' => $document->file_path ? asset('storage/' . ltrim($document->file_path, '/')) : null,
                    'mime_type' => $document->mime_type,
                    'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function publicShow($inspectionId, Request $request, $token)
    {
        $inspection = CarInspection::with([
                'items.element',
                'items.category',
                'items.subcategory',
                'creator.business',
                'documents',
                'booking.media',
            ])->findOrFail($inspectionId);

        if (empty($inspection->share_token) || $inspection->share_token !== $token) {
            abort(404);
        }

        $itemsByCategory = $inspection->items
            ->sortBy(function ($item) {
                $categoryOrder = $item->category ? (int) $item->category->sort_order : 9999;
                $subcategoryOrder = $item->subcategory ? (int) $item->subcategory->sort_order : 9999;
                $elementOrder = $item->element ? (int) $item->element->sort_order : 9999;

                return sprintf('%05d-%05d-%05d-%05d', $categoryOrder, $subcategoryOrder, $elementOrder, $item->id);
            })
            ->groupBy(function ($item) {
                return $item->category ? $item->category->name : 'Uncategorized';
            });
        $documentsByParty = $inspection->documents->groupBy('party');

        $jobSheet = null;
        $carDiagramUrl = null;
        if (!empty($inspection->job_sheet_id) && class_exists(JobSheet::class)) {
            $jobSheet = JobSheet::with('media')->find($inspection->job_sheet_id);
            if ($jobSheet && $jobSheet->media->isNotEmpty()) {
                // Prefer the most recent non-default image; fall back to any available image
                $sortedMedia = $jobSheet->media->sortByDesc('created_at');

                $selectedMedia = $sortedMedia->first(function ($media) {
                    return !empty($media->file_name) && $media->file_name !== 'jobsheet_def.png';
                });

                if (!$selectedMedia) {
                    $selectedMedia = $sortedMedia->first(function ($media) {
                        return !empty($media->file_name);
                    });
                }

                if ($selectedMedia) {
                    // Use display_url attribute which handles both storage/ and uploads/media/ paths correctly
                    $carDiagramUrl = $selectedMedia->display_url;
                }
            }
        }

        $contactDevice = null;
        if (!empty($inspection->contact_device_id) && class_exists(ContactDevice::class)) {
            $contactDevice = ContactDevice::with(['deviceModel', 'deviceCategory', 'brandOriginVariant'])->find($inspection->contact_device_id);
        }

        $business = optional($inspection->creator)->business;
        $businessLocation = null;
        if (!empty($inspection->location_id)) {
            $businessLocation = BusinessLocation::find($inspection->location_id);
        }

        // Resolve watermark image from CheckCar service settings if configured for this business
        $watermarkUrl = null;
        if ($business && Schema::hasTable('checkcar_service_settings')) {
            $serviceSetting = CheckCarServiceSetting::forBusiness($business->id)
                ->where('type', 'watermark')
                ->first();

            if ($serviceSetting && !empty($serviceSetting->watermark_image)) {
                $watermarkImage = $serviceSetting->watermark_image;

                // If it's already a full URL, use as-is; otherwise build from storage path using current domain
                if (is_string($watermarkImage) && (
                    stripos($watermarkImage, 'http://') === 0 ||
                    stripos($watermarkImage, 'https://') === 0 ||
                    strpos($watermarkImage, '//') === 0
                )) {
                    $watermarkUrl = $watermarkImage;
                } else {
                    $watermarkUrl = asset('storage/' . ltrim($watermarkImage, '/'));
                }
            }
        }

        $bookingMedia = collect(optional($inspection->booking)->media)
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($mediaItem) {
                $extension = strtolower(pathinfo($mediaItem->file_name ?? '', PATHINFO_EXTENSION));
                $videoExtensions = ['mp4', 'mov', 'avi', 'flv', 'webm'];
                $type = in_array($extension, $videoExtensions, true) ? 'video' : 'image';

                return [
                    'id' => $mediaItem->id,
                    'type' => $type,
                    'url' => $mediaItem->display_url,
                    'file_name' => $mediaItem->file_name,
                    'created_at' => $mediaItem->created_at,
                ];
            });

        return view(
            'checkcar::inspections.public_show',
            compact(
                'inspection',
                'itemsByCategory',
                'documentsByParty',
                'jobSheet',
                'carDiagramUrl',
                'contactDevice',
                'business',
                'businessLocation',
                'watermarkUrl',
                'bookingMedia'
            )
        );
    }
}
