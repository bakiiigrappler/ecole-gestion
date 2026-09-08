<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TestExcelController extends Controller
{
    public function testExcel()
    {
        try {
            $data = [
                ['Nom', 'Classe', 'Moyenne'],
                ['Jean Dupont', '2NDE-S', '18.5'],
                ['Marie Martin', '1ERE-L', '17.8'],
                ['Pierre Durand', 'TERMINALE-S', '17.2']
            ];

            $filename = 'test-excel-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(new SimpleExcelExport($data), $filename);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

class SimpleExcelExport implements \Maatwebsite\Excel\Concerns\FromArray
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }
}
