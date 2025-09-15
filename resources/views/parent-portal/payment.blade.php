<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Egesco</title>
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
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .payment-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .payment-card {
            background: var(--light-color);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--light-color);
            padding: 2rem;
            text-align: center;
        }

        .payment-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .payment-body {
            padding: 3rem;
        }

        .payment-summary {
            background: var(--light-gray);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            text-align: center;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
        }

        .detail-item .label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .detail-item .value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .amount-display {
            text-align: center;
            margin: 2rem 0;
        }

        .amount-display .amount {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .amount-display .currency {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .gateway-info {
            background: var(--light-gray);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .gateway-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .gateway-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .gateway-description {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .payment-form {
            background: var(--light-gray);
            border-radius: 0.75rem;
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
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

        .loading-spinner {
            display: none;
            text-align: center;
            margin: 2rem 0;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .security-info {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid var(--accent-cyan);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
        }

        .security-info i {
            color: var(--accent-cyan);
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .payment-body {
                padding: 2rem 1rem;
            }
            
            .payment-header {
                padding: 1.5rem 1rem;
            }
            
            .payment-header h1 {
                font-size: 1.5rem;
            }
            
            .amount-display .amount {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h1><i class="bi bi-credit-card me-3"></i>Paiement sécurisé</h1>
                <p>Transaction #{{ $payment->transaction_id }}</p>
            </div>
            
            <div class="payment-body">
                <!-- Résumé du paiement -->
                <div class="payment-summary">
                    <h3 class="mb-3">Résumé de la transaction</h3>
                    
                    <div class="payment-details">
                        <div class="detail-item">
                            <div class="label">Type de paiement</div>
                            <div class="value">{{ $payment->payment_type_label }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Payeur</div>
                            <div class="value">{{ $payment->payer_name }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Téléphone</div>
                            <div class="value">{{ $payment->payer_phone }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Date</div>
                            <div class="value">{{ $payment->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    
                    <div class="amount-display">
                        <div class="amount">{{ number_format($payment->amount, 0, ',', ' ') }}</div>
                        <div class="currency">FCFA</div>
                    </div>
                </div>

                <!-- Informations de la passerelle -->
                <div class="gateway-info">
                    <div class="gateway-icon">
                        @if($payment->payment_method == 'moov_money')
                            <i class="bi bi-phone" style="color: #007BFF;"></i>
                        @elseif($payment->payment_method == 'airtel_money')
                            <i class="bi bi-phone" style="color: #DC3545;"></i>
                        @endif
                    </div>
                    <div class="gateway-name">{{ $gateway->name }}</div>
                    <div class="gateway-description">{{ $gateway->description }}</div>
                    
                    <div class="row text-center">
                        <div class="col-md-4">
                            <small class="text-muted">Frais fixes</small>
                            <div class="fw-bold">{{ number_format($gateway->fixed_fee, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Commission</small>
                            <div class="fw-bold">{{ $gateway->transaction_fee }}%</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Total à payer</small>
                            <div class="fw-bold text-primary">{{ number_format($gateway->getTotalAmount($payment->amount), 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiement -->
                <div class="payment-form">
                    <h4 class="mb-3">Confirmer le paiement</h4>
                    
                    <form id="paymentForm" method="POST" action="{{ route('parent-portal.process-payment', $payment->transaction_id) }}">
                        @csrf
                        
                        <div class="form-floating">
                            <input type="tel" class="form-control" id="confirm_phone" name="confirm_phone" 
                                   placeholder="Numéro de téléphone" value="{{ $payment->payer_phone }}" required>
                            <label for="confirm_phone">
                                <i class="bi bi-phone me-2"></i>
                                Numéro de téléphone
                            </label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="text" class="form-control" id="pin_code" name="pin_code" 
                                   placeholder="Code PIN" maxlength="4" required>
                            <label for="pin_code">
                                <i class="bi bi-lock me-2"></i>
                                Code PIN (4 chiffres)
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="payButton">
                                <i class="bi bi-credit-card me-2"></i>
                                Payer {{ number_format($gateway->getTotalAmount($payment->amount), 0, ',', ' ') }} FCFA
                            </button>
                            
                            <a href="{{ route('parent-portal.online-enrollment') }}" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Annuler
                            </a>
                        </div>
                    </form>
                    
                    <!-- Spinner de chargement -->
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Traitement du paiement en cours...</p>
                    </div>
                    
                    <!-- Informations de sécurité -->
                    <div class="security-info">
                        <i class="bi bi-shield-check"></i>
                        <strong>Paiement sécurisé</strong> - Vos informations sont protégées par un cryptage SSL
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Afficher le spinner
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('payButton').disabled = true;
            
            // Simuler le traitement du paiement
            setTimeout(() => {
                // Soumettre le formulaire
                this.submit();
            }, 2000);
        });
        
        // Validation du code PIN
        document.getElementById('pin_code').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
            }
        });
        
        // Validation du téléphone
        document.getElementById('confirm_phone').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9+]/g, '');
        });
    </script>
</body>
</html>
