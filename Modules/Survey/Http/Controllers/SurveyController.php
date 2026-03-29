<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $surveys = DB::table('surveys')->get();
        // dd($surveys);
        // return view('surveys.index', compact('surveys'));
        return view('survey::survey.index', compact('surveys'));
    }

    public function getSurveyData()
    {
        $surveys = DB::table('surveys')->select(['id', 'title', 'description', 'type'])->get();

        return DataTables::of($surveys)
            ->addColumn(
                'action',
                function ($survey) {
                    $html = '<div class="btn-group">
                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    ' . __('messages.actions') . '
                    <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // View Action
                    if (auth()->user()->can('survey.view')) {
                        $html .= '<li><a href="' . route('survey.show', $survey->id) . '" class="view-product"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                        $html .= '<li><a href="' . url('survey/analytics/' . $survey->id . '/details') . '" class="view-product"><i class="fa fa-chart-bar"></i> ' . __('survey::lang.overview') . '</a></li>';
                    }

                    // Edit Action
                    if (auth()->user()->can('survey.update')) {
                        $html .= '<li><a href="' . route('survey.edit', $survey->id) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    }

                    // Delete Action
                    if (auth()->user()->can('survey.delete')) {
                        $html .= '<li><a href="' . route('survey.destroy', $survey->id) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                    }

                    // send Action
                    if (auth()->user()->can('survey.delete')) {
                        $html .= '<li><a href="' . route('know.send', $survey->id) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __('survey::lang.sendperson') . '</a></li>';
                    }
                    if (auth()->user()->can('survey.delete')) {
                        $html .= '<li><a href="' . route('group.send', $survey->id) . '" class="add-opening-stock"><i class="fa fa-database"></i>' . __('survey::lang.sendgroup') . '</a></li>';
                    }
                    if (auth()->user()->can('survey.delete')) {
                        $html .= '<li><a href="javascript:void(0);" class="copy-url" data-url="' . env('APP_URL') . 'general/group/' . $survey->id . '/' . $survey->title . '" onclick="copyToClipboard(this)"><i class="fa fa-copy"></i> ' . __('survey::lang.createurl') . '</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                }
            )
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $types = DB::table('types_of_services')->select('name', 'id')->get();
        $categories = DB::table('survey_categories')->where('active', 1)->orderBy('name')->get();
        // dd($types);
        return view('survey::survey.create', compact('types', 'categories'));
    }

    public function store(Request $request)
    {
        // Validate survey and questions payload
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable'],
            'survey_category_id' => ['nullable', 'integer'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'string', Rule::in(['text', 'radio', 'checkbox', 'rating', 'like'])],
        ]);

        // Additional conditional validations per question type
        foreach ($request->questions as $index => $question) {
            if (in_array($question['type'], ['radio', 'checkbox'])) {
                Validator::make($question, [
                    'options' => ['required', 'array', 'min:1'],
                ])->validate();
            }
            if ($question['type'] === 'rating') {
                Validator::make($question, [
                    'min_range' => ['required', 'integer'],
                    'max_range' => ['required', 'integer', 'gte:min_range'],
                ])->validate();
            }
        }

        DB::table('surveys')->insert([
            'title' => $request->title,
            'description' => $request->description,
            'admin_id' => auth()->user()->id,
            'type' => $request->type ?? 'general',
            'survey_category_id' => $request->survey_category_id,
        ]);
        $survey_id = DB::table('surveys')->where('title', $request->title)->value('id');
        foreach ($request->questions as $question) {
            // Resolve type_id from types table and guard against null
            $type_id = DB::table('types')->where('type_name', $question['type'])->value('id');
            if (!$type_id) {
                return back()->withErrors(['questions' => 'Invalid question type provided.'])->withInput();
            }
            if ($type_id == 2 || $type_id == 3) {
                $des = [];
                foreach ($question['options'] as $value) {
                    if (is_array($value)) {
                        $label = trim((string) ($value['label'] ?? ''));
                        if ($label === '') {
                            continue;
                        }
                        $score = isset($value['score']) && $value['score'] !== '' ? (int) $value['score'] : null;
                        $entry = ['label' => $label];
                        if ($score !== null) {
                            $entry['score'] = $score;
                        }
                        $des[] = $entry;
                    } else {
                        $label = trim((string) $value);
                        if ($label !== '') {
                            $des[] = $label;
                        }
                    }
                }
                DB::table('questions')->insert([
                    'survey_id' => $survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                    'description' => json_encode($des),
                ]);
            } elseif ($type_id == 4) {
                $range = [$question['min_range'], $question['max_range']];
                DB::table('questions')->insert([
                    'survey_id' => $survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                    'description' => json_encode($range),
                ]);
            } elseif ($type_id == 5) {
                DB::table('questions')->insert([
                    'survey_id' => $survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                    'description' => json_encode(['like', 'dislike']),
                ]);
            } else {
                DB::table('questions')->insert([
                    'survey_id' => $survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                ]);
            }
        }
        return redirect('/survey');
    }

    public function show($id)
    {
        $survey = DB::table('surveys')->where('id', $id)->first();
        $questions = DB::table('questions')->where('survey_id', $id)->get();
        $surveySettings = DB::table('survey_settings')->first();
        $business = DB::table('business')->first();
        return view('survey::survey.show', compact('survey', 'questions', 'surveySettings', 'business'));
    }

    public function edit($id)
    {
        $survey = DB::table('surveys')->where('id', $id)->first();
        $questions = DB::table('questions')->where('survey_id', $id)->get();
        $types = DB::table('types_of_services')->select('name', 'id')->get();
        $categories = DB::table('survey_categories')->where('active', 1)->orderBy('name')->get();

        return view('survey::survey.update', compact('survey', 'questions', 'types', 'categories'));
    }

    public function update(Request $request)
    {
        // dd($request);

        DB::table('surveys')
            ->where('id', $request->survey_id)
            ->update([
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'survey_category_id' => $request->survey_category_id,
            ]);
        DB::table('questions')->where('survey_id', $request->survey_id)->delete();
        // DB::table('action')->where('survey_id', $request->survey_id)->where('fill', 1)->delete();
        foreach ($request->questions as $question) {
            $type_id = $question['type_id'];
            if ($type_id == 2 || $type_id == 3) {
                $des = [];
                foreach ($question['options'] as $value) {
                    if (is_array($value)) {
                        $label = trim((string) ($value['label'] ?? ''));
                        if ($label === '') {
                            continue;
                        }
                        $score = isset($value['score']) && $value['score'] !== '' ? (int) $value['score'] : null;
                        $entry = ['label' => $label];
                        if ($score !== null) {
                            $entry['score'] = $score;
                        }
                        $des[] = $entry;
                    } else {
                        $label = trim((string) $value);
                        if ($label !== '') {
                            $des[] = $label;
                        }
                    }
                }
                DB::table('questions')->insert([
                    'survey_id' => $request->survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                    'description' => json_encode($des),
                ]);
            } elseif ($type_id == 4) {
                $range = [$question['min_range'], $question['max_range']];
                DB::table('questions')->insert([
                    'survey_id' => $request->survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                    'description' => json_encode($range),
                ]);
            } elseif ($type_id == 5) {
                DB::table('questions')->insert([
                    'survey_id' => $request->survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                    'description' => json_encode(['like', 'dislike']),
                ]);
            } else {
                DB::table('questions')->insert([
                    'survey_id' => $request->survey_id,
                    'text' => $question['question_text'],
                    'type_id' => $type_id,
                ]);
            }
        }
        return redirect('/survey');
    }


    public function destroy($id)
    {
        DB::table('questions')->where('survey_id', $id)->delete();
        DB::table('surveys')->where('id', $id)->delete();
        DB::table('old_surveys')->where('old_survey_id', $id)->delete();
        DB::table('old_questions')->where('survey_id', $id)->delete();
        DB::table('responses')->where('old_survey_id', $id)->delete();
        DB::table('action')->where('survey_id', $id)->delete();
        return redirect('/survey');
    }

    public function showSurvey($first_name, $survey_id, $action_id)
    {
        $user_id = DB::table('action')->where('id', $action_id)->first();
        // dd($user_id);
        $user = DB::table('contacts')->where('id', $user_id->user_id)->first();
        // dd($user);
        $action = DB::table('action')->where('user_id', $user->id)->where('id', $action_id)->first();
        if ($action->seen == 1) {
            $questions = DB::table('old_questions')->where('contact_id', $action->user_id)->get();
            $survey = DB::table('old_surveys')->where('contact_id', $action->user_id)->get();
        }
        // dd($action);
        else {
            $questions = DB::table('questions')->where('survey_id', $action->survey_id)->get();
            $survey = DB::table('surveys')->where('id', $action->survey_id)->get();
        }
        $surveySettings = DB::table('survey_settings')->first();
        $business = DB::table('business')->first();
        return view('survey::showsurveysend', compact('questions', 'survey', 'user', 'action', 'surveySettings', 'business'));
        echo 'eddlfr';
    }

    public function getActiveSurveys(Request $request)
    {
        $categoryId = $request->query('category_id');

        $query = DB::table('surveys')
            ->select(['id', 'title', 'description']);

        if ($categoryId) {
            $query->where('survey_category_id', $categoryId);
        }

        $surveys = $query->get();

        return response()->json(['surveys' => $surveys]);
    }

    public function showConditionalSend()
    {
        return view('survey::conditional-send');
    }

    public function getConditionalContacts(Request $request)
    {
        $actionType = $request->input('action_type');
        $actionStatus = $request->input('action_status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $contacts = collect();
        $dateColumn = '';
        $statusColumn = '';

        // Determine date and status columns based on action type
        switch ($actionType) {
            case 'direct_sale':
                $dateColumn = 'transaction_date';
                $statusColumn = 'status';
                break;
            case 'repair_transaction':
                $dateColumn = 'transaction_date';
                $statusColumn = 'status';
                break;
            case 'crm_follow_up':
                $dateColumn = 'start_datetime';
                $statusColumn = 'status';
                break;
            default:
                return response()->json(['count' => 0, 'contacts' => []]);
        }

        // Build query based on action type
        $query = DB::table('contacts')
            ->select('contacts.id', 'contacts.mobile', 'contacts.first_name', 'contacts.last_name');

        switch ($actionType) {
            case 'direct_sale':
                $query->join('transactions', 'contacts.id', '=', 'transactions.contact_id')
                    ->where('transactions.type', '=', 'sell')
                    ->where(function ($q) {
                        $q->whereNull('transactions.sub_type')->orWhere('transactions.sub_type', '!=', 'repair');
                    })
                    ->whereBetween('transactions.' . $dateColumn, [$startDate, $endDate])
                    ->where('transactions.' . $statusColumn, '=', $actionStatus);
                break;

            case 'repair_transaction':
                $query->join('transactions', 'contacts.id', '=', 'transactions.contact_id')
                    ->where('transactions.type', '=', 'sell')
                    ->where('transactions.sub_type', '=', 'repair')
                    ->whereBetween('transactions.' . $dateColumn, [$startDate, $endDate])
                    ->where('transactions.' . $statusColumn, '=', $actionStatus);
                break;

            case 'crm_follow_up':
                $query->join('crm_schedules', 'contacts.id', '=', 'crm_schedules.contact_id')
                    ->whereBetween('crm_schedules.' . $dateColumn, [$startDate, $endDate])
                    ->where('crm_schedules.' . $statusColumn, '=', $actionStatus);
                break;
        }

        // Get unique contacts only
        $contacts = $query
            ->where('contacts.contact_status', '=', 'active')
            ->whereNotNull('contacts.mobile')
            ->where('contacts.mobile', '!=', '')
            ->distinct()
            ->get();

        // Format contacts for response
        $formattedContacts = $contacts->map(function($contact) use ($actionType, $dateColumn) {
            $actionDate = '';
            switch ($actionType) {
                case 'direct_sale':
                    $actionDate = DB::table('transactions')
                        ->where('contact_id', $contact->id)
                        ->where('type', 'sell')
                        ->where(function ($q) {
                            $q->whereNull('sub_type')->orWhere('sub_type', '!=', 'repair');
                        })
                        ->orderBy($dateColumn, 'desc')
                        ->value($dateColumn);
                    break;
                case 'repair_transaction':
                    $actionDate = DB::table('transactions')
                        ->where('contact_id', $contact->id)
                        ->where('type', 'sell')
                        ->where('sub_type', 'repair')
                        ->orderBy($dateColumn, 'desc')
                        ->value($dateColumn);
                    break;
                case 'crm_follow_up':
                    $actionDate = DB::table('crm_schedules')
                        ->where('contact_id', $contact->id)
                        ->orderBy($dateColumn, 'desc')
                        ->value($dateColumn);
                    break;
            }

            return [
                'id' => $contact->id,
                'first_name' => $contact->first_name . ' ' . $contact->last_name,
                'mobile' => $contact->mobile,
                'action_date' => $actionDate
            ];
        });

        return response()->json(['count' => $formattedContacts->count(), 'contacts' => $formattedContacts]);
    }

    public function searchContactByMobile(Request $request)
    {
        $mobile = $request->input('mobile');

        if (!$mobile) {
            return response()->json(['contact' => null]);
        }

        $contact = DB::table('contacts')
            ->where('mobile', $mobile)
            ->where('contact_status', 'active')
            ->first();

        if ($contact) {
            return response()->json(['contact' => $contact]);
        }

        return response()->json(['contact' => null]);
    }

    public function sendConditionalSurvey(Request $request)
    {
        $actionType = $request->input('action_type');
        $actionStatus = $request->input('action_status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $surveyId = $request->input('survey_id');
        $channel = $request->input('channel');

        $contacts = collect();
        $dateColumn = '';
        $statusColumn = '';

        // Determine date and status columns based on action type
        switch ($actionType) {
            case 'direct_sale':
                $dateColumn = 'transaction_date';
                $statusColumn = 'status';
                break;
            case 'repair_transaction':
                $dateColumn = 'transaction_date';
                $statusColumn = 'status';
                break;
            case 'crm_follow_up':
                $dateColumn = 'start_datetime';
                $statusColumn = 'status';
                break;
            default:
                return response()->json(['success' => false, 'message' => __('survey::lang.invalid-action-type')]);
        }

        // Build query based on action type
        $query = DB::table('contacts')
            ->select('contacts.id', 'contacts.mobile', 'contacts.first_name', 'contacts.last_name');

        switch ($actionType) {
            case 'direct_sale':
                $query->join('transactions', 'contacts.id', '=', 'transactions.contact_id')
                    ->where('transactions.type', '=', 'sell')
                    ->where(function ($q) {
                        $q->whereNull('transactions.sub_type')->orWhere('transactions.sub_type', '!=', 'repair');
                    })
                    ->whereBetween('transactions.' . $dateColumn, [$startDate, $endDate])
                    ->where('transactions.' . $statusColumn, '=', $actionStatus);
                break;

            case 'repair_transaction':
                $query->join('transactions', 'contacts.id', '=', 'transactions.contact_id')
                    ->where('transactions.type', '=', 'sell')
                    ->where('transactions.sub_type', '=', 'repair')
                    ->whereBetween('transactions.' . $dateColumn, [$startDate, $endDate])
                    ->where('transactions.' . $statusColumn, '=', $actionStatus);
                break;

            case 'crm_follow_up':
                $query->join('crm_schedules', 'contacts.id', '=', 'crm_schedules.contact_id')
                    ->whereBetween('crm_schedules.' . $dateColumn, [$startDate, $endDate])
                    ->where('crm_schedules.' . $statusColumn, '=', $actionStatus);
                break;
        }

        // Get unique contacts only
        $contacts = $query
            ->where('contacts.contact_status', '=', 'active')
            ->whereNotNull('contacts.mobile')
            ->where('contacts.mobile', '!=', '')
            ->distinct()
            ->get();

        $sentCount = 0;
        $url = rtrim($request->getSchemeAndHttpHost() ?: config('app.url'), '/') . '/';

        foreach ($contacts as $contact) {
            $currentTime = now();
            DB::table('action')->insert([
                'user_id' => $contact->id,
                'survey_id' => $surveyId,
                'timesend' => $currentTime,
                'type_form' => 'Conditional Send',
            ]);
            $actionId = DB::table('action')
                ->where('timesend', $currentTime)
                ->where('user_id', $contact->id)
                ->where('survey_id', $surveyId)
                ->value('id');

            $slug = !empty($contact->first_name) ? Str::slug(Str::ascii($contact->first_name)) : 'default-slug';
            $visitUrl = $url . 'survey/' . $slug . '/' . $actionId;
            DB::table('action')
                ->where('id', $actionId)
                ->update(['user_url' => $visitUrl]);

            if ($channel === 'whatsapp') {
                $whatsappUrl = 'https://wa.me/?text=' . urlencode($visitUrl);
                $sentCount++;
            } else {
                $businessUtil = new \App\Utils\BusinessUtil();
                $restaurantUtil = new \App\Utils\RestaurantUtil();
                $moduleUtil = new \App\Utils\ModuleUtil();
                $sendsms = new \App\Http\Controllers\BusinessController($businessUtil, $restaurantUtil, $moduleUtil);
                $sendsms->sendsurveyAsSms($visitUrl, $contact->mobile, $contact->first_name);
                $sentCount++;
            }
        }

        if ($channel === 'whatsapp') {
            return response()->json(['success' => true, 'message' => __('survey::lang.whatsapp-links-generated') . ': ' . $sentCount]);
        }

        return response()->json(['success' => true, 'message' => __('survey::lang.survey-sent-successfully') . ': ' . $sentCount]);
    }
}
