<?php
session_start();
require_once "db.php";

// Vérification rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

// Extraction ID numérique
$adminID = intval(preg_replace('/^[A-Z]+_/', '', $_SESSION['user_id']));

// Récupération des informations du admin
try {
    $queryAdmin = $pdoMedical->prepare("SELECT id, nom, email FROM utilisateurs WHERE id = ?");
    $queryAdmin->execute([$adminID]);
    $admin = $queryAdmin->fetch();
    
    if (!$admin) {
        throw new Exception("Administrateur non trouvé");
    }

    // Récupération des statistiques
    $stats = [
        'medecins' => $pdoMedical->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'medecin'")->fetchColumn(),
        'infirmiers' => $pdoMedical->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'infirmier'")->fetchColumn(),
        'patients' => $pdoMedical->query("SELECT COUNT(DISTINCT patient_id) FROM rendez_vous")->fetchColumn(),
        'rdv_attente' => $pdoMedical->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'en attente'")->fetchColumn(),
        'rdv_confirme' => $pdoMedical->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'confirmé'")->fetchColumn(),
        'contacts' => $pdoMedical->query("SELECT COUNT(*) FROM contacts")->fetchColumn()
    ];

    // Récupération des utilisateurs avec contacts
    $queryUsers = $pdoMedical->query("SELECT id, nom, email,  role FROM utilisateurs ORDER BY role, nom");
    $users = $queryUsers->fetchAll();

    // Récupération des messages de contact
    $queryContacts = $pdoMedical->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 10");
    $contacts = $queryContacts->fetchAll();

    // Récupération des rendez-vous en attente
    $queryRdvAttente = $pdoMedical->query("SELECT r.*, u.nom as patient_nom 
                                         FROM rendez_vous r
                                         JOIN utilisateurs u ON r.patient_id = u.id
                                         WHERE r.statut = 'en attente'");
    $rdvAttente = $queryRdvAttente->fetchAll();

    // Récupération des rendez-vous confirmés
    $queryRdvConfirme = $pdoMedical->query("SELECT r.*, u.nom as patient_nom 
                                          FROM rendez_vous r
                                          JOIN utilisateurs u ON r.patient_id = u.id
                                          WHERE r.statut = 'confirmé'");
    $rdvConfirme = $queryRdvConfirme->fetchAll();

} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

// Gestion des actions
if (isset($_GET['action'])) {
    try {
        $userId = intval($_GET['id'] ?? 0);
        
        if ($_GET['action'] === 'delete' && $userId > 0) {
            if ($userId === $adminID) {
                throw new Exception("Vous ne pouvez pas supprimer votre propre compte");
            }
            
            $deleteStmt = $pdoMedical->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $deleteStmt->execute([$userId]);
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Utilisateur supprimé avec succès'
            ];
            header("Location: dashboard_admin.php");
            exit;
        }
        
        if ($_GET['action'] === 'delete_contact' && isset($_GET['contact_id'])) {
            $contactId = intval($_GET['contact_id']);
            $deleteStmt = $pdoMedical->prepare("DELETE FROM contacts WHERE id = ?");
            $deleteStmt->execute([$contactId]);
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Message supprimé avec succès'
            ];
            header("Location: dashboard_admin.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => $e->getMessage()
        ];
        header("Location: dashboard_admin.php");
        exit;
    }
}

// Gestion des messages
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
    <title>Tableau de bord Administrateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4a6fa5;
            --primary-dark: #3a5a8a;
            --secondary: #166088;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
        }
        
        .sidebar {
            background: var(--primary);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            color: white;
            padding: 10px 20px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .stat-card {
            border-radius: 10px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--light-gray);
            font-weight: 600;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--gray);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
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
        
        .contact-message {
            border-left: 3px solid var(--primary);
            padding-left: 10px;
        }
        
        .badge-role {
            font-weight: normal;
            text-transform: capitalize;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="d-flex align-items-center">
                <div class="user-avatar me-3">
                    <?= substr($admin['nom'], 0, 1) ?>
                </div>
                <div>
                    <h6 class="mb-0"><?= htmlspecialchars($admin['nom']) ?></h6>
                    <small>Administrateur</small>
                </div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
            <a href="admin/superadmin_users.php"><i class="fas fa-users-cog"></i> Gestion utilisateurs</a>
            <a href="admin/gestion_medicaments.php"><i class="fas fa-pills"></i> Médicaments</a>
            <a href="admin/messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> Tableau de bord</h2>
            <button class="btn btn-sm btn-primary d-md-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                <i class="fas fa-<?= $message['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-2"></i>
                <?= htmlspecialchars($message['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Médecins</h6>
                                <h2 class="mb-0"><?= $stats['medecins'] ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Infirmiers</h6>
                                <h2 class="mb-0"><?= $stats['infirmiers'] ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-nurse"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Patients</h6>
                                <h2 class="mb-0"><?= $stats['patients'] ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-injured"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">RDV en attente</h6>
                                <h2 class="mb-0"><?= $stats['rdv_attente'] ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">RDV confirmés</h6>
                                <h2 class="mb-0"><?= $stats['rdv_confirme'] ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-uppercase">Nouveaux messages</h6>
                                <h2 class="mb-0"><?= $stats['contacts'] ?></h2>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Derniers utilisateurs -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i> Derniers utilisateurs</h5>
                <a href="gestion_utilisateurs.php" class="btn btn-sm btn-primary">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($users, 0, 5) as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['nom']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['telephone'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['role'] === 'medecin' ? 'primary' : 
                                            ($user['role'] === 'infirmier' ? 'success' : 'info')
                                        ?> badge-role">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $user['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Derniers messages -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i> Derniers messages</h5>
                        <a href="contacts.php" class="btn btn-sm btn-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contacts)): ?>
                            <div class="alert alert-info mb-0">Aucun message récent</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($contacts as $contact): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($contact['name']) ?></h6>
                                            <small><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-1 contact-message"><?= htmlspecialchars(substr($contact['message'], 0, 100)) ?>...</p>
                                        <small class="text-muted"><?= htmlspecialchars($contact['subject']) ?></small>
                                        <div class="mt-2">
                                            <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-reply"></i> Répondre
                                            </a>
                                            <a href="?action=delete_contact&contact_id=<?= $contact['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Supprimer ce message ?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Rendez-vous récents -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Rendez-vous récents</h5>
                        <a href="gestion_rendezvous.php" class="btn btn-sm btn-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="rdvTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="attente-tab" data-bs-toggle="tab" data-bs-target="#attente" type="button">
                                    En attente (<?= count($rdvAttente) ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="confirmes-tab" data-bs-toggle="tab" data-bs-target="#confirmes" type="button">
                                    Confirmés (<?= count($rdvConfirme) ?>)
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="attente" role="tabpanel">
                                <?php if (empty($rdvAttente)): ?>
                                    <div class="alert alert-info mb-0">Aucun rendez-vous en attente</div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach (array_slice($rdvAttente, 0, 3) as $rdv): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($rdv['patient_nom']) ?></h6>
                                                    <small><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></small>
                                                </div>
                                                <p class="mb-1"><?= $rdv['heure'] ?> - <?= htmlspecialchars($rdv['motif']) ?></p>
                                                <div>
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Confirmer
                                                    </button>
                                                    <button class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Refuser
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="tab-pane fade" id="confirmes" role="tabpanel">
                                <?php if (empty($rdvConfirme)): ?>
                                    <div class="alert alert-info mb-0">Aucun rendez-vous confirmé</div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach (array_slice($rdvConfirme, 0, 3) as $rdv): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($rdv['patient_nom']) ?></h6>
                                                    <small><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></small>
                                                </div>
                                                <p class="mb-1"><?= $rdv['heure'] ?> - <?= htmlspecialchars($rdv['motif']) ?></p>
                                                <div>
                                                    <button class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>