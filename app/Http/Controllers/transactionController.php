<?php

namespace App\Http\Controllers;

use App\Models\transactionHeader;
use Illuminate\Http\Request;
use DB;
use Illuminate\Validation\Rules\Exists;

class transactionController extends Controller
{
    //

    public function sendToServer(Request $request){
        $authkey = env('Auth_key');
        if($request->header('Authorization','default') == $authkey){

try{
    $count = count($request->all());

    foreach($request->all() as $item){
        $table = $item['table'];
        $data = $item['data'];
        $conditions = $item['conditions'];
        $conditionsArray = [];
        $insertPayload = [];
        foreach ($conditions as $condition) {
            $conditionsArray[$condition['column']] = $condition['value'];
        }
        


        
        foreach ($data as $item) {
            $insertPayload[$item['column']]=$item['value'];
        }
        // for($i = 0; $i <= $count;$i++){

        // if($item[$i]["autoUpdate"] == "true"){

            $results = DB::connection('aceHODB')->table($table)->updateOrInsert($conditionsArray,$insertPayload);
    //         return "true";
    //     }
    //     else{
    //         DB::connection('aceHODB')->table($table)->insert($insertPayload);
    //         return "false";
        
    //     }
    // }
      
          

        $insertPayload = [];
    }

}
catch(\Illuminate\Database\QueryException $exception){
    return response()->json([
        'StatusCode'=>'500',
        'Message'=>'Failed',
        'error'=>$exception->getMessage(),
        'Data'=> $request->all()
    ],500);
}

return response()->json([
    'StatusCode'=>'200',
    'Message'=>'Success',
    'Data'=> $request->all()
],200);
}
else{
    return response()->json([
        'StatusCode'=>'401',
        'Message'=>'Unauthorized',
        
    ],401);
}
    }

    public function sendtoSiteDB(Request $request){
        $authkey = env('Auth_key');
        if($request->header('Authorization','default') == $authkey){
        try{
            foreach($request->all() as $item){
                $table = $item['table'];
                $data = $item['data'];
                $conditions = $item['conditions'];
                foreach ($conditions as $condition) {
                    $conditionsArray[$condition['column']] = $condition['value'];
                }
                
              
                foreach($data as $items){

                    $insertPayload[$items['column']] = $items['value'];
                
                }
                $results = DB::table($table)->updateOrInsert($conditionsArray,$insertPayload);
                
                            
                $insertPayload = [];
            }
        }
        catch(\Illuminate\Database\QueryException $exception){
            return response()->json([
                'StatusCode'=>'500',
                'Message'=>'Failed',
                'error'=>$exception->getMessage(),
                'Data'=> $request->all()
            ],500);
    }
    return response()->json([
        'StatusCode'=>'200',
        'Message'=>'Success',
        'Data'=> $request->all()
    ],200);
}
else{
    return response()->json([
        'StatusCode'=>'401',
        'Message'=>'Unauthorized',
        
    ],401);
}
}
}
