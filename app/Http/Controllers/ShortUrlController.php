<?php

namespace App\Http\Controllers;

use App\ShortUrl;
use Illuminate\Http\Request;

class ShortUrlController extends Controller
{
    /**
     * Redirect a short code to its long URL.
     */
    public function redirect($code)
    {
        $short = ShortUrl::where('code', $code)->first();

        if (!$short || $short->isExpired()) {
            abort(404);
        }

        $short->increment('clicks');

        return redirect()->away($short->long_url);
    }
}
