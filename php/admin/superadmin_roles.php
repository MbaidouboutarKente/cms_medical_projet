<?php
// superadmin_roles.php

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement des formulaires
    if (isset($_POST['add_role'])) {
        $roleName = filter_input(INPUT_POST, 'role_name', FILTER_SANITIZE_STRING);
        // Ajouter le rôle à la base de données
    }
    
    if (isset($_POST['assign_permissions'])) {
        // Assigner des permissions à un rôle
    }
}

// Récupérer tous les rôles
$roles = $pdoMedical->query("SELECT * FROM utilisateurs ")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestion des Rôles</title>
    <!-- En-tête commun -->
</head>
<body>
    <div class="container">
        <h1>Gestion des Rôles</h1>
        
        <!-- Formulaire d'ajout de rôle -->
        <form method="POST">
            <input type="text" name="role_name" required>
            <button type="submit" name="add_role">Ajouter un rôle</button>
        </form>
        
        <!-- Liste des rôles -->
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom </th>
                    <th>Nom du Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= $role['id'] ?></td>
                    <td><?= htmlspecialchars($role['nom']) ?></td>
                    <td><?= htmlspecialchars($role['role']) ?></td>
                    <td>
                        <a href="superadmin_role_edit.php?id=<?= $role['id'] ?>">Modifier</a>
                        <a href="?delete=<?= $role['id'] ?>" onclick="return confirm('Supprimer ce rôle?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>