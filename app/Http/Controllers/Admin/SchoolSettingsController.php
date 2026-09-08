<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SchoolSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Cette vue etait 'admin.settings.index', partagee avec
        // SettingsController qui y passe d'autres variables : les champs du
        // formulaire arrivaient vides selon la porte d'entree.
        $settings = SchoolSettings::getSettings();

        return view('admin.settings.etablissement', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.settings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => 'nullable|string|max:255',
            'primary_school_name' => 'required|string|max:255',
            'secondary_school_name' => 'required|string|max:255',
            'school_address' => 'nullable|string|max:500',
            'school_phone' => 'required|string|max:50',
            'school_email' => 'nullable|email|max:255',
            'school_website' => 'nullable|url|max:255',
            'school_bp' => 'required|string|max:100',
            'school_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_seal' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_motto' => 'nullable|string|max:500',
            'school_description' => 'nullable|string|max:1000',
            'principal_name' => 'nullable|string|max:255',
            'principal_title' => 'required|string|max:255',
            'academic_year' => 'required|string|max:50',
            'school_type' => 'nullable|string|max:100',
            'school_level' => 'nullable|string|max:100',
            'has_primary' => 'nullable|boolean',
            'has_secondary' => 'nullable|boolean',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'timezone' => 'required|string|max:100',
            'currency' => 'required|string|max:10',
            'language' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Désactiver tous les paramètres existants
        SchoolSettings::where('is_active', true)->update(['is_active' => false]);

        $data = $request->except(['school_logo', 'school_seal']);

        // Gérer le logo
        if ($request->hasFile('school_logo')) {
            $logoPath = $request->file('school_logo')->store('school', 'public');
            $data['school_logo'] = $logoPath;
        }

        // Gérer le sceau
        if ($request->hasFile('school_seal')) {
            $sealPath = $request->file('school_seal')->store('school', 'public');
            $data['school_seal'] = $sealPath;
        }

        $data['is_active'] = true;

        SchoolSettings::create($data);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Paramètres de l\'établissement mis à jour avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SchoolSettings $schoolSetting)
    {
        return view('admin.settings.show', compact('schoolSetting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolSettings $schoolSetting)
    {
        // 'admin.settings.edit' n'existe pas : le formulaire est le meme.
        return view('admin.settings.etablissement', ['settings' => $schoolSetting]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SchoolSettings $schoolSetting)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => 'nullable|string|max:255',
            'primary_school_name' => 'required|string|max:255',
            'secondary_school_name' => 'required|string|max:255',
            'school_address' => 'nullable|string|max:500',
            'school_phone' => 'required|string|max:50',
            'school_email' => 'nullable|email|max:255',
            'school_website' => 'nullable|url|max:255',
            'school_bp' => 'required|string|max:100',
            'school_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_seal' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'school_motto' => 'nullable|string|max:500',
            'school_description' => 'nullable|string|max:1000',
            'principal_name' => 'nullable|string|max:255',
            'principal_title' => 'required|string|max:255',
            'academic_year' => 'required|string|max:50',
            'school_type' => 'nullable|string|max:100',
            'school_level' => 'nullable|string|max:100',
            'has_primary' => 'nullable|boolean',
            'has_secondary' => 'nullable|boolean',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'timezone' => 'required|string|max:100',
            'currency' => 'required|string|max:10',
            'language' => 'required|string|max:10',
            /*
             * Paiement par telephone. Le code marchand n'est pas le meme d'un
             * etablissement a l'autre : sans lui, le portail parent ne peut
             * rien afficher.
             */
            'mobile_money_actif' => 'nullable|boolean',
            'airtel_money_actif' => 'nullable|boolean',
            'airtel_money_code' => 'nullable|string|max:60',
            'airtel_money_numero' => 'nullable|string|max:40',
            'airtel_money_nom' => 'nullable|string|max:120',
            'moov_money_actif' => 'nullable|boolean',
            'moov_money_code' => 'nullable|string|max:60',
            'moov_money_numero' => 'nullable|string|max:40',
            'moov_money_nom' => 'nullable|string|max:120',
            'mobile_money_consignes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['school_logo', 'school_seal']);

        // Une case decochee n'est pas postee : sans cela, ni le paiement par
        // telephone ni l'un de ses operateurs ne pourrait etre referme une fois
        // ouvert.
        $data['mobile_money_actif'] = $request->boolean('mobile_money_actif');

        foreach (\App\Support\MobileMoney::OPERATEURS as $operateur) {
            $data[$operateur['champ_actif']] = $request->boolean($operateur['champ_actif']);
        }

        // Gérer le logo
        if ($request->hasFile('school_logo')) {
            // Supprimer l'ancien logo
            if ($schoolSetting->school_logo) {
                Storage::disk('public')->delete($schoolSetting->school_logo);
            }
            $logoPath = $request->file('school_logo')->store('school', 'public');
            $data['school_logo'] = $logoPath;
        }

        // Gérer le sceau
        if ($request->hasFile('school_seal')) {
            // Supprimer l'ancien sceau
            if ($schoolSetting->school_seal) {
                Storage::disk('public')->delete($schoolSetting->school_seal);
            }
            $sealPath = $request->file('school_seal')->store('school', 'public');
            $data['school_seal'] = $sealPath;
        }

        $schoolSetting->update($data);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Paramètres de l\'établissement mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolSettings $schoolSetting)
    {
        // Supprimer les fichiers
        if ($schoolSetting->school_logo) {
            Storage::disk('public')->delete($schoolSetting->school_logo);
        }
        if ($schoolSetting->school_seal) {
            Storage::disk('public')->delete($schoolSetting->school_seal);
        }

        $schoolSetting->delete();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Paramètres supprimés avec succès.');
    }

    /**
     * Aperçu des paramètres
     */
    public function preview()
    {
        $settings = SchoolSettings::getSettings();
        return view('admin.settings.preview', compact('settings'));
    }
}
