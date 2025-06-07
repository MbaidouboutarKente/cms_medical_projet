<?php
session_start();
require_once "db.php";

if (isset($_SESSION['user_id'])) {
    // Enregistrement du log avant de détruire la session
    enregistrerActivite($pdoMedical, str_replace(['ETU_', 'MED_'], '', $_SESSION['user_id']), 
                       "Déconnexion", "Session terminée");
}

session_destroy();
header("Location: ../index.html");
// header("Location: auth.php");
exit;
?>


<!-- 
*Tables nécessaires*


CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('etudiant', 'admin') NOT NULL DEFAULT 'etudiant'
);

CREATE TABLE medicaments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    categorie VARCHAR(255) NOT NULL,
    stock INT NOT NULL DEFAULT 0
);

CREATE TABLE commandes_pharma (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en attente', 'prête', 'annulée') NOT NULL DEFAULT 'en attente',
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
);

CREATE TABLE details_commande (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes_pharma(id),
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id)
);
 -->

 *Fichier PHP complet*

<?php
session_start();
require_once "db.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);


// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: auth.php");
    exit;
}

// Génération du token CSRF
if (empty($_SESSION['pharma_token'])) {
    $_SESSION['pharma_token'] = bin2hex(random_bytes(32));
    $_SESSION['pharma_token_time'] = time();
}

// Traitement du formulaire de commande
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'commander') {
    try {
        // Vérification CSRF
        if (empty($_POST['token']) || !hash_equals($_SESSION['pharma_token'], $_POST['token'])) {
            throw new Exception("Token CSRF invalide");
        }

        // Validation des médicaments
        $medicaments = [];
        foreach ($_POST['medicaments'] as $id => $qty) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $qty = filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3]]);
            if ($id === false || $qty === false) {
                continue;
            }
            $medicaments[$id] = $qty;
        }

        // Vérification de la disponibilité en stock
        $pdo->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($medicaments), '?'));
            $stmt = $pdo->prepare("SELECT id, stock FROM medicaments WHERE id IN ($placeholders) FOR UPDATE");
            $stmt->execute(array_keys($medicaments));
            $stock_dispo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($medicaments as $id => $qty) {
                if (!isset($stock_dispo[$id])) {
                    throw new Exception("Médicament ID $id non trouvé");
                }
                if ($stock_dispo[$id] < $qty) {
                    throw new Exception("Stock insuffisant pour le médicament ID $id");
                }
            }

            // Enregistrement de la commande
            $stmt = $pdo->prepare("INSERT INTO commandes_pharma (user_id, date_commande) VALUES (?, NOW())");
            $stmt->execute([$_SESSION['user_id']]);
            $commande_id = $pdo->lastInsertId();

            // Détails de la commande et mise à jour du stock
            foreach ($medicaments as $id => $qty) {
                $stmt = $pdo->prepare("INSERT INTO details_commande (commande_id, medicament_id, quantite) VALUES (?, ?, ?)");
                $stmt->execute([$commande_id, $id, $qty]);
                $stmt = $pdo->prepare("UPDATE medicaments SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$qty, $id]);
            }

            $pdo->commit();
            $_SESSION['pharma_success'] = "Votre commande #$commande_id a bien été enregistrée.";
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        $_SESSION['pharma_errors'] = [$e->getMessage()];
    }

    header("Location: pharmacie.php");
    exit;
}

// Récupération des médicaments disponibles
$medicaments = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM medicaments WHERE stock > 0 ORDER BY categorie, nom");
    $stmt->execute();
    $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['pharma_errors'] = ["Erreur technique. Veuillez réessayer plus tard."];
}

// Récupération de l'historique des commandes
$historique = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.date_commande, c.statut, 
               COUNT(d.id) as nb_medicaments, 
               SUM(d.quantite) as total_items
        FROM commandes_pharma c
        JOIN details_commande d ON c.id = d.commande_id
        WHERE c.user_id = ?
        GROUP BY c.id
        ORDER BY c.date_commande DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // On ne bloque pas l'affichage pour cette erreur
}

?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacie Universitaire</title>
    <style>
        /* Styles CSS */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-gray: #ecf0f1;
            --dark-gray: #7f8c8d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            text-align: center;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        h1 {
            margin: 0;
            font-size: 2.2rem;
        }
        
        .pharma-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .medicaments-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .categorie-title {
            grid-column: 1 / -1;
            font-size: 1.4rem;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
            margin-top: 20px;
        }
        
        .medicament-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .medicament-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .medicament-nom {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 10px;
            color: var(--primary-color);
        }
        
        .medicament-description {
            color: var(--dark-gray);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .medicament-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .medicament-stock {
            font-size: 0.85rem;
            padding: 3px 8px;
            border-radius: 10px;
            background-color: var(--light-gray);
        }
        
        .stock-low {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .stock-critical {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .quantite-select {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .historique-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-top: 30px;
        }
        
        .historique-title {
            font-size: 1.4rem;
            color: var(--secondary-color);
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        .commande-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .commande-item:last-child {
            border-bottom: none;
        }
        
        .commande-date {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .commande-statut {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .statut-en-attente {
            background-color: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }
        
        .statut-prete {
            background-color: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }
        
        .statut-annulee {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, var(--secondary-color), #20638f);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .btn-submit:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }
        
        .no-medicaments {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: var(--dark-gray);
        }
        
        @media (max-width: 768px) {
            .medicaments-container {
                grid-template-columns: 1fr;
            }
            
            .commande-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Pharmacie Universitaire</h1>
            <p class="pharma-subtitle">Service réservé aux étudiants - Ordonnance non requise</p>
        </div>
    </header>
    <div class="container">
        <!-- Affichage des messages de succès/erreur -->
        <?php if (!empty($_SESSION['pharma_success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['pharma_success']) ?>
            </div>
            <?php unset($_SESSION['pharma_success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['pharma_errors'])): ?>
            <div class="alert alert-danger">
                <h3>Erreur(s) lors de la commande</h3>
                <ul>
                    <?php foreach ($_SESSION['pharma_errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['pharma_errors']); ?>
        <?php endif; ?>
        
        <!-- Formulaire de commande -->
        <form method="POST" id="commandeForm" novalidate>
            <input type="hidden" name="action" value="commander">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['pharma_token']) ?>">
            <div class="medicaments-container">
                <?php if (empty($medicaments)): ?>
                    <div class="no-medicaments">
                        <p>Aucun médicament disponible pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($medicaments as $medicament): ?>
                        <div class="medicament-card">
                            <h3 class="medicament-nom"><?= htmlspecialchars($medicament['nom']) ?></h3>
                            <p class="medicament-description"><?= htmlspecialchars($medicament['description']) ?></p>
                            <div class="medicament-footer">
                                <span class="medicament-stock">Stock: <?= htmlspecialchars($medicament['stock']) ?></span>
                                <select name="medicaments[<?= htmlspecialchars($medicament['id']) ?>]" class="quantite-select">
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($medicaments)): ?>
                <button type="submit" class="btn-submit">Valider ma commande</button>
            <?php endif; ?>
        </form>
        
        <!-- Historique des commandes -->
        <?php if (!empty($historique)): ?>
            <div class="historique-container">
                <h2 class="historique-title">Vos dernières commandes</h2>
                <?php foreach ($historique as $commande): ?>
                    <div class="commande-item">
                        <div>
                            <span class="commande-date">Commande #<?= htmlspecialchars($commande['id']) ?> - <?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></span>
                            <span><?= htmlspecialchars($commande['nb_medicaments']) ?> médicament(s) - <?= htmlspecialchars($commande['total_items']) ?> article(s)</span>
                        </div>
                        <span class="commande-statut <?= $commande['statut'] === 'prête' ? 'statut-prete' : ($commande['statut'] === 'annulée' ? 'statut-annulee' : 'statut-en-attente') ?>">
                            <?= htmlspecialchars(ucfirst($commande['statut'])) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Code JavaScript pour la validation du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('commandeForm');
            const submitBtn = document.querySelector('.btn-submit');

            form.addEventListener('submit', function(e) {
                let valid = true;
                const selects = document.querySelectorAll('.quantite-select');
                selects.forEach(select => {
                    if (parseInt(select.value) > 0) {
                        valid = true;
                    }
                });
                if (!valid) {
                    e.preventDefault();
                    alert('Veuillez sélectionner au moins un médicament.');
                }
            });
        });
    </script>
</body>
</html>
