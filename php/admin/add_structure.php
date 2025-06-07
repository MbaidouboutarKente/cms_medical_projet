<?php
session_start();
require_once "../db.php";

// Vérification rôle super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../auth.php");
    exit;
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token de sécurité invalide");
}

try {
    // Récupération des données
    $nom = htmlspecialchars($_POST['nom']);
    $adresse = htmlspecialchars($_POST['adresse']);
    
    // Insertion
    $insert = $pdoMedical->prepare("INSERT INTO structures (nom, adresse) VALUES (?, ?)");
    $insert->execute([$nom, $adresse]);
    
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Structure créée avec succès'
    ];
    
} catch (Exception $e) {
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => $e->getMessage()
    ];
}

header("Location: dashboard_super_admin.php");