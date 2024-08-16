<?php

    namespace App\Http\Controllers;

    use App\Models\transactionHeader;
    use Illuminate\Http\Request;
    use DB;
    use Illuminate\Validation\Rules\Exists;
    use App\Models\syncTable;
    use Illuminate\Support\Facades\Log;
    use App\Models\Tickets;
use Exception;

    class transactionController extends Controller
    {
    //
        public function getTicketData($barcode, Request $request){
            $username = env('USER_NAME');
            $password = env('PASSWORD');
            $authHeader = 'Basic ' . base64_encode($username . ':' . $password); 
            
            if($request->header('Authorization','default') == $authHeader){
                $result = Tickets::where("BARCODE",$barcode)->get();
                $res=[];
                json_encode($result);
                $retrieved = [];
                try{
                    if($result->isNotEmpty()){
                        foreach($result as $item){
                            foreach($item->getAttributes() as $key=> $value){
                                $retrieved[]=["column"=>$key,
                                                "value"=>$value];
                            }
                                
                        }
                        $res[] = ['StatusCode'=>'200',
                        'Message'=>'Success',
                        'Data'=>  $retrieved];
                    }
                    else{
                        $res[] = ['StatusCode'=>'404',
                        'Message'=>'Not Found',
                        'Data'=>[ "Barcode"=>$barcode]];
                    }
                   
                    
                  
                }
                catch(Exception $exception){
                    $res[] = ['StatusCode'=>'500',
                        'Message'=>'Failed',
                        'Error'=>$exception->getMessage(),
                        'Data'=> $retrieved];
                }
                return response()->json( $res,200);

            }
            else{
                return response()->json([
                    'StatusCode'=>'401',
                    'Message'=>'Unauthorized',

                ],401);
            }
        }
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
                $data = syncTable::where('date','>',$date)
                ->orWhere('DATE','=',$date ,'&&','TIME','>',$time)
                ->get();
                
                $res=[];
                foreach($data as $item){
                    try{


                        switch (strtolower($item['TABLENAME'])) {
                            case 'partslocation':
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
                                'action'=>$item['ACTION'],
                                'table'=>'PARTSLOCATION',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            
                            break;
                            case 'plu':
                            $response = DB::connection('aceHODB')->table('PLU')->where('PLUBARCODE',$item['COLUMN1'])
                            ->get();
                            Log::info($response);
                            if($response->isEmpty()){
                                break;
                            }
                            $retrieved=[]; 
                            foreach($response as $row){
                                foreach($row as $key => $value){
                                    $retrieved[]=["column"=>$key,
                                    "value"=>$value];
                                }
                            }
                            $res[]=[
                                'statusCode'=>200,
                                'action'=>$item['ACTION'],
                                'table'=>'PLU',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'parts':
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
                                'action'=>$item['ACTION'],
                                'table'=>'PARTS',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'postmix':

                            //ADDED BY MARK
                            if($item['ACTION'] == '3'){
                                $jsonArr = [
                                   [
                                    "column" => "PRODUCT_ID", 
                                    "value" => $item['COLUMN1'] 
                                    ], 
                                    [
                                     "column" => "PARTSID", 
                                     "value" => $item['COLUMN2']
                                    ] 
                                ];

                                $res[] = [
                                    'statusCode'=>200,
                                    'action'=>$item['ACTION'],
                                    'table'=>'POSTMIX',
                                    'message'=>'success',
                                    'data'=>$jsonArr
                                ]; 
                                break;
                            }
                            //-----------------------------------

                            $response = DB::connection('aceHODB')->table('POSTMIX')->where('PRODUCT_ID', $item['COLUMN1'])
                            ->where('PARTSID',$item['COLUMN2'])
                            ->get();
                            // Log::info($response);
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
                                'action'=>$item['ACTION'],
                                'table'=>'POSTMIX',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'adjustmentrate':
                            $response = DB::connection('aceHODB')->table('ADJUSTMENTRATE')->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ADJUSTMENTRATE',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'adjustmentratedtls':
                            $response = DB::connection('aceHODB')->table('AdjustmentRateDtls')->where('ADJID', $item['COLUMN1'])
                            ->where('PRODUCTID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'AdjustmentRateDtls',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'admissiontype':
                            $response = DB::connection('aceHODB')->table('AdmissionType')->where('ADMISSIONTYPEID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'AdmissionType',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'agts':
                            $response = DB::connection('aceHODB')->table('AGTS')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('POSID', $item['COLUMN2'])
                            ->where('CASHIERID', $item['COLUMN3'])
                            ->where('DATE', $item['COLUMN4'])
            // ->where('TIME', $item['COLUMN5'])
            // ->where('TYPE', $item['COLUMN6'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'AGTS',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'assetsdetails':
                            $response = DB::connection('aceHODB')->table('AssetsDetails')->where('ASSETID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'AssetsDetails',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'atc':
                            $response = DB::connection('aceHODB')
                            ->table('ATC')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ATC',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'athletecustomers':
                            $response = DB::connection('aceHODB')
                            ->table('AthleteCustomers')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('DATE', $item['COLUMN3'])
                            ->where('POSNO', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'AthleteCustomers',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'bopromo':
                            $response = DB::connection('aceHODB')
                            ->table('BOPromo')
                            ->where('BOPROMOID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'BOPromo',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'branches':
                            $response = DB::connection('aceHODB')
                            ->table('Branches')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Branches',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'busunit':
                            $response = DB::connection('aceHODB')
                            ->table('BusUnit')
                            ->where('BSUNITCODE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'BusUnit',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'categorycode':
                            $response = DB::connection('aceHODB')
                            ->table('CATEGORYCODE')
                            ->where('CATEGORYCODE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'CATEGORYCODE',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'chequedetails':
                            $response = DB::connection('aceHODB')
                            ->table('ChequeDetails')
                            ->where('ID', $item['COLUMN1'])
                            ->where('CHEQUENUMBER', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ChequeDetails',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'claimpromo':
                            $response = DB::connection('aceHODB')
                            ->table('ClaimPromo')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('CLAIMEDPROMOID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ClaimPromo',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'company':
                            $response = DB::connection('aceHODB')
                            ->table('Company')
                            ->where('COMPANYID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Company',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'currencies':
                            $response = DB::connection('aceHODB')
                            ->table('Currencies')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Currencies',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'currencydenomination':
                            $response = DB::connection('aceHODB')
                            ->table('CurrencyDenomination')
                            ->where('ID', $item['COLUMN1'])
                            ->get();
                            
                            $retrieved = [];
                            
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
                                'action'=>$item['ACTION'],
                                'table'=>'CurrencyDenomination',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'currencyexchangerate':
                            $response = DB::connection('aceHODB')
                            ->table('CurrencyExchangeRate')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'CurrencyExchangeRate',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'customerloyaltycards':
                            $response = DB::connection('aceHODB')
                            ->table('CustomerLoyaltyCards')
                            ->where('LOYALTYCARDID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'CustomerLoyaltyCards',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;      

                            case 'customers':
                            $response = DB::connection('aceHODB')
                            ->table('Customers')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('CUSTOMERID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Customers',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;             
                            case 'customersbank':
                            $response = DB::connection('aceHODB')
                            ->table('CustomersBank')
                            ->where('CBANKID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'CustomersBank',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break; 
                            case 'custprice':
                            $response = DB::connection('aceHODB')
                            ->table('Custprice')
                            ->where('CUSTPRICEID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Custprice',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break; 
                            case 'custpricehistory':
                            $response = DB::connection('aceHODB')
                            ->table('CustpriceHistory')
                            ->where('CUSTPRICEHISTORYID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'CustpriceHistory',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break; 
                            case 'deptsumm':
                            $response = DB::connection('aceHODB')
                            ->table('DeptSumm')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('DEPTSUMMID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'DeptSumm',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;   
                            case 'devicelist':
                            $response = DB::connection('aceHODB')
                            ->table('DeviceList')
                            ->where('DEVICELISTID', $item['COLUMN1'])
                            ->get();
                            
                            $retrieved = [];
                            
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
                                'action'=>$item['ACTION'],
                                'table'=>'DeviceList',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;     
                            case 'disctrans':
                            $response = DB::connection('aceHODB')
                            ->table('DiscTrans')
                            ->where('DISCTRANSID', $item['COLUMN1'])
                            ->where('BRANCHID', $item['COLUMN2'])
                            ->where('OUTLETID', $item['COLUMN3'])
                            ->where('TERMNO', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'DiscTrans',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;  
                            case 'divisions':
                            $response = DB::connection('aceHODB')
                            ->table('Divisions')
                            ->where('DIVISIONID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Divisions',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'employee':
                            $response = DB::connection('aceHODB')
                            ->table('Employee')
                            ->where('EMPLOYEEID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Employee',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'exemptproducts':
                            $response = DB::connection('aceHODB')
                            ->table('ExemptProducts')
                            ->where('EXEMPTPRODUCTSID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ExemptProducts',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'fdefault':
                            $response = DB::connection('aceHODB')
                            ->table('FDefault')
                            ->where('MYDEFAULT', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'FDefault',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'groups':
                            $response = DB::connection('aceHODB')
                            ->table('groups')
                            ->where('GROUPCODE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'groups',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'holidays':
                            $response = DB::connection('aceHODB')
                            ->table('Holidays')
                            ->where('DATE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Holidays',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'hositeterminals':
                            $response = DB::connection('aceHODB')
                            ->table('HOSiteTerminals')
                            ->where('ID', $item['COLUMN1'])
                            ->get();
                            
                            $retrieved = [];
                            
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
                                'action'=>$item['ACTION'],
                                'table'=>'HOSiteTerminals',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'hpromo':
                            $response = DB::connection('aceHODB')
                            ->table('HPromo')
                            ->where('PROMOID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'HPromo',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'inparkcurrencies':
                            $response = DB::connection('aceHODB')
                            ->table('InParkCurrencies')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('INPARKCURRENCYID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'InParkCurrencies',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'inparkcurrencydetails':
                            $response = DB::connection('aceHODB')
                            ->table('InParkCurrencyDetails')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('ID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'InParkCurrencyDetails',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'locsettings':
                            $response = DB::connection('aceHODB')
                            ->table('LocSettings')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'LocSettings',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'loyaltysettings':
                            $response = DB::connection('aceHODB')
                            ->table('LoyaltySettings')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'LoyaltySettings',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'loyaltytrans':
                            $response = DB::connection('aceHODB')
                            ->table('LoyaltyTrans')
                            ->where('CUSTOMERID', $item['COLUMN1'])
                            ->where('TRANSID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'LoyaltyTrans',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break; case 'manualos':
                            $response = DB::connection('aceHODB')
                            ->table('ManualOS')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('OSNO', $item['COLUMN3'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ManualOS',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'mop':
                                $response = DB::connection('aceHODB')
                                ->table('mop')
                                ->where('id', $item['COLUMN1'])
                                ->get();
    
                                $retrieved = [];
    
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
                                    'action'=>$item['ACTION'],
                                    'table'=>'mop',
                                    'message'=>'success',
                                    'data'=>$retrieved
                                ];
                                break;
                            case 'master':
                            $response = DB::connection('aceHODB')
                            ->table('Master')
                            ->where('MASTERCODE', $item['COLUMN1'])
                            ->get();
                            
                            $retrieved = [];
                            
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
                                'action'=>$item['ACTION'],
                                'table'=>'Master',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'mealstubcomponents':
                            $response = DB::connection('aceHODB')
                            ->table('MealStubComponents')
                            ->where('REFERENCEID', $item['COLUMN1'])
                            ->where('LINENO', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'MealStubComponents',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                                    // case 'OrderSLipDetails':
                                    //     $response = DB::connection('aceHODB')
                                    //     ->table('OrderSLipDetails')
                                    //     ->where('ID', $item['COLUMN1'])
                                    //     ->get();

                                    //     $retrieved = [];

                                    //         if($response ==="[]" || Empty($response) || $response->isEmpty()){
                                    //             break;
                                    //         }
                                    //         foreach($response as $row){
                                    //     foreach($row as $key => $value){
                                    //         $retrieved[]=[
                                    //             'column'=>$key,
                                    //             'value'=>$value
                                    //         ];
                                    //     }
                                    // }

                                    //     $res[]=[
                                    //         'statusCode'=>200,
                            // 'action'=>$item['ACTION'],
                                    //         'table'=>'OrderSLipDetails',
                                    //         'message'=>'success',
                                    //         'data'=>$retrieved
                                    //     ];
                                    //     break;
                            case 'orderslipheader':
                            $response = DB::connection('aceHODB')
                            ->table('OrderSlipHeader')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('ORDERSLIPNO', $item['COLUMN1'])
                            ->where('DEVICENO', $item['COLUMN1'])
                            ->where('DEVICENO', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'OrderSlipHeader',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'outletdailysales':
                            $response = DB::connection('aceHODB')
                            ->table('OutletDailySales')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN12'])
                            ->where('DATE', $item['COLUMN3'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'OutletDailySales',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'outlets':
                            $response = DB::connection('aceHODB')
                            ->table('Outlets')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Outlets',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'outlettype':
                            $response = DB::connection('aceHODB')
                            ->table('OutletType')
                            ->where('OUTLETTYPE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'OutletType',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'parkingtickets':
                            $response = DB::connection('aceHODB')
                            ->table('ParkingTickets')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'ParkingTickets',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'partsplr':
                            $response = DB::connection('aceHODB')
                            ->table('PartSplr')
                            ->where('SUPPLIERCODE', $item['COLUMN1'])
                            ->where('PRODUCT_ID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PartSplr',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'partsrequestheader':
                            $response = DB::connection('aceHODB')
                            ->table('PartsRequestHeader')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('REQUESTID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PartsRequestHeader',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'posassign':
                            $response = DB::connection('aceHODB')
                            ->table('PosAssign')
                            ->where('POSNO', $item['COLUMN1'])
                            ->where('BRANCHID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PosAssign',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'posheaders':
                            $response = DB::connection('aceHODB')
                            ->table('POSHeaders')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('DATE', $item['COLUMN2'])
                            ->where('SHIFT', $item['COLUMN3'])
                            ->where('USER', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'POSHeaders',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'ppctrans':
                            $response = DB::connection('aceHODB')
                            ->table('PPCTrans')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('ID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PPCTrans',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'prepaidcards':
                            $response = DB::connection('aceHODB')
                            ->table('PrepaidCards')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PrepaidCards',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
            // case 'PromoCustomers':
            //     $response = DB::connection('aceHODB')
            //     ->table('PromoCustomers')
            //     ->where('BRANCHID', $item['COLUMN1'])
            //     ->where('OUTLETID', $item['COLUMN2'])
            //     ->where('POSNO', $item['COLUMN3'])
            //     ->where('DATE', $item['COLUMN4'])
            //     ->get();

            //     $retrieved = [];

            //         if($response ==="[]" || Empty($response) || $response->isEmpty()){
            //             break;
            //         }
            //         foreach($response as $row){
            //     foreach($row as $key => $value){
            //         $retrieved[]=[
            //             'column'=>$key,
            //             'value'=>$value
            //         ];
            //     }
            // }

            //     $res[]=[
            //         'statusCode'=>200,
                            // 'action'=>$item['ACTION'],
            //         'table'=>'PromoCustomers',
            //         'message'=>'success',
            //         'data'=>$retrieved
            //     ];
            //     break;
            //test
                            case 'promoitems':
                            $response = DB::connection('aceHODB')
                            ->table('PromoItems')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN1'])
                            ->where('POSNO', $item['COLUMN1'])
                            ->where('DATE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PromoItems',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'psetup':
                            $response = DB::connection('aceHODB')
                            ->table('PSetup')
                            ->where('DSCCODE', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'PSetup',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                        // case 'PTL':
                        //     $response = DB::connection('aceHODB')
                        //     ->table('PTL')
                        //     ->where('BRANCHID', $item['COLUMN1'])
                        //     ->where('OUTLETID', $item['COLUMN2'])
                        //     ->where('POSNUMBER', $item['COLUMN3'])
                        //     ->where('DATE', $item['COLUMN4'])
                        //     ->get();

                        //     $retrieved = [];

                        //         if($response ==="[]" || Empty($response) || $response->isEmpty()){
                        //             break;
                        //         }
                        //         foreach($response as $row){
                        //     foreach($row as $key => $value){
                        //         $retrieved[]=[
                        //             'column'=>$key,
                        //             'value'=>$value
                        //         ];
                        //     }
                        // }

                        //     $res[]=[
                        //         'statusCode'=>200,
                            // 'action'=>$item['ACTION'],
                        //         'table'=>'PTL',
                        //         'message'=>'success',
                        //         'data'=>$retrieved
                        //     ];
                        //     break;
                            case 'redeemoutlets':
                            $response = DB::connection('aceHODB')
                            ->table('RedeemOutlets')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('PRODUCTID', $item['COLUMN3'])
                            ->get();
                            
                            $retrieved = [];
                            
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
                                'action'=>$item['ACTION'],
                                'table'=>'RedeemOutlets',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'sccustomers':
                            $response = DB::connection('aceHODB')
                            ->table('SCCustomers')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('DATE', $item['COLUMN3'])
                            ->where('POSNO', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'SCCustomers',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'siteparts':
                            $response = DB::connection('aceHODB')
                            ->table('SiteParts')
                            ->where('ARNOC', $item['COLUMN1'])
                            ->where('PRODUCT_ID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'SiteParts',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'stockcrd':
                            $response = DB::connection('aceHODB')
                            ->table('Stockcrd')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('STOCKCRDID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Stockcrd',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'subcat':
                            $response = DB::connection('aceHODB')
                            ->table('Subcat')
                            ->where('PRODUCTNO', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Subcat',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'supplier':
                            $response = DB::connection('aceHODB')
                            ->table('Supplier')
                            ->where('SUPPID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Supplier',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'tblproject':
                            $response = DB::connection('aceHODB')
                            ->table('tblProject')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'tblProject',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'tblreasons':
                            $response = DB::connection('aceHODB')
                            ->table('tblReasons')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'tblReasons',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'tenantinventory':
                            $response = DB::connection('aceHODB')
                            ->table('TenantInventory')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('TENANTINVENTORYID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'TenantInventory',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'ticketgrouping':
                            $response = DB::connection('aceHODB')
                            ->table('TicketGrouping')
                            ->where('TICKETGROUP', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'TicketGrouping',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'ticketstemplate':
                            $response = DB::connection('aceHODB')
                            ->table('TicketsTemplate')
                            ->where('TICKETTEMPLATEID', $item['COLUMN1'])
                            ->where('LINENO', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'TicketsTemplate',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'ticketstemplateheader':
                            $response = DB::connection('aceHODB')
                            ->table('TicketsTemplateHeader')
                            ->where('TICKETTEMPLATEID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'TicketsTemplateHeader',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'turnovercurrencydenomination':
                            $response = DB::connection('aceHODB')
                            ->table('TurnoverCurrencyDenomination')
                            ->where('STATIONCODE', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('POSNUMBER', $item['COLUMN3'])
                            ->where('ID', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'TurnoverCurrencyDenomination',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'ucostchange':
                            $response = DB::connection('aceHODB')
                            ->table('UCostChange')
                            ->where('SITENO', $item['COLUMN1'])
                            ->where('PRODUCT_ID', $item['COLUMN2'])
                            ->where('PDATE', $item['COLUMN3'])
                            ->where('CTIME', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'UCostChange',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'userdevices':
                            $response = DB::connection('aceHODB')
                            ->table('UserDevices')
                            ->where('ID', $item['COLUMN1'])
                            ->where('DEVICEID', $item['COLUMN12'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'UserDevices',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'users':
                            $response = DB::connection('aceHODB')
                            ->table('Users')
                            ->where('DATE', $item['COLUMN1'])
                            ->where('ID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Users',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'usersite':
                            $response = DB::connection('aceHODB')
                            ->table('UserSite')
                            ->where('ID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'UserSite',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'usertype':
                            $response = DB::connection('aceHODB')
                            ->table('UserType')
                            ->where('USERTYPEID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'UserType',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'vpromo':
                            $response = DB::connection('aceHODB')
                            ->table('VPromo')
                            ->where('PROMOID', $item['COLUMN1'])
                            ->where('PRODUCTID', $item['COLUMN2'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'VPromo',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'wallettrans':
                            $response = DB::connection('aceHODB')
                            ->table('WalletTrans')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('TERMNO', $item['COLUMN3'])
                            ->where('TRANSID', $item['COLUMN4'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'WalletTrans',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'zones':
                            $response = DB::connection('aceHODB')
                            ->table('Zones')
                            ->where('ZONEID', $item['COLUMN1'])
                            ->get();

                            $retrieved = [];

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
                                'action'=>$item['ACTION'],
                                'table'=>'Zones',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            default:
            // return;
            // break;
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
