<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function test(Request $request)
    {
        $multiplier = 3;
        $factor = 5;

        $resultArray = [
            'a'=> (function(){
                return 'a';
            })(),
            'b'=>'something',
            'c'=>(function($factor,$multiplier){
                return $factor * $multiplier;
            })($factor,$multiplier),
        ];

        return response()->json($resultArray['']);
    }
}
