<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\BusinessController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use Illuminate\Support\Str;



class KnowSurveyController extends Controller
{
    // protected $sendsms;

    // public function __construct()
    // {
    //     BusinessController $sendsms
    // }

    public function send($id)
    {
        $users = DB::table('contacts')->select('email', 'name', 'id')->get();
        return view('survey::knowSurvey.send', compact('users', 'id'));
    }
    public function store(Request $request)
    {
        $businessUtil = new BusinessUtil();
        $restaurantUtil = new RestaurantUtil();
        $moduleUtil = new ModuleUtil();

        $sendsms = new BusinessController($businessUtil, $restaurantUtil, $moduleUtil);
        $currentTime = now();
        // Build base URL dynamically from the current request context with a safe fallback
        $url = rtrim($request->getSchemeAndHttpHost() ?: config('app.url'), '/') . '/';
// $url = 'https://erp.carserv.pro/';
        DB::table('action')->insert([
            'user_id' => $request->user_id,
            'survey_id' => $request->survey_id,
            'timesend' => $currentTime,
            'type_form' => 'Know Person',
        ]);
        $action_id = DB::table('action')->select('id')
            ->where('timesend', $currentTime)
            ->where('user_id', $request->user_id)
            ->where('survey_id', $request->survey_id)
            ->first();
        $user_name = DB::table('contacts')
            ->select('email', 'first_name', 'slug', 'mobile')
            ->where('id', $request->user_id)
            ->first();
        // dd($user_name);
        if (!empty($user_name->slug)) {
            $parts = explode('-', $user_name->slug);
        } else {
            $generatedSlug = Str::slug(Str::ascii($user_name->first_name));
            $parts = !empty($generatedSlug) ? [$generatedSlug] : ['default-slug'];
        }
        $visitUrl = $url . 'survey/' . $parts[0] . '/' . $action_id->id;
        DB::table('action')
            ->where('id', $action_id->id)
            ->update(['user_url' => $visitUrl]);
        $response = $sendsms->sendsurveyAsSms($visitUrl, $user_name->mobile, $user_name->first_name);
        // return $response;
        return view('survey::dataSent.index');

        // echo $visitUrl;
        // dd($request);
    }
    //http://127.0.0.1:8000/survey/know/store/test1@gmail.com/2025-02-02 13:14:14
    public function seen($first_name, $action_id)
    {
        // Validate action_id exists and is numeric
        if (!is_numeric($action_id) || $action_id <= 0) {
            abort(400, 'Invalid survey link');
        }

        $user_id = DB::table('action')->where('id', $action_id)->first();
        if (!$user_id) {
            abort(404, 'Survey not found');
        }

        $user = DB::table('contacts')->where('id', $user_id->user_id)->first();
        if (!$user) {
            abort(404, 'User not found');
        }

        // Get contact device information with brand and model
        $contact_device = DB::table('contact_device')
            ->leftJoin('repair_device_models as rdm', 'contact_device.models_id', '=', 'rdm.id')
            ->leftJoin('categories as brand', 'contact_device.device_id', '=', 'brand.id')
            ->where('contact_device.contact_id', $user_id->user_id)
            ->select('contact_device.*', 'rdm.name as device_name', 'brand.name as brand_name')
            ->first();

        $action = DB::table('action')->where('user_id', $user->id)->where('id', $action_id)->first();
        if (!$action) {
            abort(404, 'Survey action not found');
        }

        $checkoldquestions = DB::table('old_questions')->where('contact_id', $user_id->user_id)->count();
        // dd($checkoldquestions);
        // dd($action->survey_id);
        if ($action->seen == 0) {
            // dd($user_id);

            if ($action->fill == 1)
                return view('survey::index');
            // dd($action->survey_id);

            $newquestions = DB::table('questions')->where('survey_id', $action->survey_id)->get();
            $newsurvey = DB::table('surveys')->where('id', $action->survey_id)->first();

            foreach ($newquestions as $question) {
                DB::table('old_questions')->insert([
                    'survey_id' => $question->survey_id,
                    'text' => $question->text,
                    'description' => $question->description,
                    'type_id' => $question->type_id,
                    'contact_id' => $user_id->user_id,
                    'action_id' => $action->id,
                ]);
            }
            // dd($survey);
            DB::table('old_surveys')->insert([
                'title' => $newsurvey->title,
                'description' => $newsurvey->description,
                'admin_id' => $newsurvey->admin_id,
                'contact_id' => $user_id->user_id,
                'old_survey_id' => $action->survey_id,
                'action_id' => $action->id,
            ]);
        }

        if ($action->fill == 1)
            return view('survey::index');

        $questions = DB::table('old_questions')->where('contact_id', $user_id->user_id)
            ->where('survey_id', $action->survey_id)
            ->where('action_id', $action->id)->get();
        $surveys = DB::table('old_surveys')->where('contact_id', $user_id->user_id)
            ->where('action_id', $action->id)->get();
        $surveySettings = DB::table('survey_settings')->first();
        
        // Get business information for logo
        $business = DB::table('business')->first();


        // $seen = DB::table('action')
        //     ->where('id', $action_id)
        //     ->select('seen')->first();
        // if($seen->seen)
        // $user_id = DB::table('action')->where('id', $action_id)->first();
        // // dd($user_id);
        // $user = DB::table('contacts')->where('id', $user_id->user_id)->first();
        // // dd($user);
        // $action = DB::table('action')->where('user_id', $user->id)->where('id', $action_id)->first();
        // if ($action->fill == 1)
        //     return view('survey::index');
        $actionseen = DB::table('action')
            ->where('id', $action_id)
            ->update(['seen' => 1]);
        return view('survey::showsurveysend', compact('questions', 'user', 'action', 'surveySettings', 'contact_device', 'business'))->with('survey', $surveys);
    }
    public function fill(Request $request)
    {
        // Basic validation to ensure required payload is present
        $request->validate([
            'user_id' => ['required', 'integer'],
            'surveyId' => ['required', 'integer'],
            'action_id' => ['required', 'integer'],
            'old_survey_id' => ['required', 'integer'],
            'answers' => ['required', 'array', 'min:1'],
        ]);

        // Ensure at least one non-empty answer value (covers text, radio, rating and checkbox arrays)
        $has_value = false;
        foreach ($request->answers as $val) {
            if (is_array($val)) {
                if (!empty($val)) { $has_value = true; break; }
            } else {
                if (trim((string)$val) !== '') { $has_value = true; break; }
            }
        }
        if (!$has_value) {
            return back()->withErrors(['answers' => 'Please provide at least one answer.'])->withInput();
        }

        $surveySettings = DB::table('survey_settings')->first();
        $enableIntelligent = (bool) (optional($surveySettings)->enable_intelligent ?? false);
        $threshold = (int) (optional($surveySettings)->rating_threshold ?? 80);
        $totalScore = 0;
        $scoreCount = 0;
        $ans = 0;
        $num = -1;
        foreach ($request->answers as $key => $value) {
            $rawValue = $value;
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $oldQuestion = DB::table('old_questions')->select('type_id', 'id', 'description')->where('id', $key)->first();
            if (!$oldQuestion) {
                continue;
            }

            $score = null;
            if ($oldQuestion->type_id == 2) {
                $options = json_decode($oldQuestion->description, true);
                if (is_array($options)) {
                    foreach ($options as $option) {
                        $label = is_array($option) ? ($option['label'] ?? null) : $option;
                        if ($label !== null && (string) $label === (string) $rawValue) {
                            $score = is_array($option) && isset($option['score']) ? (float) $option['score'] : null;
                            break;
                        }
                    }
                }
            } elseif ($oldQuestion->type_id == 3) {
                $selectedOptions = is_array($rawValue) ? $rawValue : json_decode($rawValue, true);
                $options = json_decode($oldQuestion->description, true);
                $scores = [];
                if (is_array($selectedOptions) && is_array($options)) {
                    foreach ($selectedOptions as $selected) {
                        foreach ($options as $option) {
                            $label = is_array($option) ? ($option['label'] ?? null) : $option;
                            if ($label !== null && (string) $label === (string) $selected) {
                                if (is_array($option) && isset($option['score'])) {
                                    $scores[] = (float) $option['score'];
                                }
                            }
                        }
                    }
                }
                if (count($scores) > 0) {
                    $score = array_sum($scores) / count($scores);
                }
            } elseif ($oldQuestion->type_id == 4) {
                $range = json_decode($oldQuestion->description, true);
                $min = isset($range[0]) ? (float) $range[0] : 0;
                $max = isset($range[1]) ? (float) $range[1] : 0;
                if ($max > $min) {
                    $score = ((float) $rawValue - $min) / ($max - $min) * 100;
                }
            } elseif ($oldQuestion->type_id == 5) {
                if ($rawValue === 'like') {
                    $score = 100;
                } elseif ($rawValue === 'dislike') {
                    $score = 0;
                }
            }

            if ($score !== null) {
                $totalScore += $score;
                $scoreCount++;
            }
            
            if ($oldQuestion->type_id == 4) {
                if ($num == -1) {
                    $num = 0;
                }
                $ans += (int)$value;
                $num++;
            }
            if ($value !== null) {
                DB::table('responses')->insert([
                    'user_id' => $request->user_id,
                    'survey_id' => $request->surveyId,
                    'question_id' => $oldQuestion->id,
                    'answer' => $value,
                    'score' => $score,
                    'old_survey_id' => $request->old_survey_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }


        $actionseen = DB::table('action')
            ->where('user_id', $request->user_id)->where('id', $request->action_id)
            ->update(['fill' => 1]);



        $averageScore = $scoreCount > 0 ? round($totalScore / $scoreCount, 2) : null;
        $showRatingPrompt = $enableIntelligent && $averageScore !== null && $averageScore >= $threshold;

        return view('survey::thanks', [
            'showRatingPrompt' => $showRatingPrompt,
            'averageScore' => $averageScore,
            'surveySettings' => $surveySettings,
            'threshold' => $threshold,
        ]);
    }
}
