<?php
session_start();
require_once "db.php";

// Configuration de l'environnement
define('ENV', 'dev'); // 'prod' en production

if (ENV === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('HTTP/1.1 403 Forbidden');
    exit('<p>Accès refusé. Seuls les médecins peuvent accéder à ce tableau de bord.</p>');
}

// Token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$medecin_id = filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT);

// Initialisation des variables
$confirmation_message = "";
$error = "";
$medecin = [];
$rendezvous = [];
$consultations = [];
$patients = [];
$personnel = [];
$notifications = [];
$today = date('Y-m-d');

// Récupération des informations du médecin
try {
    $stmt = $pdoMedical->prepare("SELECT id, nom, prenom, specialite, email, telephone FROM utilisateurs WHERE id = ? AND role = 'medecin'");
    $stmt->execute([$medecin_id]);
    $medecin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medecin) {
        throw new Exception("Médecin non trouvé dans la base de données");
    }
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des informations du médecin : " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Récupération des rendez-vous du médecin
try {
    $stmt = $pdoMedical->prepare("
        SELECT 
            r.id,
            r.date_heure,
            r.statut,
            r.motif,
            r.notes,
            r.created_at,
            r.updated_at,
            p.id AS patient_id,
            p.nom AS patient_nom,
            p.prenom AS patient_prenom,
            p.image AS patient_image,
            n.id AS notification_id,
            n.message AS notification_message,
            n.lu AS notification_lue
        FROM rendez_vous r
        JOIN utilisateurs p ON r.patient_id = p.id
        LEFT JOIN notifications n ON n.rendez_vous_id = r.id AND n.utilisateur_id = ?
        WHERE r.professionnel_sante_id = ?
        ORDER BY r.date_heure DESC
    ");
    $stmt->execute([$medecin_id, $medecin_id]);
    $rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des rendez-vous : " . $e->getMessage();
}

// Récupération des consultations du médecin
try {
    $stmt = $pdoMedical->prepare("
        SELECT 
            c.id,
            c.date_consultation,
            c.motif,
            c.diagnostic,
            c.traitement,
            c.notes,
            c.rendez_vous_id,
            r.date_heure AS rdv_date,
            p.id AS patient_id,
            p.nom AS patient_nom,
            p.prenom AS patient_prenom,
            p.image AS patient_image
        FROM consultations c
        JOIN rendez_vous r ON c.rendez_vous_id = r.id
        JOIN utilisateurs p ON r.patient_id = p.id
        WHERE r.professionnel_sante_id = ?
        ORDER BY c.date_consultation DESC
        LIMIT 5
    ");
    $stmt->execute([$medecin_id]);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des consultations : " . $e->getMessage();
}

// Récupération des patients suivis
try {
    $stmt = $pdoMedical->prepare("
        SELECT DISTINCT
            u.id,
            u.nom,
            u.prenom,
            u.image,
            u.email,
            u.telephone
        FROM utilisateurs u
        JOIN rendez_vous r ON u.id = r.patient_id
        WHERE r.professionnel_sante_id = ?
        ORDER BY u.nom, u.prenom
        LIMIT 10
    ");
    $stmt->execute([$medecin_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des patients : " . $e->getMessage();
}

// Récupération du personnel médical (autres médecins et infirmiers)
try {
    $stmt = $pdoMedical->prepare("
        SELECT 
            id,
            nom,
            prenom,
            role,
            specialite,
            image
        FROM utilisateurs
        WHERE role IN ('medecin', 'infirmier') AND id != ?
        ORDER BY role, nom, prenom
    ");
    $stmt->execute([$medecin_id]);
    $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération du personnel médical : " . $e->getMessage();
}

// Récupération des notifications non lues
try {
    $stmt = $pdoMedical->prepare("
        SELECT 
            n.id,
            n.message,
            n.lu,
            n.created_at,
            r.id AS rdv_id,
            p.nom AS patient_nom,
            p.prenom AS patient_prenom
        FROM notifications n
        JOIN rendez_vous r ON n.rendez_vous_id = r.id
        JOIN utilisateurs p ON r.patient_id = p.id
        WHERE n.utilisateur_id = ? AND n.lu = 0
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$medecin_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des notifications : " . $e->getMessage();
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Marquer une notification comme lue
        if (isset($_POST['marquer_lu'])) {
            $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
            
            if ($notification_id) {
                try {
                    $stmt = $pdoMedical->prepare("UPDATE notifications SET lu = 1 WHERE id = ? AND utilisateur_id = ?");
                    $stmt->execute([$notification_id, $medecin_id]);
                    $confirmation_message = "Notification marquée comme lue.";
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } catch (PDOException $e) {
                    $error = "Erreur lors de la mise à jour de la notification : " . $e->getMessage();
                }
            }
        }
        
        // Annuler un rendez-vous
        if (isset($_POST['annuler_rdv'])) {
            $rdv_id = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
            $motif_annulation = filter_input(INPUT_POST, 'motif_annulation', FILTER_SANITIZE_STRING);
            
            if ($rdv_id && $motif_annulation) {
                try {
                    $pdoMedical->beginTransaction();
                    
                    // Mise à jour du statut du RDV
                    $stmt = $pdoMedical->prepare("UPDATE rendez_vous SET statut = 'annulé', notes = CONCAT(IFNULL(notes, ''), ?) WHERE id = ? AND professionnel_sante_id = ?");
                    $stmt->execute(["\n\nAnnulation: ".$motif_annulation, $rdv_id, $medecin_id]);
                    
                    // Création d'une notification pour le patient
                    $rdv_info = $pdoMedical->prepare("SELECT patient_id, date_heure FROM rendez_vous WHERE id = ?")->fetch([$rdv_id]);
                    
                    $stmt = $pdoMedical->prepare("
                        INSERT INTO notifications (utilisateur_id, rendez_vous_id, message, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $message = "Votre rendez-vous du ".date('d/m/Y H:i', strtotime($rdv_info['date_heure']))." a été annulé. Motif: ".$motif_annulation;
                    $stmt->execute([$rdv_info['patient_id'], $rdv_id, $message]);
                    
                    $pdoMedical->commit();
                    $confirmation_message = "Le rendez-vous a été annulé et le patient a été notifié.";
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } catch (PDOException $e) {
                    $pdoMedical->rollBack();
                    $error = "Erreur lors de l'annulation du rendez-vous : " . $e->getMessage();
                }
            }
        }
        
        // Confirmer un rendez-vous
        if (isset($_POST['confirmer_rdv'])) {
            $rdv_id = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
            
            if ($rdv_id) {
                try {
                    $pdoMedical->beginTransaction();
                    
                    // Mise à jour du statut du RDV
                    $stmt = $pdoMedical->prepare("UPDATE rendez_vous SET statut = 'confirmé' WHERE id = ? AND professionnel_sante_id = ?");
                    $stmt->execute([$rdv_id, $medecin_id]);
                    
                    // Création d'une notification pour le patient
                    $rdv_info = $pdoMedical->prepare("SELECT patient_id, date_heure FROM rendez_vous WHERE id = ?")->fetch([$rdv_id]);
                    
                    $stmt = $pdoMedical->prepare("
                        INSERT INTO notifications (utilisateur_id, rendez_vous_id, message, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $message = "Votre rendez-vous du ".date('d/m/Y H:i', strtotime($rdv_info['date_heure']))." a été confirmé.";
                    $stmt->execute([$rdv_info['patient_id'], $rdv_id, $message]);
                    
                    $pdoMedical->commit();
                    $confirmation_message = "Le rendez-vous a été confirmé et le patient a été notifié.";
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } catch (PDOException $e) {
                    $pdoMedical->rollBack();
                    $error = "Erreur lors de la confirmation du rendez-vous : " . $e->getMessage();
                }
            }
        }
    }
}

// Fonction pour formater la date
function formatFrenchDate($date) {
    $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    
    $timestamp = strtotime($date);
    return ucfirst($days[date('w', $timestamp)]) . ' ' . date('d', $timestamp) . ' ' . $months[date('n', $timestamp)-1] . ' ' . date('Y H:i', $timestamp);
}

// Fonction pour obtenir la classe CSS du statut
function getStatusClass($statut) {
    switch (strtolower($statut)) {
        case 'confirmé': return 'bg-success';
        case 'annulé': return 'bg-danger';
        case 'terminé': return 'bg-secondary';
        case 'en attente': return 'bg-warning';
        default: return 'bg-primary';
    }
}

// Fonction pour calculer l'âge à partir de la date de naissance
function calculateAge($birthdate) {
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Médical</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@5.10.1/main.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            margin-bottom: 5px;
            border-radius: 5px;
            padding: 10px 15px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7em;
        }
        
        .notification-item {
            border-left: 3px solid var(--secondary-color);
            margin-bottom: 5px;
            padding: 10px;
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            background-color: #f1f1f1;
        }
        
        .notification-unread {
            border-left-color: var(--accent-color);
            background-color: #f8f9fa;
        }
        
        .fc-event {
            cursor: pointer;
            font-size: 0.85em;
            padding: 2px 4px;
        }
        
        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-card .count {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .rdv-item {
            transition: all 0.3s;
            border-left: 3px solid var(--secondary-color);
        }
        
        .rdv-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .consultation-item {
            border-left: 3px solid #27ae60;
        }
        
        .patient-item {
            transition: all 0.3s;
        }
        
        .patient-item:hover {
            background-color: #f8f9fa;
        }
        
        .today-highlight {
            background-color: #fff3cd;
            border-radius: 5px;
            padding: 2px 5px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column flex-shrink-0 p-3" style="width: 250px;">
            <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <span class="fs-4">Médecin</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="bi bi-speedometer2"></i>
                        Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="rendezvous.php" class="nav-link">
                        <i class="bi bi-calendar-check"></i>
                        Rendez-vous
                    </a>
                </li>
                <li>
                    <a href="consultations/voir_consultation.php" class="nav-link">
                        <i class="bi bi-file-earmark-medical"></i>
                        Consultations
                    </a>
                </li>
                <li>
                    <a href="patients.php" class="nav-link">
                        <i class="bi bi-people"></i>
                        Patients
                    </a>
                </li>
                <li>
                    <a href="dossiers/dossier_patient.php" class="nav-link">
                        <i class="bi bi-folder"></i>
                        Dossiers médicaux
                    </a>
                </li>
            
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown">
                    <?php if (isset($medecin['image'])): ?>
                        <img src="../img/uploads/<?= htmlspecialchars($medecin['image']) ?>" alt="Photo profil" width="32" height="32" class="rounded-circle me-2">
                    <?php else: ?>
                        <i class="bi bi-person-circle me-2"></i>
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-speedometer2"></i> Tableau de bord
                    <small class="text-muted fs-6"><?= date('d/m/Y') ?></small>
                </h2>
                
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-primary position-relative" type="button" id="dropdownNotifications" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="badge bg-danger notification-badge"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownNotifications" style="width: 300px;">
                        <?php if (!empty($notifications)): ?>
                            <li><h6 class="dropdown-header">Nouvelles notifications</h6></li>
                            <?php foreach ($notifications as $notif): ?>
                                <li>
                                    <div class="notification-item <?= !$notif['lu'] ? 'notification-unread' : '' ?>">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($notif['patient_nom']) . ' ' . htmlspecialchars($notif['patient_prenom']) ?></strong>
                                            <small><?= date('H:i', strtotime($notif['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-1"><?= htmlspecialchars($notif['message']) ?></p>
                                        <form method="POST" class="text-end">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                            <button type="submit" name="marquer_lu" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-check-circle"></i> Marquer comme lu
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="#">Aucune nouvelle notification</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-center" href="notifications.php"><i class="bi bi-list-check"></i> Voir toutes les notifications</a></li>
                    </ul>
                </div>
            </div>

            <!-- Messages d'alerte -->
            <?php if ($confirmation_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($confirmation_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-primary">
                        <i class="bi bi-calendar-check"></i>
                        <div class="count">
                            <?= count(array_filter($rendezvous, function($rdv) { 
                                return date('Y-m-d', strtotime($rdv['date_heure'])) == date('Y-m-d'); 
                            })) ?>
                        </div>
                        <div class="label">RDV aujourd'hui</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-success">
                        <i class="bi bi-people"></i>
                        <div class="count"><?= count($patients) ?></div>
                        <div class="label">Patients suivis</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-warning">
                        <i class="bi bi-file-earmark-text"></i>
                        <div class="count"><?= count($consultations) ?></div>
                        <div class="label">Consultations récentes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-info">
                        <i class="bi bi-bell"></i>
                        <div class="count"><?= count($notifications) ?></div>
                        <div class="label">Nouvelles notifications</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Calendrier et RDV -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-calendar3"></i> Calendrier</h5>
                            <a href="prendre_rdv.php" class="btn btn-sm btn-light">
                                <i class="bi bi-plus-circle"></i> Nouveau RDV
                            </a>
                        </div>
                        <div class="card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Rendez-vous aujourd'hui</h5>
                                </div>
                                <div class="card-body">
                                    <div id="rdv-list">
                                        <?php 
                                        $today_rdv = array_filter($rendezvous, function($rdv) use ($today) {
                                            return date('Y-m-d', strtotime($rdv['date_heure'])) == $today && $rdv['statut'] !== 'annulé';
                                        });
                                        
                                        if (!empty($today_rdv)): 
                                            usort($today_rdv, function($a, $b) {
                                                return strtotime($a['date_heure']) - strtotime($b['date_heure']);
                                            });
                                        ?>
                                            <?php foreach (array_slice($today_rdv, 0, 5) as $rdv): ?>
                                                <div class="mb-3 p-3 rdv-item rounded">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <?php if ($rdv['patient_image']): ?>
                                                            <img src="../img/uploads/<?= htmlspecialchars($rdv['patient_image']) ?>" class="patient-avatar me-2">
                                                        <?php else: ?>
                                                            <div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                                                                <i class="bi bi-person"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></strong>
                                                            <div class="text-muted small">
                                                                <?= date('H:i', strtotime($rdv['date_heure'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="badge <?= getStatusClass($rdv['statut']) ?>">
                                                            <?= htmlspecialchars($rdv['statut']) ?>
                                                        </span>
                                                        <div>
                                                            <a href="rendezvous.php?id=<?= $rdv['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <?php if ($rdv['statut'] === 'en attente'): ?>
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                                    <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                                                    <button type="submit" name="confirmer_rdv" class="btn btn-sm btn-outline-success" title="Confirmer">
                                                                        <i class="bi bi-check-circle"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-outline-danger" title="Annuler" data-bs-toggle="modal" data-bs-target="#annulerRdvModal" data-rdv-id="<?= $rdv['id'] ?>">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="alert alert-info">Aucun rendez-vous prévu aujourd'hui</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-file-earmark-medical"></i> Dernières consultations</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($consultations)): ?>
                                        <?php foreach ($consultations as $consult): ?>
                                            <div class="mb-3 p-3 consultation-item rounded">
                                                <div class="d-flex align-items-center mb-2">
                                                    <?php if ($consult['patient_image']): ?>
                                                        <img src="uploads/<?= htmlspecialchars($consult['patient_image']) ?>" class="patient-avatar me-2">
                                                    <?php else: ?>
                                                        <div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                                                            <i class="bi bi-person"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($consult['patient_prenom'] . ' ' . $consult['patient_nom']) ?></strong>
                                                        <div class="text-muted small">
                                                            <?= date('d/m/Y', strtotime($consult['date_consultation'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="mb-2"><strong>Motif:</strong> <?= htmlspecialchars($consult['motif']) ?></p>
                                                <div class="d-flex justify-content-end">
                                                    <a href="consultation.php?id=<?= $consult['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Voir détails
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">Aucune consultation récente</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patients et équipe -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Patients récents</h5>
                            <a href="patients.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-right"></i> Voir tous
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($patients)): ?>
                                <?php foreach ($patients as $patient): ?>
                                    <div class="mb-3 p-2 patient-item rounded">
                                        <div class="d-flex align-items-center">
                                            <?php if ($patient['image']): ?>
                                                <img src="../img/uploads/<?= htmlspecialchars($patient['image']) ?>" class="patient-avatar me-2">
                                            <?php else: ?>
                                                <div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <strong><?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?></strong>
                                                <div class="text-muted small">
                                                    <!-- <?= $patient['genre'] == 'M' ? 'Homme' : 'Femme' ?>, <?= calculateAge($patient['date_naissance']) ?> ans -->
                                                </div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="dossiers/dossier_patient.php?id=<?= $patient['id'] ?>"><i class="bi bi-file-earmark-text"></i> Dossier médical</a></li>
                                                    <li><a class="dropdown-item" href="prendre_rdv.php?patient_id=<?= $patient['id'] ?>"><i class="bi bi-calendar-plus"></i> Nouveau RDV</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="patient.php?id=<?= $patient['id'] ?>"><i class="bi bi-info-circle"></i> Détails</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Aucun patient récent</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Équipe médicale</h5>
                            <a href="equipe.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-right"></i> Voir tous
                            </a>
                        </div>
                        <div class="card-body">
                        <?php foreach ($personnel as $member): ?>
                                    <div class="mb-3 p-2 d-flex align-items-center">
                                        <?php if ($member['image']): ?>
                                            <img src="../img/uploads/<?= htmlspecialchars($member['image']) ?>" class="patient-avatar me-2">
                                        <?php else: ?>
                                            <div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?></strong>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars(ucfirst($member['role'])) ?>
                                                <?php if ($member['specialite']): ?>
                                                    - <?= htmlspecialchars($member['specialite']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="messagerie.php?to=<?= $member['id'] ?>" class="btn btn-sm btn-outline-primary" title="Envoyer un message">
                                            <i class="bi bi-envelope"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                <div class="alert alert-info">Aucun membre d'équipe trouvé</div>
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Annuler RDV -->
    <div class="modal fade" id="annulerRdvModal" tabindex="-1" aria-labelledby="annulerRdvModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="annulerRdvModalLabel">Annuler un rendez-vous</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="rdv_id" id="modalRdvId">
                        <div class="mb-3">
                            <label for="motif_annulation" class="form-label">Motif d'annulation</label>
                            <textarea class="form-control" id="motif_annulation" name="motif_annulation" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" name="annuler_rdv" class="btn btn-danger">Confirmer l'annulation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/fr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@5.10.1/main.min.js"></script>
    <script>
        // Initialisation du calendrier
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                locale: 'fr',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php foreach ($rendezvous as $rdv): ?>
                    {
                        id: '<?= $rdv['id'] ?>',
                        title: '<?= addslashes($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?>',
                        start: '<?= $rdv['date_heure'] ?>',
                        extendedProps: {
                            motif: '<?= addslashes($rdv['motif']) ?>',
                            statut: '<?= $rdv['statut'] ?>'
                        },
                        backgroundColor: 
                            '<?= $rdv['statut'] === 'annulé' ? '#dc3545' : 
                              ($rdv['statut'] === 'confirmé' ? '#28a745' : 
                              ($rdv['statut'] === 'terminé' ? '#6c757d' : '#ffc107')) ?>',
                        borderColor: 
                            '<?= $rdv['statut'] === 'annulé' ? '#dc3545' : 
                              ($rdv['statut'] === 'confirmé' ? '#28a745' : 
                              ($rdv['statut'] === 'terminé' ? '#6c757d' : '#ffc107')) ?>',
                        textColor: '#fff'
                    },
                    <?php endforeach; ?>
                ],
                eventClick: function(info) {
                    window.location.href = 'rendezvous.php?id=' + info.event.id;
                },
                businessHours: {
                    daysOfWeek: [1, 2, 3, 4, 5],
                    startTime: '08:00',
                    endTime: '18:00'
                },
                nowIndicator: true,
                navLinks: true,
                selectable: true,
                selectMirror: true,
                select: function(arg) {
                    window.location.href = 'prendre_rdv.php?date=' + arg.startStr;
                }
            });
            calendar.render();

            // Gestion du modal d'annulation
            var annulerModal = document.getElementById('annulerRdvModal');
            annulerModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var rdvId = button.getAttribute('data-rdv-id');
                var modal = this;
                modal.querySelector('#modalRdvId').value = rdvId;
            });

            // Marquer les notifications comme lues
            var notificationDropdown = document.getElementById('dropdownNotifications');
            notificationDropdown.addEventListener('shown.bs.dropdown', function () {
                // Ici vous pourriez ajouter une requête AJAX pour marquer les notifications comme lues
            });
        });

        // Rafraîchissement automatique toutes les 5 minutes
        setTimeout(function(){
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>