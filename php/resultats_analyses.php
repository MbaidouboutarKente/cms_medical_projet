<?php
session_start();

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion à la base de données
require_once "db.php";

// Vérification de la connexion et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: auth.php");
    exit;
}

// Protection contre la fixation de session
session_regenerate_id(true);

// Nettoyage de l'ID utilisateur
$user_id = filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT);

// Récupération des analyses médicales
try {
    $query = "SELECT * FROM analyses WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $pdoMedical->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $analyses = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    exit;
}
try {
    $query = "SELECT a.*, u.nom
              FROM analyses a 
              INNER JOIN utilisateurs u ON a.user_id = u.id 
              WHERE a.user_id = :user_id 
              ORDER BY a.created_at DESC";
    $stmt = $pdoMedical->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $analysesNom = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    exit;
}

// try {
//     $query = "SELECT u.nom, a.*
//               FROM utilisateurs u
//               INNER JOIN analyses a ON u.id = a.user_id
//               WHERE u.id = :user_id
//               ORDER BY a.created_at DESC";
//     $stmt = $pdoMedical->prepare($query);
//     $stmt->bindParam(":user_id", $_SESSION['user_id']);
//     $stmt->execute();
//     $analyses = $stmt->fetchAll();
// } catch (PDOException $e) {
//     echo "Erreur de connexion à la base de données : " . $e->getMessage();
//     exit;
// }

?>


<!DOCTYPE html>
<html>
<head>
    <title>Mes analyses médicales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            padding: 20px;
        }
        h1 {
            color: #3498db;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 300;
            font-size: 36px;
        }
        .welcome-message {
            text-align: center;
            font-size: 24px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 98%;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: #fff;
            font-weight: 400;
        }
        .normal {
            color: #2ecc71;
            font-weight: bold;
        }
        .modere {
            color: #f1c40f;
            font-weight: bold;
        }
        .grave {
            color: #e74c3c;
            font-weight: bold;
        }
        button.voir-plus {
            background-color: #3498db;
            color: #fff;
            border: none;
            margin: 10px;
            padding: 10px 20px;
            font-size: 15px;
            text-shadow: #333;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        button.voir-plus:hover {
            background-color: #2980b9;
        }
        .detail {
            background-color: #f9f9f9;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .aucune-analyse {
            text-align: center;
            font-size: 24px;
            color: #666;
            margin-top: 50px;
        }
        .btn-retour {
            background-color: blue;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        

    </style>
</head>
<body>
<button class="btn-retour" onclick="history.back()">Retour</button>
    <?php if ($analyses) { ?>
        <h1>Mes analyses médicales</h1>
        
        <p class="welcome-message">Bonjour <?= $analysesNom[0]['nom'] ?>, voici vos analyses médicales :</p>

        <p><strong>Date :</strong> <?= $analysesNom[0]['created_at'] ?></p>
        <p><strong>Symptômes :</strong> <?= isset($analysesNom[0]['symptoms']) ? $analysesNom[0]['symptoms'] : 'Non spécifiés' ?></p>
        <p><strong>Résultat :</strong> 
                <?= isset($analysesNom[0]['result']) ? $analysesNom[0]['result'] : 'Non spécifié' ?>
        </p>
        <p><strong>Interprétation :</strong> <?= isset($analysesNom[0]['severity']) ? ($analysesNom[0]['severity'] === 0 ? 'Vos résultats sont normaux.' : ($analysesNom[0]['severity'] === 2 ? 'Vos résultats indiquent une légère anomalie.' : 'Vos résultats indiquent une anomalie grave.')) : 'Non spécifié' ?></p>
        
        

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Résultat</th>
                    <th>Niveau de gravité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analyses as $analysis) { ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($analysis['created_at'])) ?></td>
                        <td><?= $analysis['result'] ?></td>
                        <td>
                            <?php 
                            switch ($analysis['severity']) {
                                case 0:
                                    echo "<span class='normal'>Normal</span>";
                                    break;
                                case 1:
                                    echo "<span class='modere'>Modéré</span>";
                                    break;
                                case 2:
                                    echo "<span class='grave'>Grave</span>";
                                    break;
                                default:
                                    echo "Inconnu";
                            }
                            ?>
                        </td>
                        <td>
                            <button class="voir-plus" data-id="<?= $analysis['id'] ?>">Voir plus</button>
                        </td>
                    </tr>
                    <tr class="detail" id="detail-<?= $analysis['id'] ?>" style="display: none;">
                        <td colspan="4">
                            <h2>Détails de l'analyse</h2>
                            <p><strong>Symptômes :</strong> <?= $analysis['symptoms'] ?></p>
                            <p><strong>Température :</strong> <?= $analysis['temperature'] ?>°C</p>
                            <p><strong>Taux d'oxygène :</strong> <?= $analysis['oxygenLevel'] ?>%</p>
                            <p><strong>Couleur des yeux :</strong> <?= $analysis['eyeColor'] ?></p>
                            <p><strong>Couleur de l'urine :</strong> <?= $analysis['urineColor'] ?></p>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="aucune-analyse">
            <p>.</p>
            <h1>Mes analyses médicales</h1>
            <p class="welcome-message">Bonjour, nous n'avons trouvé aucune analyse médicale pour vous.</p>
            <div class="aucune-analyse">
                <p>Ne vous inquiétez pas, cela signifie simplement que vous n'avez pas encore effectué d'analyse médicale.</p>
                <p>Si vous avez des questions ou des préoccupations concernant votre santé, n'hésitez pas à contacter votre médecin.</p>
            </div>
        </div>
    <?php } ?>

    <script>
        const buttons = document.querySelectorAll('.voir-plus');

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const detail = document.getElementById(`detail-${id}`);

                if (detail.style.display === 'none') {
                    detail.style.display = 'table-row';
                    button.textContent = 'Voir moins';
                } else {
                    detail.style.display = 'none';
                    button.textContent = 'Voir plus';
                }
            });
        });
    </script>
</body>
</html>

