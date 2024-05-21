<?php

namespace App\Models;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcelImport extends Model implements ToCollection, WithHeadingRow
{
    use HasFactory;

    public static function ExcelData(string $filepath):\Illuminate\Support\Collection
    {
    return Excel::import(new static(), $filepath)->toCollection();
    }
    public function collection(Collection $rows)
    {
        
    }
}
