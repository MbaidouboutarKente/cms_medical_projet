<?php
session_start();
require_once "db.php";

// Vérification des permissions
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Récupération de l'ID du médicament
$medicament_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$medicament_id) {
    header("Location: gestion_medicaments.php");
    exit;
}

// Récupération des données actuelles
try {
    $stmt = $pdoMedical->prepare("SELECT * FROM medicaments WHERE id = ?");
    $stmt->execute([$medicament_id]);
    $medicament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$medicament) {
        header("Location: gestion_medicaments.php");
        exit;
    }
} catch (PDOException $e) {
    die("Erreur de récupération des données");
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifier'])) {
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
            
            // Mise à jour du médicament
            $stmt = $pdoMedical->prepare("UPDATE medicaments 
                                         SET nom = ?, description = ?, prix = ?, 
                                             stock = ?, categorie_id = ?
                                         WHERE id = ?");
            $stmt->execute([$nom, $description, $prix, $stock, $categorie_id, $medicament_id]);
            
            // Gestion de l'image si nouvelle image fournie
            if (!empty($_FILES['image']['name'])) {
                $uploadDir = 'images/medicaments/';
                
                // Supprimer l'ancienne image si elle existe
                $oldImages = glob($uploadDir . $medicament_id . '.*');
                foreach ($oldImages as $oldImage) {
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                
                // Télécharger la nouvelle image
                $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $targetFile = $uploadDir . $medicament_id . '.' . $imageFileType;
                
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
            $_SESSION['success'] = "Médicament mis à jour avec succès!";
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

// Vérification de l'existence d'une image
$imagePath = null;
$imageExtensions = ['jpg', 'jpeg', 'png'];
foreach ($imageExtensions as $ext) {
    $possiblePath = 'images/medicaments/' . $medicament_id . '.' . $ext;
    if (file_exists($possiblePath)) {
        $imagePath = $possiblePath;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Médicament</title>
    <style>
        /* [Conserver les mêmes styles que pour l'ajout] */
        .current-image {
            max-width: 200px;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Modifier le Médicament</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="modifier_medicament.php?id=<?= $medicament_id ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom">Nom du médicament:</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($medicament['nom']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($medicament['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="prix">Prix (€):</label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" 
                       value="<?= htmlspecialchars($medicament['prix']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stock:</label>
                <input type="number" id="stock" name="stock" min="0" 
                       value="<?= htmlspecialchars($medicament['stock']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="categorie_id">Catégorie:</label>
                <select id="categorie_id" name="categorie_id" required>
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= htmlspecialchars($categorie['id']) ?>" 
                            <?= $categorie['id'] == $medicament['categorie_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categorie['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Image actuelle:</label>
                <?php if ($imagePath): ?>
                    <img src="<?= $imagePath ?>" class="current-image">
                    <p><small><?= basename($imagePath) ?></small></p>
                <?php else: ?>
                    <p>Aucune image disponible</p>
                <?php endif; ?>
                
                <label for="image">Nouvelle image:</label>
                <input type="file" id="image" name="image" accept="image/jpeg, image/png">
                <p><small>Laisser vide pour conserver l'image actuelle</small></p>
            </div>
            
            <button type="submit" name="modifier" class="btn">Mettre à jour</button>
            <a href="gestion_medicaments.php" class="btn" style="background: #ccc;">Annuler</a>
        </form>
    </div>
</body>
</html>