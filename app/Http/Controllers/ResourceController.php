<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResourceController extends Controller
{
    //

    function show (Resource $resource){
        return Inertia::render('Resource', [
            'resource'=>$resource
        ]);
    }
}
