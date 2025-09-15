<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi - Egesco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --secondary-blue: #1e40af;
            --accent-cyan: #06b6d4;
            --success-green: #059669;
            --warning-orange: #d97706;
            --danger-red: #dc2626;
            --dark-color: #1e293b;
            --light-color: #ffffff;
            --light-gray: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, var(--success-green), #047857);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .success-card {
            background: var(--light-color);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }

        .success-header {
            background: linear-gradient(135deg, var(--success-green), #047857);
            color: var(--light-color);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            position: relative;
            z-index: 1;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .success-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .success-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .success-body {
            padding: 3rem;
        }

        .receipt-card {
            background: var(--light-gray);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid var(--success-green);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .receipt-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success-green);
            margin-bottom: 0.5rem;
        }

        .receipt-number {
            font-size: 1.1rem;
            color: #6c757d;
        }

        .receipt-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .receipt-item {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
        }

        .receipt-item .label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .receipt-item .value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .amount-section {
            text-align: center;
            margin: 2rem 0;
            padding: 2rem;
            background: var(--light-color);
            border-radius: 0.75rem;
            border: 2px dashed var(--success-green);
        }

        .amount-label {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .amount-value {
            font-size: 3rem;
            font-weight: 700;
            color: var(--success-green);
            margin-bottom: 0.5rem;
        }

        .amount-currency {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .next-steps {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid var(--accent-cyan);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .next-steps h4 {
            color: var(--accent-cyan);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .next-steps h4 i {
            margin-right: 0.75rem;
        }

        .steps-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .steps-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }

        .steps-list li i {
            color: var(--success-green);
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-success {
            border: 2px solid var(--success-green);
            color: var(--success-green);
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-success:hover {
            background: var(--success-green);
            color: var(--light-color);
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--light-color);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .success-body {
                padding: 2rem 1rem;
            }
            
            .success-header {
                padding: 2rem 1rem;
            }
            
            .success-header h1 {
                font-size: 2rem;
            }
            
            .amount-value {
                font-size: 2rem;
            }
            
            .receipt-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-header">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h1>Paiement réussi !</h1>
                <p>Votre inscription a été confirmée avec succès</p>
            </div>
            
            <div class="success-body">
                <!-- Reçu de paiement -->
                <div class="receipt-card">
                    <div class="receipt-header">
                        <div class="receipt-title">Reçu de paiement</div>
                        <div class="receipt-number">Transaction #{{ $payment->transaction_id }}</div>
                    </div>
                    
                    <div class="receipt-details">
                        <div class="receipt-item">
                            <div class="label">Date de paiement</div>
                            <div class="value">{{ $payment->paid_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="receipt-item">
                            <div class="label">Payeur</div>
                            <div class="value">{{ $payment->payer_name }}</div>
                        </div>
                        <div class="receipt-item">
                            <div class="label">Téléphone</div>
                            <div class="value">{{ $payment->payer_phone }}</div>
                        </div>
                        <div class="receipt-item">
                            <div class="label">Méthode de paiement</div>
                            <div class="value">{{ $payment->payment_method_label }}</div>
                        </div>
                        <div class="receipt-item">
                            <div class="label">Type de paiement</div>
                            <div class="value">{{ $payment->payment_type_label }}</div>
                        </div>
                        <div class="receipt-item">
                            <div class="label">Référence</div>
                            <div class="value">{{ $payment->gateway_transaction_id }}</div>
                        </div>
                    </div>
                    
                    <div class="amount-section">
                        <div class="amount-label">Montant payé</div>
                        <div class="amount-value">{{ number_format($payment->amount, 0, ',', ' ') }}</div>
                        <div class="amount-currency">FCFA</div>
                    </div>
                </div>

                <!-- Prochaines étapes -->
                <div class="next-steps">
                    <h4><i class="bi bi-info-circle"></i>Prochaines étapes</h4>
                    <ul class="steps-list">
                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Un email de confirmation vous a été envoyé</span>
                        </li>
                        <li>
                            <i class="bi bi-clock"></i>
                            <span>L'école traitera votre inscription sous 24-48h</span>
                        </li>
                        <li>
                            <i class="bi bi-phone"></i>
                            <span>Vous recevrez un appel pour confirmer les détails</span>
                        </li>
                        <li>
                            <i class="bi bi-calendar-check"></i>
                            <span>La date de rentrée vous sera communiquée par SMS</span>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                    <button class="btn btn-outline-success" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>
                        Imprimer le reçu
                    </button>
                    
                    <a href="{{ route('parent-portal.login') }}" class="btn btn-primary">
                        <i class="bi bi-person me-2"></i>
                        Accéder au portail parent
                    </a>
                    
                    <a href="{{ route('parent-portal.online-enrollment') }}" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus me-2"></i>
                        Inscrire un autre enfant
                    </a>
                </div>

                <!-- Informations de contact -->
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="bi bi-telephone me-2"></i>
                        Pour toute question : <strong>+241 XX XX XX XX</strong>
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-envelope me-2"></i>
                        Email : <strong>contact@Egesco.com</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
