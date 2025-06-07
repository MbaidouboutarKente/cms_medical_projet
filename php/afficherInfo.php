<?php
session_start();
include "db.php";
include "functions.php";

// Vérification de session active
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== "etudiant") {
    header("Location: login.php");
    exit;
}

// 🔹 Récupération des infos utilisateur depuis `utilisateurs`
$queryUser = $pdoMedical->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$queryUser->execute([intval(str_replace("MED_", "", $_SESSION['user_id']))]);
$userData = $queryUser->fetch();

// 🔹 Récupération des infos académiques depuis `etudiants`
$queryEtudiant = $pdoCampus->prepare("SELECT * FROM etudiants WHERE matricule = ?");
$queryEtudiant->execute([$userData['matricule']]);
$etudiantData = $queryEtudiant->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant</title>
</head>
<body>

<header>
    <h2>Bienvenue, <?php echo htmlspecialchars($userData['nom'] ?? "Nom non disponible"); ?> (<?php echo htmlspecialchars($etudiantData['prenom'] ?? "Prénom non disponible"); ?>)</h2>
</header>

<section>
    <h3>🧑‍💼 Informations générales (Table `utilisateurs`)</h3>
    <ul>
        <?php foreach ($userData as $key => $value) {
            echo "<li><strong>$key :</strong> " . htmlspecialchars($value) . "</li>";
        } ?>
    </ul>
</section>

<section>
    <h3>📖 Informations académiques (Table `etudiants`)</h3>
    <ul>
        <?php foreach ($etudiantData as $key => $value) {
            echo "<li><strong>$key :</strong> " . htmlspecialchars($value) . "</li>";
        } ?>
    </ul>
</section>

<nav>
    <ul>
        <li><a href="cours.php">📚 Mes cours</a></li>
        <li><a href="paiement.php">💳 Paiements</a></li>
        <li><a href="profil.php">👤 Mon profil</a></li>
        <li><a href="logout.php">🚪 Déconnexion</a></li>
    </ul>
</nav>

<main>
    <section>
        <h3>📢 Annonces importantes</h3>
        <p>Aucune annonce pour le moment.</p>
    </section>

    <section>
        <h3>📊 Statistiques</h3>
        <p>Progression : 75% du semestre complété.</p>
        <p>Prochain examen : 20 Mai 2025</p>
    </section>
</main>

</body>
</html>
