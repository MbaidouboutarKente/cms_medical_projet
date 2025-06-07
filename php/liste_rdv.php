<?php
// dashboard_etudiant.php
session_start();
require_once "db.php";

// Activation du debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: auth.php");
    exit;
}

// Sécurité
session_regenerate_id(true);

// Récupération données utilisateur
$userID = filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    // 1. Récupération du profil complet (utilisateur + étudiant + patient)
    $queryUser = $pdoMedical->prepare("
        SELECT u.*, e.*, p.id as patient_id
        FROM utilisateurs u
        JOIN campus_db.etudiants e ON u.matricule = e.matricule
        LEFT JOIN patients p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $queryUser->execute([$userID]);
    $userData = $queryUser->fetch();

    if (!$userData) {
        throw new Exception("Profil étudiant introuvable");
    }

    // 2. Vérification de l'existence du patient
    if (empty($userData['patient_id'])) {
        throw new Exception("Aucun profil patient associé à votre compte");
    }
    $patientID = $userData['patient_id'];

    // 3. Vérification paiement (simplifiée pour l'exemple)
    $queryPayment = $pdoMedical->prepare("
        SELECT statut FROM paiements 
        WHERE utilisateur_id = ? 
        ORDER BY date_paiement DESC 
        LIMIT 1
    ");
    $queryPayment->execute([$userID]);
    $paiementStatut = strtolower($queryPayment->fetchColumn() ?? 'non payé');
    $accesServices = in_array($paiementStatut, ['payé', 'paye', 'paid', 'validé']);

    // 4. Récupération des rendez-vous
    $rdvAVenir = [];
    $rdvNotifications = [];

    if ($accesServices) {
        // RDV à venir (inclut maintenant le motif)
        $queryRdv = $pdoMedical->prepare("
            SELECT r.*, 
                   u.nom AS medecin_nom, 
                   u.prenom AS medecin_prenom,
                   u.specialite,
                   'medecin' AS type_professionnel
            FROM rendez_vous r
            JOIN utilisateurs u ON r.professionnel_sante_id = u.id
            WHERE r.patient_id = ?
              AND r.date_heure >= NOW()
              AND r.statut IN ('en attente', 'confirmé')
            ORDER BY r.date_heure ASC
            LIMIT 5
        ");
        $queryRdv->execute([$patientID]);
        $rdvAVenir = $queryRdv->fetchAll();

        // Notifications (inclut les statuts annulé et rejeté)
        $queryNotif = $pdoMedical->prepare("
            SELECT r.*, 
                   u.nom AS medecin_nom, 
                   u.prenom AS medecin_prenom,
                   u.specialite,
                   'medecin' AS type_professionnel
            FROM rendez_vous r
            JOIN utilisateurs u ON r.professionnel_sante_id = u.id
            WHERE r.patient_id = ?
              AND r.statut IN ('confirmé', 'rejeté', 'annulé')
              AND r.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY r.updated_at DESC
            LIMIT 5
        ");
        $queryNotif->execute([$patientID]);
        $rdvNotifications = $queryNotif->fetchAll();
    }

} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?= htmlspecialchars($userData['prenom'] ?? 'Etudiant') ?></title>
    <link rel="stylesheet" href="../css/etudiant.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .rdv-card {
            border-left: 4px solid #4e73df;
            margin-bottom: 15px;
        }
        .rdv-confirme { border-left-color: #1cc88a; }
        .rdv-attente { border-left-color: #f6c23e; }
        .rdv-annule { border-left-color: #e74a3b; }
        .rdv-motif {
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Section RDV à venir -->
        <?php if ($accesServices): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt mr-2"></i>Mes rendez-vous</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($rdvAVenir)): ?>
                        <div class="row">
                            <?php foreach ($rdvAVenir as $rdv): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card rdv-card rdv-<?= $rdv['statut'] === 'confirmé' ? 'confirme' : 'attente' ?>">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?= date('d/m/Y H:i', strtotime($rdv['date_heure'])) ?>
                                                <span class="badge badge-<?= 
                                                    $rdv['statut'] === 'confirmé' ? 'success' : 
                                                    ($rdv['statut'] === 'en attente' ? 'warning' : 'danger')
                                                ?> float-right">
                                                    <?= ucfirst($rdv['statut']) ?>
                                                </span>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?>
                                            </h6>
                                            <p class="card-text">
                                                <strong>Spécialité:</strong> <?= htmlspecialchars($rdv['specialite']) ?>
                                            </p>
                                            <?php if (!empty($rdv['motif'])): ?>
                                                <p class="rdv-motif">
                                                    <strong>Motif:</strong> <?= htmlspecialchars($rdv['motif']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between">
                                                <a href="details_rdv.php?id=<?= $rdv['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-info-circle"></i> Détails
                                                </a>
                                                <?php if ($rdv['statut'] === 'en attente'): ?>
                                                    <a href="annuler_rdv.php?id=<?= $rdv['id'] ?>" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="liste_rdv.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Voir tous mes rendez-vous
                        </a>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h4>Aucun rendez-vous à venir</h4>
                            <p class="text-muted">Vous n'avez pas de rendez-vous programmé pour le moment.</p>
                            <a href="prendre_rdv.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Prendre un rendez-vous
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle"></i> Accès limité</h4>
                <p>Vous devez régler votre contribution pour accéder aux services de rendez-vous.</p>
                <a href="paiement.php" class="btn btn-primary">
                    <i class="fas fa-credit-card"></i> Payer maintenant
                </a>
            </div>
        <?php endif; ?>

        <!-- Section Debug (optionnelle) -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="card mt-4">
                <div class="card-header bg-dark text-white">
                    <h4>Informations de débogage</h4>
                </div>
                <div class="card-body">
                    <pre><?php 
                        echo "User ID: " . $userID . "\n";
                        echo "Patient ID: " . ($patientID ?? 'null') . "\n";
                        echo "Statut paiement: " . $paiementStatut . "\n";
                        echo "Nombre de RDV: " . count($rdvAVenir) . "\n";
                        print_r($rdvAVenir);
                    ?></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>