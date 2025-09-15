@extends('layouts.app')

@section('title', 'Reçu d\'inscription - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Inscriptions</a></li>
<li class="breadcrumb-item"><a href="{{ route('enrollments.show', $enrollment) }}">Inscription #{{ $enrollment->id }}</a></li>
<li class="breadcrumb-item active">Reçu</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>
                        Reçu d'inscription
                    </h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm" onclick="printReceipt()">
                            <i class="bi bi-printer me-1"></i>
                            Imprimer
                        </button>
                        <a href="{{ route('enrollments.download-receipt', $enrollment) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-download me-1"></i>
                            Télécharger
                        </a>
                    </div>
                </div>
                <div class="card-body" id="receipt-content">
                    <!-- En-tête de l'établissement -->
                    <div class="text-center mb-4 border-bottom pb-3">
                        @if($schoolSettings && $schoolSettings->school_logo)
                            <img src="{{ $schoolSettings->logo_url }}" alt="Logo {{ $schoolSettings->school_name }}" class="mb-2" style="max-height: 60px;">
                        @endif
                        <h2 class="text-primary mb-1">{{ $schoolSettings->school_name ?? 'Egesco' }}</h2>
                        <p class="mb-1">{{ $schoolSettings->school_type ?? 'Système de Gestion Scolaire' }}</p>
                        <p class="mb-1 text-muted">{{ $schoolSettings->city ?? 'Libreville' }}, {{ $schoolSettings->country ?? 'Gabon' }}</p>
                        <p class="mb-0 text-muted">Tél: {{ $schoolSettings->school_phone ?? '+241 XX XX XX XX' }} | Email: {{ $schoolSettings->school_email ?? 'contact@Egesco.ga' }}</p>
                    </div>

                    <!-- Informations du reçu -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4 class="text-success mb-3">REÇU D'INSCRIPTION</h4>
                            <p><strong>Numéro :</strong> {{ $enrollment->receipt_number }}</p>
                            <p><strong>Date :</strong> {{ $enrollment->enrollment_date->format('d/m/Y') }}</p>
                            <p><strong>Année scolaire :</strong> {{ $enrollment->academicYear->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="border p-3 bg-light">
                                <h5 class="text-primary mb-2">Statut de paiement</h5>
                                {!! $enrollment->payment_status_badge !!}
                                @if($enrollment->payment_due_date)
                                    <br><small class="text-muted">Échéance : {{ $enrollment->payment_due_date->format('d/m/Y') }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Informations de l'inscrit -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-info border-bottom pb-2">Informations de l'inscrit</h5>
                            <p class="mb-1"><strong>Nom complet :</strong> {{ $enrollment->applicant_full_name }}</p>
                            <p class="mb-1"><strong>Date de naissance :</strong> {{ $enrollment->applicant_date_of_birth->format('d/m/Y') }}</p>
                            <p class="mb-1"><strong>Âge :</strong> {{ $enrollment->applicant_age }} ans</p>
                            <p class="mb-1"><strong>Sexe :</strong> {{ $enrollment->applicant_gender === 'male' ? 'Masculin' : 'Féminin' }}</p>
                            @if($enrollment->applicant_phone)
                                <p class="mb-1"><strong>Téléphone :</strong> {{ $enrollment->applicant_phone }}</p>
                            @endif
                            @if($enrollment->applicant_email)
                                <p class="mb-1"><strong>Email :</strong> {{ $enrollment->applicant_email }}</p>
                            @endif
                            <p class="mb-0"><strong>Adresse :</strong> {{ $enrollment->applicant_address }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-info border-bottom pb-2">Parent/Tuteur responsable</h5>
                            <p class="mb-1"><strong>Nom complet :</strong> {{ $enrollment->parent_full_name }}</p>
                            <p class="mb-1"><strong>Lien de parenté :</strong> {{ $enrollment->parent_relationship_label }}</p>
                            <p class="mb-1"><strong>Téléphone :</strong> {{ $enrollment->parent_phone }}</p>
                            @if($enrollment->parent_email)
                                <p class="mb-0"><strong>Email :</strong> {{ $enrollment->parent_email }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Informations scolaires -->
                    <div class="mb-4">
                        <h5 class="text-warning border-bottom pb-2">Informations scolaires</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Classe :</strong> {{ $enrollment->schoolClass->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Niveau :</strong> {{ $enrollment->schoolClass->level->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Cycle :</strong> {{ ucfirst($enrollment->schoolClass->level->cycle ?? 'N/A') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Détails financiers -->
                    <div class="mb-4">
                        <h5 class="text-success border-bottom pb-2">Détails financiers</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">Frais d'inscription total</td>
                                        <td class="text-end">{{ $enrollment->formatted_total_fees }}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td class="fw-bold">Montant payé</td>
                                        <td class="text-end fw-bold">{{ $enrollment->formatted_amount_paid }}</td>
                                    </tr>
                                    <tr class="{{ $enrollment->balance_due > 0 ? 'table-warning' : 'table-success' }}">
                                        <td class="fw-bold">Reste à percevoir</td>
                                        <td class="text-end fw-bold">
                                            {{ $enrollment->balance_due > 0 ? $enrollment->formatted_balance_due : '0 FCFA' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Informations de paiement -->
                    @if($enrollment->amount_paid > 0)
                    <div class="mb-4">
                        <h5 class="text-primary border-bottom pb-2">Informations de paiement</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Méthode de paiement :</strong> {{ $enrollment->payment_method_label }}</p>
                                @if($enrollment->payment_reference)
                                    <p class="mb-1"><strong>Référence :</strong> {{ $enrollment->payment_reference }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if($enrollment->payment_notes)
                                    <p class="mb-1"><strong>Notes :</strong> {{ $enrollment->payment_notes }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Notes générales -->
                    @if($enrollment->notes)
                    <div class="mb-4">
                        <h5 class="text-muted border-bottom pb-2">Notes</h5>
                        <p class="mb-0">{{ $enrollment->notes }}</p>
                    </div>
                    @endif

                    <!-- Pied de page -->
                    <div class="row mt-5 pt-4 border-top">
                        <div class="col-md-6">
                            <div class="border-top pt-3 text-center">
                                <p class="mb-0"><strong>Signature de l'administration</strong></p>
                                <br><br>
                                <p class="mb-0 text-muted">____________________</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border-top pt-3 text-center">
                                <p class="mb-0"><strong>Signature du parent/tuteur</strong></p>
                                <br><br>
                                <p class="mb-0 text-muted">____________________</p>
                            </div>
                        </div>
                    </div>

                    <!-- Informations importantes -->
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle me-2"></i>Informations importantes</h6>
                        <ul class="mb-0 small">
                            <li>Ce reçu fait foi de votre inscription pour l'année scolaire {{ $enrollment->academicYear->name ?? 'en cours' }}</li>
                            <li>Conservez précieusement ce document</li>
                            @if($enrollment->balance_due > 0)
                                <li class="text-warning"><strong>Solde restant à régler : {{ $enrollment->formatted_balance_due }}</strong></li>
                                @if($enrollment->payment_due_date)
                                    <li class="text-warning">Date limite de paiement : {{ $enrollment->payment_due_date->format('d/m/Y') }}</li>
                                @endif
                            @endif
                            <li>En cas de questions, contactez l'administration</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .card-header .btn {
        display: none !important;
    }
    
    .breadcrumb {
        display: none !important;
    }
    
    .navbar, .sidebar {
        display: none !important;
    }
    
    .container-fluid {
        padding: 0 !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
        border-bottom: 2px solid #000 !important;
    }
    
    .text-primary {
        color: #000 !important;
    }
    
    .text-success {
        color: #000 !important;
    }
    
    .text-warning {
        color: #000 !important;
    }
    
    .text-info {
        color: #000 !important;
    }
    
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
    }
}
</style>

<script>
function printReceipt() {
    // Ouvrir le PDF dans une nouvelle fenêtre pour impression
    const printWindow = window.open('{{ route("enrollments.download-receipt", $enrollment) }}', '_blank');
    
    // Attendre le chargement du PDF et déclencher l'impression
    if (printWindow) {
        printWindow.onload = function() {
            setTimeout(function() {
                printWindow.print();
            }, 1000); // Délai pour s'assurer que le PDF est complètement chargé
        };
    }
}
</script>
@endsection 
