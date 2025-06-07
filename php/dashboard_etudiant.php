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
$userID = filter_var(str_replace("MED_", "", $_SESSION['user_id']), FILTER_SANITIZE_NUMBER_INT);

// Récupérer l'id du patient lié à l'utilisateur
$patientID = null;
$stmt = $pdoMedical->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$userID]);
$patientID = $stmt->fetchColumn();
if (!$patientID) {
    $patientID = 0; // Pour éviter les erreurs SQL
}

// Traitement de la marque comme lue
if (isset($_POST['marquer_comme_lu'])) {
    $rdvId = filter_var($_POST['rdv_id'], FILTER_SANITIZE_NUMBER_INT);
    $updateNotif = $pdoMedical->prepare("UPDATE rendez_vous SET notification_envoyee = 1 WHERE id = ?");
    $updateNotif->execute([$rdvId]);
}

try {
    // Récupération profil de base
    $queryUser = $pdoMedical->prepare("SELECT u.*, e.* 
                                     FROM medical_db.utilisateurs u
                                     JOIN campus_db.etudiants e ON u.matricule = e.matricule
                                     WHERE u.id = ?");
    $queryUser->execute([$userID]);
    $userData = $queryUser->fetch();

    if (!$userData) throw new Exception("Profil introuvable");

    // Détermination année scolaire (Septembre-Août)
    $moisActuel = date("m");
    $anneeScolaire = ($moisActuel >= 9) ? date("Y")."-".(date("Y")+1) : (date("Y")-1)."-".date("Y");

    // Vérification paiement avec gestion des erreurs
    $paiementStatut = 'non payé';
    $queryPayment = $pdoMedical->prepare("SELECT statut FROM paiements 
                                        WHERE utilisateur_id = ? AND annee_scolaire = ? 
                                        ORDER BY date_paiement DESC LIMIT 1");
    if ($queryPayment->execute([$userID, $anneeScolaire])) {
        $paiementStatut = strtolower(trim($queryPayment->fetchColumn() ?? 'non payé'));
    }

    $accesServices = in_array($paiementStatut, ['payé', 'paye', 'paid', 'validé']);

    // Récupération RDV à venir et notifications si accès autorisé
    $rdvAVenir = [];
    $rdvNotifications = [];
    $nbNouvellesNotifs = 0;
    
    if ($accesServices && $userID) {
        // RDV à venir (statut en attente ou confirmé, date future)
        $queryRdv = $pdoMedical->prepare("
            SELECT r.*, 
                COALESCE(m.nom, i.nom) AS professionnel_nom,
                CASE WHEN r.professionnel_sante_id IS NOT NULL THEN 'medecin' ELSE 'infirmier' END AS professionnel_type,
                m.specialite AS specialite_service
            FROM rendez_vous r
            LEFT JOIN utilisateurs m ON r.professionnel_sante_id = m.id
            LEFT JOIN utilisateurs i ON r.professionnel_sante_id = i.id
            WHERE r.patient_id = ? 
                AND r.date_heure >= NOW()
                AND r.statut IN ('en attente', 'confirmé')
            ORDER BY r.date_heure ASC
            LIMIT 3
        ");
        $queryRdv->execute([$userID]);
        $rdvAVenir = $queryRdv->fetchAll();

        // Notifications pour les RDV non lus ou récents
        $queryNotif = $pdoMedical->prepare("
            SELECT r.*, 
                COALESCE(m.nom, i.nom) AS professionnel_nom,
                CASE WHEN r.professionnel_sante_id IS NOT NULL THEN 'medecin' ELSE 'infirmier' END AS professionnel_type,
                m.specialite AS specialite_service
            FROM rendez_vous r
            LEFT JOIN utilisateurs m ON r.professionnel_sante_id = m.id
            LEFT JOIN utilisateurs i ON r.professionnel_sante_id = i.id
            WHERE r.patient_id = ? 
                AND r.statut IN ('Confirmé', 'Annulé')
                AND (r.notification_envoyee = 0 OR r.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))
            ORDER BY r.notification_envoyee ASC, r.updated_at DESC
            LIMIT 5
        ");
        $queryNotif->execute([$userID]);
        $rdvNotifications = $queryNotif->fetchAll();
        
        // Compter les nouvelles notifications non lues
        $queryCount = $pdoMedical->prepare("
            SELECT COUNT(*) FROM rendez_vous 
            WHERE patient_id = ? 
            AND statut IN ('Confirmé', 'Annulé') 
            AND notification_envoyee = 0
        ");
        $queryCount->execute([$userID]);
        $nbNouvellesNotifs = $queryCount->fetchColumn();
    }

} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?= htmlspecialchars($userData['prenom']) ?></title>
    <link rel="stylesheet" href="../css/etudiant.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         .notification-bell {
            position: relative;
            display: inline-block;
        }
        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .notifications-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            max-width: 350px;
            display: none;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            padding: 15px;
        }
        .notification-item {
            border-left: 4px solid;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }
        .notification-item.unread {
            background-color: #f8f9fa;
        }
        .notification-item i {
            font-size: 1.2rem;
        }
        .notification-content {
            flex: 1;
        }
        .notification-actions {
            display: flex;
            gap: 5px;
        }
        .mark-as-read {
            cursor: pointer;
            color: #6c757d;
            font-size: 0.8rem;
        }
        .mark-as-read:hover {
            color: #0d6efd;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1>Bienvenue, <?= htmlspecialchars($userData['prenom']) ?> !</h1>
                <p>Année scolaire <?= htmlspecialchars($anneeScolaire) ?></p>
            </div>
            <div class="profile-container">
                <div class="profile-picture">
                    <img src="<?= !empty($userData['image']) ? '../img/uploads/' . htmlspecialchars($userData['image']) : '../img/profile.png' ?>" alt="Photo de profil" class="profile-img">
                </div>
                <div class="profile-info">
                    <p class="profile-role"><strong><?= htmlspecialchars($userData['nom']) ?></strong></p>
                    <p class="profile-role">Étudiant - <?= htmlspecialchars($userData['filiere']) ?></p>
                </div>
            </div>

              <!-- Cloche de notification -->
              <div style="position:absolute; top:30px; right:30px;">
                <div class="notification-bell">
                    <a href="#" id="notificationToggle">
                        <i class="fas fa-bell"></i>
                        <?php if ($nbNouvellesNotifs > 0): ?>
                            <span class="notification-count"><?= $nbNouvellesNotifs ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

      <!-- Panneau notifications -->
      <div class="notifications-container" id="notificationsPanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Notifications</h5>
                <?php if (!empty($rdvNotifications)): ?>
                    <a href="#" id="markAllAsRead" class="btn btn-sm btn-outline-secondary">Tout marquer comme lu</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($rdvNotifications)): ?>
                <?php foreach ($rdvNotifications as $notif): ?>
                    <div class="notification-item <?= $notif['notification_envoyee'] ? '' : 'unread' ?>" 
                         style="border-left-color:<?= $notif['statut'] === 'Confirmé' ? '#2ecc71' : '#e74c3c' ?>">
                        <i class="fas fa-calendar-check" style="color:<?= $notif['statut'] === 'Confirmé' ? '#2ecc71' : '#e74c3c' ?>"></i>
                        <div class="notification-content">
                            <strong>
                                RDV <?= htmlspecialchars(date('d/m/Y H:i', strtotime($notif['date_heure']))) ?> 
                                avec <?= htmlspecialchars($notif['professionnel_nom']) ?> (<?= htmlspecialchars($notif['professionnel_type']) ?>)
                            </strong><br>
                            Statut : 
                            <?php if ($notif['statut'] === 'Confirmé'): ?>
                                <span style="color:green;">Confirmé</span>
                            <?php else: ?>
                                <span style="color:red;">Annulé</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notif['notification_envoyee']): ?>
                                <form method="post" class="mark-read-form">
                                    <input type="hidden" name="rdv_id" value="<?= $notif['id'] ?>">
                                    <input type="hidden" name="marquer_comme_lu" value="1">
                                    <button type="submit" class="mark-as-read" title="Marquer comme lu">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="details_rdv.php?id=<?= $notif['id'] ?>" class="text-primary" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-3">
                    <i class="fas fa-bell-slash fa-2x text-muted"></i>
                    <p class="mt-2">Aucune notification</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <!-- Colonne gauche -->
            <div class="col col-4">
                <!-- Carte Profil -->
                <div class="card">
                    <div class="card-header">
                        Mon Profil
                    </div>
                    <div class="card-body">
                        <ul class="profile-info">
                            <li>
                                <span class="info-label">Matricule:</span>
                                <span class="p"><?= htmlspecialchars($userData['matricule']) ?></span>
                            </li>
                            <li>
                                <span class="info-label">Nom:</span>
                                <span class="p"><?= htmlspecialchars($userData['nom']) ?></span>
                            </li>
                            <li>
                                <span class="info-label">Prénom:</span>
                                <span class="p"><?= htmlspecialchars($userData['prenom']) ?></span>
                            </li>
                            <li>
                                <span class="info-label">Email:</span>
                                <span class="p"><?= htmlspecialchars($userData['email']) ?></span>
                            </li>
                            <li>
                                <span class="info-label">Filière:</span>
                                <span class="p"><?= htmlspecialchars($userData['filiere']) ?></span>
                            </li>
                            <li>
                                <span class="info-label">Niveau:</span>
                                <span class="p"><?= htmlspecialchars($userData['niveau']) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Carte Paiement -->
                <div class="card">
                    <div class="card-header">
                        Statut du Paiement
                    </div>
                    <div class="card-body">
                        <?php if ($accesServices): ?>
                            <div class="alert alert-success">
                                <h3>Paiement confirmé</h3>
                                <p>Vous avez accès à tous les services médicaux</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h3>Paiement requis</h3>
                                <p>Veuillez régler votre contribution pour accéder aux services</p>
                                <a href="paiement.php" class="btn btn-primary btn-block">Payer maintenant</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne droite -->
            <div class="col col-8">
                <!-- RDV à venir -->
                <?php if ($accesServices): ?>
                    <div class="card">
                        <div class="card-header">
                        📅 Mes prochains rendez-vous
                        </div>
                        <div class="card-body">
                            <?php if (!empty($rdvAVenir)): ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Date et Heure</th>
                                                <th>Professionnel</th>
                                                <th>Spécialité/Service</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rdvAVenir as $rdv): ?>
                                                <tr>
                                                    <td> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($rdv['date_heure']))) ?></td>
                                                    <td> <?= htmlspecialchars($rdv['professionnel_nom']) ?> (<?= htmlspecialchars($rdv['professionnel_type']) ?>)</td>
                                                    <td> <?= htmlspecialchars($rdv['specialite_service']) ?></td>
                                                    <td>
                                                        <a href="details_rdv.php?id=<?= $rdv['id'] ?>" class="btn btn-primary">
                                                            Détails
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="liste_rdv.php" class="btn btn-primary" style="margin-top: 15px;">
                                    Voir tous mes rendez-vous
                                </a>
                            <?php else: ?>
                                <div class="alert alert-warning txt">
                                    <h3 class="rdvimg">📅</h3>
                                    <p class="rdvtxt">Vous n'avez aucun rendez-vous à venir.</p>
                                    <a href="prendre_rdv.php" class="btn btn-primary">
                                        Prendre un rendez-vous
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Services médicaux -->
                <div class="card">
                    <div class="card-header">
                        Services médicaux
                    </div>
                    <div class="card-body">
                        <?php if ($accesServices): ?>
                            <div class="services-grid">
                                <div class="service-card">
                                    <div class="service-icon">🩺</div>
                                    <h3>Prendre RDV</h3>
                                    <p>Prenez rendez-vous avec un professionnel de santé</p>
                                    <a href="prendre_rdv.php" class="btn btn-primary">Accéder</a>
                                </div>
                                
                                <div class="service-card">
                                    <div class="service-icon">🩸</div>
                                    <h3>Analyses</h3>
                                    <p>Demander ou consulter des analyses</p>
                                    <a href="analyses.php" class="btn btn-primary">Accéder</a>
                                </div>

                                <div class="service-card">
                                    <div class="service-icon">📋</div>
                                    <h3>Resultats Analyses</h3>
                                    <p>Resultats De Mes Analyses</p>
                                    <a href="resultats_analyses.php" class="btn btn-primary">Resultats Analyses</a>
                                </div>
                                
                                <div class="service-card">
                                    <div class="service-icon">💊</div>
                                    <h3>Pharmacie</h3>
                                    <p>Demande de médicaments et ordonnances</p>
                                    <a href="pharmacie.php" class="btn btn-primary">Accéder</a>
                                </div>

                                <div class="service-card">
                                    <div class="service-icon">📄</div>
                                    <h3>Certificats</h3>
                                    <p>Demander un certificat médical</p>
                                    <a href="demande_certificat.php" class="btn btn-primary">Demander</a>
                                </div>
                                
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h3>Accès restreint</h3>
                                <p>Vous devez régler votre contribution pour accéder aux services médicaux</p>
                                <a href="paiement.php" class="btn btn-primary">Payer maintenant</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <a href="profil.php">Mon profil</a>
            <!-- <a href="parametres.php">Paramètres</a> -->
            <a href="aide.php">Aide</a>
            <a href="logout.php" style="color: var(--danger);">Déconnexion</a>
        </div>
    </div>
    <script>
        // Gestion des notifications
        document.getElementById('notificationToggle').addEventListener('click', function(e) {
            e.preventDefault();
            const panel = document.getElementById('notificationsPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });

        // Fermer les notifications
        document.querySelectorAll('.notification-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.notification-item').style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => {
                    this.closest('.notification-item').remove();
                    // Masquer le panel si plus de notifications
                    if (document.querySelectorAll('.notification-item').length === 0) {
                        document.getElementById('notificationsPanel').style.display = 'none';
                    }
                }, 300);
            });
        });

        // Auto-fermeture après 5 secondes
        setTimeout(() => {
            document.getElementById('notificationsPanel').style.display = 'none';
        }, 5000);
    </script>
</body>
</html>