<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TestExportController extends Controller
{
    public function testPDF()
    {
        try {
            $data = [
                'title' => 'Test Export PDF',
                'content' => 'Ceci est un test d\'export PDF',
                'date' => now()->format('d/m/Y H:i')
            ];

            $pdf = Pdf::loadView('test-pdf', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('test-export.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
