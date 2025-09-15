<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu d'inscription #{{ $enrollment->receipt_number }}</title>
    <style>
        @page {
            size: A5;
            margin: 15mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 9px;
            color: #666;
        }
        
        .receipt-title {
            background: #007bff;
            color: white;
            text-align: center;
            padding: 8px;
            margin: 10px 0;
            font-weight: bold;
            font-size: 14px;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .info-section {
            margin-bottom: 12px;
        }
        
        .info-section h3 {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 3px 8px;
            margin: 0 0 5px 0;
            font-size: 11px;
            font-weight: bold;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .payment-box {
            background: #e7f3ff;
            border: 1px solid #007bff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        
        .amount {
            font-weight: bold;
            font-size: 12px;
        }
        
        .total-line {
            border-top: 1px solid #007bff;
            padding-top: 5px;
            margin-top: 5px;
            font-weight: bold;
            color: #007bff;
        }
        
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
        }
        
        .signature {
            margin-top: 15px;
            text-align: right;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            width: 150px;
            margin: 20px 0 5px auto;
        }
        
        strong { font-weight: bold; }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        @if($schoolSettings && $schoolSettings->school_logo)
            <img src="{{ $schoolSettings->logo_local_path }}" alt="Logo {{ $schoolSettings->school_name }}" style="max-height: 50px; margin-bottom: 10px;">
        @endif
        <h1>{{ $schoolSettings->school_name ?? 'Egesco' }}</h1>
        <p>{{ $schoolSettings->school_type ?? 'Système de Gestion Scolaire' }} - {{ $schoolSettings->city ?? 'Libreville' }}, {{ $schoolSettings->country ?? 'Gabon' }}</p>
        <p>Tél: {{ $schoolSettings->school_phone ?? '+241 XX XX XX XX' }}</p>
    </div>

    <!-- Titre du reçu -->
    <div class="receipt-title">REÇU D'INSCRIPTION</div>

    <!-- Informations du reçu -->
    <div class="receipt-info">
        <div>
            <strong>N° {{ $enrollment->receipt_number }}</strong><br>
            <strong>Date:</strong> {{ $enrollment->enrollment_date->format('d/m/Y') }}
        </div>
        <div style="text-align: right;">
            <strong>Année:</strong> {{ $enrollment->academicYear->name ?? 'N/A' }}<br>
            Statut: 
            @if($enrollment->payment_status === 'paid')
                <span class="status status-paid">PAYÉ</span>
            @elseif($enrollment->payment_status === 'partial')
                <span class="status status-partial">PARTIEL</span>
            @else
                <span class="status status-unpaid">NON PAYÉ</span>
            @endif
        </div>
    </div>

    <!-- Informations élève -->
    <div class="info-section">
        <h3>ÉLÈVE</h3>
        <div class="row">
            <span><strong>Nom:</strong> {{ $enrollment->applicant_first_name }} {{ $enrollment->applicant_last_name }}</span>
            <span><strong>Sexe:</strong> {{ $enrollment->applicant_gender === 'male' ? 'M' : 'F' }}</span>
        </div>
        <div class="row">
            <span><strong>Classe:</strong> {{ $enrollment->schoolClass->name ?? 'N/A' }}</span>
            <span><strong>Cycle:</strong> {{ ucfirst($enrollment->schoolClass->getSafeCycle() ?? 'N/A') }}</span>
        </div>
        @if($enrollment->applicant_date_of_birth)
        <div class="row">
            <span><strong>Né(e) le:</strong> {{ $enrollment->applicant_date_of_birth->format('d/m/Y') }}</span>
            <span></span>
        </div>
        @endif
    </div>

    <!-- Informations parent -->
    <div class="info-section">
        <h3>PARENT/TUTEUR</h3>
        <div class="row">
            <span><strong>Nom:</strong> {{ $enrollment->parent_first_name }} {{ $enrollment->parent_last_name }}</span>
            <span><strong>Lien:</strong> {{ $enrollment->getParentRelationshipLabelAttribute() }}</span>
        </div>
        <div class="row">
            <span><strong>Tél:</strong> {{ $enrollment->parent_phone }}</span>
            <span></span>
        </div>
    </div>

    <!-- Détails paiement -->
    <div class="payment-box">
        <h3 style="background: none; border: none; padding: 0; margin: 0 0 8px 0; color: #007bff;">PAIEMENT</h3>
        
        <div class="row">
            <span>Frais d'inscription:</span>
            <span class="amount">{{ number_format($enrollment->total_fees, 0, ',', ' ') }} FCFA</span>
        </div>
        
        <div class="row">
            <span>Montant payé:</span>
            <span class="amount">{{ number_format($enrollment->amount_paid, 0, ',', ' ') }} FCFA</span>
        </div>
        
        <div class="row total-line">
            <span><strong>Reste à payer:</strong></span>
            <span class="amount"><strong>{{ number_format($enrollment->balance_due, 0, ',', ' ') }} FCFA</strong></span>
        </div>

        @if($enrollment->payment_method)
        <div style="margin-top: 8px; font-size: 10px;">
            <strong>Mode:</strong> {{ $enrollment->getPaymentMethodLabelAttribute() }}
            @if($enrollment->payment_reference)
                | <strong>Réf:</strong> {{ $enrollment->payment_reference }}
            @endif
        </div>
        @endif

        @if($enrollment->payment_due_date && $enrollment->balance_due > 0)
        <div style="margin-top: 5px; padding: 3px; background: #fff3cd; border-radius: 2px; font-size: 9px;">
            <strong>⚠️ À régler avant le {{ $enrollment->payment_due_date->format('d/m/Y') }}</strong>
        </div>
        @endif
    </div>

    <!-- Signature -->
    <div class="signature">
        <div>Cachet et signature</div>
        <div class="signature-line"></div>
        <div style="font-size: 9px;">Administration</div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p><strong>Ce reçu fait foi de paiement - À conserver précieusement</strong></p>
        <p>{{ $schoolSettings->school_name ?? 'Egesco' }} - {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html> 
