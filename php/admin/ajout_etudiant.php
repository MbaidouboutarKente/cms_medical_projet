<?php
session_start();
include "../db.php";

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricule = $_POST['matricule'] ?? "";
    $nom = $_POST['nom'] ?? "";
    $prenom = $_POST['prenom'] ?? "";
    $email = $_POST['email'] ?? "";
    $filiere = $_POST['filiere'] ?? ($_POST['filiere_libre'] ?? "");
    $niveau = $_POST['niveau'] ?? "";
    $statut_etudiant = $_POST['statut_etudiant'] ?? "Nouveau";
    $date_inscription = $_POST['date_inscription'] ?? "";
    $statut = $_POST['statut'] ?? "actif";

    // Vérification des champs obligatoires
    if (empty($matricule) || empty($nom) || empty($prenom) || empty($email) || empty($filiere) || empty($niveau) || empty($date_inscription)) {
        $message = "🔴 Erreur : Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "🔴 Erreur : Format d'email invalide.";
    } else {
        try {
            // Vérification de l'unicité du matricule et de l'email
            $queryCheck = $pdoCampus->prepare("SELECT id FROM etudiants WHERE matricule = ? OR email = ?");
            $queryCheck->execute([$matricule, $email]);
            if ($queryCheck->fetch()) {
                $message = "🔴 Erreur : Matricule ou email déjà existant !";
            } else {
                // Insertion sécurisée dans la base de données
                $queryInsert = $pdoCampus->prepare("
                    INSERT INTO etudiants (matricule, nom, prenom, email, filiere, niveau, statut_etudiant, date_inscription, statut) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($queryInsert->execute([$matricule, $nom, $prenom, $email, $filiere, $niveau, $statut_etudiant, $date_inscription, $statut])) {
                    $message = "✅ Étudiant ajouté avec succès !";
                } else {
                    $message = "🔴 Erreur SQL : " . implode(" | ", $queryInsert->errorInfo());
                }
            }
        } catch (PDOException $e) {
            $message = "🔴 Erreur de base de données : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout d'un Étudiant</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Ajouter un Étudiant</h1>

        <?php if (!empty($message)): ?>
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Matricule :</label>
            <input type="text" name="matricule" required>

            <label>Nom :</label>
            <input type="text" name="nom" required>

            <label>Prénom :</label>
            <input type="text" name="prenom" required>

            <label>Email :</label>
            <input type="email" name="email" required>

            <label>Filière :</label>
            <select name="filiere" id="filiere">
                <option value="informatique">Informatique</option>
                <option value="biologie">Biologie</option>
                <option value="bio-medical">Bio-Médical</option>
                <option value="physique">Physique</option>
                <option value="chimie">Chimie</option>
                <option value="mathematique">Mathématiques</option>
                <option value="autre">Autre (saisie libre)</option>
            </select>
            <input type="text" name="filiere_libre" id="filiere_libre" placeholder="Autre filière..." style="display:none;">

            <label>Niveau :</label>
            <select name="niveau" required>
                <option value="L1">Licence 1</option>
                <option value="L2">Licence 2</option>
                <option value="L3">Licence 3</option>
                <option value="M1">Master 1</option>
                <option value="M2">Master 2</option>
            </select>

            <label>Date d'inscription :</label>
            <input type="date" name="date_inscription" required>

            <label>Statut Étudiant :</label>
            <select name="statut_etudiant">
                <option value="Ancien">Ancien</option>
                <option value="Nouveau">Nouveau</option>
            </select>

            <label>Statut :</label>
            <select name="statut">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
            </select>

            <button type="submit">✅ Ajouter Étudiant</button>
        </form>

        <a href="dashboard_admin.php">Retour au tableau de bord</a>
    </div>

    <script>
        document.getElementById('filiere').addEventListener('change', function() {
            var champLibre = document.getElementById('filiere_libre');
            champLibre.style.display = (this.value === "autre") ? "block" : "none";
        });
    </script>
</body>
</html>
