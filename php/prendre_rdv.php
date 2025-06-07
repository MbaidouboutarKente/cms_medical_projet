<?php
session_start();
require_once "db.php";

// Configuration de l'environnement (développement/production)
define('ENV', 'dev'); // Changer en 'prod' pour production

if (ENV === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Vérification de l'authentification (uniquement étudiants)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header('HTTP/1.1 403 Forbidden');
    exit('<p>Accès refusé. Seuls les étudiants peuvent prendre un rendez-vous.</p>');
}

$etudiant_id = filter_var(str_replace("MED_", "", $_SESSION['user_id']), FILTER_SANITIZE_NUMBER_INT);
// $etudiant_id = $_SESSION['user_id'];
$confirmation_message = "";
$professionnels = [];

try {
    // Récupérer les professionnels de santé
    $stmt = $pdoMedical->prepare("SELECT id, nom, role FROM utilisateurs WHERE role IN ('medecin', 'infirmier') ORDER BY nom");
    $stmt->execute();
    $professionnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $confirmation_message = "<p class='error'>Erreur lors de la récupération des professionnels : " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Initialiser les variables pour stocker les valeurs des champs
$destinataire_id = '';
$date_heure = '';
$motif = '';
$type_professionnel = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des entrées
    $destinataire_id = filter_input(INPUT_POST, 'destinataire_id', FILTER_VALIDATE_INT);
    $date_heure = filter_input(INPUT_POST, 'date_heure', FILTER_DEFAULT);
    $motif = filter_input(INPUT_POST, 'motif', FILTER_DEFAULT);
    $type_professionnel = filter_input(INPUT_POST, 'type_professionnel', FILTER_DEFAULT);

    // Sanitisation des entrées
    $date_heure = htmlspecialchars($date_heure);
    $motif = htmlspecialchars($motif);
    $type_professionnel = htmlspecialchars($type_professionnel);

    if (empty($destinataire_id) || empty($date_heure) || empty($motif) || empty($type_professionnel)) {
        $confirmation_message = "<p class='error'>Veuillez remplir tous les champs.</p>";
    } else {
        try {
            // Vérifier que le professionnel existe et correspond au type choisi
            $stmt = $pdoMedical->prepare("SELECT id FROM utilisateurs WHERE id = ? AND role = ? AND role IN ('medecin', 'infirmier')");
            $stmt->execute([$destinataire_id, $type_professionnel]);
            if ($stmt->fetch() === false) {
                $confirmation_message = "<p class='error'>Professionnel invalide.</p>";
            } else {
                // Insérer le rendez-vous avec le motif
                $sql = "INSERT INTO rendez_vous (patient_id, professionnel_sante_id, date_heure, Motif, statut)
                        VALUES (:patient_id, :professionnel_id, :date_heure, :motif, 'En attente')";
                $stmt = $pdoMedical->prepare($sql);
                $stmt->execute([
                    ':patient_id' => $etudiant_id,
                    ':professionnel_id' => $destinataire_id,
                    ':date_heure' => $date_heure,
                    ':motif' => $motif
                ]);
                $rendez_vous_id = $pdoMedical->lastInsertId();
                $confirmation_message = "<p class='success'>Rendez-vous demandé avec succès.</p>";

                // Enregistrer la notification
                $notif_sql = "INSERT INTO notifications (utilisateur_id, rendez_vous_id, message, lu)
                          VALUES (:utilisateur_id, :rendez_vous_id, :message, 0)";
                $notif_stmt = $pdoMedical->prepare($notif_sql);
                $notif_stmt->execute([
                    ':utilisateur_id' => $destinataire_id,
                    ':rendez_vous_id' => $rendez_vous_id,
                    ':message' => 'Nouveau rendez-vous demandé par un étudiant'
                ]);

                // Redirection après succès pour éviter le rechargement de la page
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit();
            }
        } catch (PDOException $e) {
            $confirmation_message = "<p class='error'>Erreur lors de la prise du rendez-vous : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// Affichage du message de succès si redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $confirmation_message = "<p class='success'>Rendez-vous demandé avec succès.</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre un rendez-vous</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background-color: #f4f4f4; 
            text-align: center; 
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        form { 
            max-width: 500px; 
            padding: 20px; 
            border-radius: 10px; 
            background: white; 
            box-shadow: 0px 0px 10px #ccc; 
            margin: auto; 
        }
        label, select, input, textarea, button { 
            display: block; 
            width: 100%; 
            margin-bottom: 15px; 
            font-size: 16px; 
        }
        button { 
            background-color: #007bff; 
            color: white; 
            padding: 10px; 
            border-radius: 5px; 
            border: none; 
            cursor: pointer; 
        }
        button:hover { 
            background-color: #0056b3; 
        }
        .success { 
            color: green; 
            font-weight: bold; 
            padding: 10px;
            border-radius: 5px;
            background-color: #e8f5e9;
            margin-bottom: 20px;
        }
        .error { 
            color: red; 
            font-weight: bold; 
            padding: 10px;
            border-radius: 5px;
            background-color: #ffebee;
            margin-bottom: 20px;
        }
        select, input, textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-calendar-check"></i> Prise de Rendez-vous Médical</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

    <div class="container">
        <h2>Prendre un rendez-vous</h2>
        
        <!-- Affichage du message de confirmation -->
        <div><?= $confirmation_message; ?></div>
        
        <form method="POST" action="prendre_rdv.php">
            <label for="type_professionnel">Type de professionnel :</label>
            <select name="type_professionnel" id="type_professionnel" onchange="this.form.submit()">
                <option value="">-- Sélectionnez un type de professionnel --</option>
                <option value="medecin">Médecin</option>
                <option value="infirmier">Infirmier</option>
            </select>
            
            <label for="destinataire_id">Choisissez un professionnel de santé :</label>
            <select name="destinataire_id" id="destinataire_id" required>
                <option value="">-- Sélectionnez un professionnel --</option>
                <?php 
                $type_professionnel = isset($_POST['type_professionnel']) ? $_POST['type_professionnel'] : '';
                foreach ($professionnels as $pro): 
                    if (empty($type_professionnel) || $pro['role'] === $type_professionnel): ?>
                        <option value="<?= htmlspecialchars($pro['id']); ?>" <?= $destinataire_id == $pro['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pro['nom']); ?> (<?= ucfirst(htmlspecialchars($pro['role'])); ?>)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <label for="date_heure">Date et Heure du rendez-vous :</label>
            <input type="datetime-local" name="date_heure" id="date_heure" value="<?= htmlspecialchars($date_heure); ?>" required>
            
            <label for="motif">Motif du rendez-vous :</label>
            <textarea name="motif" id="motif" required placeholder="Exemple : Consultation, Suivi médical, Vaccin..."><?= htmlspecialchars($motif); ?></textarea>
            
            <button type="submit">Envoyer la demande</button>
        </form>
    </div>
</nav>

<script>
    // Récupérer le type de professionnel sélectionné
    const typeProfessionnelSelect = document.getElementById('type_professionnel');
    const destinataireSelect = document.getElementById('destinataire_id');
    
    // Fonction pour filtrer les professionnels en fonction du type sélectionné
    function filterProfessionnels() {
        const selectedType = typeProfessionnelSelect.value;
        const options = destinataireSelect.options;
        
        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            const optionRole = option.text.toLowerCase().includes(selectedType);
            
            if (selectedType === '') {
                option.style.display = '';
            } else {
                option.style.display = optionRole ? '' : 'none';
            }
        }
    }
    
    // Ajouter un écouteur d'événements pour le changement de type de professionnel
    typeProfessionnelSelect.addEventListener('change', filterProfessionnels);
    
    // Filtrer les professionnels au chargement de la page si un type est déjà sélectionné
    document.addEventListener('DOMContentLoaded', filterProfessionnels);
</script>
</body>
</html>