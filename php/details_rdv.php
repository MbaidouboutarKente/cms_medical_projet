<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de rendez-vous invalide.");
}

$rdvID = intval($_GET['id']);

try {
    $stmt = $pdoMedical->prepare("
        SELECT r.date_rdv, r.type_rdv, r.statut,
               COALESCE(u1.nom, u2.nom) AS professionnel_nom,
               u1.email AS email_medecin, u2.email AS email_infirmier,
               CASE 
                   WHEN r.medecin_id IS NOT NULL THEN 'Médecin'
                   WHEN r.infirmier_id IS NOT NULL THEN 'Infirmier'
                   ELSE 'Non attribué'
               END AS professionnel_type
        FROM rendez_vous r
        LEFT JOIN utilisateurs u1 ON r.medecin_id = u1.id AND u1.role = 'medecin'
        LEFT JOIN utilisateurs u2 ON r.infirmier_id = u2.id AND u2.role = 'infirmier'
        WHERE r.id = ?
    ");
    
    $stmt->execute([$rdvID]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rdv) {
        die("Rendez-vous introuvable.");
    }
} catch (PDOException $e) {
    die("Erreur lors du chargement des détails.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Rendez-vous</title>
    <link rel="stylesheet" href="../css/styrdv.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>🔍 Détails du Rendez-vous</h1>
        <div class="card">
            <p><strong>📅 Date :</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($rdv['date_rdv']))) ?></p>
            <p><strong>🩺 Type :</strong> <?= htmlspecialchars(ucfirst($rdv['type_rdv'])) ?></p>
            <p><strong>👨‍⚕️ Professionnel :</strong> <?= htmlspecialchars($rdv['professionnel_nom'] ?? 'Non attribué') ?> (<?= htmlspecialchars($rdv['professionnel_type']) ?>)</p>
            <p><strong>📧 Contact :</strong> <?= htmlspecialchars($rdv['email_medecin'] ?? $rdv['email_infirmier'] ?? 'Non disponible') ?></p>
            <p><strong>⚡ Statut :</strong> <span class="status"><?= htmlspecialchars($rdv['statut']) ?></span></p>
        </div>
        <div class="actions">
            <a href="liste_rdv.php" class="btn">↩ Retour à la liste</a>
            <?php if ($rdv['statut'] === 'En attente'): ?>
                <a href="modifier_rdv.php?id=<?= htmlspecialchars($rdvID) ?>" class="btn btn-warning">✏ Modifier</a>
                <a href="annuler_rdv.php?id=<?= htmlspecialchars($rdvID) ?>" class="btn btn-danger"
                   onclick="return confirm('Voulez-vous vraiment annuler ce rendez-vous ?')">
                   ✖ Annuler
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
