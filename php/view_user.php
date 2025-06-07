<?php
session_start();
require_once "db.php";

// Vérification du rôle (Admin ou Super Admin)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin')) {
    header("Location: auth.php");
    exit;
}

$userId = intval($_GET['id'] ?? 0);

try {
    // Récupération des infos utilisateur
    $query = $pdoMedical->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $query->execute([$userId]);
    $user = $query->fetch();
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé.");
    }
    
    // Récupération des rendez-vous pour les patients
    $rdvs = [];
    if ($user['role'] === 'patient') {
        $queryRdvs = $pdoMedical->prepare("SELECT * FROM rendez_vous WHERE patient_id = ?");
        $queryRdvs->execute([$userId]);
        $rdvs = $queryRdvs->fetchAll();
    }
    
} catch (Exception $e) {
    die("<div class='error'>❌ " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Déterminer la page de retour en fonction du rôle
$dashboardPage = ($_SESSION['role'] === 'super_admin') ? 'dashboard_super_admin.php' : 'dashboard_admin.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Utilisateur</title>
    <link rel="stylesheet" href="styles.css"> <!-- Ajoute un fichier CSS externe si nécessaire -->
    <style>
        /* Styles généraux */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        /* Cartes */
        .card {
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0px 3px 6px rgba(0,0,0,0.1);
        }

        .card-header {
            font-size: 18px;
            font-weight: bold;
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }

        .card-body {
            padding: 10px;
        }

        /* Tableaux */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        /* Bouton Retour */
        .btn-retour {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-retour:hover {
            background-color: #218838;
        }

        /* Gestion des erreurs */
        .error {
            color: red;
            font-weight: bold;
            margin-top: 15px;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Détails de l'utilisateur</h1>
        
        <div class="card">
            <div class="card-header">
                📌 Informations personnelles
            </div>
            <div class="card-body">
                <p><strong>ID :</strong> <?= $user['id'] ?></p>
                <p><strong>Nom :</strong> <?= htmlspecialchars($user['nom']) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Rôle :</strong> <?= ucfirst($user['role']) ?></p>
                <p><strong>Date d'inscription :</strong> <?= date('d/m/Y H:i', strtotime($user['date_inscription'])) ?></p>
            </div>
        </div>

        <?php if ($user['role'] === 'patient' && !empty($rdvs)): ?>
        <div class="card">
            <div class="card-header">
                📅 Rendez-vous médicaux
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rdvs as $rdv): ?>
                        <tr>
                            <td><?= $rdv['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($rdv['date'])) ?></td>
                            <td><?= $rdv['heure'] ?></td>
                            <td><?= ucfirst($rdv['statut']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        <button class="btn-retour" onclick="history.back()">Retour à la page précédente</button>
        
    </div>
</body>
</html>
