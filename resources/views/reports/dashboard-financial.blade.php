<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Financier - {{ $schoolName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 3px solid #059669;
        }
        
        .header h1 {
            color: #059669;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        
        .export-info {
            text-align: right;
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            padding: 8px 15px;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
        }
        
        .stat-title {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }
        
        .stat-subtitle {
            font-size: 10px;
            color: #888;
            margin-top: 3px;
        }
        
        .financial-summary {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .financial-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e0f2fe;
        }
        
        .financial-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 14px;
        }
        
        .financial-label {
            color: #666;
            font-size: 12px;
        }
        
        .financial-amount {
            color: #059669;
            font-weight: bold;
            font-size: 13px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #059669, #10b981);
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background: #059669;
            color: white;
            padding: 8px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        
        .table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
        }
        
        .table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding: 10px;
            border-top: 1px solid #e9ecef;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-success {
            color: #059669;
        }
        
        .text-warning {
            color: #d97706;
        }
        
        .text-danger {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $schoolName }}</h1>
        <h2>Rapport Financier - Année {{ $currentYear->name ?? '2024-2025' }}</h2>
    </div>
    
    <div class="export-info">
        Généré le {{ $exportDate }} | Page 1
    </div>
    
    <!-- Résumé Financier -->
    <div class="section">
        <div class="section-title">💰 Résumé Financier</div>
        <div class="financial-summary">
            <div class="financial-item">
                <span class="financial-label">Total des Frais Attendus</span>
                <span class="financial-amount">{{ number_format($financialStats['total_fees'] ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Montant Collecté</span>
                <span class="financial-amount text-success">{{ number_format($financialStats['yearly_revenue'] ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Solde à Percevoir</span>
                <span class="financial-amount text-danger">{{ number_format($financialStats['balance_due'] ?? 0, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="financial-item">
                <span class="financial-label">Taux de Collecte</span>
                <span class="financial-amount">{{ $financialStats['collection_rate'] ?? 0 }}%</span>
            </div>
        </div>
        
        <!-- Barre de progression -->
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $financialStats['collection_rate'] ?? 0 }}%"></div>
        </div>
        <div class="progress-text">
            Progression de la collecte des frais scolaires
        </div>
    </div>
    
    <!-- Revenus par Période -->
    <div class="section">
        <div class="section-title">📅 Revenus par Période</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Revenus Mensuels</div>
                <div class="stat-value">{{ number_format($financialStats['monthly_revenue'] ?? 0, 0, ',', ' ') }}</div>
                <div class="stat-subtitle">FCFA - {{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Revenus Annuels</div>
                <div class="stat-value">{{ number_format($financialStats['yearly_revenue'] ?? 0, 0, ',', ' ') }}</div>
                <div class="stat-subtitle">FCFA - {{ $currentYear->name ?? '2024-2025' }}</div>
            </div>
        </div>
    </div>
    
    <!-- Statut des Paiements -->
    <div class="section">
        <div class="section-title">💳 Statut des Paiements</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Paiements Complets</div>
                <div class="stat-value text-success">{{ number_format($financialStats['paid_count'] ?? 0) }}</div>
                <div class="stat-subtitle">
                    @php
                        $totalPayments = ($financialStats['paid_count'] ?? 0) + ($financialStats['partial_count'] ?? 0) + ($financialStats['unpaid_count'] ?? 0);
                        $paidPercentage = $totalPayments > 0 ? round((($financialStats['paid_count'] ?? 0) / $totalPayments) * 100, 1) : 0;
                    @endphp
                    {{ $paidPercentage }}% du total
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Paiements Partiels</div>
                <div class="stat-value text-warning">{{ number_format($financialStats['partial_count'] ?? 0) }}</div>
                <div class="stat-subtitle">
                    @php
                        $partialPercentage = $totalPayments > 0 ? round((($financialStats['partial_count'] ?? 0) / $totalPayments) * 100, 1) : 0;
                    @endphp
                    {{ $partialPercentage }}% du total
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Non Payés</div>
                <div class="stat-value text-danger">{{ number_format($financialStats['unpaid_count'] ?? 0) }}</div>
                <div class="stat-subtitle">
                    @php
                        $unpaidPercentage = $totalPayments > 0 ? round((($financialStats['unpaid_count'] ?? 0) / $totalPayments) * 100, 1) : 0;
                    @endphp
                    {{ $unpaidPercentage }}% du total
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Transactions</div>
                <div class="stat-value">{{ number_format($totalPayments) }}</div>
                <div class="stat-subtitle">Inscriptions traitées</div>
            </div>
        </div>
    </div>
    
    <!-- Analyse de Performance -->
    <div class="section">
        <div class="section-title">📈 Analyse de Performance</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Montant Moyen par Inscription</div>
                <div class="stat-value">
                    @php
                        $avgAmount = $totalPayments > 0 ? round(($financialStats['yearly_revenue'] ?? 0) / $totalPayments) : 0;
                    @endphp
                    {{ number_format($avgAmount, 0, ',', ' ') }}
                </div>
                <div class="stat-subtitle">FCFA par élève</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Potentiel de Recouvrement</div>
                <div class="stat-value text-warning">
                    @php
                        $recoveryPotential = $financialStats['balance_due'] ?? 0;
                        $recoveryPercentage = $financialStats['total_fees'] > 0 ? round(($recoveryPotential / $financialStats['total_fees']) * 100, 1) : 0;
                    @endphp
                    {{ $recoveryPercentage }}%
                </div>
                <div class="stat-subtitle">du total attendu</div>
            </div>
        </div>
    </div>
    
    <!-- Recommandations -->
    <div class="section">
        <div class="section-title">💡 Recommandations</div>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #059669;">
            @if(($financialStats['collection_rate'] ?? 0) < 70)
                <p style="margin-bottom: 10px; font-size: 11px;"><strong>⚠️ Attention :</strong> Le taux de collecte est en dessous de 70%. Il est recommandé de :</p>
                <ul style="margin-left: 20px; font-size: 11px; color: #666;">
                    <li>Contacter les familles en retard de paiement</li>
                    <li>Mettre en place un plan d'échelonnement des paiements</li>
                    <li>Organiser des séances d'information sur les modalités de paiement</li>
                </ul>
            @elseif(($financialStats['collection_rate'] ?? 0) < 90)
                <p style="margin-bottom: 10px; font-size: 11px;"><strong>✅ Bon :</strong> Le taux de collecte est satisfaisant. Pour l'améliorer :</p>
                <ul style="margin-left: 20px; font-size: 11px; color: #666;">
                    <li>Suivre régulièrement les paiements partiels</li>
                    <li>Encourager les paiements anticipés</li>
                </ul>
            @else
                <p style="margin-bottom: 10px; font-size: 11px;"><strong>🎉 Excellent :</strong> Le taux de collecte est excellent ! Continuez sur cette lancée.</p>
            @endif
        </div>
    </div>
    
    <div class="footer">
        <p>Rapport financier généré automatiquement par le système de gestion scolaire Egesco | {{ $exportDate }}</p>
    </div>
</body>
</html>
