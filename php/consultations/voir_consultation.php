<?php
session_start();
require_once "../db.php";

// Activation du débogage complet
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est un professionnel de santé
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['medecin', 'infirmier'])) {
    die("<p class='error'>Accès refusé. Seuls les professionnels peuvent voir les consultations.</p>");
}

$professionnel_id = $_SESSION['user_id'];
$consultation_id = $_GET['id'] ?? null;

try {
    if ($consultation_id) {
        // Récupérer une consultation spécifique
        $sql = "SELECT c.id, c.date_consultation, c.motif, c.diagnostic, c.traitement, c.notes, 
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.image AS patient_image
                FROM consultations c
                JOIN utilisateurs p ON c.patient_id = p.id
                WHERE c.id = :consultation_id AND c.professionnel_sante_id = :professionnel_id";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute([':consultation_id' => $consultation_id, ':professionnel_id' => $professionnel_id]);
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$consultation) {
            $message = "<p class='error'>Consultation introuvable ou non attribuée à ce professionnel.</p>";
        }
    } else {
        // Récupérer toutes les consultations du professionnel
        $sql = "SELECT c.id, c.date_consultation, c.motif, c.diagnostic, c.traitement, c.notes, 
                       p.nom AS patient_nom, p.prenom AS patient_prenom
                FROM consultations c
                JOIN utilisateurs p ON c.patient_id = p.id
                WHERE c.professionnel_sante_id = :professionnel_id
                ORDER BY c.date_consultation DESC";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute([':professionnel_id' => $professionnel_id]);
        $consultation_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = "<p class='error'>Erreur SQL : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Consultations Médicales</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Consultations Médicales</h2>

    <?= $message ?? ''; ?>

    <?php if ($consultation_id && $consultation): ?>
        <img src="<?= $consultation['patient_image']; ?>" alt="Photo du patient">
        <p><strong>Date :</strong> <?= $consultation['date_consultation']; ?></p>
        <p><strong>Motif :</strong> <?= $consultation['motif']; ?></p>
        <p><strong>Diagnostic :</strong> <?= $consultation['diagnostic']; ?></p>
        <p><strong>Traitement :</strong> <?= $consultation['traitement']; ?></p>
        <p><strong>Notes :</strong> <?= $consultation['notes']; ?></p>
        <p><strong>Patient :</strong> <?= $consultation['patient_nom'] . " " . $consultation['patient_prenom']; ?></p>
    <?php elseif (!$consultation_id): ?>
        <div class="list">
            <?php if (!empty($consultation_liste)): ?>
                <?php foreach ($consultation_liste as $consult): ?>
                    <p><a href="consultations.php?id=<?= $consult['id']; ?>">📅 <?= $consult['date_consultation']; ?> - <?= $consult['patient_nom'] . " " . $consult['patient_prenom']; ?> (<?= $consult['motif']; ?>)</a></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error">Aucune consultation disponible pour ce professionnel.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
