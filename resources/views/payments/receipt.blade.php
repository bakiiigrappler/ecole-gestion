<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement - {{ $payment->transaction_id }}</title>
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
            background: white;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        /* En-tête de l'école */
        .school-header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .school-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: #2c3e50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .school-motto {
            font-size: 14px;
            color: #7f8c8d;
            font-style: italic;
        }

        .school-info {
            margin-top: 15px;
            font-size: 11px;
            color: #666;
        }

        /* Titre du reçu */
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Informations du reçu */
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .info-section h3 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #34495e;
        }

        .info-value {
            color: #2c3e50;
        }

        /* Détails du paiement */
        .payment-details {
            background: #fff;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .payment-details h3 {
            text-align: center;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .payment-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 14px;
            color: #27ae60;
        }

        .amount-highlight {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-value {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
        }

        /* Informations de l'étudiant */
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .student-info h3 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .student-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Signature et validation */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #bdc3c7;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }

        .signature-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #bdc3c7;
            font-size: 10px;
            color: #7f8c8d;
        }

        /* Badges de statut */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        /* Styles d'impression */
        @media print {
            body {
                font-size: 11px;
            }
            
            .receipt-container {
                max-width: none;
                margin: 0;
                padding: 15px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .receipt-container {
                box-shadow: none;
            }
        }

        /* Bouton d'impression */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .print-button:hover {
            background: #2980b9;
        }

        /* QR Code placeholder */
        .qr-code {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimer
    </button>

    <div class="receipt-container">
        <!-- En-tête de l'école -->
        <div class="school-header">
            <div class="school-logo">
                SG
            </div>
            <div class="school-name">Lycée StudiaGabon</div>
            <div class="school-motto">Excellence • Discipline • Réussite</div>
            <div class="school-info">
                <div>BP 1234 Libreville, Gabon</div>
                <div>Tél: +241 01 23 45 67 | Email: contact@studiagabon.ga</div>
                <div>Site web: www.studiagabon.ga</div>
            </div>
        </div>

        <!-- Titre du reçu -->
        <div class="receipt-title">Reçu de Paiement</div>

        <!-- Informations du reçu -->
        <div class="receipt-info">
            <div class="info-section">
                <h3>Informations du Reçu</h3>
                <div class="info-item">
                    <span class="info-label">N° de Reçu:</span>
                    <span class="info-value">{{ $payment->receipt_number ?: 'RCP' . date('Ymd') . strtoupper(substr($payment->transaction_id, -6)) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">N° de Transaction:</span>
                    <span class="info-value">{{ $payment->transaction_id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date d'émission:</span>
                    <span class="info-value">{{ $payment->created_at ? $payment->created_at->format('d/m/Y à H:i') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date de paiement:</span>
                    <span class="info-value">{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y à H:i') : 'En attente' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut:</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ $payment->status }}">
                            {{ $payment->status_label }}
                        </span>
                    </span>
                </div>
            </div>

            <div class="info-section">
                <h3>Informations du Payeur</h3>
                <div class="info-item">
                    <span class="info-label">Nom complet:</span>
                    <span class="info-value">{{ $payment->payer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Téléphone:</span>
                    <span class="info-value">{{ $payment->payer_phone }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $payment->payer_email ?: 'Non renseigné' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Méthode de paiement:</span>
                    <span class="info-value">{{ $payment->payment_method_label }}</span>
                </div>
            </div>
        </div>

        <!-- Détails du paiement -->
        <div class="payment-details">
            <h3>Détails du Paiement</h3>
            <div class="payment-grid">
                <div>
                    <div class="payment-item">
                        <span class="info-label">Type de paiement:</span>
                        <span class="info-value">{{ $payment->payment_type_label }}</span>
                    </div>
                    <div class="payment-item">
                        <span class="info-label">Devise:</span>
                        <span class="info-value">{{ $payment->currency ?: 'FCFA' }}</span>
                    </div>
                    @if($payment->gateway_transaction_id)
                    <div class="payment-item">
                        <span class="info-label">Référence passerelle:</span>
                        <span class="info-value">{{ $payment->gateway_transaction_id }}</span>
                    </div>
                    @endif
                </div>
                <div>
                    <div class="payment-item">
                        <span class="info-label">Montant:</span>
                        <span class="info-value">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="payment-item">
                        <span class="info-label">Frais de transaction:</span>
                        <span class="info-value">0 FCFA</span>
                    </div>
                    <div class="payment-item">
                        <span class="info-label">Total payé:</span>
                        <span class="info-value">{{ $payment->formatted_amount }}</span>
                    </div>
                </div>
            </div>
            
            <div class="amount-highlight">
                <div>Montant Total</div>
                <div class="amount-value">{{ $payment->formatted_amount }}</div>
            </div>
        </div>

        <!-- Informations de l'étudiant -->
        @if($payment->student)
        <div class="student-info">
            <h3>Informations de l'Étudiant</h3>
            <div class="student-details">
                <div>
                    <div class="info-item">
                        <span class="info-label">Nom complet:</span>
                        <span class="info-value">{{ $payment->student->first_name }} {{ $payment->student->last_name }}</span>
                    </div>
                    @if($payment->enrollment && $payment->enrollment->schoolClass)
                    <div class="info-item">
                        <span class="info-label">Classe:</span>
                        <span class="info-value">{{ $payment->enrollment->schoolClass->name }}</span>
                    </div>
                    @endif
                </div>
                <div>
                    @if($payment->enrollment && $payment->enrollment->academicYear)
                    <div class="info-item">
                        <span class="info-label">Année académique:</span>
                        <span class="info-value">{{ $payment->enrollment->academicYear->name }}</span>
                    </div>
                    @endif
                    <div class="info-item">
                        <span class="info-label">Statut inscription:</span>
                        <span class="info-value">{{ $payment->enrollment->status ?? 'Active' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($payment->notes)
        <div class="info-section">
            <h3>Notes</h3>
            <p>{{ $payment->notes }}</p>
        </div>
        @endif

        <!-- Signature et validation -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Signature du Caissier</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Signature du Payeur</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p><strong>Lycée StudiaGabon</strong> - Reçu généré le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>Ce reçu est valide et constitue une preuve de paiement officielle.</p>
            <p>En cas de problème, contactez l'administration au +241 01 23 45 67</p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
