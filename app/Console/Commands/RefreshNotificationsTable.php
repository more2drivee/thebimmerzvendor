<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RefreshNotificationsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the notifications table every 1 hours';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = Auth::user();
        $date = Carbon::now();
        // $business_id = $user->business_id;
        $job_sheet = DB::table('repair_job_sheets')
            // ->where('repair_job_sheets.business_id', $business_id)
            ->where('repair_job_sheets.due_date', '<', $date)
            ->leftJoin('transactions', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
            ->where(function ($query) {
                $query->whereNull('transactions.status')
                    ->orWhere(function ($q) {
                        $q->where('transactions.status', '!=', 'final')
                            ->where('transactions.payment_status', '!=', 'paid')
                            ->orWhereNull('transactions.payment_status');
                    });
            })
            ->select('repair_job_sheets.id', 'repair_job_sheets.job_sheet_no', 'repair_job_sheets.location_id')->get();
        //dd($job_sheet);
        $job_ids = [];
        $user_ids = [];
        foreach ($job_sheet as $job) {
            $job_ids[] = $job->id;
            $users = DB::table('users')->where('location_id', $job->location_id)->select('id')->get();
            foreach ($users as $user) {
                $user_ids[] = [
                    'user_id' => $user->id,
                    'job_sheet_id' => $job->id,
                ];
                $flag = DB::table('notifications')->where('user_id', $user->id)->where('job_sheet_id', $job->id)->exists();
                if (!$flag) {
                    DB::table('notifications')->insert([
                        'id' => $job->location_id . $user->id . $job->job_sheet_no,
                        'user_id' => $user->id,
                        'job_sheet_id' => $job->id,
                        'job_sheet_no' => $job->job_sheet_no,
                        'status' => 0
                    ]);
                }
            }
        }
        $job_sheet_ids_list = collect($user_ids)->pluck('job_sheet_id')->toArray();
        $user_ids_list = collect($user_ids)->pluck('user_id')->toArray();
        $conditions = collect($job_sheet_ids_list)->map(function ($jobSheetId, $index) use ($user_ids_list) {
            return "(`job_sheet_id` = $jobSheetId AND `user_id` = {$user_ids_list[$index]})";
        })->implode(' OR ');

        DB::table('notifications')
            ->whereRaw("NOT ($conditions)")
            ->delete();
        // DB::table('notifications')->truncate();
        $this->info('Notifications table refreshed successfully.');
    }
}
