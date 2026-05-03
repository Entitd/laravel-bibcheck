<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ParseBibRequest;

class ApiBibValidationController extends Controller
{
    public function __construct(

    )
    {}
    public function checkGost(ParseBibRequest $request)
    {
        $content = $request->Content();
    }
}
