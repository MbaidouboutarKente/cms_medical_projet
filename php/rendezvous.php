<?php
session_start();
require_once "db.php";
// require_once "pdoMedical.php"; // Connexion sécurisée à la base

// Activation du debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de l'authentification (uniquement médecins/infirmiers)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['medecin', 'infirmier'])) {
    die("<p class='error'>Accès refusé. Seuls les professionnels de santé peuvent voir les rendez-vous.</p>");
}

$professionnel_id = intval(str_replace("MED_", "", $_SESSION['user_id']));
$rendez_vous_id = $_GET['id'] ?? null;

try {
    if ($rendez_vous_id) {
        // Récupérer les détails d'un seul rendez-vous
        $sql = "SELECT r.id, r.date_heure, r.statut, r.motif, 
                       p.nom AS patient_nom, p.prenom AS patient_prenom, p.image AS patient_image
                FROM rendez_vous r
                JOIN utilisateurs p ON r.patient_id = p.id
                WHERE r.id = :rendez_vous_id AND r.professionnel_sante_id = :professionnel_id";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute([':rendez_vous_id' => $rendez_vous_id, ':professionnel_id' => $professionnel_id]);
        $rendez_vous = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rendez_vous) {
            $message = "<p class='error'>Rendez-vous introuvable ou non attribué à ce professionnel.</p>";
        }
    } else {
        // Récupérer tous les rendez-vous du professionnel
        $sql = "SELECT r.id, r.date_heure, r.statut, r.motif, 
                       p.nom AS patient_nom, p.prenom AS patient_prenom
                FROM rendez_vous r
                JOIN utilisateurs p ON r.patient_id = p.id
                WHERE r.professionnel_sante_id = :professionnel_id
                ORDER BY r.date_heure DESC";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute([':professionnel_id' => $professionnel_id]);
        $rendez_vous_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = "<p class='error'>Erreur SQL : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails des Rendez-vous</title>
    <style>
        /* Style général */
body {
    font-family: Arial, sans-serif;
    margin: 40px;
    background-color: #f8f9fa;
    text-align: center;
}

/* Conteneur principal */
.container {
    max-width: 700px;
    padding: 25px;
    border-radius: 10px;
    background: white;
    box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    margin: auto;
}

/* Titre */
h2 {
    color: #007bff;
    font-size: 24px;
    margin-bottom: 20px;
}

/* Paragraphes */
p {
    font-size: 16px;
    color: #333;
    line-height: 1.6;
}

/* Messages d'erreur */
.error {
    color: red;
    font-weight: bold;
    background-color: #f8d7da;
    padding: 10px;
    border-radius: 5px;
}

/* Liste des rendez-vous */
.list {
    text-align: left;
    padding: 15px;
    border-top: 1px solid #ddd;
}

/* Liens vers les rendez-vous */
.list a {
    display: block;
    padding: 10px;
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
    border-radius: 5px;
    transition: background 0.3s;
}

.list a:hover {
    background-color: #007bff;
    color: white;
}

/* Image du patient */
img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin-bottom: 15px;
    border: 3px solid #007bff;
}

    </style>
</head>
<body>

<div class="container">
    <h2>Détails des Rendez-vous</h2>

    <?= $message ?? ''; ?>

    <?php if ($rendez_vous_id && $rendez_vous): ?>
        <img src="../img/uploads/<?= $rendez_vous['patient_image']; ?>" alt="Photo du patient">
        <p><strong>Date et Heure :</strong> <?= $rendez_vous['date_heure']; ?></p>
        <p><strong>Statut :</strong> <?= $rendez_vous['statut']; ?></p>
        <p><strong>Motif :</strong> <?= $rendez_vous['motif']; ?></p>
        <p><strong>Patient :</strong> <?= $rendez_vous['patient_nom'] . " " . $rendez_vous['patient_prenom']; ?></p>
    <?php elseif (!$rendez_vous_id): ?>
        <div class="list">
            <?php if (!empty($rendez_vous_liste)): ?>
                <?php foreach ($rendez_vous_liste as $rdv): ?>
                    <p><a href="rendezvous.php?id=<?= $rdv['id']; ?>">📅 <?= $rdv['date_heure']; ?> - <?= $rdv['patient_nom'] . " " . $rdv['patient_prenom']; ?> (<?= $rdv['statut']; ?>)</a></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error">Aucun rendez-vous disponible pour ce professionnel.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
