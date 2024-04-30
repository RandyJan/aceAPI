<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transactionHeader extends Model
{
    use HasFactory;

    protected $table = 'transactionHeader';

    protected $connection = 'aceHODB';

    protected $fillable = 
    [
        'SHIFT',
        'TIME',
        'USER',
        'INVNO',
        'TOTAL',
        'INFO',
        'POSTED',
        'CUSTOMERID',
        'CUSTOMERCODE',
        'CUSTOMERNAME',
        'DINEIN',
        'POINTSEARNED',
        'RESETTER',
        'CUSTTYPE',
        'SCNAME',
        'SCADDRESS',
        'SCID',
        'TRANSNO',
        'TRANSTYPE',
        'HEADCNT',
        'SCCNT',
        'ATTENDANT',
        'ORDERSLIPNO',
        'OCCNT',
        'PWDCNT',
        'TABLEID',
        'TRHID',
        'TURNOVERID',
        'UPLOADED',
        'TRDCOUNT',
        'UPCOUNT',
        'REF1',
        'REF2',
        'REF3',
        'REF4',
        'REF5',

    ];
}
