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
$notifications = [];

// Récupération des informations du médecin
try {
    $stmt = $pdoMedical->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE id = ? AND role = 'medecin'");
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

// Marquer une notification comme lue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marquer_lu'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
        
        if ($notification_id) {
            try {
                $stmt = $pdoMedical->prepare("UPDATE notifications SET lu = 1 WHERE id = ? AND utilisateur_id = ?");
                $stmt->execute([$notification_id, $medecin_id]);
                $confirmation_message = "Notification marquée comme lue.";
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour de la notification : " . $e->getMessage();
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier Médical</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            border-left: 3px solid #0d6efd;
            margin-bottom: 5px;
            padding: 10px;
            transition: all 0.3s;
        }
        .notification-item:hover {
            background-color: #f1f1f1;
        }
        .notification-unread {
            border-left-color: #dc3545;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-calendar-heart"></i> Calendrier Médical
            </a>
            
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-light position-relative" type="button" id="dropdownNotifications" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="badge bg-danger notification-badge"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownNotifications">
                        <?php if (!empty($notifications)): ?>
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
                        <li><a class="dropdown-item" href="notifications.php"><i class="bi bi-list-check"></i> Voir toutes les notifications</a></li>
                    </ul>
                </div>
                
                <!-- Profil médecin -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownProfile" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> 
                        <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfile">
                        <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person"></i> Mon profil</a></li>
                        <li><a class="dropdown-item" href="parametres.php"><i class="bi bi-gear"></i> Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
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

        <div class="row">
            <div class="col-md-8">
                <div class="calendar-container p-3 mb-4">
                    <div id="calendar"></div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Rendez-vous à venir</h5>
                    </div>
                    <div class="card-body">
                        <div id="rdv-list">
                            <?php 
                            $now = new DateTime();
                            $upcoming_rdv = array_filter($rendezvous, function($rdv) use ($now) {
                                $rdv_date = new DateTime($rdv['date_heure']);
                                return $rdv_date > $now && $rdv['statut'] !== 'annulé';
                            });
                            
                            if (!empty($upcoming_rdv)): 
                                usort($upcoming_rdv, function($a, $b) {
                                    return strtotime($a['date_heure']) - strtotime($b['date_heure']);
                                });
                            ?>
                                <?php foreach (array_slice($upcoming_rdv, 0, 5) as $rdv): ?>
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($rdv['patient_image']): ?>
                                                <img src="uploads/<?= htmlspecialchars($rdv['patient_image']) ?>" class="patient-avatar me-2">
                                            <?php else: ?>
                                                <div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($rdv['patient_prenom']) . ' ' . htmlspecialchars($rdv['patient_nom']) ?></strong>
                                                <div class="text-muted small">
                                                    <?= formatFrenchDate($rdv['date_heure']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= getStatusClass($rdv['statut']) ?>">
                                                <?= htmlspecialchars($rdv['statut']) ?>
                                            </span>
                                            <a href="rendezvous.php?id=<?= $rdv['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Voir
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Aucun rendez-vous à venir</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Derniers rendez-vous</h5>
                    </div>
                    <div class="card-body">
                        <div id="last-rdv">
                            <?php 
                            $past_rdv = array_filter($rendezvous, function($rdv) use ($now) {
                                $rdv_date = new DateTime($rdv['date_heure']);
                                return $rdv_date < $now;
                            });
                            
                            if (!empty($past_rdv)): 
                                usort($past_rdv, function($a, $b) {
                                    return strtotime($b['date_heure']) - strtotime($a['date_heure']);
                                });
                            ?>
                                <?php foreach (array_slice($past_rdv, 0, 3) as $rdv): ?>
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($rdv['patient_image']): ?>
                                                <img src="uploads/<?= htmlspecialchars($rdv['patient_image']) ?>" class="patient-avatar me-2">
                                            <?php else: ?>
                                                <div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($rdv['patient_prenom']) . ' ' . htmlspecialchars($rdv['patient_nom']) ?></strong>
                                                <div class="text-muted small">
                                                    <?= formatFrenchDate($rdv['date_heure']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= getStatusClass($rdv['statut']) ?>">
                                                <?= htmlspecialchars($rdv['statut']) ?>
                                            </span>
                                            <a href="rendezvous.php?id=<?= $rdv['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Voir
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Aucun rendez-vous passé</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour les détails du RDV -->
    <div class="modal fade" id="rdvModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Détails du rendez-vous</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="rdvModalBody">
                    <!-- Contenu chargé dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/fr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation du calendrier
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'fr',
                initialView: 'dayGridMonth',
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
                            statut: '<?= $rdv['statut'] ?>',
                            motif: '<?= addslashes($rdv['motif']) ?>',
                            patient_id: '<?= $rdv['patient_id'] ?>',
                            patient_nom: '<?= addslashes($rdv['patient_nom']) ?>',
                            patient_prenom: '<?= addslashes($rdv['patient_prenom']) ?>',
                            patient_image: '<?= $rdv['patient_image'] ?>',
                            notification: <?= $rdv['notification_id'] ? 'true' : 'false' ?>,
                            notification_message: '<?= isset($rdv['notification_message']) ? addslashes($rdv['notification_message']) : '' ?>'
                        },
                        backgroundColor: getStatusColor('<?= $rdv['statut'] ?>'),
                        borderColor: getStatusColor('<?= $rdv['statut'] ?>'),
                        textColor: '#fff'
                    },
                    <?php endforeach; ?>
                ],
                eventClick: function(info) {
                    const event = info.event;
                    const modal = new bootstrap.Modal(document.getElementById('rdvModal'));
                    
                    let patientImage = event.extendedProps.patient_image 
                        ? `<img src="uploads/${event.extendedProps.patient_image}" class="patient-avatar me-2">`
                        : `<div class="patient-avatar bg-light d-flex align-items-center justify-content-center me-2">
                              <i class="bi bi-person"></i>
                           </div>`;
                    
                    let notificationBadge = event.extendedProps.notification
                        ? `<span class="badge bg-danger float-end">Notification</span>`
                        : '';
                    
                    let notificationSection = event.extendedProps.notification_message
                        ? `<div class="alert alert-info mt-3">
                              <strong><i class="bi bi-bell-fill"></i> Notification:</strong>
                              <p>${event.extendedProps.notification_message}</p>
                           </div>`
                        : '';
                    
                    document.getElementById('rdvModalBody').innerHTML = `
                        <div class="d-flex align-items-center mb-3">
                            ${patientImage}
                            <div>
                                <h4 class="mb-0">${event.extendedProps.patient_prenom} ${event.extendedProps.patient_nom}</h4>
                                <span class="badge ${getStatusClass(event.extendedProps.statut)}">
                                    ${event.extendedProps.statut}
                                </span>
                                ${notificationBadge}
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="bi bi-calendar-event"></i> Date et heure</h5>
                            <p>${event.start.toLocaleString('fr-FR', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="bi bi-card-text"></i> Motif</h5>
                            <p>${event.extendedProps.motif || 'Non spécifié'}</p>
                        </div>
                        
                        ${notificationSection}
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="dossier_patient.php?id=${event.extendedProps.patient_id}" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark-medical"></i> Dossier patient
                            </a>
                            <a href="rendezvous.php?id=${event.id}" class="btn btn-primary">
                                <i class="bi bi-pencil-square"></i> Modifier
                            </a>
                        </div>
                    `;
                    
                    modal.show();
                },
                dateClick: function(info) {
                    // Redirection vers la page de création de RDV avec la date présélectionnée
                    window.location.href = `prendre_rdv.php?date=${info.dateStr}`;
                }
            });
            
            calendar.render();
            
            // Fonction pour obtenir la couleur en fonction du statut
            function getStatusColor(statut) {
                switch (statut.toLowerCase()) {
                    case 'confirmé': return '#28a745';
                    case 'annulé': return '#dc3545';
                    case 'terminé': return '#6c757d';
                    case 'en attente': return '#ffc107';
                    default: return '#007bff';
                }
            }
            
            // Fonction pour obtenir la classe CSS en fonction du statut
            function getStatusClass(statut) {
                switch (statut.toLowerCase()) {
                    case 'confirmé': return 'bg-success';
                    case 'annulé': return 'bg-danger';
                    case 'terminé': return 'bg-secondary';
                    case 'en attente': return 'bg-warning';
                    default: return 'bg-primary';
                }
            }
        });
    </script>
</body>
</html>