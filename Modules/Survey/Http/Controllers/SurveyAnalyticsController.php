<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SurveyAnalyticsController extends Controller
{
    public function index()
    {
        $surveys = DB::table('surveys')->select(['id', 'title'])->get();
        return view('survey::analytics.index', compact('surveys'));
    }

    public function getSurveyAnalytics($surveyId = null)
    {
        $query = DB::table('surveys')
            ->leftJoin('action', 'action.survey_id', '=', 'surveys.id')
            ->leftJoin('responses', function ($join) {
                $join->on('responses.survey_id', '=', 'surveys.id')
                    ->on('responses.user_id', '=', 'action.user_id');
            });

        if ($surveyId) {
            $query->where('surveys.id', $surveyId);
        }

        $data = $query->select([
                'surveys.id',
                'surveys.title',
                DB::raw('COUNT(DISTINCT action.id) as total_sent'),
                DB::raw('COUNT(DISTINCT CASE WHEN action.seen = 1 THEN action.id END) as total_seen'),
                DB::raw('COUNT(DISTINCT CASE WHEN action.fill = 1 THEN action.id END) as total_filled'),
                DB::raw('COUNT(DISTINCT responses.id) as total_responses'),
                DB::raw('ROUND(COUNT(DISTINCT CASE WHEN action.fill = 1 THEN action.id END) * 100.0 / NULLIF(COUNT(DISTINCT action.id), 0), 2) as response_rate'),
            ])
            ->groupBy('surveys.id', 'surveys.title')
            ->get();

        return DataTables::of($data)
            ->addColumn('action', function ($survey) {
                $html = '<div class="btn-group">
                    <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown">
                        ' . __('messages.actions') . '
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left">';

                $html .= '<li><a href="' . url('survey/analytics/' . $survey->id . '/details') . '" class="view-product"><i class="fa fa-chart-bar"></i> ' . __('survey::lang.overview') . '</a></li>';
                $html .= '<li><a href="' . url('survey/analytics/' . $survey->id . '/export') . '" class="export-product"><i class="fa fa-download"></i> ' . __('survey::lang.export_responses') . '</a></li>';

                $html .= '</ul></div>';
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getSurveyDetails($surveyId)
    {
        $survey = DB::table('surveys')->where('id', $surveyId)->first();
        $questionFilter = request('question_filter', 'both');

        // Get questions based on filter
        $questions = DB::table('questions')->where('survey_id', $surveyId);
        if ($questionFilter == 'old') {
            $questions = DB::table('old_questions')
                ->where('survey_id', $surveyId)
                ->select('id', 'text', 'description', 'type_id')
                ->distinct();
        } elseif ($questionFilter == 'current') {
            $questions = $questions->select('id', 'text', 'description', 'type_id');
        } else {
            // Both - get from questions table
            $questions = $questions->select('id', 'text', 'description', 'type_id');
        }
        $questions = $questions->get();

        // Get customers who responded to this survey
        $customers = DB::table('responses')
            ->join('contacts', 'contacts.id', '=', 'responses.user_id')
            ->where('responses.survey_id', $surveyId)
            ->select('contacts.id', 'contacts.name', 'contacts.mobile')
            ->distinct()
            ->orderBy('contacts.name')
            ->get();

        // Get customer filter parameter
        $customerId = request('customer_id');

        $analytics = [];
        foreach ($questions as $question) {
            $query = DB::table('responses')
                ->join('old_questions', 'old_questions.id', '=', 'responses.question_id')
                ->leftJoin('contacts', 'contacts.id', '=', 'responses.user_id')
                ->where('old_questions.survey_id', $surveyId)
                ->where('old_questions.text', $question->text)
                ->select(
                    'responses.answer',
                    'contacts.name as contact_name',
                    'contacts.mobile as contact_mobile',
                    'contacts.id as contact_id'
                );

            // Apply customer filter if selected
            if ($customerId) {
                $query->where('contacts.id', $customerId);
            }

            // Get all responses for analytics (not paginated for calculations)
            $allResponses = $query->get();
            
            // Get paginated responses for table display
            $paginatedResponses = $query->paginate(10, ['*'], 'page_' . $question->id);

            $analytics[$question->id] = [
                'question' => $question->text,
                'type_id' => $question->type_id,
                'total_responses' => $allResponses->count(),
                'responses' => $paginatedResponses,
                'all_responses' => $allResponses,
            ];

            if ($question->type_id == 1) {
                $textAnswers = [];
                foreach ($allResponses as $response) {
                    $textAnswers[] = $response->answer;
                }
                $analytics[$question->id]['text_answers'] = $textAnswers;
            } elseif ($question->type_id == 2) {
                $options = json_decode($question->description, true);
                $optionLabels = [];
                $optionScores = [];
                if (is_array($options)) {
                    foreach ($options as $option) {
                        $label = is_array($option) ? ($option['label'] ?? null) : $option;
                        $score = is_array($option) && isset($option['score']) ? (int)$option['score'] : null;
                        if ($label !== null) {
                            $optionLabels[] = $label;
                            $optionScores[$label] = $score;
                        }
                    }
                }
                $optionCounts = !empty($optionLabels) ? array_fill_keys($optionLabels, 0) : [];
                foreach ($allResponses as $response) {
                    if (isset($optionCounts[$response->answer])) {
                        $optionCounts[$response->answer]++;
                    }
                }
                $analytics[$question->id]['option_counts'] = $optionCounts;
                $analytics[$question->id]['option_scores'] = $optionScores;
                
                // Calculate weighted average rating
                $totalScore = 0;
                $totalResponses = 0;
                foreach ($optionCounts as $label => $count) {
                    if ($count > 0 && isset($optionScores[$label]) && $optionScores[$label] !== null) {
                        $totalScore += $optionScores[$label] * $count;
                        $totalResponses += $count;
                    }
                }
                if ($totalResponses > 0) {
                    $analytics[$question->id]['average_rating'] = round($totalScore / $totalResponses, 2);
                }
            } elseif ($question->type_id == 3) {
                $options = json_decode($question->description, true);
                $optionLabels = [];
                $optionScores = [];
                if (is_array($options)) {
                    foreach ($options as $option) {
                        $label = is_array($option) ? ($option['label'] ?? null) : $option;
                        $score = is_array($option) && isset($option['score']) ? (int)$option['score'] : null;
                        if ($label !== null) {
                            $optionLabels[] = $label;
                            $optionScores[$label] = $score;
                        }
                    }
                }
                $optionCounts = !empty($optionLabels) ? array_fill_keys($optionLabels, 0) : [];
                foreach ($allResponses as $response) {
                    $selectedOptions = json_decode($response->answer, true);
                    if (is_array($selectedOptions)) {
                        foreach ($selectedOptions as $option) {
                            if (isset($optionCounts[$option])) {
                                $optionCounts[$option]++;
                            }
                        }
                    }
                }
                $analytics[$question->id]['option_counts'] = $optionCounts;
                $analytics[$question->id]['option_scores'] = $optionScores;
                
                // Calculate weighted average rating
                $totalScore = 0;
                $totalResponses = 0;
                foreach ($optionCounts as $label => $count) {
                    if ($count > 0 && isset($optionScores[$label]) && $optionScores[$label] !== null) {
                        $totalScore += $optionScores[$label] * $count;
                        $totalResponses += $count;
                    }
                }
                if ($totalResponses > 0) {
                    $analytics[$question->id]['average_rating'] = round($totalScore / $totalResponses, 2);
                }
            } elseif ($question->type_id == 4) {
                $ratings = [];
                foreach ($allResponses as $response) {
                    $ratings[] = (int)$response->answer;
                }
                if (!empty($ratings)) {
                    $analytics[$question->id]['average_rating'] = round(array_sum($ratings) / count($ratings), 2);
                    $analytics[$question->id]['rating_distribution'] = array_count_values($ratings);
                }
            } elseif ($question->type_id == 5) {
                $likeCounts = ['like' => 0, 'dislike' => 0];
                foreach ($allResponses as $response) {
                    if (isset($likeCounts[$response->answer])) {
                        $likeCounts[$response->answer]++;
                    }
                }
                $analytics[$question->id]['like_counts'] = $likeCounts;
                $totalLikeResponses = $likeCounts['like'] + $likeCounts['dislike'];
                if ($totalLikeResponses > 0) {
                    $analytics[$question->id]['like_percentage'] = round(($likeCounts['like'] / $totalLikeResponses) * 100, 2);
                } else {
                    $analytics[$question->id]['like_percentage'] = 0;
                }
            }
        }

        $sentData = DB::table('action')
            ->where('survey_id', $surveyId)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN seen = 1 THEN 1 END) as seen'),
                DB::raw('COUNT(CASE WHEN fill = 1 THEN 1 END) as filled'),
            ])
            ->first();

        // Calculate overall rating from all questions with scores (radio, checkbox, star rating)
        $overallRating = null;
        $ratedQuestions = [];
        foreach ($analytics as $questionId => $data) {
            if (isset($data['average_rating'])) {
                $rating = $data['average_rating'];
                // Normalize to 0-5 scale: if rating is > 5, assume it's on 0-100 scale
                if ($rating > 5) {
                    $rating = ($rating / 100) * 5;
                }
                $ratedQuestions[] = $rating;
            }
        }
        if (!empty($ratedQuestions)) {
            $overallRating = round(array_sum($ratedQuestions) / count($ratedQuestions), 2);
        }

        return view('survey::analytics.details', compact('survey', 'questions', 'analytics', 'sentData', 'customers', 'overallRating'));
    }

    public function exportResponses($surveyId)
    {
        $survey = DB::table('surveys')->where('id', $surveyId)->first();
        $questions = DB::table('questions')->where('survey_id', $surveyId)->get();

        $responses = DB::table('responses')
            ->join('contacts', 'contacts.id', '=', 'responses.user_id')
            ->join('old_questions', 'old_questions.id', '=', 'responses.question_id')
            ->where('old_questions.survey_id', $surveyId)
            ->select(
                'contacts.name as contact_name',
                'contacts.email as contact_email',
                'contacts.mobile as contact_mobile',
                'old_questions.text as question_text',
                'responses.answer',
                'responses.created_at as response_date'
            )
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="survey_' . $surveyId . '_responses.csv"',
        ];

        $callback = function () use ($responses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Contact Name', 'Email', 'Mobile', 'Question', 'Answer', 'Response Date']);

            foreach ($responses as $response) {
                fputcsv($file, [
                    $response->contact_name,
                    $response->contact_email,
                    $response->contact_mobile,
                    $response->question_text,
                    $response->answer,
                    $response->response_date,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getResponseTrends($surveyId, $days = 30)
    {
        $startDate = now()->subDays($days);

        $trends = DB::table('action')
            ->where('survey_id', $surveyId)
            ->where('timesend', '>=', $startDate)
            ->select([
                DB::raw('DATE(timesend) as date'),
                DB::raw('COUNT(*) as sent'),
                DB::raw('COUNT(CASE WHEN seen = 1 THEN 1 END) as seen'),
                DB::raw('COUNT(CASE WHEN fill = 1 THEN 1 END) as filled'),
            ])
            ->groupBy(DB::raw('DATE(timesend)'))
            ->orderBy('date')
            ->get();

        return response()->json($trends);
    }

    public function searchCustomers(Request $request)
    {
        if ($request->ajax()) {
            $term = $request->input('q', '');
            $surveyId = $request->input('survey_id');
            $page = $request->input('page', 1);
            $perPage = 10;

            $query = DB::table('responses')
                ->join('contacts', 'contacts.id', '=', 'responses.user_id')
                ->where('responses.survey_id', $surveyId)
                ->select('contacts.id', 'contacts.name', 'contacts.mobile')
                ->distinct();

            if ($term) {
                $query->where(function($q) use ($term) {
                    $q->where('contacts.name', 'like', '%' . $term . '%')
                      ->orWhere('contacts.mobile', 'like', '%' . $term . '%');
                });
            }

            $total = $query->count();
            $customers = $query->orderBy('contacts.name')
                ->skip(($page - 1) * $perPage)
                ->take($perPage + 1)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->name . ' (' . $item->mobile . ')'
                    ];
                });

            $more = $customers->count() > $perPage;
            if ($more) {
                $customers->pop();
            }

            return response()->json([
                'results' => $customers,
                'pagination' => [
                    'more' => $more
                ]
            ]);
        }

        abort(404);
    }
}
