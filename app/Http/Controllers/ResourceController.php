<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ResourceController extends Controller
{
    //

    function show($id)
    {
        $resource = Cache::rememberForever("resource_{$id}", function () use ($id) {
            return Resource::with('user')->findOrFail($id)->toArray();
        });
        return Inertia::render('Resource', [
            'resource' => $resource
        ]);
    }
}
