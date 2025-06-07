<?php
session_start();
require_once "db.php";

// Vérification connexion et rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: auth.php");
    exit;
}

// Protection contre la fixation de session
session_regenerate_id(true);

// Nettoyage de l'ID utilisateur
$user_id = filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT);

// Génération de token CSRF sécurisé
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
    $_SESSION['token_time'] = time();
}

// Stockage des valeurs saisies
$old_values = $_SESSION["form_input"] ?? [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Vérification CSRF avec expiration (30 minutes)
    if (empty($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        $errors[] = "⚠️ Token invalide ou expiré. Veuillez rafraîchir la page.";
    } elseif (time() - $_SESSION['token_time'] > 10800) {
        // Régénérer le token CSRF après expiration
        $_SESSION['token'] = bin2hex(random_bytes(32));
        $_SESSION['token_time'] = time();
        $errors[] = "⚠️ Le formulaire a expiré. Veuillez le renvoyer.";
    } else {
        unset($_SESSION['token'], $_SESSION['token_time']);
    }

    // Nettoyage et validation des données
    $_SESSION["form_input"] = $_POST;
    $input = [
        'age' => filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 120]
        ]),
        'temperature' => filter_input(INPUT_POST, 'temperature', FILTER_VALIDATE_FLOAT, [
            'options' => ['min_range' => 35, 'max_range' => 42]
        ]),
        'oxygenLevel' => filter_input(INPUT_POST, 'oxygenLevel', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 70, 'max_range' => 100]
        ]),
        'symptoms' => substr(htmlspecialchars($_POST["symptoms"]), 0, 500),
        'eyeColor' => htmlspecialchars($_POST["eyeColor"]),
        'urineColor' => htmlspecialchars($_POST["urineColor"]),
        'saveAnalysis' => $_POST["saveAnalysis"] === 'oui' ? 'oui' : 'non'
    ];

    // Validation des champs
    if (!$input['age']) $errors[] = "⚠️ L'âge doit être compris entre 1 et 120 ans.";
    if (!$input['temperature']) $errors[] = "⚠️ La température doit être entre 35°C et 42°C.";
    if (!$input['oxygenLevel']) $errors[] = "⚠️ Le taux d'oxygène doit être entre 70% et 100%.";

    // Validation des sélecteurs
    $allowedColors = [
        'eyeColor' => ['normal', 'jaunâtre', 'rougeâtre'],
        'urineColor' => ['jaune clair', 'jaune foncé', 'brunâtre']
    ];
    foreach ($allowedColors as $field => $values) {
        if (!in_array($input[$field], $values)) {
            $errors[] = "⚠️ Valeur invalide pour le champ " . str_replace('Color', '', $field);
        }
    }

    if (empty($errors)) {
        // 🔹 Analyse médicale améliorée
        $result = [];
        $severity = 0; // 0: normal, 1: modéré, 2: grave

        // Analyse de la température
        if ($input['temperature'] >= 39) {
            $result[] = "🟥 Fièvre élevée ! Consultez un médecin rapidement.";
            $severity = 2;
        } elseif ($input['temperature'] >= 38) {
            $result[] = "🟠 Fièvre modérée. Hydratez-vous bien.";
            $severity = max($severity, 1);
        }

        // Analyse de l'oxygène
        if ($input['oxygenLevel'] < 90) {
            $result[] = "🔴 Saturation critique ! Consultation immédiate.";
            $severity = 2;
        } elseif ($input['oxygenLevel'] < 95) {
            $result[] = "🟠 Oxygène légèrement faible. Surveillez.";
            $severity = max($severity, 1);
        }

        // Analyse des symptômes avec détection améliorée
        $symptomsLower = mb_strtolower($input['symptoms']);
        
        if (preg_match('/(toux|fièvre|frissons)/', $symptomsLower) && 
            preg_match('/(difficulté respiratoire|essoufflement)/', $symptomsLower)) {
            $result[] = "🔴 Symptômes respiratoires sévères - Urgence !";
            $severity = 2;
        } elseif (preg_match('/(toux|fièvre)/', $symptomsLower)) {
            $result[] = "🟡 Infection respiratoire possible (grippe, bronchite).";
            $severity = max($severity, 1);
        }
        
        if (preg_match('/(fatigue|épuisement)/', $symptomsLower) && 
            preg_match('/(maux de tête|vertiges)/', $symptomsLower)) {
            $result[] = "💤 Fatigue intense : Repos et hydratation.";
            $severity = max($severity, 1);
        }

        // Analyse des couleurs
        $colorAnalysis = [
            'eyeColor' => [
                'jaunâtre' => ["🟡 Jaunisse possible : Bilan hépatique.", 2],
                'rougeâtre' => ["🔴 Yeux rouges : Allergie ou infection.", 1]
            ],
            'urineColor' => [
                'brunâtre' => ["🟤 Urine très foncée : Problème rénal/hépatique.", 2],
                'jaune foncé' => ["🟠 Urine foncée : Buvez plus d'eau.", 1]
            ]
        ];
        
        foreach ($colorAnalysis as $field => $cases) {
            if (isset($cases[$input[$field]])) {
                $result[] = $cases[$input[$field]][0];
                $severity = max($severity, $cases[$input[$field]][1]);
            }
        }

        // Conclusion
        if ($severity === 0) {
            array_unshift($result, "✅ Aucune anomalie détectée.");
        } elseif ($severity === 2) {
            array_unshift($result, "🚨 URGENCE : Consultez un médecin !");
        }

        $finalResult = implode("\n", $result);

        // 🔹 Enregistrement en base si demandé
        if ($input['saveAnalysis'] === 'oui') {
            try {
                $stmt = $pdoMedical->prepare("INSERT INTO analyses 
                    (user_id, age, symptoms, temperature, oxygenLevel, eyeColor, urineColor, result, severity, created_at) 
                    VALUES (:user_id, :age, :symptoms, :temperature, :oxygenLevel, :eyeColor, :urineColor, :result, :severity, NOW())");
                
                $stmt->execute([
                    "user_id" => $user_id,
                    "age" => $input['age'],
                    "symptoms" => $input['symptoms'],
                    "temperature" => $input['temperature'],
                    "oxygenLevel" => $input['oxygenLevel'],
                    "eyeColor" => $input['eyeColor'],
                    "urineColor" => $input['urineColor'],
                    "result" => $finalResult,
                    "severity" => $severity
                ]);
                
                $_SESSION["analysis_result"] = [
                    'message' => "✅ Analyse enregistrée avec succès.\n\n" . $finalResult,
                    'severity' => $severity,
                    'input' => $input
                ];
            } catch (PDOException $e) {
                error_log("Erreur base de données: " . $e->getMessage());
                $errors[] = "⚠️ Erreur lors de l'enregistrement. Veuillez réessayer.";
                $_SESSION["errors"] = $errors;
            }
        } else {
            $_SESSION["analysis_result"] = [
                'message' => "🧐 Résultat d'analyse (non enregistré):\n\n" . $finalResult,
                'severity' => $severity,
                'input' => $input
            ];
        }
    } else {
        $_SESSION["errors"] = $errors;
    }

    header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🩺 Analyse Médicale Étudiante</title>
    <style>
        :root {
            --success-bg: #d4edda;
            --success-text: #155724;
            --warning-bg: #fff3cd;
            --warning-text: #856404;
            --danger-bg: #f8d7da;
            --danger-text: #721c24;
            --primary-color: #007bff;
            --primary-hover: #0069d9;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        
        .medical-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 700px;
            margin: 0 auto;
            transition: transform 0.3s ease;
        }
        
        .medical-card:hover {
            transform: translateY(-5px);
        }
        
        .medical-header {
            background: linear-gradient(135deg, var(--primary-color), #00b4ff);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .medical-header::after {
            content: "";
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .medical-header h1 {
            margin: 0;
            font-size: 1.8rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .medical-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .medical-body {
            padding: 25px;
        }
        
        .severity-0 { border-left: 5px solid #28a745; }
        .severity-1 { border-left: 5px solid #ffc107; }
        .severity-2 { border-left: 5px solid #dc3545; }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        
        label::after {
            content: " *";
            color: #dc3545;
            opacity: 0.8;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        button {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        button:hover {
            background: linear-gradient(135deg, var(--primary-hover), #0056b3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: currentColor;
            opacity: 0.3;
        }
        
        .alert-danger {
            background: var(--danger-bg);
            color: var(--danger-text);
        }
        
        .alert-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
        }
        
        .alert-success {
            background: var(--success-bg);
            color: var(--success-text);
        }
        
        .symptom-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: -12px;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 10px;
            box-sizing: border-box;
        }
        
        /* Styles pour les options de couleur */
        select option[value="normal"] { background-color: #ffffff; }
        select option[value="jaunâtre"] { background-color: #fffacd; }
        select option[value="rougeâtre"] { background-color: #ffdddd; }
        select option[value="jaune clair"] { background-color: #ffff99; }
        select option[value="jaune foncé"] { background-color: #ffcc00; }
        select option[value="brunâtre"] { background-color: #d2b48c; }
        
        select option:hover {
            filter: brightness(90%);
        }
        
        /* Animation pour les erreurs */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .error-field {
            animation: shake 0.5s ease;
            border-color: #dc3545 !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .medical-header h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Style pour le récapitulatif */
        .recap-list {
            list-style-type: none;
            padding: 0;
            margin: 15px 0 0;
        }
        
        .recap-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
        }
        
        .recap-list li:last-child {
            border-bottom: none;
        }
        
        .recap-list strong {
            min-width: 120px;
            display: inline-block;
            color: #495057;
        }
        
        /* Amélioration des séparateurs */
        hr {
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
            margin: 20px 0;
        }
        .btn-retour{
            position: fixed;
            top: 10px;
            left: 20px;
            z-index: 100;
            width: 100px;
        }
    </style>
</head>
<body>
<button class="btn-retour" onclick="history.back()">Retour </button>
    <div class="medical-card">
        <div class="medical-header">
            <h1><i class="fas fa-stethoscope"></i> Analyse Médicale Étudiante</h1>
            <p>Service de diagnostic préliminaire</p>
        </div>
        
        <div class="medical-body">
            <?php if (!empty($_SESSION["errors"])): ?>
                <div class="alert alert-danger">
                    <h3 style="margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> Erreurs détectées</h3>
                    <ul style="margin-bottom: 0;">
                        <?php foreach ($_SESSION["errors"] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION["errors"]); ?>
            <?php endif; ?>
            
            <?php if (!empty($_SESSION["analysis_result"])): ?>
                <div class="alert alert-<?= 
                    $_SESSION["analysis_result"]['severity'] === 2 ? 'danger' : 
                    ($_SESSION["analysis_result"]['severity'] === 1 ? 'warning' : 'success') 
                ?> severity-<?= $_SESSION["analysis_result"]['severity'] ?>">
                    <div style="white-space: pre-line; font-size: 1.05rem;"><?= htmlspecialchars($_SESSION["analysis_result"]['message']) ?></div>
                    
                    <?php if (!empty($_SESSION["analysis_result"]['input'])): ?>
                        <hr>
                        <h4 style="margin-bottom: 15px;"><i class="fas fa-clipboard-list"></i> Récapitulatif</h4>
                        <ul class="recap-list">
                            <li><strong>Âge :</strong> <?= htmlspecialchars($_SESSION["analysis_result"]['input']['age']) ?> ans</li>
                            <li><strong>Température :</strong> <?= htmlspecialchars($_SESSION["analysis_result"]['input']['temperature']) ?>°C</li>
                            <li><strong>Saturation O₂ :</strong> <?= htmlspecialchars($_SESSION["analysis_result"]['input']['oxygenLevel']) ?>%</li>
                            <li><strong>Symptômes :</strong> <?= htmlspecialchars($_SESSION["analysis_result"]['input']['symptoms']) ?></li>
                            <li><strong>Couleur yeux :</strong> <?= htmlspecialchars($_SESSION["analysis_result"]['input']['eyeColor']) ?></li>
                            <li><strong>Couleur urine :</strong> <?= htmlspecialchars($_SESSION["analysis_result"]['input']['urineColor']) ?></li>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php unset($_SESSION["analysis_result"]); ?>
            <?php endif; ?>
            
            <form method="POST" id="medicalForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'] ?? '') ?>">
                
                <div class="form-group">
                    <label for="age"><i class="fas fa-user"></i> Âge</label>
                    <input type="number" id="age" name="age" 
                           value="<?= htmlspecialchars($_SESSION['form_input']['age'] ?? '') ?>" 
                           min="1" max="120" required
                           class="<?= (!empty($_SESSION['age_error']) ? 'error-field' : '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="symptoms"><i class="fas fa-comment-medical"></i> Symptômes</label>
                    <textarea id="symptoms" name="symptoms" required
                              placeholder="Décrivez vos symptômes en détail (ex: fièvre depuis 2 jours, toux sèche, fatigue intense...)"><?= 
                              htmlspecialchars($_SESSION['form_input']['symptoms'] ?? '') ?></textarea>
                    <div class="symptom-hint">Séparez les symptômes par des virgules pour plus de précision</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="temperature"><i class="fas fa-thermometer-half"></i> Température (°C)</label>
                            <input type="number" id="temperature" name="temperature" 
                                   value="<?= htmlspecialchars($_SESSION['form_input']['temperature'] ?? '') ?>" 
                                   step="0.1" min="35" max="42" required
                                   class="<?= (!empty($_SESSION['temp_error']) ? 'error-field' : '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="oxygenLevel"><i class="fas fa-lungs"></i> Saturation O₂ (%)</label>
                            <input type="number" id="oxygenLevel" name="oxygenLevel" 
                                   value="<?= htmlspecialchars($_SESSION['form_input']['oxygenLevel'] ?? '') ?>" 
                                   min="70" max="100" required
                                   class="<?= (!empty($_SESSION['oxygen_error']) ? 'error-field' : '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="eyeColor"><i class="fas fa-eye"></i> Couleur des yeux</label>
                            <select id="eyeColor" name="eyeColor" required>
                                <option value="normal" <?= ($_SESSION['form_input']['eyeColor'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal (blanc)</option>
                                <option value="jaunâtre" <?= ($_SESSION['form_input']['eyeColor'] ?? '') === 'jaunâtre' ? 'selected' : '' ?>>Jaunâtre</option>
                                <option value="rougeâtre" <?= ($_SESSION['form_input']['eyeColor'] ?? '') === 'rougeâtre' ? 'selected' : '' ?>>Rougeâtre</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="urineColor"><i class="fas fa-tint"></i> Couleur de l'urine</label>
                            <select id="urineColor" name="urineColor" required>
                                <option value="jaune clair" <?= ($_SESSION['form_input']['urineColor'] ?? '') === 'jaune clair' ? 'selected' : '' ?>>Jaune clair</option>
                                <option value="jaune foncé" <?= ($_SESSION['form_input']['urineColor'] ?? '') === 'jaune foncé' ? 'selected' : '' ?>>Jaune foncé</option>
                                <option value="brunâtre" <?= ($_SESSION['form_input']['urineColor'] ?? '') === 'brunâtre' ? 'selected' : '' ?>>Brunâtre</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="saveAnalysis"><i class="fas fa-save"></i> Enregistrer cette analyse ?</label>
                    <select id="saveAnalysis" name="saveAnalysis" required>
                        <option value="non" <?= ($_SESSION['form_input']['saveAnalysis'] ?? '') === 'non' ? 'selected' : '' ?>>Non, juste un diagnostic temporaire</option>
                        <option value="oui" <?= ($_SESSION['form_input']['saveAnalysis'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui, enregistrer dans mon dossier</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit">
                        <i class="fas fa-search"></i> Analyser mes symptômes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="text-center text-muted" style="margin-top: 25px;">
        <small>
            <i class="fas fa-info-circle"></i> Cet outil ne remplace pas un avis médical professionnel. 
            En cas d'urgence, contactez immédiatement un médecin.
        </small>
    </div>
    
    <script>
        // Validation côté client améliorée
        document.getElementById('medicalForm').addEventListener('submit', function(e) {
            let valid = true;
            let errorFields = [];
            
            // Fonction de validation générique
            function validateField(fieldId, min, max, message) {
                const field = document.getElementById(fieldId);
                const value = parseFloat(field.value);
                
                if (isNaN(value) || value < min || value > max) {
                    field.classList.add('error-field');
                    errorFields.push({field: field, message: message});
                    return false;
                } else {
                    field.classList.remove('error-field');
                    return true;
                }
            }
            
            // Validation des champs
            valid &= validateField('age', 1, 120, "L'âge doit être entre 1 et 120 ans");
            valid &= validateField('temperature', 35, 42, "La température doit être entre 35°C et 42°C");
            valid &= validateField('oxygenLevel', 70, 100, "Le taux d'oxygène doit être entre 70% et 100%");
            
            // Validation des symptômes
            const symptoms = document.getElementById('symptoms');
            if (symptoms.value.trim().length < 5) {
                symptoms.classList.add('error-field');
                errorFields.push({field: symptoms, message: "Veuillez décrire vos symptômes"});
                valid = false;
            } else {
                symptoms.classList.remove('error-field');
            }
            
            if (!valid) {
                e.preventDefault();
                
                // Construction du message d'erreur
                let errorMessage = "Veuillez corriger les erreurs suivantes :\n\n";
                errorFields.forEach(err => {
                    errorMessage += `- ${err.message}\n`;
                });
                
                alert(errorMessage);
                
                // Focus sur le premier champ erroné
                if (errorFields.length > 0) {
                    errorFields[0].field.focus();
                }
            }
        });
        
        // Suppression de l'animation d'erreur après 3 secondes
        document.querySelectorAll('.error-field').forEach(field => {
            setTimeout(() => {
                field.classList.remove('error-field');
            }, 3000);
        });
    </script>
    
    <?php 
    // Nettoyage des erreurs de champ
    unset($_SESSION['age_error'], $_SESSION['temp_error'], $_SESSION['oxygen_error'], $_SESSION['form_input']); 
    ?>
</body>
</html>