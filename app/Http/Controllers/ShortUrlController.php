<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShortUrlController extends Controller
{
    /**
     * Generate or return an existing short link via Short.io.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'original_url' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $appUrl = rtrim(config('app.url'), '/');

        if (! str_starts_with($validated['original_url'], $appUrl)) {
            return response()->json([
                'message' => 'Only URLs from this website are allowed.',
            ], 422);
        }

        $response = Http::withHeaders([
            'Authorization' => (string) config('services.short_io.api_key'),
            'Content-Type' => 'application/json',
            'accept' => 'application/json',
        ])->post('https://api.short.io/links', [
            'originalURL' => $validated['original_url'],
            'domain' => config('services.short_io.domain'),
            'allowDuplicates' => false,
        ]);

        if (! $response->successful()) {
            return response()->json([
                'message' => $response->json('message') ?? 'Failed to generate short link.',
            ], 500);
        }

        $shortUrl = $response->json('secureShortURL') ?? $response->json('shortURL');

        return response()->json([
            'short_url' => $shortUrl,
        ]);
    }
}
