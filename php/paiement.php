<?php
session_start();
include "db.php";

// 🔹 Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    die("🔴 Accès refusé.");
}

session_regenerate_id(true);

// 🔹 Récupérer l'email de l'utilisateur depuis la session
$userEmail = $_SESSION['email'] ?? null;

if (!$userEmail) {
    die("🔴 Erreur : Email utilisateur non défini.");
}

// 🔹 Récupération des infos académiques avec l'email
$queryEtudiant = $pdoCampus->prepare("SELECT * FROM etudiants WHERE email = ?");
$queryEtudiant->execute([$userEmail]);
$etudiantData = $queryEtudiant->fetch();

if (!$etudiantData) {
    die("🔴 Erreur : Aucun étudiant trouvé avec cet email.");
}

// 🔹 Détermination de l'année scolaire actuelle
$anneeActuelle = date("Y");
$moisActuel = date("m");
$anneeScolaire = ($moisActuel >= 9) ? "$anneeActuelle-" . ($anneeActuelle + 1) : ($anneeActuelle - 1) . "-$anneeActuelle";

// 🔍 Vérification des paiements effectués
$queryPayment = $pdoMedical->prepare("
    SELECT montant, statut, date_paiement FROM paiements WHERE utilisateur_id = ? AND annee_scolaire = ?
");
$queryPayment->execute([intval(str_replace("MED_", "", $_SESSION['user_id'])), $anneeScolaire]);
$paiementData = $queryPayment->fetch();

// 🔹 Si le paiement a été effectué, redirection vers le tableau de bord
if ($paiementData) {
    header("Location: dashboard_etudiant.php");
    exit;
}

// 🔹 Vérification stricte du montant à payer
$montantCMS = ($etudiantData['statut_etudiant'] === "Nouveau") ? 5000 : 3000;

// 🔹 Gestion du paiement
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $queryInsertPaiement = $pdoMedical->prepare("
            INSERT INTO paiements (utilisateur_id, montant, annee_scolaire, statut, date_paiement) 
            VALUES (?, ?, ?, 'payé', NOW())
        ");
        if ($queryInsertPaiement->execute([intval(str_replace("MED_", "", $_SESSION['user_id'])), $montantCMS, $anneeScolaire])) {
            $_SESSION['paiement_statut'] = 'payé';
            header("Location: dashboard_etudiant.php");
            exit;
        } else {
            $message = "🔴 Échec de l'enregistrement du paiement.";
        }
    } catch (PDOException $e) {
        $message = "🔴 Erreur SQL : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement CMS Médical</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --light-gray: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }
        
        h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin: 1.5rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .student-info {
            background-color: var(--light);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
        }
        
        .info-item strong {
            color: var(--gray);
            display: block;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .payment-section {
            background-color: var(--light);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .amount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin: 1rem 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--success);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background-color: var(--light);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }
        
        .payment-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            margin: 1rem 0;
        }
        
        .paid {
            color: var(--success);
        }
        
        .unpaid {
            color: var(--danger);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-top: 1.5rem;
        }
        
        .back-link:hover {
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-credit-card"></i> Paiement du CMS Médical</h1>
            <p>Gestion des paiements pour l'année scolaire <?php echo htmlspecialchars($anneeScolaire); ?></p>
        </header>
        
        <section class="student-info">
            <h2><i class="fas fa-user-graduate"></i> Informations Étudiant</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Nom complet</strong>
                    <?php echo htmlspecialchars($etudiantData['prenom'] . ' ' . $etudiantData['nom']); ?>
                </div>
                <div class="info-item">
                    <strong>Matricule</strong>
                    <?php echo htmlspecialchars($etudiantData['matricule']); ?>
                </div>
                <div class="info-item">
                    <strong>Email</strong>
                    <?php echo htmlspecialchars($etudiantData['email']); ?>
                </div>
                <div class="info-item">
                    <strong>Filière</strong>
                    <?php echo htmlspecialchars($etudiantData['filiere']); ?>
                </div>
                <div class="info-item">
                    <strong>Niveau</strong>
                    <?php echo htmlspecialchars($etudiantData['niveau']); ?>
                </div>
                <div class="info-item">
                    <strong>Statut</strong>
                    <?php echo htmlspecialchars($etudiantData['statut_etudiant']); ?>
                </div>
            </div>
        </section>
        
        <section class="payment-section">
            <h2><i class="fas fa-money-bill-wave"></i> Détails du Paiement</h2>
            
            <?php if ($paiementData): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p>Votre paiement a déjà été enregistré pour cette année scolaire.</p>
                </div>
                
                <div class="payment-status paid">
                    <i class="fas fa-check-circle"></i>
                    <span>Paiement confirmé</span>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Montant payé</strong>
                        <?php echo htmlspecialchars($paiementData['montant']); ?> FCFA
                    </div>
                    <div class="info-item">
                        <strong>Date de paiement</strong>
                        <?php echo htmlspecialchars($paiementData['date_paiement']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Statut</strong>
                        <?php echo htmlspecialchars($paiementData['statut']); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Vous devez payer le CMS avant d'accéder aux informations médicales.</p>
                </div>
                
                <div class="payment-status unpaid">
                    <i class="fas fa-times-circle"></i>
                    <span>Paiement en attente</span>
                </div>
                
                <div class="amount">
                    <?php echo htmlspecialchars($montantCMS); ?> FCFA
                </div>
                
                <form method="POST">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Payer maintenant
                    </button>
                </form>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger" style="margin-top: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        
        <a href="dashboard_etudiant.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>
</body>
</html>