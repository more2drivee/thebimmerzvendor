<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;


class GeneralGroupController extends Controller
{
    public function index($id, $title)
    {
        $questions = DB::table('questions')->where('survey_id', $id)->get();
        $survey = DB::table('surveys')->where('id', $id)->first();
        $surveySettings = DB::table('survey_settings')->first();

        return view('survey::generalgroup.index', compact('questions', 'survey', 'surveySettings'));
    }

    public function store(Request $request)
    {
        // Validate basic participant info and answers
        $request->validate([
            'surveyId' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:50'],
            'answers' => ['required', 'array', 'min:1'],
        ]);

        // Ensure at least one non-empty answer value is present
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
        DB::table('general_group')->insert([
            'survey_id' => $request->surveyId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $number_of_fill = DB::table('general_group')->select('number_of_fill')->where('email', $request->email)->first();

        foreach ($request->answers as $key => $value) {
            $rawValue = $value;
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $question = DB::table('questions')->select('type_id', 'description')->where('id', $key)->first();
            if (!$question) {
                continue;
            }
            $score = null;
            if ($question->type_id == 2) {
                $options = json_decode($question->description, true);
                if (is_array($options)) {
                    foreach ($options as $option) {
                        $label = is_array($option) ? ($option['label'] ?? null) : $option;
                        if ($label !== null && (string) $label === (string) $rawValue) {
                            $score = is_array($option) && isset($option['score']) ? (float) $option['score'] : null;
                            break;
                        }
                    }
                }
            } elseif ($question->type_id == 3) {
                $selectedOptions = is_array($rawValue) ? $rawValue : json_decode($rawValue, true);
                $options = json_decode($question->description, true);
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
            } elseif ($question->type_id == 4) {
                $range = json_decode($question->description, true);
                $min = isset($range[0]) ? (float) $range[0] : 0;
                $max = isset($range[1]) ? (float) $range[1] : 0;
                if ($max > $min) {
                    $score = ((float) $rawValue - $min) / ($max - $min) * 100;
                }
            } elseif ($question->type_id == 5) {
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
            DB::table('response_general_group')->insert([
                'number_of_fill' => $number_of_fill->number_of_fill,
                'survey_id' => $request->surveyId,
                'question_id' => $key,
                'answer' => $value,
                'score' => $score,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $averageScore = $scoreCount > 0 ? round($totalScore / $scoreCount, 2) : null;
        $showRatingPrompt = $enableIntelligent && $averageScore !== null && $averageScore >= $threshold;

        return view('survey::thanks', [
            'showRatingPrompt' => $showRatingPrompt,
            'averageScore' => $averageScore,
            'surveySettings' => $surveySettings,
            'threshold' => $threshold,
        ]);
    }

    public function show()
    {
        $generalGroups = DB::table('general_group')->get();
        return view('survey::generalgroup.show', compact('generalGroups'));
    }

    public function getData()
    {
        $generalGroups = DB::table('general_group')
            ->join('surveys', 'surveys.id', '=', 'general_group.survey_id')
            ->select(
                'number_of_fill',
                'name',
                'phone',
                'email',
                'survey_id',
                'surveys.title',
            )
            ->get();
        // dd($groups);
        return DataTables::of($generalGroups)
            ->addColumn(
                'action',
                function ($group) {
                    $html = '<div class="btn-group">
                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    ' . __('messages.actions') . '
                    <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // View Group
                    if (auth()->user()->can('survey.view')) {
                        $html .= '<li><a href="' . route('show.answer', $group->number_of_fill) . '" class="view-product"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                }
            )
            ->rawColumns(['action'])
            ->make(true);
    }

    public function showAnswer($id)
    {
        $data = DB::table('general_group')->where('number_of_fill', $id)->first(); 
        $survey = DB::table('surveys')->where('id', $data->survey_id)->first(); 
        $questions = DB::table('questions')->where('survey_id', $data->survey_id)->get(); 
        return view('survey::generalgroup.answer', compact('data', 'questions','survey'));
    }
}
