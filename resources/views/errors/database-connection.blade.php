<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problème de Connexion - Gestion École</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
        }
        .error-icon {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .error-title {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .retry-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }
        .retry-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        .status-indicator {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            border-left: 4px solid #ffc107;
        }
        .status-text {
            color: #856404;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .status-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        .connection-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #dc3545;
            animation: blink 1s infinite;
        }
        .status-dot.connected {
            background: #28a745;
            animation: none;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        .auto-reload-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
            color: #1565c0;
        }
        .countdown {
            font-weight: bold;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-wifi-off"></i>
        </div>
        
        <h1 class="error-title">Problème de Connexion</h1>
        
        <p class="error-message">
            Nous ne pouvons pas nous connecter à la base de données en ce moment. 
            Cela peut être dû à un problème de connexion internet ou à une maintenance du serveur.
        </p>
        
        <div class="status-indicator">
            <div class="status-text">
                <i class="bi bi-info-circle me-2"></i>
                Statut de la connexion
            </div>
            <div class="connection-status">
                <div class="status-dot" id="statusDot"></div>
                <span class="status-details" id="statusText">Vérification en cours...</span>
            </div>
        </div>
        
        <div class="auto-reload-info">
            <i class="bi bi-arrow-clockwise me-2"></i>
            <span id="autoReloadText">Rechargement automatique dans <span class="countdown" id="countdown">30</span> secondes</span>
        </div>
        
        <div class="mt-4">
            <button class="btn retry-button" onclick="retryConnection()">
                <i class="bi bi-arrow-clockwise me-2"></i>
                Réessayer maintenant
            </button>
            <button class="btn retry-button" onclick="goToHome()" style="background: linear-gradient(45deg, #28a745, #20c997);">
                <i class="bi bi-house me-2"></i>
                Retour à l'accueil
            </button>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="bi bi-lightbulb me-1"></i>
                <strong>Conseil :</strong> Vérifiez votre connexion internet et réessayez dans quelques instants.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let countdownInterval;
        let checkInterval;
        let countdown = 30;
        let isChecking = false;
        
        // Éléments DOM
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        const countdownElement = document.getElementById('countdown');
        const autoReloadText = document.getElementById('autoReloadText');
        
        // Démarrer le compte à rebours
        function startCountdown() {
            countdown = 30;
            countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    reloadPage();
                }
            }, 1000);
        }
        
        // Vérifier la connexion
        async function checkConnection() {
            if (isChecking) return;
            
            isChecking = true;
            statusText.textContent = 'Vérification en cours...';
            
            try {
                // Essayer de faire une requête simple à l'API
                const response = await fetch('/api/health-check', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    timeout: 5000
                });
                
                if (response.ok) {
                    // Connexion rétablie
                    statusDot.classList.add('connected');
                    statusText.textContent = 'Connexion rétablie !';
                    autoReloadText.innerHTML = '<i class="bi bi-check-circle me-2 text-success"></i>Connexion rétablie, rechargement automatique...';
                    
                    // Arrêter le compte à rebours et recharger
                    clearInterval(countdownInterval);
                    clearInterval(checkInterval);
                    
                    setTimeout(() => {
                        reloadPage();
                    }, 2000);
                    
                    return true;
                } else {
                    throw new Error('Réponse non OK');
                }
            } catch (error) {
                // Connexion toujours en échec
                statusDot.classList.remove('connected');
                statusText.textContent = 'Connexion en échec';
                isChecking = false;
                return false;
            }
        }
        
        // Recharger la page
        function reloadPage() {
            // Utiliser l'URL précédente passée par le serveur
            const previousUrl = '{{ $previousUrl ?? "/" }}';
            window.location.href = previousUrl;
        }
        
        // Réessayer manuellement
        function retryConnection() {
            clearInterval(countdownInterval);
            autoReloadText.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Vérification en cours...';
            
            checkConnection().then(success => {
                if (!success) {
                    // Si la connexion n'est pas rétablie, redémarrer le compte à rebours
                    startCountdown();
                }
            });
        }
        
        // Aller à l'accueil
        function goToHome() {
            // Utiliser l'URL précédente ou l'accueil
            const previousUrl = '{{ $previousUrl ?? "/" }}';
            const homeUrl = previousUrl.includes('/login') || previousUrl.includes('/register') ? '/' : previousUrl;
            window.location.href = homeUrl;
        }
        
        // Démarrer la vérification périodique
        function startPeriodicCheck() {
            // Vérifier toutes les 5 secondes
            checkInterval = setInterval(checkConnection, 5000);
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Sauvegarder l'URL actuelle pour le rechargement
            if (!sessionStorage.getItem('previousUrl')) {
                sessionStorage.setItem('previousUrl', document.referrer || '/');
            }
            
            // Démarrer le compte à rebours
            startCountdown();
            
            // Démarrer la vérification périodique
            startPeriodicCheck();
            
            // Vérifier immédiatement
            checkConnection();
        });
        
        // Nettoyer les intervalles quand la page se ferme
        window.addEventListener('beforeunload', function() {
            if (countdownInterval) clearInterval(countdownInterval);
            if (checkInterval) clearInterval(checkInterval);
        });
    </script>
</body>
</html>
