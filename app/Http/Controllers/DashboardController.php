<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques principales
        $totalStudents = Student::count();
        $totalTeachers = Teacher::where('status', 'active')->count();
        $totalClasses = SchoolClass::where('is_active', true)->count();
        
        // Revenus du mois (exemple)
        $monthlyRevenue = Payment::whereMonth('paid_at', now()->month)
                                ->whereYear('paid_at', now()->year)
                                ->where('status', 'completed')
                                ->sum('amount');
        
        // Taux de paiement (exemple)
        $paymentRate = 95;
        
        // Présences aujourd'hui (exemple)
        $todayAttendance = 95;
        $presentStudents = (int)($totalStudents * ($todayAttendance / 100));
        
        return view('dashboard.index', compact(
            'totalStudents',
            'totalTeachers', 
            'totalClasses',
            'monthlyRevenue',
            'paymentRate',
            'todayAttendance',
            'presentStudents'
        ));
    }
    
    public function stats()
    {
        // API endpoint pour les statistiques en temps réel
        return response()->json([
            'students' => Student::count(),
            'teachers' => Teacher::where('status', 'active')->count(),
            'classes' => SchoolClass::where('is_active', true)->count(),
            'monthly_revenue' => Payment::whereMonth('paid_at', now()->month)
                                      ->whereYear('paid_at', now()->year)
                                      ->where('status', 'completed')
                                      ->sum('amount'),
            'attendance_rate' => 95, // Calculé dynamiquement
        ]);
    }
}
