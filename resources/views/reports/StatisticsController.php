<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();

        $totalTeachers = Teacher::count();
        $activeTeachers = Teacher::where('status', 'active')->count();

        $totalClasses = SchoolClass::count();
        $activeClasses = SchoolClass::where('is_active', true)->count();

        $currentYear = AcademicYear::where('is_current', true)->first();

        $totalRevenue = 0;
        $monthlyRevenue = 0;
        $paymentsCompleted = 0;
        $paymentsPending = 0;
        $paymentsCancelled = 0;

        $enrollmentPaymentStats = [
            'pending' => 0,
            'partial' => 0,
            'completed' => 0,
            'overdue' => 0,
        ];

        $enrollmentsByCycle = [
            'preprimaire' => 0,
            'primaire' => 0,
            'college' => 0,
            'lycee' => 0,
        ];

        if ($currentYear) {
            $totalRevenue = Payment::whereHas('enrollment', function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })->sum('amount');

            $monthlyRevenue = Payment::whereHas('enrollment', function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })->whereMonth('paid_at', now()->month)
              ->whereYear('paid_at', now()->year)
              ->sum('amount');

            $paymentsCompleted = Payment::whereHas('enrollment', function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })->where('status', 'completed')->count();

            $paymentsPending = Payment::whereHas('enrollment', function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })->where('status', 'pending')->count();

            $paymentsCancelled = Payment::whereHas('enrollment', function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })->where('status', 'cancelled')->count();

            // Enrollment payment status
            foreach (array_keys($enrollmentPaymentStats) as $status) {
                $enrollmentPaymentStats[$status] = Enrollment::where('academic_year_id', $currentYear->id)
                    ->where('payment_status', $status)
                    ->count();
            }

            // Enrollments by cycle
            foreach (array_keys($enrollmentsByCycle) as $cycle) {
                $enrollmentsByCycle[$cycle] = Enrollment::where('academic_year_id', $currentYear->id)
                    ->whereHas('schoolClass.level', function ($q) use ($cycle) {
                        $q->where('cycle', $cycle);
                    })->count();
            }
        } else {
            // Global fallback if no current academic year
            $totalRevenue = Payment::sum('amount');
            $monthlyRevenue = Payment::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount');
            $paymentsCompleted = Payment::where('status', 'completed')->count();
            $paymentsPending = Payment::where('status', 'pending')->count();
            $paymentsCancelled = Payment::where('status', 'cancelled')->count();
        }
        // Revenus mensuels optimisés
        $revenusParMois = Payment::select(
                DB::raw('MONTH(paid_at) as mois'),
                DB::raw('SUM(amount) as total')
            )
            ->whereYear('paid_at', now()->year)
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->pluck('total', 'mois'); // => [1 => 15000, 2 => 20000, ...]

        $monthlyRevenueLabels = [];
        $monthlyRevenueData = [];

        // Génère les 12 mois (même ceux sans paiements = 0)
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create()->month($i)->translatedFormat('F');
            $monthlyRevenueLabels[] = $monthName;
            $monthlyRevenueData[] = $revenusParMois[$i] ?? 0; // 0 si pas de revenus pour ce mois
        }

        return view('reports.statistics', [
            'currentYear' => $currentYear,
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'totalTeachers' => $totalTeachers,
            'activeTeachers' => $activeTeachers,
            'totalClasses' => $totalClasses,
            'activeClasses' => $activeClasses,
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'paymentsCompleted' => $paymentsCompleted,
            'paymentsPending' => $paymentsPending,
            'paymentsCancelled' => $paymentsCancelled,
            'enrollmentPaymentStats' => $enrollmentPaymentStats,
            'enrollmentsByCycle' => $enrollmentsByCycle,
            'monthlyRevenueLabels' => $monthlyRevenueLabels,
            'monthlyRevenueData' => $monthlyRevenueData,
        ]);


    }
}


