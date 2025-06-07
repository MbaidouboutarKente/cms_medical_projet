<?php
// Activation des erreurs en développement (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "../db.php";

// Vérification rôle super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../auth.php");
    exit;
}


// Génération du token CSRF s'il n'existe pas
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validation de l'ID utilisateur
$superadminID = filter_var($_SESSION['user_id'], FILTER_SANITIZE_NUMBER_INT);
if (empty($superadminID)) {
    die("ID utilisateur invalide");
}

/**
 * Vérifie si une table existe dans la base de données
 */
function tableExists($pdoMedical, $tableName) {
    try {
        $result = $pdoMedical->query("SELECT 1 FROM $tableName LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Exécute une requête SQL de manière sécurisée avec fallback
 */
function safeFetchAll($pdoMedical, $sql, $params = [], $default = []) {
    try {
        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage());
        return $default;
    }
}

/**
 * Compte les entrées dans une table avec condition optionnelle
 */
function safeCount($pdoMedical, $table, $condition = '') {
    try {
        $sql = "SELECT COUNT(*) FROM $table";
        if (!empty($condition)) {
            $sql .= " WHERE $condition";
        }
        return $pdoMedical->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count Error for $table: " . $e->getMessage());
        return 0;
    }
}

// Initialisation des données
$superadmin = [];
$stats = [];
$recentUsers = [];
$recentContacts = [];
$recentAlertes = [];
$adminActivity = [];
$healthStats = [];


try {
    // 1. Récupération des informations du superadmin
    $superadmin = safeFetchAll($pdoMedical, 
        "SELECT id, nom, email
         FROM utilisateurs 
         WHERE id = ?", 
        [$superadminID]
    )[0] ?? [];

    if (empty($superadmin)) {
        throw new Exception("Compte SuperAdmin introuvable");
    }

    // 2. Statistiques globales
    $stats = [
        'total_users' => safeCount($pdoMedical, 'utilisateurs'),
        'active_users' => safeCount($pdoMedical, 'utilisateurs', "last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)"),
        'admins' => safeCount($pdoMedical, 'utilisateurs', "role = 'admin'"),
        'medecins' => safeCount($pdoMedical, 'utilisateurs', "role = 'medecin'"),
        'infirmiers' => safeCount($pdoMedical, 'utilisateurs', "role = 'infirmier'"),
        'patients' => tableExists($pdoMedical, 'rendez_vous') ? 
                     safeCount($pdoMedical, 'rendez_vous', "1 GROUP BY patient_id") : 0,
        'rdv_total' => safeCount($pdoMedical, 'rendez_vous'),
        'contacts' => safeCount($pdoMedical, 'contacts'),
        'system_alerts' => safeCount($pdoMedical, 'system_alerts', "is_read = 0"),
        'unread_messages' => safeCount($pdoMedical, 'contacts', "status = 'unread'")
    ];

    // 3. Derniers utilisateurs inscrits
    if (tableExists($pdoMedical, 'utilisateurs')) {
        $recentUsers = safeFetchAll($pdoMedical,
            "SELECT id, nom, email, role, date_inscription
             FROM utilisateurs 
             ORDER BY date_inscription DESC 
             LIMIT 5"
        );
    }

    // 4. Derniers messages de contact
    if (tableExists($pdoMedical, 'contacts')) {
        $recentContacts = safeFetchAll($pdoMedical,
            "SELECT * FROM contacts 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
    }

       // 4. Derniers Alertes
       if (tableExists($pdoMedical, 'system_alerts ')) {
        $recentAlertes = safeFetchAll($pdoMedical,
            "SELECT * FROM system_alerts  
             ORDER BY created_at DESC 
             LIMIT 5"
        );
    }

    // 5. Activité récente des administrateurs
    if (tableExists($pdoMedical, 'admin_activity')) {
        $adminActivity = safeFetchAll($pdoMedical,
            "SELECT u.nom, a.action, a.created_at 
             FROM admin_activity a
             JOIN utilisateurs u ON a.user_id = u.id
             WHERE u.role IN ('super_admin', 'admin')
             ORDER BY a.created_at DESC 
             LIMIT 5"
        );
    }

    // 6. Statistiques du système de santé
    $healthStats = [
        'certificats' => tableExists($pdoMedical, 'certificats') ? safeCount($pdoMedical, 'certificats') : 0,
        'prescriptions' => tableExists($pdoMedical, 'prescriptions') ? safeCount($pdoMedical, 'prescriptions') : 0,
        'medicaments' => tableExists($pdoMedical, 'medicaments') ? safeCount($pdoMedical, 'medicaments') : 0,
        'consultations' => tableExists($pdoMedical, 'consultations') ? safeCount($pdoMedical, 'consultations') : 0
    ];

} catch (Exception $e) {
    // Journalisation de l'erreur
    error_log("[SuperAdmin Dashboard] " . date('Y-m-d H:i:s') . " - " . $e->getMessage());
    
    // Message générique pour l'utilisateur
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'action_handlers.php'; // Externalisation du traitement des actions
}

// Le reste de votre code HTML/JS suit ici...

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Token invalide");
    }

    try {
        switch ($_POST['action']) {
            case 'promote_to_admin':
                $userId = intval($_POST['user_id']);
                $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET role = 'admin' WHERE id = ? AND role != 'superadmin'");
                $stmt->execute([$userId]);
                
                // Journalisation
                $logStmt = $pdoMedical->prepare("INSERT INTO admin_activity (user_id, action) VALUES (?, ?)");
                $logStmt->execute([$superadminID, "Promotion de l'utilisateur $userId en admin"]);
                
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Utilisateur promu administrateur avec succès'
                ];
                break;
                
            case 'demote_from_admin':
                $userId = intval($_POST['user_id']);
                $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET role = 'medecin' WHERE id = ? AND role = 'admin'");
                $stmt->execute([$userId]);
                
                $logStmt = $pdoMedical->prepare("INSERT INTO admin_activity (user_id, action) VALUES (?, ?)");
                $logStmt->execute([$superadminID, "Rétrogradation de l'admin $userId"]);
                
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Administrateur rétrogradé avec succès'
                ];
                break;
                
            case 'delete_user':
                $userId = intval($_POST['user_id']);
                if ($userId === $superadminID) {
                    throw new Exception("Vous ne pouvez pas supprimer votre propre compte");
                }
                
                $pdoMedical->beginTransaction();
                
                // Suppression en cascade (exemple)
                $stmt = $pdoMedical->prepare("DELETE FROM utilisateurs WHERE id = ?");
                $stmt->execute([$userId]);
                
              
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Utilisateur supprimé avec succès'
                ];
                break;
                
            case 'mark_message_read':
                $messageId = intval($_POST['message_id']);
                $stmt = $pdoMedical->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
                $stmt->execute([$messageId]);
                
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Message marqué comme lu'
                ];
                break;
        }
        
        header("Location: dashboard_super_admin.php");
        exit;
        
    } catch (Exception $e) {
        if ($pdoMedical->inTransaction()) {
            $pdoMedical->rollBack();
        }
        
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => $e->getMessage()
        ];
        header("Location: dashboard_super_admin.php");
        exit;
    }
}

// Gestion du marquage des messages comme lus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = $_POST['message_id'];
    
    // Mettre à jour le statut du message
    $sql = "UPDATE contacts SET status = 'read' WHERE id = :message_id";
    $stmt = $pdoMedical->prepare($sql);
    $stmt->execute([':message_id' => $message_id]);

    // Rediriger pour éviter le resoumission du formulaire
    header("Location: dashboard_super_admin.php");

    exit();
}
// Gestion des messages flash
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --info: #36b9cc;
            --dark: #5a5c69;
            --light: #f8f9fc;
            --sidebar: #212529;
            --sidebar-dark: #191c1f;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--light);
        }
        
        #wrapper {
            display: flex;
        }
        
        #sidebar {
            width: 250px;
            background: var(--sidebar);
            color: white;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            height: 4.375rem;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 800;
            padding: 1.5rem 1rem;
            text-align: center;
            letter-spacing: 0.05rem;
            z-index: 1;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 0 1rem 1rem;
        }
        
        .nav-item .nav-link {
            position: relative;
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 600;
        }
        
        .nav-item .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-item .nav-link i {
            margin-right: 0.5rem;
        }
        
        .nav-item .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-item .nav-link.active::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary);
        }
        
        #content {
            width: 100%;
            overflow-x: hidden;
        }
        
        .topbar {
            height: 4.375rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background: white;
        }
        
        .topbar #sidebarToggle {
            color: var(--dark);
        }
        
        .topbar .dropdown-list {
            padding: 0;
            border: none;
            overflow: hidden;
            width: 20rem;
        }
        
        .dropdown-list-item {
            border-bottom: 1px solid var(--light);
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        
        .dropdown-list-item:hover {
            background: var(--light);
        }
        
        .dropdown-list-item .text-truncate {
            max-width: 13.375rem;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .stat-card {
            border-left: 0.25rem solid;
            border-radius: 0.35rem;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
        }
        
        .stat-card.success {
            border-left-color: var(--secondary);
        }
        
        .stat-card.info {
            border-left-color: var(--info);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning);
        }
        
        .stat-card.danger {
            border-left-color: var(--danger);
        }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .stat-card .stat-label {
            font-size: 0.875rem;
            color: var(--dark);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            color: #dddfeb;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(87deg, var(--primary) 0, var(--primary-dark) 100%) !important;
        }
        
        .chart-area {
            position: relative;
            height: 10rem;
            width: 100%;
        }
        
        @media (min-width: 768px) {
            .chart-area {
                height: 20rem;
            }
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .activity-badge {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .activity-badge.primary {
            background-color: var(--primary);
        }
        
        .activity-badge.success {
            background-color: var(--secondary);
        }
        
        .activity-badge.danger {
            background-color: var(--danger);
        }
        
        .activity-item {
            position: relative;
            padding-bottom: 1.5rem;
            border-left: 1px solid #e3e6f0;
        }
        
        .activity-item:last-child {
            border-left: 1px solid transparent;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            left: -6px;
            top: 0;
            border-radius: 50%;
            background: #e3e6f0;
        }
        
        .activity-item.primary::before {
            background: var(--primary);
        }
        
        .activity-item.success::before {
            background: var(--secondary);
        }
        
        .activity-item.danger::before {
            background: var(--danger);
        }
        
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="sidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Super Admin</div>
            </a>
            
            <hr class="sidebar-divider my-0">
            
            <li class="nav-item active">
                <a class="nav-link" href="dashboard_super_admin.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">
                Administration
            </div>
            
            <li class="nav-item">
                <a class="nav-link" href="superadmin_users.php">
                    <i class="fas fa-fw fa-users-cog"></i>
                    <span>Gestion des utilisateurs</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="superadmin_roles.php">
                    <i class="fas fa-fw fa-user-tag"></i>
                    <span>Gestion des rôles</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="gestion_medicaments.php">
                    <i class="fas fa-fw fa-key"></i>
                    <span>Medicament</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">
                Système
            </div>
           
            
            
            
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Sauvegarde</span>
                </a>
            </li>
            
            <hr class="sidebar-divider d-none d-md-block">
            
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        
        <!-- Content Wrapper -->
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand topbar mb-4 static-top shadow" style="background: #337ab7;  padding: 70px 10px; border-bottom: 5px solid black;">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>        
                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto" style=" position: absolute; right: 35%;" >
                    <!-- Nav Item - Alerts -->
                    <li class="nav-item dropdown no-arrow mx-1" >
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell fa-fw"></i>
                            <span class="badge badge-danger badge-counter"><?= $stats['system_alerts'] > 0 ? $stats['system_alerts'] : '' ?></span>
                        </a>
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in">
                            <h6 class="dropdown-header">
                                Centre d'alertes
                            </h6>
                            <?php if ($stats['system_alerts'] > 0): ?>
                                <?php foreach (array_slice($recentAlertes, 0, 3) as $contact): ?>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <div class="mr-3">
                                            <div class="icon-circle bg-primary">
                                                <i class="fas fa-envelope text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500"><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></div>
                                            <span class="font-weight-bold">Nouveau Alerte : <?= htmlspecialchars($contact['message']) ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <a class="dropdown-item text-center small text-gray-500" href="superadmin_messages.php">Voir toutes les alertes</a>
                            <?php else: ?>
                                <div class="dropdown-item text-center small text-gray-500">Aucune alerte</div>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <!-- Nav Item - Messages -->
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-envelope fa-fw"></i>
                            <span class="badge badge-danger badge-counter"><?= $stats['contacts'] > 0 ? $stats['contacts'] : '' ?></span>
                        </a>
                        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in">
                            <h6 class="dropdown-header">
                                Centre de messages
                            </h6>
                            <?php if ($stats['contacts'] > 0): ?>
                                <?php foreach (array_slice($recentContacts, 0, 3) as $contact): ?>
                                    <a class="dropdown-item d-flex align-items-center" href="#">
                                        <div class="dropdown-list-image mr-3">
                                            <div class="icon-circle bg-success">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-truncate"><?= htmlspecialchars(substr($contact['message'], 0, 50)) ?>...</div>
                                            <div class="small text-gray-500"><?= htmlspecialchars($contact['name']) ?> · <?= date('d/m/Y', strtotime($contact['created_at'])) ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <a class="dropdown-item text-center small text-gray-500" href="superadmin_messages.php">Voir tous les messages</a>
                            <?php else: ?>
                                <div class="dropdown-item text-center small text-gray-500">Aucun message</div>
                            <?php endif; ?>
                        </div>
                    </li>
                    
                    <div class="topbar-divider d-none d-sm-block"></div>
                    
                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($superadmin['nom']) ?></span>
                            <div class="user-avatar">
                                <?= substr($superadmin['nom'], 0, 1) ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                Profil
                            </a>
                            <a class="dropdown-item" href="superadmin_settings.php">
                                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                Paramètres
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="../logout.php" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Déconnexion
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <!-- End of Topbar -->
            
            <!-- Begin Page Content -->
            <div class="container-fluid">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= $message['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                        <i class="fas fa-<?= $message['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
                        <?= htmlspecialchars($message['text']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Tableau de bord</h1>
                    <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-download fa-sm text-white-50"></i> Générer rapport
                    </a>
                </div>
                
                <!-- Content Row -->
                <div class="row">
                    <!-- Total Users Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 stat-card primary">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Utilisateurs totaux</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_users'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Users Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 stat-card success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Utilisateurs actifs (30j)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['active_users'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admins Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 stat-card info">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Administrateurs</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['admins'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-shield fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Messages Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 stat-card warning">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Messages non lus</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['unread_messages'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-envelope fa-2x text-gray-300 stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Row -->
                <div class="row">
                    <!-- Derniers utilisateurs -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Derniers utilisateurs inscrits</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                        <a class="dropdown-item" href="superadmin_users.php">Voir tous</a>
                                        <a class="dropdown-item" href="superadmin_add_user.php">Ajouter un utilisateur</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Rôle</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user['nom']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $user['role'] === 'admin' ? 'primary' : 
                                                            ($user['role'] === 'medecin' ? 'info' : 
                                                            ($user['role'] === 'infirmier' ? 'success' : 'secondary'))
                                                        ?>">
                                                            <?= htmlspecialchars($user['role']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                                                    <td>
                                                        <div class="dropdown no-arrow">
                                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="userActions" data-bs-toggle="dropdown">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                                                <a class="dropdown-item" href="../view_user.php?id=<?= $user['id'] ?>">
                                                                    <i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i>
                                                                    Voir
                                                                </a>
                                                                
                                                                <a class="dropdown-item" href="superadmin_edit_user.php?id=<?= $user['id'] ?>">
                                                                    <i class="fas fa-user-shield fa-sm fa-fw mr-2 text-gray-400"></i>
                                                                    Modifier
                                                                </a>
                                                                <?php if ($user['id'] !== $superadminID): ?>
                                                                    <form method="POST" class="dropdown-item">
                                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                                        <input type="hidden" name="action" value="delete_user">
                                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                        <button type="submit" class="btn btn-link p-0 text-start w-100 text-danger" onclick="return confirm('Supprimer cet utilisateur ?')">
                                                                            <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>
                                                                            Supprimer
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Derniers messages -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Derniers messages</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                        <a class="dropdown-item" href="messages.php">Voir tous</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentContacts)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-envelope-open-text fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">Aucun message récent</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                    <?php foreach ($recentContacts as $contact): ?>
                                        <div class="list-group-item list-group-item-action <?= $contact['status'] === 'read' ? 'bg-light' : ''; ?>">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= htmlspecialchars($contact['name']) ?></h6>
                                                <small><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1"><?= htmlspecialchars(substr($contact['message'], 0, 100)) ?>...</p>
                                            <small class="text-muted"><?= htmlspecialchars($contact['subject']) ?></small>
                                            <div class="mt-2">
                                                <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-reply"></i> Répondre
                                                </a>
                                                
                                                <?php if ($contact['status'] === 'unread'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="message_id" value="<?= $contact['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Marquer comme lu
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-check-circle"></i> Déjà lu</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <!-- Content Row -->
            <div class="row">
                    <!-- Statistiques de santé -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Statistiques du système de santé</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            Certificats médicaux</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $healthStats['certificats'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-file-medical fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                            Prescriptions</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $healthStats['prescriptions'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-prescription-bottle-alt fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                            Médicaments</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $healthStats['medicaments'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-pills fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                            Consultations</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $healthStats['consultations'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activité récente -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Activité récente des administrateurs</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                                        <a class="dropdown-item" href="superadmin_logs.php">Voir tous les journaux</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="activities">
                                    <?php if (empty($adminActivity)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                                            <p class="text-gray-500">Aucune activité récente</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($adminActivity as $activity): ?>
                                            <div class="activity-item <?= 
                                                strpos($activity['action'], 'Suppression') !== false ? 'danger' : 
                                                (strpos($activity['action'], 'Promotion') !== false ? 'success' : 'primary')
                                            ?>">
                                                <div class="activity-content">
                                                    <div class="timeline-time">
                                                        <span class="time"><?= date('H:i', strtotime($activity['created_at'])) ?></span>
                                                        <span class="date"><?= date('d/m/Y', strtotime($activity['created_at'])) ?></span>
                                                    </div>
                                                    <h5 class="timeline-header">
                                                        <span class="activity-badge <?= 
                                                            strpos($activity['action'], 'Suppression') !== false ? 'danger' : 
                                                            (strpos($activity['action'], 'Promotion') !== false ? 'success' : 'primary')
                                                        ?>"></span>
                                                        <?= htmlspecialchars($activity['nom']) ?>
                                                    </h5>
                                                    <div class="timeline-body">
                                                        <?= htmlspecialchars($activity['action']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <!-- Statistiques RDV -->
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Statistiques des rendez-vous</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                            RDV Totaux</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['rdv_total'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4">
                                        <div class="card border-left-success shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                            Patients uniques</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['patients'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-user-injured fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                            Médecins</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['medecins'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-user-md fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                            Infirmiers</div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['infirmiers'] ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-user-nurse fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Prêt à partir?</h5>
                    <button class="close" type="button" data-bs-dismiss="modal">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Sélectionnez "Déconnexion" ci-dessous si vous êtes prêt à terminer votre session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
                    <a class="btn btn-primary" href="../logout.php">Déconnexion</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom scripts for all pages-->
    <script>
        // Sidebar toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });

        // Close any open menu when clicking elsewhere
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-toggle')) {
                var dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(function(dropdown) {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                });
            }
        });

        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>