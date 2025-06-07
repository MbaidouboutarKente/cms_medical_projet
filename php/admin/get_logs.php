<?php
require_once "db.php";
session_start();

header('Content-Type: text/html; charset=utf-8');

// Vérification de sécurité renforcée
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    die('<tr><td colspan="6" class="text-center text-danger">Accès non autorisé</td></tr>');
}

try {
    $query = $pdo->prepare("
        SELECT l.*, u.nom as user_name, u.role as user_role 
        FROM logs l
        LEFT JOIN utilisateurs u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 100
    ");
    $query->execute();
    $logs = $query->fetchAll(PDO::FETCH_ASSOC);

    if (empty($logs)) {
        echo '<tr><td colspan="6" class="text-center">Aucune activité récente</td></tr>';
    } else {
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>'.htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at']))).'</td>';
            // ... (reste du code d'affichage)
            echo '</tr>';
        }
    }
} catch (PDOException $e) {
    error_log("PDO Error in get_logs.php: ".$e->getMessage());
    echo '<tr><td colspan="6" class="text-center text-danger">Erreur technique</td></tr>';
}
?>