<?php

namespace App\Utils;

use App\ShortUrl;
use Illuminate\Support\Str;

class UrlShortener
{
    /**
     * Shorten a long URL and return the full short URL.
     */
    public function shorten(string $longUrl): string
    {
        $normalized = trim($longUrl);

        if ($normalized === '') {
            return $normalized;
        }

        // Reuse existing short code for the same URL if it exists
        $existing = ShortUrl::where('long_url', $normalized)->first();
        if ($existing) {
            return url('/s/' . $existing->code);
        }

        // Generate a unique short code
        do {
            $code = Str::random(7);
        } while (ShortUrl::where('code', $code)->exists());

        ShortUrl::create([
            'code' => $code,
            'long_url' => $normalized,
        ]);

        return url('/s/' . $code);
    }

    /**
     * Resolve a short code to the ShortUrl model.
     */
    public function resolve(string $code): ?ShortUrl
    {
        return ShortUrl::where('code', $code)->first();
    }
}
