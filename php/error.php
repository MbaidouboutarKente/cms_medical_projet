<?php
// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Journaliser l'erreur
error_log("Erreur 500 survenue - URI: " . $_SERVER['REQUEST_URI']);

// Définir le code HTTP
http_response_code(500);

// Configurer le type de contenu
header('Content-Type: text/html; charset=utf-8');

// Inclure les dépendances si nécessaire
$requiresDB = false;
if ($requiresDB) {
    require_once __DIR__.'/../db.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur 500 | Problème technique</title>
    <style>
        :root {
            --primary: #d32f2f;
            --secondary: #f5f5f5;
            --text: #333;
            --light: #fff;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--secondary);
            color: var(--text);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .error-container {
            max-width: 600px;
            width: 90%;
            padding: 2rem;
            background: var(--light);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 5px solid var(--primary);
        }
        
        h1 {
            color: var(--primary);
            font-size: 3rem;
            margin: 0 0 1rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #b71c1c;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .technical-details {
            margin-top: 2rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            text-align: left;
            display: none; /* Caché par défaut */
        }
        
        .toggle-details {
            color: var(--primary);
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 1rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Erreur 500</h1>
        <h2>Erreur interne du serveur</h2>
        <p>
            Une erreur technique s'est produite. Notre équipe a été automatiquement notifiée et travaille à résoudre le problème.
        </p>
        <p>
            Veuillez réessayer plus tard ou nous contacter si le problème persiste.
        </p>
        
        <div class="actions">
            <a href="../index.html" class="btn btn-primary">
                <span>🏠</span> Page d'accueil
            </a>
            <a href="contact.php" class="btn btn-secondary">
                <span>✉️</span> Nous contacter
            </a>
        </div>
        
        <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
            <div style="margin-top: 1.5rem;">
                <a href="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER']); ?>" class="btn btn-secondary">
                    <span>↩️</span> Retour à la page précédente
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Détails techniques (pour le débogage en dev) -->
        <?php if (isset($_GET['debug']) && $_GET['debug'] === 'true'): ?>
            <div class="technical-details" style="display: block;">
                <h3>Détails techniques :</h3>
                <p><strong>URI :</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? ''); ?></p>
                <p><strong>Heure :</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                <p><strong>Référent :</strong> <?php echo htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'N/A'); ?></p>
            </div>
        <?php else: ?>
            <div class="toggle-details" onclick="toggleDetails()">
                Afficher les détails techniques
            </div>
            <div class="technical-details" id="techDetails">
                <h3>Détails techniques :</h3>
                <p><strong>Erreur ID :</strong> ERR_<?php echo bin2hex(random_bytes(4)); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleDetails() {
            const details = document.getElementById('techDetails');
            details.style.display = details.style.display === 'block' ? 'none' : 'block';
        }
        
        // Envoyer un rapport d'erreur
        if (navigator.sendBeacon) {
            const data = new FormData();
            data.append('error', '500');
            data.append('url', window.location.href);
            data.append('timestamp', new Date().toISOString());
            navigator.sendBeacon('/error-log.php', data);
        }
    </script>
</body>
</html>