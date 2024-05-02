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

            $count = count($request->all());
            $res = [];
            foreach($request->all() as $item){
        try{
        $table = $item['table'];
        $data = $item['data'];
        $conditions = $item['conditions'];
        $conditionsArray = [];
        $insertPayload = [];
        if($item['auto_update'] =='true'){
        foreach ($conditions as $condition) {
            $conditionsArray[$condition['column']] = $condition['value'];
        }
        
        
        
        
        foreach ($data as $item) {
            $insertPayload[$item['column']]=$item['value'];
        }

            $results = DB::connection('aceHODB')->table($table)->updateOrInsert($conditionsArray,$insertPayload);
        }
        else{
            foreach ($conditions as $condition) {
                $conditionsArray[$condition['column']] = $condition['value'];
            }
            
            
            
            
            foreach ($data as $item) {
                $insertPayload[$item['column']]=$item['value'];
            }
            DB::connection('aceHODB')->table($table)->insert($insertPayload);
        }
        $res[] = ['StatusCode'=>'200',
                    'Message'=>'Success',
                    'Data'=> $conditions];

         $insertPayload = [];
    }

    catch(\Illuminate\Database\QueryException $exception){
        $res[] = ['StatusCode'=>'500',
                    'Message'=>'Failed',
                    'Error'=>$exception->getMessage(),
                    'Data'=> $conditions];
    }
}

return response()->json(
    $res,200);
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
            $res = [];
            foreach($request->all() as $item){
                try{
                $table = $item['table'];
                $data = $item['data'];
                $conditions = $item['conditions'];
                if($item['auto_update']=='true')
                {
                foreach ($conditions as $condition) {
                    $conditionsArray[$condition['column']] = $condition['value'];
                }
                
              
                foreach($data as $items){

                    $insertPayload[$items['column']] = $items['value'];
                
                }
                $results = DB::table($table)->updateOrInsert($conditionsArray,$insertPayload);
            }
            else{
                foreach ($conditions as $condition) {
                    $conditionsArray[$condition['column']] = $condition['value'];
                }
                
              
                foreach($data as $items){

                    $insertPayload[$items['column']] = $items['value'];
                
                }
                DB::table($table)->insert($insertPayload);
            }
                $res[]=['StatusCode'=>'200',
                'Message'=>'Success',
                'Data'=>  $conditions];
                $insertPayload = [];
            }
            catch(\Illuminate\Database\QueryException $exception){
               
                    $res[] = ['StatusCode'=>'500',
                    'Message'=>'Failed',
                    'error'=>$exception->getMessage(),
                    'Data'=>  $conditions];
             
            }
    }
    return response()->json($res,200);
}
else{
    return response()->json([
        'StatusCode'=>'401',
        'Message'=>'Unauthorized',
        
    ],401);
}
}
}
