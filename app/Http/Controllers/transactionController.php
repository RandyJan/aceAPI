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
    break;
case 'PLU':
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
        'table'=>'PLU',
        'message'=>'success',
        'data'=>$retrieved
    ];
    break;
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
    break;
    case 'POSTMIX':
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
            'table'=>'POSTMIX',
            'message'=>'success',
            'data'=>$retrieved
        ];
        break;
        case 'AdjustmenRate':
            $response = DB::connection('aceHODB')->table('AdjustmenRate')->where('ID', $item['COLUMN1'])
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
    'table'=>'AdjustmenRate',
    'message'=>'success',
    'data'=>$retrieved
];
break;
case 'AdjustmentRateDtls':
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
        'table'=>'AdjustmentRateDtls',
        'message'=>'success',
        'data'=>$retrieved
    ];
    break;
    case 'AdmissionType':
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
            'table'=>'AdmissionType',
            'message'=>'success',
            'data'=>$retrieved
        ];
        break;
        case 'AGTS':
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
                'table'=>'AGTS',
                'message'=>'success',
                'data'=>$retrieved
            ];
            break;
            case 'AssetsDetails':
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
                    'table'=>'AssetsDetails',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break;
                case 'ATC':
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
                        'table'=>'ATC',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                    break;
                    case 'AthleteCustomers':
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
                            'table'=>'AthleteCustomers',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break;
            case 'BOPromo':
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
                    'table'=>'BOPromo',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break;
                case 'Branches':
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
                        'table'=>'Branches',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                    break;
                    case 'BusUnit':
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
                            'table'=>'BusUnit',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break;
        case 'CATEGORYCODE':
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
                'table'=>'CATEGORYCODE',
                'message'=>'success',
                'data'=>$retrieved
            ];
            break;
            case 'ChequeDetails':
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
                    'table'=>'ChequeDetails',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break;
                case 'ClaimPromo':
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
                        'table'=>'ClaimPromo',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                    break;
                    case 'Company':
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
                            'table'=>'Company',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break;
                        case 'Currencies':
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
                                'table'=>'Currencies',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'CurrencyDenomination':
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
                                    'table'=>'CurrencyDenomination',
                                    'message'=>'success',
                                    'data'=>$retrieved
                                ];
                                break;
                                case 'CurrencyExchangeRate':
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
            'table'=>'CurrencyExchangeRate',
            'message'=>'success',
            'data'=>$retrieved
        ];
        break;
    case 'CustomerLoyaltyCards':
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
            'table'=>'CustomerLoyaltyCards',
            'message'=>'success',
            'data'=>$retrieved
        ];
        break;      

        case 'Customers':
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
                'table'=>'Customers',
                'message'=>'success',
                'data'=>$retrieved
            ];
            break;             
            case 'CustomersBank':
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
                    'table'=>'CustomersBank',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break; 
                case 'Custprice':
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
                        'table'=>'Custprice',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                    break; 
                    case 'CustpriceHistory':
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
                            'table'=>'CustpriceHistory',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break; 
                        case 'DeptSumm':
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
                                'table'=>'DeptSumm',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;   
                            case 'DeviceList':
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
                                    'table'=>'DeviceList',
                                    'message'=>'success',
                                    'data'=>$retrieved
                                ];
                                break;     
                                case 'DiscTrans':
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
                                        'table'=>'DiscTrans',
                                        'message'=>'success',
                                        'data'=>$retrieved
                                    ];
                                    break;  
                                    case 'Divisions':
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
                                            'table'=>'Divisions',
                                            'message'=>'success',
                                            'data'=>$retrieved
                                        ];
                                        break;
                                        case 'Employee':
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
                                                'table'=>'Employee',
                                                'message'=>'success',
                                                'data'=>$retrieved
                                            ];
                                            break;
            case 'ExemptProducts':
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
                    'table'=>'ExemptProducts',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break;
                case 'FDefault':
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
                            'table'=>'groups',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break;
                        case 'Holidays':
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
                                'table'=>'Holidays',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'HOSiteTerminals':
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
                                    'table'=>'HOSiteTerminals',
                                    'message'=>'success',
                                    'data'=>$retrieved
                                ];
                                break;
                                case 'HPromo':
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
                                        'table'=>'HPromo',
                                        'message'=>'success',
                                        'data'=>$retrieved
                                    ];
                                    break;
                                    case 'InParkCurrencies':
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
                                            'table'=>'InParkCurrencies',
                                            'message'=>'success',
                                            'data'=>$retrieved
                                        ];
                                        break;
                                        case 'InParkCurrencyDetails':
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
                                                'table'=>'InParkCurrencyDetails',
                                                'message'=>'success',
                                                'data'=>$retrieved
                                            ];
            break;
            case 'LocSettings':
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
                    'table'=>'LocSettings',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break;
                case 'LoyaltySettings':
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
                        'table'=>'LoyaltySettings',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                    break;
                    case 'LoyaltyTrans':
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
                            'table'=>'LoyaltyTrans',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break; case 'ManualOS':
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
                                'table'=>'ManualOS',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'Master':
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
                                    'table'=>'Master',
                                    'message'=>'success',
                                    'data'=>$retrieved
                                ];
                                break;
                                case 'MealStubComponents':
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
                                        'table'=>'MealStubComponents',
                                        'message'=>'success',
                                        'data'=>$retrieved
                                    ];
                                    break;
                                    case 'OrderSLipDetails':
                                        $response = DB::connection('aceHODB')
                                        ->table('OrderSLipDetails')
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
                                            'table'=>'OrderSLipDetails',
                                            'message'=>'success',
                                            'data'=>$retrieved
                                        ];
                                        break;
                                        case 'OrderSlipHeader':
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
                                                'table'=>'OrderSlipHeader',
                                                'message'=>'success',
                                                'data'=>$retrieved
                                            ];
                                            break;
                                            case 'OutletDailySales':
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
                                                    'table'=>'OutletDailySales',
                                                    'message'=>'success',
                                                    'data'=>$retrieved
                                                ];
break;
case 'Outlets':
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
'table'=>'Outlets',
'message'=>'success',
'data'=>$retrieved
];
break;
case 'OutletType':
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
'table'=>'OutletType',
'message'=>'success',
'data'=>$retrieved
];
break;
case 'ParkingTickets':
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
'table'=>'ParkingTickets',
'message'=>'success',
'data'=>$retrieved
];
break;
case 'PartSplr':
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
'table'=>'PartSplr',
'message'=>'success',
'data'=>$retrieved
];
break;
case 'PartsRequestHeader':
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
'table'=>'PartsRequestHeader',
'message'=>'success',
'data'=>$retrieved
];
break;
case 'PosAssign':
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
    'table'=>'PosAssign',
    'message'=>'success',
    'data'=>$retrieved
];
break;
case 'POSHeaders':
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
        'table'=>'POSHeaders',
        'message'=>'success',
        'data'=>$retrieved
    ];
    break;
    case 'PPCTrans':
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
            'table'=>'PPCTrans',
            'message'=>'success',
            'data'=>$retrieved
        ];
        break;
        case 'PrepaidCards':
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
                'table'=>'PrepaidCards',
                'message'=>'success',
                'data'=>$retrieved
            ];
            break;
            case 'PromoCustomers':
                $response = DB::connection('aceHODB')
                ->table('PromoCustomers')
                ->where('BRANCHID', $item['COLUMN1'])
                ->where('OUTLETID', $item['COLUMN2'])
                ->where('POSNO', $item['COLUMN3'])
                ->where('DATE', $item['COLUMN4'])
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
                    'table'=>'PromoCustomers',
                    'message'=>'success',
                    'data'=>$retrieved
                ];
                break;
                case 'PromoItems':
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
                        'table'=>'PromoItems',
                        'message'=>'success',
                        'data'=>$retrieved
                    ];
                    break;
                    case 'PSetup':
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
                            'table'=>'PSetup',
                            'message'=>'success',
                            'data'=>$retrieved
                        ];
                        break;
                        case 'PTL':
                            $response = DB::connection('aceHODB')
                            ->table('PTL')
                            ->where('BRANCHID', $item['COLUMN1'])
                            ->where('OUTLETID', $item['COLUMN2'])
                            ->where('POSNUMBER', $item['COLUMN3'])
                            ->where('DATE', $item['COLUMN4'])
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
                                'table'=>'PTL',
                                'message'=>'success',
                                'data'=>$retrieved
                            ];
                            break;
                            case 'RedeemOutlets':
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
                                    'table'=>'RedeemOutlets',
                                    'message'=>'success',
                                    'data'=>$retrieved
                                ];
                                break;
                                case 'SCCustomers':
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
                                        'table'=>'SCCustomers',
                                        'message'=>'success',
                                        'data'=>$retrieved
                                    ];
                                    break;
                                    case 'SiteParts':
                                        $response = DB::connection('aceHODB')
                                        ->table('SiteParts')
                                        ->where('ARNOC', $item['COLUMN1'])
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
                                            'table'=>'SiteParts',
                                            'message'=>'success',
                                            'data'=>$retrieved
                                        ];
                                        break;
                                        case 'Stockcrd':
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
                                                'table'=>'Stockcrd',
                                                'message'=>'success',
                                                'data'=>$retrieved
                                            ];
                                    break;
                                    case 'Subcat':
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
                                            'table'=>'Subcat',
                                            'message'=>'success',
                                            'data'=>$retrieved
                                        ];
                                        break;
                                        case 'Supplier':
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
                                                'table'=>'Supplier',
                                                'message'=>'success',
                                                'data'=>$retrieved
                                            ];
                                            break;
                                            case 'tblProject':
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
                                                    'table'=>'tblProject',
                                                    'message'=>'success',
                                                    'data'=>$retrieved
                                                ];
                                                break;
                                                case 'tblReasons':
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
                                                        'table'=>'tblReasons',
                                                        'message'=>'success',
                                                        'data'=>$retrieved
                                                    ];
                                                    break;
                                                    case 'TenantInventory':
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
                                                            'table'=>'TenantInventory',
                                                            'message'=>'success',
                                                            'data'=>$retrieved
                                                        ];
                                                        break;
                                                        case 'TicketGrouping':
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
                                                                'table'=>'TicketGrouping',
                                                                'message'=>'success',
                                                                'data'=>$retrieved
                                                            ];
                                                            break;
                                                            case 'TicketsTemplate':
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
                                                                    'table'=>'TicketsTemplate',
                                                                    'message'=>'success',
                                                                    'data'=>$retrieved
                                                                ];
                                                                break;
                                                                case 'TicketsTemplateHeader':
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
                                                                        'table'=>'TicketsTemplateHeader',
                                                                        'message'=>'success',
                                                                        'data'=>$retrieved
                                                                    ];
                                                                    break;
                                                                    case 'TurnoverCurrencyDenomination':
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
                                                                            'table'=>'TurnoverCurrencyDenomination',
                                                                            'message'=>'success',
                                                                            'data'=>$retrieved
                                                                        ];
                                                                        break;
                                                                        case 'UCostChange':
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
                                                                                'table'=>'UCostChange',
                                                                                'message'=>'success',
                                                                                'data'=>$retrieved
                                                                            ];
                                                                            break;
                                                                            case 'UserDevices':
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
                                                                                    'table'=>'UserDevices',
                                                                                    'message'=>'success',
                                                                                    'data'=>$retrieved
                                                                                ];
                                                                                break;
                                                                                case 'Users':
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
                                                                                        'table'=>'Users',
                                                                                        'message'=>'success',
                                                                                        'data'=>$retrieved
                                                                                    ];
                                                                                    break;
                                                                                    case 'UserSite':
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
                                                                                            'table'=>'UserSite',
                                                                                            'message'=>'success',
                                                                                            'data'=>$retrieved
                                                                                        ];
                                                                                        break;
                                                                                        case 'UserType':
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
                                                                                                'table'=>'UserType',
                                                                                                'message'=>'success',
                                                                                                'data'=>$retrieved
                                                                                            ];
                                                                                            break;
                                                                                            case 'VPromo':
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
                                                                                                    'table'=>'VPromo',
                                                                                                    'message'=>'success',
                                                                                                    'data'=>$retrieved
                                                                                                ];
                                                                                                break;
                                                                                                case 'WalletTrans':
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
                                                                                                        'table'=>'WalletTrans',
                                                                                                        'message'=>'success',
                                                                                                        'data'=>$retrieved
                                                                                                    ];
                                                                                                    break;
                                                                                                    case 'Zones':
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
