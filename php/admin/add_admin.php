<?php
session_start();
require_once "../db.php";

// Vérification rôle super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: auth.php");
    exit;
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token de sécurité invalide");
}

try {
    // Récupération des données
    $nom = htmlspecialchars($_POST['nom']);
    $matricule = htmlspecialchars($_POST['matricule']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Vérification email existant
    $checkEmail = $pdoMedical->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $checkEmail->execute([$email]);
    
    if ($checkEmail->fetch()) {
        throw new Exception("Cet email est déjà utilisé");
    }
    
    // Insertion
    $insert = $pdoMedical->prepare("INSERT INTO utilisateurs (matricule, nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, 'admin')");
    $insert->execute([$matricule, $nom, $email, $password]);
    
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Administrateur créé avec succès'
    ];
    
} catch (Exception $e) {
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => $e->getMessage()
    ];
}

header("Location: dashboard_super_admin.php");