<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../db.php";

// Vérification rôle super admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth.php");
    exit;
}


// Extraction ID numérique
$superAdminID = intval(preg_replace('/^[A-Z]+_/', '', $_SESSION['user_id']));

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajouter'])) {
    $errors = [];
    
    // Validation des données
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $prix = filter_input(INPUT_POST, 'prix', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $categorie_id = filter_input(INPUT_POST, 'categorie_id', FILTER_VALIDATE_INT);

    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if ($prix === false || $prix <= 0) $errors[] = "Prix invalide";
    if ($stock === false || $stock < 0) $errors[] = "Stock invalide";
    if ($categorie_id === false) $errors[] = "Catégorie invalide";

    if (empty($errors)) {
        try {
            $pdoMedical->beginTransaction();
            
            // Insertion du médicament
            $stmt = $pdoMedical->prepare("INSERT INTO medicaments 
                                        (nom, description, prix, stock, categorie_id) 
                                        VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $description, $prix, $stock, $categorie_id]);
            $medicament_id = $pdoMedical->lastInsertId();
            
            // Gestion de l'image
            if (!empty($_FILES['image']['name'])) {
                $uploadDir = '../../img/medicaments/';
                $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $targetFile = $uploadDir . $medicament_id . '.' . $imageFileType;
                
                // Validation du fichier
                $check = getimagesize($_FILES['image']['tmp_name']);
                if ($check === false) {
                    throw new Exception("Le fichier n'est pas une image valide");
                }
                
                if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
                    throw new Exception("Seuls les formats JPG, JPEG et PNG sont autorisés");
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    throw new Exception("Erreur lors du téléchargement de l'image");
                }
            }
            
            $pdoMedical->commit();
            $_SESSION['success'] = "Médicament ajouté avec succès!";
            header("Location: gestion_medicaments.php");
            exit;
            
        } catch (Exception $e) {
            $pdoMedical->rollBack();
            $errors[] = "Erreur: " . $e->getMessage();
        }
    }
}

// Récupération des catégories
$categories = [];
try {
    $stmt = $pdoMedical->query("SELECT id, nom FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur de chargement des catégories";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Médicament</title>
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], 
        input[type="number"],
        textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error { color: red; }
        .success { color: green; }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Ajouter un Nouveau Médicament</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="ajout_medicament.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom">Nom du médicament:</label>
                <input type="text" id="nom" name="nom" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="prix">Prix (€):</label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stock initial:</label>
                <input type="number" id="stock" name="stock" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="categorie_id">Catégorie:</label>
                <select id="categorie_id" name="categorie_id" required>
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= htmlspecialchars($categorie['id']) ?>">
                            <?= htmlspecialchars($categorie['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="image">Image du médicament:</label>
                <input type="file" id="image" name="image" accept="image/jpeg, image/png">
            </div>
            
            <button type="submit" name="ajouter" class="btn">Ajouter le Médicament</button>
            <a href="gestion_medicaments.php" class="btn" style="background: #ccc;">Annuler</a>
        </form>
    </div>
</body>
</html>