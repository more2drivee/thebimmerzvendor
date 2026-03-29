<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Business;

class AppController extends Controller
{
    
    public function getAboutSettings()
    {
        $business_id = request()->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);

       
        if (is_string($business->common_settings)) {
            $settings = json_decode($business->common_settings, true) ?? [];
        } elseif (is_array($business->common_settings)) {
            $settings = $business->common_settings;
        } else {
            $settings = [];
        }

        $about_us = $settings['about_us'] ?? '';

        return view('app.settings', compact('about_us'));
    }

    
    public function updateAboutSettings(Request $request)
    {
        $request->validate([
            'about_us' => 'nullable|string'
        ]);

        $business_id = request()->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);

       
        if (is_string($business->common_settings)) {
            $current_settings = json_decode($business->common_settings, true) ?? [];
        } elseif (is_array($business->common_settings)) {
            $current_settings = $business->common_settings;
        } else {
            $current_settings = [];
        }

  
        $current_settings['about_us'] = $request->about_us;

        $business->common_settings = json_encode($current_settings);
        $business->save();

        return redirect()->back()->with('success', 'About Us updated successfully');
    }
}