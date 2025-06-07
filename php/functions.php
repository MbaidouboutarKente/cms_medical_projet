<?php
function getRedirectPage($role) {
    $redirections = [
        "etudiant" => "dashboard_etudiant.php",
        "infirmier" => "dashboard_infirmier.php",
        "medecin" => "dashboard_medecin.php",
        "admin" => "dashboard_admin.php",
        "super_admin" => "admin/dashboard_super_admin.php"
    ];
    return $redirections[$role] ?? "../index.html";
}

function securiserSession() {
    session_start();
    session_regenerate_id(true);
}
?>

<?php

/**
 * Récupère les données du super admin
 */
function getSuperAdminData(PDO $pdo, string $userId): array
{
    $stmt = $pdo->prepare("
        SELECT id, nom, email, created_at 
        FROM utilisateurs 
        WHERE id = ? AND role = 'super_admin'
    ");
    $stmt->execute([$userId]);
    
    $data = $stmt->fetch();
    
    if (!$data) {
        throw new Exception("Super administrateur non trouvé");
    }
    
    return $data;
}

/**
 * Compte les utilisateurs par rôle
 */
function getTotalUsersByRole(PDO $pdo, string $role): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = ?");
    $stmt->execute([$role]);
    return $stmt->fetchColumn();
}

/**
 * Récupère les dernières activités
 */
function getRecentActivityLogs(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare("
        SELECT description, created_at, action_type 
        FROM activity_logs 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    $logs = [];
    $icons = [
        'login' => 'sign-in-alt',
        'user_create' => 'user-plus',
        'config_change' => 'cog'
    ];
    
    while ($row = $stmt->fetch()) {
        $logs[] = [
            'description' => $row['description'],
            'created_at' => date('d/m/Y H:i', strtotime($row['created_at'])),
            'icon' => $icons[$row['action_type']] ?? 'info-circle'
        ];
    }
    
    return $logs;
}

/**
 * Gère la création d'utilisateurs
 */
function handleUserCreation(PDO $pdo, array $data): string
{
    // Validation des données
    $required = ['nom', 'email', 'password', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }
    
    // Vérification email unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
    $stmt->execute([$data['email']]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cet email est déjà utilisé");
    }
    
    // Création de l'utilisateur
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $pdo->prepare("
        INSERT INTO utilisateurs (nom, email, mot_de_passe, role, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ")->execute([
        $data['nom'],
        $data['email'],
        $hashedPassword,
        $data['role']
    ]);
    
    // Journalisation
    logActivity(
        $pdo, 
        "Création d'un nouvel utilisateur: " . $data['email'],
        'user_create',
        $_SESSION['user_id']
    );
    
    return "✅ Utilisateur créé avec succès";
}

/**
 * Vérifie le token CSRF
 */
function verifyCsrfToken(): void
{
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Token de sécurité invalide");
    }
}

?>