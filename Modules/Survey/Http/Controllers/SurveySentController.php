<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SurveySentController extends Controller
{
    public function index(Request $request)
    {
        $action = DB::table('action')->get();

        return view('survey::dataSent.index', compact('action'));
    }
    public function getSurveyDataSent()
    {
        $data = DB::table('action')
            ->join('contacts', 'contacts.id', '=', 'action.user_id')
            ->join('surveys', 'surveys.id', '=', 'action.survey_id')
            ->select(
                'surveys.id',
                'surveys.title',
                'contacts.name',
                'seen',
                'fill',
                'type_form',
                'user_id',
                'survey_id',
                'user_url',
                'contacts.first_name',
                'slug',
                'action.id AS action_id'
            )
            ->distinct()
            ->get();


        // dd($data);
        return DataTables::of($data)->addColumn(
            'action',
            function ($survey) {
                $html = '<div class="btn-group">
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                ' . __('messages.actions') . '
                <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                $html .= '<li><a href="javascript:void(0);" class="copy-url" data-url="' . $survey->user_url . '" onclick="copyToClipboard(this)"><i class="fa fa-copy"></i> ' . __('survey::lang.copyurl') . '</a></li>';

                $html .= '<li><a href="' . url('survey/sent') . '/' . $survey->first_name . '/' . $survey->id . '/' . $survey->action_id . '" class="view-product"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                $html .= '</ul></div>';

                return $html;
            }
        )
            ->rawColumns(['action'])
            ->make(true);
    }
    public function showAnswer($first_name, $survey_id, $action_id)
    {
        $action = DB::table('action')->where('id', $action_id)->first();
        if (!$action) {
            abort(404);
        }

        $user = DB::table('contacts')->select('name', 'id')->where('id', $action->user_id)->first();
        if (!$user) {
            abort(404);
        }

        $oldSurvey = DB::table('old_surveys')
            ->where('contact_id', $user->id)
            ->where('action_id', $action_id)
            ->first();

        if (!$oldSurvey) {
            abort(404, 'Survey not found');
        }

        $survey = DB::table('surveys')->where('id', $oldSurvey->old_survey_id)->first();
        if (!$survey) {
            abort(404, 'Survey not found');
        }

        $questions = DB::table('old_questions')
            ->where('survey_id', $oldSurvey->old_survey_id)
            ->where('contact_id', $user->id)
            ->where('action_id', $action_id)
            ->get();

        $responses = DB::table('responses')
            ->where('user_id', $user->id)
            ->where('old_survey_id', $oldSurvey->id)
            ->pluck('answer', 'question_id');

        $surveySettings = DB::table('survey_settings')->first();
        $business = DB::table('business')->first();

        return view('survey::dataSent.show', compact('survey', 'questions', 'user', 'responses', 'surveySettings', 'business'));
    }
}
