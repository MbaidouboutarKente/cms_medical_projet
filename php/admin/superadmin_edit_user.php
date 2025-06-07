<?php
// superadmin_edit_user.php
// superadmin_edit_user.php

// Initialisation de la session et vérification des autorisations
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Protection contre les attaques
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Vérification des droits super_admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../auth.php");
    exit;
}

// Connexion à la base de données
require_once '../db.php';

// Initialisation des variables
$error = '';
$success = '';
$user = [];

// Récupération de l'ID utilisateur à modifier
$user_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: superadmin_users.php");
    exit;
}

// Récupération des données de l'utilisateur
try {
    $stmt = $pdoMedical->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: superadmin_users.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données utilisateur";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupération et nettoyage des données
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $role = filter_var($_POST['role'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $actif = isset($_POST['actif']) ? 1 : 0;

        // Validation des données
        if (empty($nom)  || empty($email) || empty($role)) {
            $error = "Tous les champs obligatoires doivent être remplis";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse email n'est pas valide";
        } elseif (!in_array($role, ['etudiant', 'infirmier', 'medecin', 'admin', 'super_admin'])) {
            $error = "Rôle utilisateur invalide";
        } else {
            // Mise à jour en base de données
            try {
                $stmt = $pdoMedical->prepare("UPDATE utilisateurs 
                                      SET nom = ?, email = ?, role = ?, is_active = ?, updated_at = NOW() 
                                      WHERE id = ?");
                $stmt->execute([$nom,  $email, $role, $actif, $user_id]);
                
                $success = "Utilisateur mis à jour avec succès";
                // Rechargement des données
                $stmt = $pdoMedical->prepare("SELECT * FROM utilisateurs WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        }
    }
}

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Utilisateur - SuperAdmin</title>
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .header h1 {
            color: var(--dark-color);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a5a80;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .form-check-input {
            margin-right: 10px;
        }
        
        .form-check-label {
            user-select: none;
        }
        
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
        }
        
        .role-option input[type="radio"] {
            display: none;
        }
        
        .role-option input[type="radio"]:checked + .role-label {
            background-color: var(--primary-color);
            color: white;
        }
        
        .role-label {
            display: block;
            padding: 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .role-selector {
                flex-direction: column;
            }
            
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Modifier l'utilisateur</h1>
            <a href="superadmin_users.php" class="btn btn-secondary">Retour à la liste</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" class="form-control" 
                           value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                </div>
                
                
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Rôle</label>
                    <div class="role-selector">
                        <div class="role-option">
                            <input type="radio" id="role_etudiant" name="role" value="etudiant" 
                                <?= ($user['role'] ?? '') === 'etudiant' ? 'checked' : '' ?>>
                            <label for="role_etudiant" class="role-label">Étudiant</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_infirmier" name="role" value="infirmier" 
                                <?= ($user['role'] ?? '') === 'infirmier' ? 'checked' : '' ?>>
                            <label for="role_infirmier" class="role-label">Infirmier</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_medecin" name="role" value="medecin" 
                                <?= ($user['role'] ?? '') === 'medecin' ? 'checked' : '' ?>>
                            <label for="role_medecin" class="role-label">Médecin</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_admin" name="role" value="admin" 
                                <?= ($user['role'] ?? '') === 'admin' ? 'checked' : '' ?>>
                            <label for="role_admin" class="role-label">Administrateur</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_super_admin" name="role" value="super_admin" 
                                <?= ($user['role'] ?? '') === 'super_admin' ? 'checked' : '' ?>>
                            <label for="role_super_admin" class="role-label">Super Admin</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="actif" name="actif" class="form-check-input" 
                           <?= ($user['actif'] ?? 0) ? 'checked' : '' ?>>
                    <label for="actif" class="form-check-label">Compte activé</label>
                </div>
                
                <div class="form-group">
                    <p><strong>Date de création :</strong> <?= htmlspecialchars($user['date_inscription'] ?? '') ?></p>
                    <p><strong>Dernière modification :</strong> <?= htmlspecialchars($user['updated_at'] ?? '') ?></p>
                </div>
                
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </form>
        </div>
    </div>
</body>
</html>