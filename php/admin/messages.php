<?php
session_start();
require_once "../db.php"; // Connexion sécurisée à la base

// Activation du debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est bien connecté
if (!isset($_SESSION['user_id'])) {
    die("<p class='error'>Accès refusé. Vous devez être connecté pour voir les messages.</p>");
}

$message_id = $_GET['id'] ?? null;
$filtre_status = $_GET['status'] ?? null;
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    if ($message_id) {
        // Récupérer un message spécifique
        $sql = "SELECT id, name, email, subject, message, status, created_at, updated_at 
                FROM contacts
                WHERE id = :message_id";

        $stmt = $pdoMedical->prepare($sql);
        $stmt->execute([':message_id' => $message_id]);
        $message_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message_details) {
            $message = "<p class='error'>Message introuvable.</p>";
        }
    } else {
        // Construire la requête avec filtre et pagination
        $sql = "SELECT id, name, subject, status, created_at FROM contacts";
        if ($filtre_status) {
            $sql .= " WHERE status = :status";
        }
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdoMedical->prepare($sql);
        if ($filtre_status) {
            $stmt->bindParam(':status', $filtre_status);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compter le nombre total de messages pour la pagination
        $count_sql = "SELECT COUNT(*) FROM contacts";
        if ($filtre_status) {
            $count_sql .= " WHERE status = :status";
        }
        $count_stmt = $pdoMedical->prepare($count_sql);
        if ($filtre_status) {
            $count_stmt->bindParam(':status', $filtre_status);
        }
        $count_stmt->execute();
        $total_messages = $count_stmt->fetchColumn();
        $total_pages = ceil($total_messages / $limit);
    }
} catch (PDOException $e) {
    $message = "<p class='error'>Erreur SQL : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messages Reçus</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* 🌍 Style général */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            text-align: center;
        }

        /* 📦 Conteneur principal */
        .container {
            max-width: 800px;
            padding: 25px;
            border-radius: 10px;
            background: white;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
            margin: 40px auto;
        }

        /* 📬 Titre */
        h2 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* 📨 Liste des messages */
        .list {
            text-align: left;
            padding: 15px;
            border-top: 1px solid #ddd;
        }

        .list a {
            display: block;
            padding: 12px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            border-radius: 5px;
            transition: background 0.3s, color 0.3s;
        }

        .list a:hover {
            background-color: #007bff;
            color: white;
        }

        /* ❌ Messages d'erreur */
        .error {
            color: red;
            font-weight: bold;
            background-color: #f8d7da;
            padding: 12px;
            border-radius: 5px;
        }

        /* 📜 Détails du message */
        .message-details {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background: #f4f4f4;
            text-align: left;
            margin: 20px auto;
            max-width: 600px;
        }

        /* 🏷️ Champs des détails */
        .message-details p {
            margin: 10px 0;
            font-size: 16px;
            color: #333;
        }

        /* 🔍 Filtre */
        .filter {
            margin: 20px 0;
        }

        .filter select {
            padding: 8px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .filter button {
            padding: 10px;
            font-size: 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .filter button:hover {
            background: #0056b3;
        }

        /* 🔢 Pagination */
        .pagination {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }

        .pagination a {
            text-decoration: none;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 5px;
            font-size: 16px;
            background: #007bff;
            color: white;
            transition: background 0.3s;
        }

        .pagination a:hover,
        .pagination a.active {
            background: #0056b3;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>Messages Reçus</h2>

    <?= $message ?? ''; ?>

    <?php if ($message_id && $message_details): ?>
        <div class="message-details">
            <p><strong>Nom :</strong> <?= htmlspecialchars($message_details['name']); ?></p>
            <p><strong>Email :</strong> <?= htmlspecialchars($message_details['email']); ?></p>
            <p><strong>Sujet :</strong> <?= htmlspecialchars($message_details['subject']); ?></p>
            <p><strong>Message :</strong> <?= nl2br(htmlspecialchars($message_details['message'])); ?></p>
            <p><strong>Status :</strong> <?= htmlspecialchars($message_details['status']); ?></p>
            <p><strong>Créé le :</strong> <?= $message_details['created_at']; ?></p>
            <p><strong>Mis à jour le :</strong> <?= $message_details['updated_at']; ?></p>
        </div>
        <a href="messages.php" class="btn">🔙 Retour aux messages</a>
    <?php elseif (!$message_id): ?>
        <div class="filter">
            <form method="GET" action="messages.php">
                <label for="status">Filtrer par statut :</label>
                <select name="status" id="status">
                    <option value="">Tous</option>
                    <option value="non_lu" <?= $filtre_status == "non_lu" ? "selected" : ""; ?>>Non lu</option>
                    <option value="lu" <?= $filtre_status == "lu" ? "selected" : ""; ?>>Lu</option>
                </select>
                <button type="submit">🔍 Filtrer</button>
            </form>
        </div>

        <div class="list">
            <?php if (!empty($messages_list)): ?>
                <?php foreach ($messages_list as $msg): ?>
                    <p><a href="messages.php?id=<?= $msg['id']; ?>">📩 <?= htmlspecialchars($msg['subject']); ?> - <?= htmlspecialchars($msg['name']); ?> (<?= htmlspecialchars($msg['status']); ?>)</a></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error">Aucun message trouvé.</p>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="messages.php?page=<?= $i; ?>&status=<?= $filtre_status; ?>" class="<?= ($page == $i) ? 'active' : ''; ?>">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
