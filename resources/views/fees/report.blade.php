@extends('layouts.app')

@section('title', 'Rapport des Frais - StudiaGabon')

@section('breadcrumb')
<li class="breadcrumb-item active">Frais Scolaires</li>
<li class="breadcrumb-item active">Rapport</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Rapport des Frais Scolaires</h1>
                    <p class="text-muted">Rapport détaillé pour {{ $academicYear->name }}</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Imprimer
                    </button>
                    <a href="{{ route('fees.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-funnel me-2"></i>Filtres
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('fees.report') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="fee_type" class="form-label">Type de frais</label>
                                <select class="form-select" id="fee_type" name="fee_type">
                                    <option value="">Tous les types</option>
                                    <option value="tuition" {{ request('fee_type') == 'tuition' ? 'selected' : '' }}>Scolarité</option>
                                    <option value="registration" {{ request('fee_type') == 'registration' ? 'selected' : '' }}>Inscription</option>
                                    <option value="transport" {{ request('fee_type') == 'transport' ? 'selected' : '' }}>Transport</option>
                                    <option value="uniform" {{ request('fee_type') == 'uniform' ? 'selected' : '' }}>Uniforme</option>
                                    <option value="other" {{ request('fee_type') == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="is_paid" class="form-label">Statut de paiement</label>
                                <select class="form-select" id="is_paid" name="is_paid">
                                    <option value="">Tous les statuts</option>
                                    <option value="1" {{ request('is_paid') == '1' ? 'selected' : '' }}>Payé</option>
                                    <option value="0" {{ request('is_paid') == '0' ? 'selected' : '' }}>Non payé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="is_overdue" class="form-label">Échéance</label>
                                <select class="form-select" id="is_overdue" name="is_overdue">
                                    <option value="">Toutes les échéances</option>
                                    <option value="1" {{ request('is_overdue') == '1' ? 'selected' : '' }}>Échus</option>
                                    <option value="0" {{ request('is_overdue') == '0' ? 'selected' : '' }}>En cours</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="class_id" class="form-label">Classe</label>
                                <select class="form-select" id="class_id" name="class_id">
                                    <option value="">Toutes les classes</option>
                                    @foreach($report['classes'] ?? [] as $class)
                                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Filtrer
                                </button>
                                <a href="{{ route('fees.report') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Effacer
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques du rapport -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-dark">Total des frais</h6>
                            <h3 class="mb-0 text-dark">{{ number_format($report['statistics']['total_fees'] ?? 0, 0, ',', ' ') }} FCFA</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-cash-stack fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-dark">Montant collecté</h6>
                            <h3 class="mb-0 text-dark">{{ number_format($report['statistics']['total_collected'] ?? 0, 0, ',', ' ') }} FCFA</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-dark">Montant en attente</h6>
                            <h3 class="mb-0 text-dark">{{ number_format($report['statistics']['total_pending'] ?? 0, 0, ',', ' ') }} FCFA</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock fs-1 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-dark">Taux de collecte</h6>
                            <h3 class="mb-0 text-dark">{{ number_format($report['statistics']['collection_rate'] ?? 0, 1) }}%</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des frais -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>Détail des Frais
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($report['enrollment_fees']) && $report['enrollment_fees']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Classe</th>
                                        <th>Type de frais</th>
                                        <th>Montant</th>
                                        <th>Date d'échéance</th>
                                        <th>Statut</th>
                                        <th>Montant payé</th>
                                        <th>Reste à payer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report['enrollment_fees'] as $fee)
                                        <tr class="{{ $fee->is_overdue() ? 'table-danger' : '' }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        {{ substr($fee->enrollment->student->first_name ?? 'A', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $fee->enrollment->student->first_name ?? 'N/A' }} {{ $fee->enrollment->student->last_name ?? 'N/A' }}</div>
                                                        <small class="text-muted">{{ $fee->enrollment->student->matricule ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $fee->enrollment->schoolClass->name ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ ucfirst($fee->fee_type ?? 'N/A') }}</span>
                                            </td>
                                            <td class="fw-bold">{{ number_format($fee->amount ?? 0, 0, ',', ' ') }} FCFA</td>
                                            <td>
                                                @if($fee->due_date)
                                                    <span class="{{ $fee->is_overdue() ? 'text-danger fw-bold' : 'text-muted' }}">
                                                        {{ $fee->due_date->format('d/m/Y') }}
                                                    </span>
                                                    @if($fee->is_overdue())
                                                        <br><small class="text-danger">En retard de {{ $fee->days_overdue }} jour(s)</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Non défini</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($fee->is_paid)
                                                    <span class="badge bg-success">Payé</span>
                                                @else
                                                    <span class="badge bg-warning">En attente</span>
                                                @endif
                                            </td>
                                            <td class="text-success">{{ number_format($fee->paid_amount ?? 0, 0, ',', ' ') }} FCFA</td>
                                            <td class="text-danger">{{ number_format($fee->remaining_amount ?? 0, 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4 class="text-muted mt-3">Aucun frais trouvé</h4>
                            <p class="text-muted">Aucun frais ne correspond aux critères de recherche.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<style>
@media print {
    .btn, .card-header, .breadcrumb {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}
</style>
@endsection
