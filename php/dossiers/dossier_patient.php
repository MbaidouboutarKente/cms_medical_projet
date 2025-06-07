<?php
session_start();
require_once "../db.php"; // Connexion sécurisée à la base

// Activation du debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est un professionnel de santé
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['medecin', 'infirmier'])) {
    die("<p class='error'>Accès refusé. Seuls les professionnels peuvent voir les dossiers patients.</p>");
}

$professionnel_id = $_SESSION['user_id'];
$patient_id = $_GET['id'] ?? null;

try {
    if ($patient_id) {
        // Récupérer le dossier médical du patient
        $sql = "SELECT p.id, p.nom, p.prenom, p.image, pa.sexe, 
                       d.groupe_sanguin, d.allergies, d.antécédents_medicaux, d.traitements, d.notes
                FROM utilisateurs p
                JOIN dossiers_medicaux d ON p.id = d.patient_id
                JOIN patients pa ON pa.user_id = p.id
                WHERE p.id = :patient_id";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute([':patient_id' => $patient_id]);
        $dossier_patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dossier_patient) {
            $message = "<p class='error'>Dossier patient introuvable.</p>";
        }
    } else {
        // Lister tous les dossiers médicaux des patients
        $sql = "SELECT p.id, p.nom, p.prenom, d.groupe_sanguin 
                FROM utilisateurs p
                JOIN dossiers_medicaux d ON p.id = d.patient_id
                ORDER BY p.nom ASC";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute();
        $dossiers_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = "<p class='error'>Erreur SQL : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossiers Patients</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Style général */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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
            margin: 40px auto;
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

        /* Liste des dossiers */
        .list {
            text-align: left;
            padding: 15px;
            border-top: 1px solid #ddd;
        }

        /* Liens vers les dossiers */
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
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #007bff;
        }

        /* Cadre d'information */
        .patient-info {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background: #f4f4f4;
            text-align: left;
            margin: 20px auto;
            max-width: 600px;
        }

        .patient-info p {
            margin: 10px 0;
            font-size: 16px;
            color: #333;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>Dossier Médical du Patient</h2>

    <?= $message ?? ''; ?>

    <?php if ($patient_id && $dossier_patient): ?>
        <img src="../../img/uploads/<?= $dossier_patient['image']; ?>" alt="Photo du patient">
        <p><strong>Nom :</strong> <?= $dossier_patient['nom'] . " " . $dossier_patient['prenom']; ?></p>
        <p><strong>Sexe :</strong> <?= $dossier_patient['sexe']; ?></p>
        <p><strong>Groupe sanguin :</strong> <?= $dossier_patient['groupe_sanguin']; ?></p>
        <p><strong>Allergies :</strong> <?= $dossier_patient['allergies']; ?></p>
        <p><strong>Antécédents médicaux :</strong> <?= $dossier_patient['antécédents_medicaux']; ?></p>
        <p><strong>Traitements :</strong> <?= $dossier_patient['traitements']; ?></p>
        <p><strong>Notes :</strong> <?= $dossier_patient['notes']; ?></p>
    <?php elseif (!$patient_id): ?>
        <div class="list">
            <?php if (!empty($dossiers_liste)): ?>
                <?php foreach ($dossiers_liste as $dossier): ?>
                    <p><a href="dossier_patient.php?id=<?= $dossier['id']; ?>">📄 <?= $dossier['nom'] . " " . $dossier['prenom']; ?> (<?= $dossier['groupe_sanguin']; ?>)</a></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error">Aucun dossier médical disponible.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
