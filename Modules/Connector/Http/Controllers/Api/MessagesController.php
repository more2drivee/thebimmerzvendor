<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Colors\Rgb\Channels\Red;

class MessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('connector::messages.index');
    }

    public function qrcode()
    {
        echo 'kked';
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('connector::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $id_message = DB::table('messages')->insertGetId([
            'user_id' => $user_id,
            'message' => $request->message,
            'type' => 'yes',
        ]);
        $stringIDmessage = (string)$id_message;
        // dd($stringIDmessage);
        $ids = DB::table('contacts')->select('id')->get();
        foreach ($ids as $id) {
            // dd($id->id);
            DB::table('messages')->insert([
                'user_id' => $id->id,
                'type' => $stringIDmessage
            ]);
        }
        return redirect('/');

        // dd($request);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show()
    {
        $user = Auth::user();
        $auth_user_id = $user->id;
        $allmessages = DB::table('messages')->where('type', 'yes')->where('status', 0)->get();
        $data = [];
        $responses = [];
        foreach ($allmessages as $message) {
            $seenOrnot = DB::table('messages')
                ->where('user_id', $auth_user_id)
                ->where('type', $message->id)
                ->select('seen', 'message', 'id')->first();
            $dataseen = NULL;
            // return response()->json(["data" => $seenOrnot]);
            if ($seenOrnot == NULL) {
                $dataseen = 0;
            } else {
                $dataseen = $seenOrnot->seen;
            }

            $user = DB::table('messages')->where('id', $seenOrnot->id)->select('user_id')->first();
            $user_name = DB::table('contacts')->where('id', $user->user_id)->select('name')->first();
            $responses[] = [
                "name" => $user_name->name,
                "response" => $seenOrnot->message,
                "user_id" => $auth_user_id,
                // "status" => $message_data->seen
            ];
            // dd($seenOrnot);


            // $response = json_decode($message->responses, true);

            // if (is_array($response)) {
            //     foreach ($response as $i) {
            //         // return response()->json(["data" => $response]);
            //         $user = DB::table('messages')->where('id', $i)->select('user_id')->first();
            //         $user_name = DB::table('contacts')->where('id', $user->user_id)->select('name')->first();
            //         $message_data = DB::table('messages')->where('id', $i)->select('message AS response', 'user_id', 'seen')->first();
            //         $responses[] = [
            //             "name" => $user_name->name,
            //             "response" => $message_data->response,
            //             "user_id" => $message_data->user_id,
            //             // "status" => $message_data->seen
            //         ];
            //     }
            // }
            // $datanotseen = DB::table('messages')
            //     ->where('seen', 0)->where('type', $message->id)
            //     ->where('message', NULL)
            //     ->select('user_id', 'message', 'seen', 'id')->get();

            // foreach ($datanotseen as $data) {
            //     $user = DB::table('messages')->where('id', $data->id)->select('user_id')->first();
            //     $user_name = DB::table('contacts')->where('id', $user->user_id)->select('name')->first();
            //     $responses[] = [
            //         "name" => $user_name->name,
            //         "response" => $data->message,
            //         "user_id" => $data->user_id,
            //         // "status" => $data->seen
            //     ];
            // }
            // $responses[] = DB::table('messages')
            //     ->where('seen', 0)->where('type', $message->id)
            //     ->select('user_id', 'message', 'seen')->get();

            $admin = DB::table('messages')->where('id', $message->id)->select('user_id')->first();
            $admin_name = DB::table('users')->where('id', $admin->user_id)->select('username')->first();
            $resdata[] = [
                'id' => $message->id,
                'message' => $message->message,
                'status' => $dataseen,
                'name' => $admin_name->username,
                'responses' => $responses
            ];

            $responses = [];
        }

        return response()->json(["data" => $resdata]);
    }

    public function storeResponse(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;
        // return response()->json(["data" => $user->id]);
        $res = DB::table('messages')->where('id', $request->id)->select('responses')->first();
        $response = json_decode($res->responses, true);
        DB::table('messages')->where('user_id', $user_id)->where('type', $request->id)->update(['message' => $request->message, 'seen' => 1]);
        $id = DB::table('messages')->where('user_id', $user_id)->where('type', $request->id)
            ->select('id')->first();
        $response[] = $id->id;
        $resstring = json_encode($response);

        DB::table('messages')->where('id', $request->id)->update([
            'responses' => $resstring
        ]);
        return response()->json(["data" => "success"]);
    }


    public function showMessage($id)
    {
        $message = DB::table('messages')->where('id', $id)->first();
        $usercreatemessage = DB::table('users')->where('id', $message->user_id)->first();
        $usercreatename = $usercreatemessage->first_name . ' ' . $usercreatemessage->last_name;
        $commentes = [];
        $allusercommented = json_decode($message->responses, true);
        if (!empty($allusercommented)) {
            foreach ($allusercommented as $commentid) {
                $commentofuser = DB::table('messages')->where('id', $commentid)->first();
                $usercomment = DB::table('contacts')->where('id', $commentofuser->user_id)->select('name')->first();
                $commentes[] = [
                    'userName' => $usercomment->name,
                    'comment' => $commentofuser->message
                ];
            }
        }
        // dd($usercreatename);

        return view('layouts.partials.show_message', compact('commentes', 'usercreatename', 'message'));
    }

    public function changestatus($id)
    {
        $status = DB::table('messages')->where('id', $id)->value('status');

        DB::table('messages')->where('id', $id)->update([
            'status' => !$status
        ]);
        return redirect()->route('show.message', $id);
    }
    public function messagechangestatus(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;
        DB::table('messages')->where('user_id', $user_id)->where('type', $request->id)->update([
            'seen' => 1
        ]);
        return response()->json(["data" => "success"]);
    }
    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('connector::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
