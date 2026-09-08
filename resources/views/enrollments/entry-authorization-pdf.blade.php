<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorisation d'Entrée - {{ $enrollment->enrollment_code }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 9px;
            line-height: 1.2;
            color: #333;
        }
        
        .container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header p {
            margin: 1px 0;
            font-size: 7px;
            color: #666;
        }
        
        .title-box {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            text-align: center;
            padding: 6px;
            margin: 5px 0;
            font-weight: bold;
            font-size: 13px;
            border-radius: 3px;
        }
        
        .content {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex: 1;
        }
        
        .left-section {
            flex: 1;
            padding-right: 15px;
        }
        
        .right-section {
            width: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border-left: 2px solid #007bff;
            padding-left: 15px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 5px 8px;
            margin-bottom: 5px;
            border-radius: 2px;
        }
        
        .info-box h3 {
            margin: 0 0 3px 0;
            font-size: 9px;
            color: #007bff;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
            font-size: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-nouveau { background: #cfe2ff; color: #084298; }
        .badge-passant { background: #d1e7dd; color: #0f5132; }
        .badge-redoublant { background: #fff3cd; color: #664d03; }
        .badge-reinscription { background: #e7f3ff; color: #0056b3; }
        .badge-nouvelle { background: #d1e7dd; color: #0f5132; }
        
        .qr-section {
            text-align: center;
        }
        
        .qr-code {
            border: 3px solid #007bff;
            padding: 5px;
            background: white;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        
        .enrollment-code {
            font-weight: bold;
            font-size: 11px;
            color: #007bff;
            margin-top: 5px;
            word-break: break-all;
        }
        
        .validity-box {
            background: #d1e7dd;
            border: 2px solid #0f5132;
            padding: 8px;
            margin-top: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .validity-box h4 {
            margin: 0 0 3px 0;
            color: #0f5132;
            font-size: 10px;
        }
        
        .validity-box p {
            margin: 0;
            font-weight: bold;
            font-size: 11px;
            color: #0f5132;
        }
        
        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
        }
        
        .photo-placeholder {
            width: 70px;
            height: 85px;
            border: 2px solid #007bff;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #999;
            margin: 0 auto 5px;
            border-radius: 3px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            width: 120px;
            margin: 15px auto 3px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 3px solid #ffc107;
            padding: 4px 6px;
            margin-top: 5px;
            font-size: 7px;
            border-radius: 2px;
        }
        
        .authorization-text {
            background: #d1e7dd;
            border: 2px solid #0f5132;
            padding: 10px;
            margin: 15px 0;
            border-radius: 3px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #0f5132;
            line-height: 1.4;
        }
        
        strong { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            @if($schoolSettings && $schoolSettings->school_logo)
                <img src="{{ $schoolSettings->logo_base64 ?? '' }}" alt="Logo" style="max-height: 40px; margin-bottom: 5px;">
            @endif
            <h1>{{ $schoolSettings->school_name ?? 'Egesco' }}</h1>
            <p>{{ $schoolSettings->school_type ?? 'Système de Gestion Scolaire' }} - {{ $schoolSettings->city ?? 'Libreville' }}, {{ $schoolSettings->country ?? 'Gabon' }}</p>
            <p>Tél: {{ $schoolSettings->school_phone ?? '+241 XX XX XX XX' }} | Email: {{ $schoolSettings->school_email ?? 'contact@ecole.ga' }}</p>
        </div>

        <!-- Titre -->
        <div class="title-box">
            🎓 AUTORISATION D'ENTRÉE - ANNÉE SCOLAIRE {{ $enrollment->academicYear->name ?? 'N/A' }}
        </div>

        <!-- Contenu principal -->
        <div class="content">
            <!-- Section gauche - Informations de l'élève uniquement -->
            <div class="left-section">
                <!-- Informations élève -->
                <div class="info-box">
                    <h3>👤 Informations de l'Élève</h3>
                    <div class="info-row">
                        <span class="info-label">Nom complet :</span>
                        <span class="info-value"><strong>{{ $enrollment->applicant_first_name }} {{ $enrollment->applicant_last_name }}</strong></span>
                    </div>
                    @if($enrollment->student && $enrollment->student->student_id)
                    <div class="info-row">
                        <span class="info-label">Matricule :</span>
                        <span class="info-value"><strong>{{ $enrollment->student->student_id }}</strong></span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Date de naissance :</span>
                        <span class="info-value">{{ $enrollment->identite_naissance?->format('d/m/Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sexe :</span>
                        <span class="info-value">{{ $enrollment->applicant_gender === 'male' ? 'Masculin' : 'Féminin' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Classe :</span>
                        <span class="info-value"><strong>{{ $enrollment->schoolClass->name ?? 'N/A' }}</strong> ({{ ucfirst($enrollment->schoolClass->getSafeCycle() ?? 'N/A') }})</span>
                    </div>
                    @if($enrollment->student_status)
                    <div class="info-row">
                        <span class="info-label">Statut :</span>
                        <span class="info-value">
                            @if($enrollment->student_status === 'nouveau')
                                <span class="badge badge-nouveau">NOUVEAU</span>
                            @elseif($enrollment->student_status === 'passant')
                                <span class="badge badge-passant">PASSANT</span>
                            @elseif($enrollment->student_status === 'redoublant')
                                <span class="badge badge-redoublant">REDOUBLANT</span>
                            @endif
                        </span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Type d'inscription :</span>
                        <span class="info-value">
                            @if($enrollment->is_reinscription)
                                <span class="badge badge-reinscription">RÉINSCRIPTION</span>
                            @else
                                <span class="badge badge-nouvelle">NOUVELLE INSCRIPTION</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date d'inscription :</span>
                        <span class="info-value">{{ $enrollment->enrollment_date?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Reçu N° :</span>
                        <span class="info-value"><strong>{{ $enrollment->receipt_number }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Code d'inscription :</span>
                        <span class="info-value"><strong>{{ $enrollment->enrollment_code }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Section droite - QR Code et Autorisation -->
            <div class="right-section">
                <!-- Code d'inscription visuel (sans QR code externe) -->
                <div class="qr-section">
                    <!-- Simuler un QR code avec le code d'inscription -->
                    <div style="width: 150px; height: 150px; border: 3px solid #007bff; background: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; box-sizing: border-box;">
                        <div style="font-size: 8px; color: #007bff; margin-bottom: 5px; font-weight: bold;">CODE D'INSCRIPTION</div>
                        <div style="font-size: 14px; font-weight: bold; color: #000; text-align: center; letter-spacing: 1px; margin: 5px 0;">
                            {{ $enrollment->enrollment_code }}
                        </div>
                        <div style="width: 100%; height: 60px; background: repeating-linear-gradient(0deg, #000 0px, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 6px, #fff 6px, #fff 8px); margin: 10px 0;"></div>
                        <div style="font-size: 7px; color: #666; text-align: center;">Scanner ce code<br>pour vérifier</div>
                    </div>
                    <div class="enrollment-code" style="margin-top: 8px;">
                        {{ $enrollment->enrollment_code }}
                    </div>
                </div>

                <!-- Texte d'autorisation -->
                <div class="authorization-text">
                    ✓ L'ÉLÈVE EST AUTORISÉ(E)<br>
                    À COMMENCER LES COURS<br>
                    POUR L'ANNÉE SCOLAIRE<br>
                    {{ $enrollment->academicYear->name ?? 'N/A' }}
                </div>

                <!-- Signature -->
                <div style="margin-top: 15px; font-size: 7px; text-align: center; width: 100%;">
                    <div>Cachet et Signature</div>
                    <div style="border-bottom: 1px solid #333; width: 120px; margin: 10px auto 2px;"></div>
                    <div style="color: #666;">Direction</div>
                </div>
            </div>
        </div>

        <!-- Pied de page compact -->
        <div style="margin-top: 8px; text-align: center; font-size: 6px; color: #666; border-top: 1px solid #dee2e6; padding-top: 3px;">
            <p style="margin: 1px 0;"><strong>Ce document est obligatoire pour accéder à l'établissement - À conserver précieusement</strong></p>
            <p style="margin: 1px 0;">{{ $schoolSettings->school_name ?? 'Egesco' }} - Généré le {{ now()->format('d/m/Y') }}
            @if($enrollment->is_reinscription)
                | ✓ Réinscription
            @endif
            </p>
        </div>
    </div>
</body>
</html>

