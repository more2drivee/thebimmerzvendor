<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use ConsoleTVs\Charts\Facades\Charts;

class DashboardController extends Controller
{
    public function index()
    {
        $surveysCount = DB::table('surveys')->count();
        $surveysCountSent = DB::table('action')->count();
        $total_customers = DB::table('contacts')->count();

        $countActionSeen = DB::table('action')->select('seen')->where('seen', 1)->count();
        $countActionFill = DB::table('action')->select('fill')->where('fill', 1)->count();

        $responsesRate = DB::table('responses')
            ->join('questions', function ($join) {
                $join->on('questions.survey_id', '=', 'responses.survey_id')
                    ->on('questions.id', '=', 'responses.question_id')
                    ->where('questions.type_id', '=', '4');
            })
            ->select('responses.answer')
            ->get();

        $sum = 0;
        $count = $responsesRate->count();

        for ($i = 0; $i < $count; $i++) {
            $sum += (float) $responsesRate[$i]->answer;
        }

        $averageRate = $count > 0 ? (float)($sum / $count) : 0.0;
        $responseRate = $surveysCountSent > 0 ? round(($countActionFill / $surveysCountSent) * 100, 2) : 0;

        $counters = [
            __('survey::lang.count-of-surveys') => [
                'data' => $surveysCount,
                'icon' => 'fa-solid fa-clipboard-list'
            ],
            __('survey::lang.count-of-surveys-sent-to-users') => [
                'data' => $surveysCountSent,
                'icon' => 'fa-solid fa-paper-plane'
            ],
            __('survey::lang.response_rate') => [
                'data' => $responseRate . '%',
                'icon' => 'fa-solid fa-chart-line'
            ],
            __('survey::lang.average-of-rate') => [
                'data' => number_format($averageRate, 1),
                'icon' => 'fa-solid fa-star'
            ]
        ];

        $status_chart = [
            'labels' => [
                __('survey::lang.open'),
                __('survey::lang.filled')
            ],
            'data' => [$countActionSeen, $countActionFill],
            'colors' => ['#f59e0b', '#10b981']
        ];

        $recentSurveys = DB::table('surveys')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('survey::dashboard.index', compact(
            'counters',
            'status_chart',
            'recentSurveys',
            'averageRate',
            'responseRate'
        ));
    }
}
