<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\BusinessController;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupSurveyController extends Controller
{

    public function send($id)
    {
        $groups = DB::table('groups')->get();
        // dd($groups);
        return view('survey::groupsurvey.send', compact('groups', 'id'));
    }
    public function store(Request $request)
    {

        // dd($request);
        $businessUtil = new BusinessUtil();
        $restaurantUtil = new RestaurantUtil();
        $moduleUtil = new ModuleUtil();

        $sendsms = new BusinessController($businessUtil, $restaurantUtil, $moduleUtil);

        $users = DB::table('user_group')->select('user_id')->where('group_id', $request->group_id)->get();
        $groups = DB::table('groups')->select('name')->where('id', $request->group_id)->first();
        $urls = [];
        // Build base URL dynamically from the current request context with a safe fallback
        $baseUrl = rtrim($request->getSchemeAndHttpHost() ?: config('app.url'), '/') . '/';
        foreach ($users as $user) {
            $currentTime = now();

            DB::table('action')->insert([
                'user_id' => $user->user_id,
                'survey_id' => $request->survey_id,
                'timesend' => $currentTime,
                'type_form' => $groups->name,
            ]);
            $action_id = DB::table('action')->select('id')
                ->where('timesend', $currentTime)
                ->where('user_id', $request->user_id)
                ->where('survey_id', $request->survey_id)
                ->first();
            $user_name = DB::table('contacts')
                ->select('email', 'first_name', 'slug')
                ->where('id', $user->user_id)
                ->first();
            if (!empty($user_name->slug)) {
                $parts = explode('-', $user_name->slug);
            } else {
                $generatedSlug = Str::slug(Str::ascii($user_name->first_name));
                $parts = !empty($generatedSlug) ? [$generatedSlug] : ['default-slug'];
            }
            $visitUrl = $baseUrl . 'survey/' . $parts[0] . '/' . $action_id->id;
            // $visitUrl = $currentUrl . $user_name->email . '/' . $currentTime;
            // $urls[] = $visitUrl;
            $response = $sendsms->sendsurveyAsSms($visitUrl, $user_name->mobile, $user_name->first_name);

            DB::table('action')
                ->where('id', $action_id->id)
                ->update(['user_url' => $visitUrl]);
        }
        // print_r($urls);
        return view('survey::dataSent.index');
    }
}
