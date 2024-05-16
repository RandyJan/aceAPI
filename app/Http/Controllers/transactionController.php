<?php

namespace App\Http\Controllers;

use App\Models\transactionHeader;
use Illuminate\Http\Request;
use DB;
use Illuminate\Validation\Rules\Exists;
use App\Models\syncTable;
use Illuminate\Support\Facades\Log;

class transactionController extends Controller
{
    //

    public function sendToServer(Request $request){
        $username = env('USER_NAME');
        $password = env('PASSWORD');
        $authHeader = 'Basic ' . base64_encode($username . ':' . $password); 
      
        if($request->header('Authorization','default') == $authHeader){

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
        $username = env('USER_NAME');
        $password = env('PASSWORD');
        $authHeader = 'Basic ' . base64_encode($username . ':' . $password); 
        
        if($request->header('Authorization','default') == $authHeader){
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
public function syncTable($date,$time,Request $request){
    $username = env('USER_NAME');
    $password = env('PASSWORD');
    $authHeader = 'Basic ' . base64_encode($username . ':' . $password); 
    
    if($request->header('Authorization','default') == $authHeader){
        $data = syncTable::where('date','>=',$date)
        ->where('time','>=',$time)
        ->get();
        $res=[];
        foreach($data as $item){
            try{
        switch ($item['TABLENAME']) {
            case 'PARTSLOCATION':
                $response = DB::connection('aceHODB')->table('PartsLocation')->where('BRANCHID',$item['COLUMN1'])
                ->where('PRODUCT_ID',$item['COLUMN2'])
                ->get();
            //   Log::info($response);
            if($response->isEmpty()){
                break;
            }
                $retrieved=[]; 
                foreach($response[0] as $key => $value){
                    $retrieved[]=["column"=>$key,
                        "value"=>$value];
                   }
                $res[]=[
                    'statusCode'=>200,
                    'table'=>'PARTSLOCATION',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
            case 'PLU':
                $response = DB::connection('aceHODB')->table('PLU')->where('PLUBARCODE',$item['COLUMN1'])
                ->get();
                if($response->isEmpty()){
                    break;
                }
                $retrieved=[]; 
                foreach($response[0] as $key => $value){
                    $retrieved[]=["column"=>$key,
                        "value"=>$value];
                   }
                $res[]=[
                    'statusCode'=>200,
                    'table'=>'PLU',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
            case 'PARTS':
                $response = DB::connection('aceHODB')->table('PARTS')->where('PRODUCT_ID',$item['COLUMN1'])->get();
                $retrieved=[]; 
                if($response->isEmpty()){
                    break;
                }
                foreach($response as $row){

                    foreach($row as $key => $value){
                        $retrieved[]=["column"=>$key,
                        "value"=>$value];
                    }
                }
                $res[]=[
                    'statusCode'=>200,
                    'table'=>'PARTS',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                case 'POSTMIX':
                    $response = DB::connection('aceHODB')->table('POSTMIX')->where('PRODUCT_ID', $item['COLUMN1'])
                    ->where('PARTSID',$item['COLUMN2'])
                    ->get();
                    Log::info($response);
                    $retrieved = [];
                    // if(($response == null)){ 
                        if($response ==="[]" || Empty($response) || $response->isEmpty()){
                            break;
                        }
                        foreach($response as $row){
                    foreach($row as $key => $value){
                        $retrieved[]=[
                            'column'=>$key,
                            'value'=>$value
                        ];
                    }
                }

                    $res[]=[
                        'statusCode'=>200,
                        'table'=>'POSTMIX',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                // }
            // default:
            // return;
                // $res[]=[
                //     'statusCode'=>404,
                //     'message'=>'unknown data',
                //     'table'=>$item['TABLENAME'],
                //     'data'=>$item];
        }
    }
    catch(\Illuminate\Database\QueryException $exception){
        $res[]=[
            'statusCode'=>404,
            'message'=>'unknown data',
            'data'=>[$exception]
        ];
    }
    }

        return $res;
}
        else{
            return response()->json([
                'StatusCode'=>'401',
                'Message'=>'Unauthorized',
                
            ],401);
        }
}
}
