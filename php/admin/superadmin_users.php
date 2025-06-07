<?php
// Activation des erreurs en développement (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once "../db.php";

// Vérification rôle super admin

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth.php");
    exit;
}


// Initialisation CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Token invalide");
    }

    $action = $_POST['action'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    try {
        switch ($action) {
            case 'promote_to_admin':
                if ($userId) {
                    $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET role = 'admin' WHERE id = ? AND role != 'super_admin'");
                    $stmt->execute([$userId]);
                    logAction($pdoMedical, $_SESSION['user_id'], "Promotion utilisateur $userId en admin");
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Utilisateur promu administrateur'];
                }
                break;

            case 'demote_to_user':
                if ($userId) {
                    $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET role = 'user' WHERE id = ? AND role = 'admin'");
                    $stmt->execute([$userId]);
                    logAction($pdoMedical, $_SESSION['user_id'], "Rétrogradation admin $userId");
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Administrateur rétrogradé'];
                }
                break;

            case 'delete_user':
                if ($userId && $userId != $_SESSION['user_id']) {
                    // Désactivation plutôt que suppression
                    $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET is_active = 0, deleted_at = NOW() WHERE id = ?");
                    $stmt->execute([$userId]);
                    logAction($pdoMedical, $_SESSION['user_id'], "Désactivation utilisateur $userId");
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Utilisateur désactivé'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Auto-suppression interdite'];
                }
                break;

            case 'reset_password':
                if ($userId) {
                    $tempPassword = generateTempPassword();
                    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET password = ?, must_reset_password = 1 WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    // Ici vous devriez envoyer le mot de passe temporaire par email
                    logAction($pdoMedical, $_SESSION['user_id'], "Réinitialisation mot de passe utilisateur $userId");
                    $_SESSION['flash_message'] = [
                        'type' => 'success', 
                        'message' => "Mot de passe réinitialisé. Nouveau mot de passe temporaire : $tempPassword"
                    ];
                }
                break;
        }

        header("Location: superadmin_users.php");
        exit;
    } catch (PDOException $e) {
        error_log("User action error: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Erreur lors de l\'opération'];
        header("Location: superadmin_users.php");
        exit;
    }
}

// Récupération des utilisateurs avec pagination
$currentPage = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1);
$perPage = 10;
$offset = ($currentPage - 1) * $perPage;

// Filtres de recherche
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$roleFilter = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// Construction de la requête
$query = "SELECT * FROM utilisateurs WHERE is_active = 1";
$params = [];
$countQuery = "SELECT COUNT(*) FROM utilisateurs WHERE is_active = 1";

if (!empty($search)) {
    $query .= " AND (nom LIKE ? OR email LIKE ?)";
    $countQuery .= " AND (nom LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($roleFilter) && in_array($roleFilter, ['admin', 'etudiant', 'medecin', 'infirmier'])) {
    $query .= " AND role = ?";
    $countQuery .= " AND role = ?";
    $params[] = $roleFilter;
}

// Comptage total
$totalUsers = $pdoMedical->prepare($countQuery);
$totalUsers->execute($params);
$total = $totalUsers->fetchColumn();

// Récupération des utilisateurs
$query .= " ORDER BY date_inscription DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdoMedical->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des rôles disponibles pour le filtre
$roles = $pdoMedical->query("SELECT DISTINCT role FROM utilisateurs WHERE role != 'super_admin'")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <div class="wrapper">
        <!-- <?php include __DIR__.'/../includes/sidebar_super_admin.php'; ?> -->

        <div class="main-content">
            <!-- <?php include __DIR__.'/../includes/topbar.php'; ?> -->

            <div class="container-fluid">
                <!-- Messages flash -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> mt-3">
                        <?= $_SESSION['flash_message']['message'] ?>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <!-- Titre et boutons -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Gestion des Utilisateurs</h1>
                    <a href="superadmin_add_user.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-user-plus fa-sm text-white-50"></i> Ajouter un utilisateur
                    </a>
                </div>

                <!-- Filtres -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." 
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-4">
                                    <select name="role" class="form-control">
                                        <option value="">Tous les rôles</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role ?>" <?= $role === $roleFilter ? 'selected' : '' ?>>
                                                <?= ucfirst($role) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filtrer
                                    </button>
                                    <a href="superadmin_users.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Réinitialiser
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tableau des utilisateurs -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Liste des Utilisateurs</h6>
                        <span class="badge bg-primary">Total : <?= $total ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Inscription</th>
                                        <th>Dernière connexion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Aucun utilisateur trouvé</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['nom']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $user['role'] === 'admin' ? 'primary' : 
                                                    ($user['role'] === 'medecin' ? 'info' : 
                                                    ($user['role'] === 'infirmier' ? 'success' : 'secondary'))
                                                ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                                            <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="superadmin_edit_user.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-primary" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <?php if ($user['role'] === 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <input type="hidden" name="action" value="demote_to_user">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" class="btn btn-warning" title="Rétrograder">
                                                                <i class="fas fa-user-minus"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif ($user['role'] !== 'admin' && $user['role'] !== 'super_admin'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <input type="hidden" name="action" value="promote_to_admin">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" class="btn btn-success" title="Promouvoir admin">
                                                                <i class="fas fa-user-shield"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-info" title="Réinitialiser mot de passe">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                    </form>

                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Confirmer la désactivation de cet utilisateur ?');">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" class="btn btn-danger" title="Désactiver">
                                                                <i class="fas fa-user-slash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&role=<?= $roleFilter ?>">
                                            Précédent
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $roleFilter ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($currentPage < ceil($total / $perPage)): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&role=<?= $roleFilter ?>">
                                            Suivant
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/admin.js"></script>
    <script>
    // Activation des tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>
</html>