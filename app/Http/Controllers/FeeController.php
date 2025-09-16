<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Rediriger vers le nouveau système de gestion des frais
        return redirect('/fees/dashboard');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classes = SchoolClass::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $feeTypes = [
            'tuition' => 'Scolarité',
            'registration' => 'Inscription',
            'uniform' => 'Uniforme',
            'transport' => 'Transport',
            'meal' => 'Repas',
            'other' => 'Autre'
        ];

        $frequencies = [
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'yearly' => 'Annuel',
            'one_time' => 'Unique'
        ];

        return view('fees.create', compact('classes', 'academicYears', 'feeTypes', 'frequencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log the incoming request data
        Log::info('Fee creation request data:', $request->all());
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'fee_type' => 'required|in:tuition,registration,uniform,transport,meal,other',
            'frequency' => 'required|in:monthly,quarterly,yearly,one_time',
            'class_id' => 'nullable|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'due_date' => 'nullable|date',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean'
        ]);

        // Définir les valeurs par défaut
        $validated['is_mandatory'] = $request->has('is_mandatory');
        $validated['is_active'] = $request->has('is_active');

        // Debug: Log the validated data
        Log::info('Fee creation validated data:', $validated);

        $fee = Fee::create($validated);

        return redirect()->route('fees.index')
            ->with('success', 'Frais créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $fee = Fee::with(['schoolClass', 'academicYear'])
            ->findOrFail($id);

        return view('fees.show', compact('fee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $fee = Fee::findOrFail($id);
        $classes = SchoolClass::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $feeTypes = [
            'tuition' => 'Scolarité',
            'registration' => 'Inscription',
            'uniform' => 'Uniforme',
            'transport' => 'Transport',
            'meal' => 'Repas',
            'other' => 'Autre'
        ];

        $frequencies = [
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'yearly' => 'Annuel',
            'one_time' => 'Unique'
        ];

        return view('fees.edit', compact('fee', 'classes', 'academicYears', 'feeTypes', 'frequencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $fee = Fee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'fee_type' => 'required|in:tuition,registration,uniform,transport,meal,other',
            'frequency' => 'required|in:monthly,quarterly,yearly,one_time',
            'class_id' => 'nullable|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'due_date' => 'nullable|date',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean'
        ]);

        // Définir les valeurs par défaut
        $validated['is_mandatory'] = $request->has('is_mandatory');
        $validated['is_active'] = $request->has('is_active');

        $fee->update($validated);

        return redirect()->route('fees.index')
            ->with('success', 'Frais mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $fee = Fee::findOrFail($id);
        
        // Note: Payments are now linked to enrollments, not directly to fees
        // So we can safely delete fees without checking for payments

        $fee->delete();

        return redirect()->route('fees.index')
            ->with('success', 'Frais supprimé avec succès.');
    }

    /**
     * Calculer les statistiques des frais
     */
    private function calculateFeeStats()
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            return [
                'totalFees' => 0,
                'totalAmount' => 0,
                'activeFees' => 0,
                'mandatoryFees' => 0
            ];
        }

        // Total des frais de l'année académique
        $totalFees = Fee::where('academic_year_id', $currentYear->id)->count();
        
        // Montant total des frais
        $totalAmount = Fee::where('academic_year_id', $currentYear->id)->sum('amount');
        
        // Frais actifs
        $activeFees = Fee::where('academic_year_id', $currentYear->id)
            ->where('is_active', true)
            ->count();
        
        // Frais obligatoires
        $mandatoryFees = Fee::where('academic_year_id', $currentYear->id)
            ->where('is_mandatory', true)
            ->count();

        return [
            'totalFees' => $totalFees,
            'totalAmount' => $totalAmount,
            'activeFees' => $activeFees,
            'mandatoryFees' => $mandatoryFees
        ];
    }
}
