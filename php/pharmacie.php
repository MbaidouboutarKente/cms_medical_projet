<?php
// Initialisation de la session et configuration
session_start();
require_once "db.php";

// Configuration du débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ======================================================================
// SECTION AUTHENTIFICATION ET SÉCURITÉ
// ======================================================================

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    $_SESSION['auth_error'] = "Veuillez vous connecter pour accéder à la pharmacie";
    header("Location: auth.php");
    exit;
}

if ($_SESSION['role'] !== 'etudiant') {
    $_SESSION['auth_error'] = "Accès réservé aux étudiants";
    header("Location: index.php");
    exit;
}

// Validation de l'ID utilisateur
$user_id = $_SESSION['user_id']; // Conservation de l'ID de session original

// Vérification supplémentaire si l'ID contient un préfixe (ex: 'MED_123')
if (preg_match('/^[A-Z]+_(\d+)$/', $user_id, $matches)) {
    $user_id = $matches[1]; // Extraction de la partie numérique
}

$user_id = filter_var($user_id, FILTER_VALIDATE_INT);
if ($user_id === false) {
    session_unset();
    session_destroy();
    $_SESSION['auth_error'] = "Identifiant utilisateur invalide";
    header("Location: auth.php");
    exit;
}

// Vérification de la connexion à la base de données
if (!$pdoMedical) {
    die("Erreur de connexion à la base de données");
}

// Vérification de l'existence de l'utilisateur en base
try {
    $stmt = $pdoMedical->prepare("SELECT id FROM utilisateurs WHERE id = ?");
    if (!$stmt->execute([$user_id]) || $stmt->rowCount() === 0) {
        session_unset();
        session_destroy();
        $_SESSION['auth_error'] = "Compte introuvable";
        header("Location: auth.php");
        exit;
    }
} catch (PDOException $e) {
    die("Erreur de vérification utilisateur: " . $e->getMessage());
}

// Génération du token CSRF sécurisé
if (empty($_SESSION['pharma_token'])) {
    try {
        $_SESSION['pharma_token'] = bin2hex(random_bytes(32));
        $_SESSION['pharma_token_time'] = time();
    } catch (Exception $e) {
        die("Erreur de génération du token de sécurité");
    }
}

// ======================================================================
// TRAITEMENT DU FORMULAIRE
// ======================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $medicaments = [];

    // Vérification de l'action demandée
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'confirmer_commande':
                try {
                    // Validation CSRF
                    if (empty($_POST['token']) || !hash_equals($_SESSION['pharma_token'], $_POST['token'])) {
                        throw new Exception("Erreur de sécurité. Veuillez rafraîchir la page.");
                    }

                    // Vérification de l'ancienneté du token (15 minutes max)
                    if (time() - $_SESSION['pharma_token_time'] > 900) {
                        throw new Exception("La session a expiré. Veuillez recommencer.");
                    }

                    // Validation des médicaments
                    if (empty($_POST['medicaments']) || !is_array($_POST['medicaments'])) {
                        throw new Exception("Aucun médicament sélectionné");
                    }

                    // Filtrage et validation des données
                    foreach ($_POST['medicaments'] as $id => $qty) {
                        $id = filter_var($id, FILTER_VALIDATE_INT);
                        $qty = filter_var($qty, FILTER_VALIDATE_INT, [
                            'options' => ['min_range' => 1, 'max_range' => 3]
                        ]);

                        if ($id !== false && $qty !== false) {
                            $medicaments[$id] = $qty;
                        }
                    }

                    if (empty($medicaments)) {
                        throw new Exception("Veuillez sélectionner au moins un médicament valide");
                    }

                    // Traitement de la commande
                    $pdoMedical->beginTransaction();
                    try {
                        // Vérification de l'existence de l'utilisateur
                        $stmt = $pdoMedical->prepare("SELECT id FROM utilisateurs WHERE id = ?");
                        if (!$stmt->execute([$user_id])) {
                            throw new Exception("Erreur de vérification utilisateur");
                        }
                        
                        if ($stmt->rowCount() === 0) {
                            session_unset();
                            session_destroy();
                            throw new Exception("Votre compte n'existe plus. Veuillez vous reconnecter.");
                        }

                        // Vérification du stock et récupération des prix
                        $placeholders = implode(',', array_fill(0, count($medicaments), '?'));
                        $stmt = $pdoMedical->prepare("SELECT id, nom, prix, stock FROM medicaments WHERE id IN ($placeholders) FOR UPDATE");
                        $stmt->execute(array_keys($medicaments));
                        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $stock_errors = [];
                        $prix_medicaments = [];
                        foreach ($stocks as $stock) {
                            if ($stock['stock'] < $medicaments[$stock['id']]) {
                                $stock_errors[] = "Stock insuffisant pour " . htmlspecialchars($stock['nom']) . 
                                               " (demandé: {$medicaments[$stock['id']]}, disponible: {$stock['stock']})";
                            }
                            $prix_medicaments[$stock['id']] = $stock['prix'];
                        }

                        if (!empty($stock_errors)) {
                            throw new Exception(implode("<br>", $stock_errors));
                        }

                        // Enregistrement de la commande
                        $stmt = $pdoMedical->prepare("INSERT INTO commandes_pharma (user_id, date_commande) VALUES (?, NOW())");
                        if (!$stmt->execute([$user_id])) {
                            throw new Exception("Erreur lors de la création de la commande");
                        }
                        $commande_id = $pdoMedical->lastInsertId();

                        // Détails de la commande et mise à jour du stock
                        foreach ($medicaments as $id => $qty) {
                            // Insertion des détails de commande
                            $stmt = $pdoMedical->prepare("INSERT INTO details_commande 
                                                        (commande_id, medicament_id, quantite, prix_unitaire) 
                                                        VALUES (?, ?, ?, ?)");
                            if (!$stmt->execute([$commande_id, $id, $qty, $prix_medicaments[$id]])) {
                                throw new Exception("Erreur lors de l'ajout du médicament ID $id");
                            }

                            // Mise à jour du stock
                            $stmt = $pdoMedical->prepare("UPDATE medicaments SET stock = stock - ? WHERE id = ?");
                            if (!$stmt->execute([$qty, $id])) {
                                throw new Exception("Erreur de mise à jour du stock pour le médicament ID $id");
                            }

                            // Vérification que le stock a bien été mis à jour
                            if ($stmt->rowCount() === 0) {
                                throw new Exception("Aucune ligne mise à jour pour le médicament ID $id");
                            }
                        }

                        $pdoMedical->commit();
                        $_SESSION['pharma_success'] = "Votre commande #$commande_id a bien été enregistrée. Retrait possible dans 1h.";
                        unset($_SESSION['commande_preparee']);
                    } catch (Exception $e) {
                        $pdoMedical->rollBack();
                        throw $e;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
                break;

            case 'preparer_commande':
                try {
                    if (empty($_POST['medicaments']) || !is_array($_POST['medicaments'])) {
                        throw new Exception("Aucun médicament sélectionné");
                    }

                    $medsPrepare = [];
                    foreach ($_POST['medicaments'] as $id => $qty) {
                        $id = filter_var($id, FILTER_VALIDATE_INT);
                        $qty = filter_var($qty, FILTER_VALIDATE_INT, [
                            'options' => ['min_range' => 1, 'max_range' => 3]
                        ]);

                        if ($id !== false && $qty !== false) {
                            $medsPrepare[$id] = $qty;
                        }
                    }

                    if (empty($medsPrepare)) {
                        throw new Exception("Veuillez sélectionner au moins un médicament valide");
                    }

                    $_SESSION['commande_preparee'] = $medsPrepare;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
                break;

            case 'annuler_commande':
                unset($_SESSION['commande_preparee']);
                $_SESSION['pharma_info'] = "Commande annulée";
                break;

            default:
                $errors[] = "Action non reconnue";
        }
    }

    if (!empty($errors)) {
        $_SESSION['pharma_errors'] = $errors;
    }

    header("Location: pharmacie.php");
    exit;
}

// ======================================================================
// RÉCUPÉRATION DES DONNÉES
// ======================================================================

// Récupération des médicaments disponibles
// Récupération des médicaments disponibles avec gestion des images
$medicaments = [];
$current_categorie = null;
$medicaments_sans_image = []; // Pour suivre les médicaments sans image

try {
    $sql = "SELECT m.*, c.nom AS categorie_nom 
            FROM medicaments m
            JOIN categories c ON m.categorie_id = c.id
            WHERE m.stock > 0 
            ORDER BY c.nom, m.nom";
    
    $stmt = $pdoMedical->prepare($sql);
    
    if (!$stmt->execute()) {
        throw new PDOException("Erreur d'exécution de requête");
    }
    
    $medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($medicaments)) {
        $_SESSION['pharma_info'] = "Aucun médicament disponible pour le moment.";
    } else {
        // Vérification des images pour chaque médicament
        foreach ($medicaments as &$medicament) {
            $image_path = '../img/medicaments/' . $medicament['id'] . '.jpg';
            
            if (!file_exists($image_path)) {
                $medicament['image_missing'] = true;
                $medicaments_sans_image[] = $medicament['id'] . ' - ' . $medicament['nom'];
                
                // Utiliser une image par défaut
                $medicament['image_path'] = '../img/medicaments/default.jpg';
            } else {
                $medicament['image_path'] = $image_path;
                $medicament['image_missing'] = false;
            }
        }
        unset($medicament); // Détruire la référence
        
        // Signalement des médicaments sans image (journalisation)
        if (!empty($medicaments_sans_image)) {
            $message = "Médicaments sans image : " . implode(', ', $medicaments_sans_image);
            error_log($message);
            
            // Optionnel : notifier l'administrateur
            if (count($medicaments_sans_image) > 3) {
                $_SESSION['pharma_info'] = "Certains médicaments n'ont pas d'image. L'administrateur a été notifié.";
                // mail('admin@example.com', 'Médicaments sans image', $message);
            }
        }
    }

} catch (PDOException $e) {
    error_log("ERREUR MEDICAMENTS: " . $e->getMessage());
    $_SESSION['pharma_errors'] = [
        "Désolé, nous rencontrons des difficultés techniques.",
        "Détail: " . (strpos($e->getMessage(), 'SQLSTATE') !== false ? 'Problème base de données' : 'Système indisponible')
    ];
}


// Récupération de l'historique des commandes
$historique = [];
try {
    $stmt = $pdoMedical->prepare("
        SELECT c.id, c.date_commande, c.statut, 
               COUNT(d.id) as nb_medicaments, 
               SUM(d.quantite) as total_items
        FROM commandes_pharma c
        JOIN details_commande d ON c.id = d.commande_id
        WHERE c.user_id = ?
        GROUP BY c.id
        ORDER BY c.date_commande DESC
        LIMIT 5
    ");
    if (!$stmt->execute([$user_id])) {
        throw new PDOException("Erreur lors de la récupération de l'historique");
    }
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur DB historique: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacie Universitaire</title>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet"> -->
    
    <link rel="stylesheet" href="../css/pharmacie.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Pharmacie Universitaire</h1>
            <p class="pharma-subtitle">Service réservé aux étudiants - Ordonnance non requise</p>
        </div>
    </header>
    <button class="btn-retour" onclick="history.back()"><-</button>

    
    <div class="container">
        <!-- Affichage des messages -->
        <?php if (!empty($_SESSION['pharma_errors'])): ?>
            <div class="alert alert-danger">
                <h4>Erreur(s) lors de la commande</h4>
                <ul>
                    <?php foreach ($_SESSION['pharma_errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['pharma_errors']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['pharma_success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['pharma_success']) ?>
            </div>
            <?php unset($_SESSION['pharma_success']); ?>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['pharma_info'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_SESSION['pharma_info']) ?>
            </div>
            <?php unset($_SESSION['pharma_info']); ?>
        <?php endif; ?>

        <!-- Formulaire de commande -->
        <?php if (isset($_SESSION['commande_preparee'])): ?>
            <div class="confirmation-box">
                <h2>Confirmation de votre commande</h2>
                <p>Veuillez vérifier les détails avant validation :</p>
                
                <?php 
                // Récupération des détails des médicaments sélectionnés
                $medsSelectionnes = [];
                $placeholders = implode(',', array_fill(0, count($_SESSION['commande_preparee']), '?'));
                $stmt = $pdoMedical->prepare("SELECT id, nom, prix FROM medicaments WHERE id IN ($placeholders)");
                $stmt->execute(array_keys($_SESSION['commande_preparee']));
                $medsDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($medsDetails as $med) {
                    $medsSelectionnes[$med['id']] = $med;
                }
                ?>
                
                <div class="medicaments-confirmes">
                    <?php foreach ($_SESSION['commande_preparee'] as $id => $qty): ?>
                        <?php if (isset($medsSelectionnes[$id])): ?>
                            <div class="medicament-confirme">
                                <span><?= htmlspecialchars($medsSelectionnes[$id]['nom']) ?> (x<?= $qty ?>)</span>
                                <span><?= number_format($medsSelectionnes[$id]['prix'] * $qty, 2) ?> €</span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" id="confirmationForm">
                    <input type="hidden" name="action" value="confirmer_commande">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['pharma_token']) ?>">
                    <?php foreach ($_SESSION['commande_preparee'] as $id => $qty): ?>
                        <input type="hidden" name="medicaments[<?= $id ?>]" value="<?= $qty ?>">
                    <?php endforeach; ?>
                    
                    <div class="confirmation-actions">
                        <button type="submit" class="btn btn-primary">Confirmer la commande</button>
                        <button type="submit" name="action" value="annuler_commande" class="btn btn-secondary">Annuler</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <form method="POST" id="commandeForm">
                <input type="hidden" name="action" value="preparer_commande">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['pharma_token']) ?>">
                
                <div class="medicaments-container">
                    <?php if (empty($medicaments)): ?>
                        <div class="no-medicaments">
                            <p><?= $_SESSION['pharma_info'] ?? 'Aucun médicament disponible pour le moment.' ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($medicaments as $medicament): ?>
                            <?php if ($medicament['categorie_nom'] !== $current_categorie): ?>
                                <?php $current_categorie = $medicament['categorie_nom']; ?>
                                <h2 class="categorie-title"><?= htmlspecialchars($current_categorie) ?></h2>
                            <?php endif; ?>
                            
                            <div class="medicament-card">
                              <div class="medicament-image-container">
                                    <img src="<?= htmlspecialchars($medicament['image_path']) ?>" 
                                        alt="<?= htmlspecialchars($medicament['nom']) ?>"
                                        class="<?= $medicament['image_missing'] ? 'missing-image' : '' ?>">
                                    <?php if ($medicament['image_missing']): ?>
                                        <span class="image-warning">Image non disponible</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="medicament-nom"><?= htmlspecialchars($medicament['nom']) ?></h3>
                                <p class="medicament-description"><?= htmlspecialchars($medicament['description']) ?></p>
                                <p class="medicament-prix">Prix: <?= number_format($medicament['prix'], 2) ?> €</p>
                                
                                <div class="medicament-footer">
                                    <span class="medicament-stock <?= 
                                        $medicament['stock'] < 5 ? 'stock-critical' : 
                                        ($medicament['stock'] < 10 ? 'stock-low' : '') 
                                    ?>">
                                        Stock: <?= $medicament['stock'] ?>
                                    </span>
                                    
                                    <select name="medicaments[<?= $medicament['id'] ?>]" class="quantite-select">
                                        <option value="0">0</option>
                                        <?php for ($i = 1; $i <= min(3, $medicament['stock']); $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($medicaments)): ?>
                    <button type="submit" class="btn btn-primary">Préparer la commande</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
        
        <!-- Historique des commandes -->
        <?php if (!empty($historique)): ?>
            <div class="historique-container">
                <h2 class="historique-title">Vos dernières commandes</h2>
                <?php foreach ($historique as $commande): ?>
                    <div class="commande-item">
                        <div>
                            <span class="commande-date">Commande #<?= htmlspecialchars($commande['id']) ?> - <?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></span>
                            <span><?= htmlspecialchars($commande['nb_medicaments']) ?> médicament(s) - <?= htmlspecialchars($commande['total_items']) ?> article(s)</span>
                        </div>
                        <span class="commande-statut <?= 
                            $commande['statut'] === 'prête' ? 'statut-prete' : 
                            ($commande['statut'] === 'annulée' ? 'statut-annulee' : 'statut-en-attente') 
                        ?>">
                            <?= htmlspecialchars(ucfirst($commande['statut'])) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Validation améliorée du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const commandeForm = document.getElementById('commandeForm');
            const confirmationForm = document.getElementById('confirmationForm');
            
            // Validation du formulaire de sélection
            if (commandeForm) {
                commandeForm.addEventListener('submit', function(e) {
                    const selects = commandeForm.querySelectorAll('.quantite-select');
                    let hasSelection = false;
                    let stockErrors = false;
                    
                    selects.forEach(select => {
                        const selectedQty = parseInt(select.value);
                        const maxQty = parseInt(select.lastElementChild.value);
                        
                        if (selectedQty > 0) {
                            hasSelection = true;
                            if (selectedQty > maxQty) {
                                select.style.borderColor = 'red';
                                stockErrors = true;
                            } else {
                                select.style.borderColor = '';
                            }
                        }
                    });
                    
                    if (!hasSelection) {
                        e.preventDefault();
                        alert('Veuillez sélectionner au moins un médicament.');
                    } else if (stockErrors) {
                        e.preventDefault();
                        alert('Certaines quantités sélectionnées dépassent le stock disponible.');
                    }
                });
            }
            
            // Protection contre la double soumission
            if (confirmationForm) {
                confirmationForm.addEventListener('submit', function() {
                    const submitBtn = confirmationForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Traitement en cours...';
                });
            }
            
            // Mise en évidence des sélections
            const quantitySelects = document.querySelectorAll('.quantite-select');
            quantitySelects.forEach(select => {
                select.addEventListener('change', function() {
                    if (parseInt(this.value) > 0) {
                        this.parentNode.parentNode.style.border = '2px solid var(--success-color)';
                        this.parentNode.parentNode.style.boxShadow = '0 0 0 2px rgba(46, 204, 113, 0.3)';
                    } else {
                        this.parentNode.parentNode.style.border = '';
                        this.parentNode.parentNode.style.boxShadow = '';
                    }
                });
            });
        });
    </script>
</body>
</html>