<?php
session_start();
require_once "../db.php";

// Vérification du rôle Admin ou Super Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth.php");
    exit;
}

// Gestion des erreurs et des messages
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? "";

// Récupération des médicaments avec pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $stmt = $pdoMedical->prepare("SELECT * FROM medicaments ORDER BY nom LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "❌ Erreur de chargement des médicaments.";
}

// Suppression d'un médicament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $deleteId = intval($_POST["delete_id"]);
    
    try {
        // Supprimer les références dans details_commande
        $stmt = $pdoMedical->prepare("DELETE FROM details_commande WHERE medicament_id = ?");
        $stmt->execute([$deleteId]);
    
        // Maintenant, supprimer le médicament
        $stmt = $pdoMedical->prepare("DELETE FROM medicaments WHERE id = ?");
        $stmt->execute([$deleteId]);
    
        $_SESSION['success'] = "✅ Médicament supprimé avec succès.";
        header("Location: gestion_medicaments.php");
        exit;
    } catch (PDOException $e) {
        $errors[] = "❌ Erreur SQL : " . $e->getMessage();
    }
    
}

// Récupération du médicament à modifier
$editId = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : null;
$medicamentEdit = null;

if ($editId) {
    try {
        $stmt = $pdoMedical->prepare("SELECT * FROM medicaments WHERE id = ?");
        $stmt->execute([$editId]);
        $medicamentEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = "❌ Erreur de récupération du médicament.";
    }
}

// Modification d'un médicament
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["modifier"])) {
    $editId = intval($_POST["edit_id"]);
    $nom = filter_input(INPUT_POST, "nom", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $prix = filter_input(INPUT_POST, "prix", FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, "stock", FILTER_VALIDATE_INT);

    if ($prix === false || $prix <= 0) {
        $errors[] = "⚠️ Prix invalide.";
    }
    if ($stock === false || $stock < 0) {
        $errors[] = "⚠️ Stock invalide.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdoMedical->prepare("UPDATE medicaments SET nom = ?, prix = ?, stock = ? WHERE id = ?");
            $stmt->execute([$nom, $prix, $stock, $editId]);

            // Gestion de l'image si téléchargée
            if (!empty($_FILES['image']['name'])) {
                $uploadDir = "../../img/medicaments/";
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $targetFile = $uploadDir . $editId . '.' . $extension;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $errors[] = "⚠️ Erreur lors du téléchargement de l’image.";
                }
            }

            $_SESSION["success"] = "✅ Médicament mis à jour avec succès.";
            header("Location: gestion_medicaments.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "❌ Erreur lors de la modification.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🩺 Gestion des Médicaments</title>
    <!-- <link rel="stylesheet" href="styles.css">  -->
     <style>
        /* 🌐 Styles généraux */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            text-align: center;
            padding: 20px;
        }

        /* 📦 Conteneur principal */
        .container {
            max-width: 800px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
            margin: auto;
        }

        /* 📝 Titres */
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        /* ✅ Messages de succès */
        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* ❌ Messages d'erreur */
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* 🩺 Tableau des médicaments */
        .medicaments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .medicaments-table th, .medicaments-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .medicaments-table th {
            background: #007bff;
            color: white;
        }

        .medicaments-table tr:nth-child(even) {
            background: #f2f2f2;
        }

        /* 📷 Images des médicaments */
        .medicaments-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        /* ✏️ Bouton Modifier */
        .btn-edit {
            background: #007bff;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit:hover {
            background: #0056b3;
        }

        /* ❌ Bouton Supprimer */
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* 📑 Formulaire de modification */
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"], 
        input[type="number"], 
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }

     </style>
</head>
<body>
    <div class="container">
        <h1>📋 Gestion des Médicaments</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-box"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <table class="medicaments-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Prix (Frc )</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medicaments as $medicament): ?>
                <tr>
                    <td><?= htmlspecialchars($medicament['id']) ?></td>
                    <td><img src="../../img/medicaments/<?= htmlspecialchars($medicament['id']) ?>.jpg" alt="Image"></td>
                    <td><?= htmlspecialchars($medicament['nom']) ?></td>
                    <td><?= number_format($medicament['prix'], 2) ?> Frc</td>
                    <td><?= htmlspecialchars($medicament['stock']) ?></td>
                    <td>
                        <a href="?edit_id=<?= $medicament['id'] ?>" class="btn btn-edit">✏️ Modifier</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= $medicament['id'] ?>">
                            <button type="submit" class="btn btn-delete" onclick="return confirm('Confirmer la suppression ?')">❌ Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($medicamentEdit): ?>
            <div class="container">
                <h2>✏️ Modifier le Médicament</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($medicamentEdit['id']) ?>">

                    <label>Nom :</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($medicamentEdit['nom']) ?>" required>

                    <label>Prix (Frc) :</label>
                    <input type="number" name="prix" step="0.01" value="<?= htmlspecialchars($medicamentEdit['prix']) ?>" required>

                    <label>Stock :</label>
                    <input type="number" name="stock" value="<?= htmlspecialchars($medicamentEdit['stock']) ?>" required>

                    <label>Changer l’image :</label>
                    <input type="file" name="image" accept="image/jpeg, image/png">

                    <button type="submit" name="modifier">🔄 Mettre à jour</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
