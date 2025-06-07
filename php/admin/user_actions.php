<?php
require_once 'auth_check.php';

// Vérification CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Erreur de sécurité");
}

$superadminID = intval(preg_replace('/^[A-Z]+_/', '', $_SESSION['user_id']));

try {
    switch ($_POST['action']) {
        case 'promote_to_admin':
            $userId = intval($_POST['user_id']);
            $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET role = 'admin' WHERE id = ? AND role != 'superadmin'");
            $stmt->execute([$userId]);
            break;

        case 'demote_from_admin':
            $userId = intval($_POST['user_id']);
            $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET role = 'medecin' WHERE id = ? AND role = 'admin'");
            $stmt->execute([$userId]);
            break;

        case 'delete_user':
            $userId = intval($_POST['user_id']);
            if ($userId === $superadminID) {
                throw new Exception("Auto-suppression interdite");
            }
            
            // Désactivation plutôt que suppression
            $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET active = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            break;

        default:
            throw new Exception("Action non reconnue");
    }

    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Action réalisée avec succès'];
} catch (Exception $e) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => $e->getMessage()];
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;