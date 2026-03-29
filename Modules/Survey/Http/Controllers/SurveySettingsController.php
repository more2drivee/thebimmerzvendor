<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SurveySettingsController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('survey.update')) {
            abort(403, 'Unauthorized action.');
        }

        $surveySettings = DB::table('survey_settings')->first();

        return view('survey::settings.index', compact('surveySettings'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('survey.update')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'active_theme' => ['required', Rule::in(['light', 'dark'])],
            'enable_intelligent' => ['nullable', Rule::in([0, 1, true, false])],
            'rating_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'facebook_url' => ['nullable', 'string'],
            'instagram_url' => ['nullable', 'string'],
            'google_review_url' => ['nullable', 'string'],
        ]);

        $payload = [
            'active_theme' => $data['active_theme'],
            'enable_intelligent' => (bool) ($data['enable_intelligent'] ?? false),
            'rating_threshold' => $data['rating_threshold'],
            'facebook_url' => $data['facebook_url'] ?? null,
            'instagram_url' => $data['instagram_url'] ?? null,
            'google_review_url' => $data['google_review_url'] ?? null,
            'updated_at' => now(),
        ];

        $existing = DB::table('survey_settings')->first();
        if ($existing) {
            DB::table('survey_settings')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('survey_settings')->insert($payload);
        }

        $output = ['success' => true, 'msg' => __('lang_v1.updated_success')];

        return redirect()->back()->with(['status' => $output]);
    }
}
