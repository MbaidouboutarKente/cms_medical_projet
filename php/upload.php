<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("Accès non autorisé.");
}

$userID = intval(str_replace("MED_", "", $_SESSION['user_id']));

// 🔹 Récupération du nom de l'utilisateur
$queryUser = $pdoMedical->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
$queryUser->execute([$userID]);
$userData = $queryUser->fetch();

if (!$userData) {
    die("Utilisateur introuvable.");
}

// 🔹 Extraire les 5 premières lettres du nom (et nettoyer)
$nomUtilisateur = preg_replace('/[^a-zA-Z]/', '', $userData['nom']); // Supprimer caractères spéciaux
$nomFichier = strtolower(substr($nomUtilisateur, 0, 5)); // Prendre les 5 premières lettres

// 🔹 Vérification et gestion de l'upload
if (!empty($_FILES['image']['name'])) {
    $targetDir = "../img/uploads/";

    // Créer le dossier s'il n'existe pas
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Déterminer l'extension
    $fileExtension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($fileExtension, $allowedTypes)) {
        die("Format d’image non autorisé.");
    }

    // 🔹 Enregistrer uniquement avec les 5 premières lettres du nom + extension
    $fileName = $nomFichier . "." . $fileExtension;
    $targetFilePath = $targetDir . $fileName;

    // 🔹 Vérifier si le fichier existe déjà et le remplacer
    if (file_exists($targetFilePath)) {
        unlink($targetFilePath); // Supprimer l'ancien fichier
    }

    // 🔹 Déplacer l'image et enregistrer en base
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
        try {
            $query = $pdoMedical->prepare("UPDATE utilisateurs SET image = ? WHERE id = ?");
            $query->execute([$fileName, $userID]);
            header("Location: profil.php?upload=success");
            exit;
        } catch (PDOException $e) {
            die("Erreur base de données : " . $e->getMessage());
        }
    } else {
        die("Échec du déplacement du fichier.");
    }
} else {
    die("Aucune image sélectionnée.");
}
?>
