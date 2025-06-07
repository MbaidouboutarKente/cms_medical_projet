<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');


// Vérification du rôle médecin et expiration de session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin' ||
    (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600))) {
    session_unset();
    session_destroy();
    die(json_encode(["error" => "Session expirée, veuillez vous reconnecter."]));
}
$_SESSION['last_activity'] = time();

require_once "db.php";

try {
    $pdoMedical->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdoMedical->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(["error" => "Erreur serveur, réessayez plus tard."]));
}

$medecinID = intval(str_replace("MED_", "", $_SESSION['user_id']));

// Récupération des informations médecin
$stmt = $pdoMedical->prepare("
    SELECT id, nom, specialite, COALESCE(numero_professionnel, 'N/A') AS numero_professionnel 
    FROM utilisateurs WHERE id = ? AND role = 'medecin'
");
$stmt->execute([$medecinID]);
$medecin = $stmt->fetch();

if (!$medecin) {
    die(json_encode(["error" => "Médecin non trouvé."]));
}

// Récupération des certificats en attente
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Récupération des certificats en attente avec jointure via le matricule
$stmt = $pdoMedical->prepare("
    SELECT 
        c.id, 
        c.date_creation, 
        c.contenu, 
        u.nom AS patient_nom, 
        u.matricule AS patient_matricule,
        e.prenom AS patient_prenom,
        e.date_naissance,
        e.matricule AS etudiant_matricule,
        c.commentaire_medecin
    FROM certificats c
    JOIN utilisateurs u ON c.patient_id = u.id
    LEFT JOIN campus_db.etudiants e ON u.matricule = e.matricule
    WHERE c.statut = 'en attente'
    ORDER BY c.date_creation DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$certificats = $stmt->fetchAll();
// Traitement des dates de naissance

$date_naissance = $etudiant['date_naissance'] ?? 'Non spécifié';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Erreur de sécurité !");
        }

        $certificat_id = intval($_POST['certificat_id']);
        $commentaireMedecin = trim($_POST['commentaire_medecin'] ?? '');

        // Vérification que le certificat existe
        $stmt = $pdoMedical->prepare("SELECT id FROM certificats WHERE id = ? LIMIT 1");
        $stmt->execute([$certificat_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Certificat non trouvé");
        }

        // Mise à jour du certificat avec le commentaire
        $stmt = $pdoMedical->prepare("
            UPDATE certificats 
            SET statut = 'validé', 
                medecin_id = ?, 
                medecin_nom = ?, 
                commentaire_medecin = ?, 
                date_validation = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $medecinID, 
            $medecin['nom'], 
            $commentaireMedecin, 
            $certificat_id
        ]);

        echo json_encode(["success" => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}

// Comptage total des certificats
$queryCount = $pdoMedical->query("SELECT COUNT(*) FROM certificats WHERE statut = 'en attente'");
$totalCertificats = $queryCount->fetchColumn();
$totalPages = ceil($totalCertificats / $limit);


$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<?php
// Fonction pour calculer l'âge
function calculerAge($date_naissance) {
    $naissance = new DateTime($date_naissance);
    $aujourdhui = new DateTime();
    $difference = $aujourdhui->diff($naissance);
    return $difference->y;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Espace médecin pour la gestion des certificats médicaux">
    <title>Espace Médecin - Gestion des Certificats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-light: #3d566e;
            --secondary: #3498db;
            --secondary-light: #5dade2;
            --success: #27ae60;
            --success-light: #2ecc71;
            --danger: #e74c3c;
            --danger-light: #ec7063;
            --warning: #f39c12;
            --light: #ecf0f1;
            --lighter: #f8f9fa;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --gray-light: #bdc3c7;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: #f5f7fa;
            color: var(--dark);
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .main-content {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-top: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h1 {
            color: var(--primary);
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header-left p {
            color: var(--gray);
            font-size: 14px;
        }
        
        .user-card {
            background: var(--lighter);
            border-radius: var(--border-radius);
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }
        
        .user-info {
            line-height: 1.4;
        }
        
        .user-info strong {
            display: block;
            color: var(--primary);
        }
        
        .user-info span {
            font-size: 13px;
            color: var(--gray);
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: var(--secondary);
            color: white;
        }
        
        .badge-success {
            background: var(--success);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            background-color: #f8f9fa;
            border-left: 4px solid var(--secondary);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .message i {
            font-size: 20px;
        }
        
        .message.success {
            background-color: #e8f5e9;
            border-color: var(--success);
        }
        
        .message.error {
            background-color: #ffebee;
            border-color: var(--danger);
        }
        
        .message.warning {
            background-color: #fff8e1;
            border-color: var(--warning);
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2 i {
            color: var(--secondary);
        }
        
        .certificat-list {
            margin-top: 30px;
        }
        
        .certificat {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
            transition: var(--transition);
            border: 1px solid var(--light);
        }
        
        .certificat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .certificat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .certificat-title {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .certificat-patient {
            font-weight: 500;
            color: var(--primary-light);
        }
        
        .certificat-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            color: var(--gray);
            font-size: 14px;
        }
        
        .certificat-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .certificat-meta-item i {
            color: var(--secondary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-family: inherit;
            transition: var(--transition);
        }
        
        textarea.form-control {
            min-height: 200px;
            resize: vertical;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-light);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: flex;
            width: 100%;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
            background: var(--lighter);
            border-radius: var(--border-radius);
        }
        
        .empty-state i {
            font-size: 50px;
            color: var(--gray-light);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-top: 15px;
            color: var(--gray);
        }
        
        .empty-state .btn {
            margin-top: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: var(--primary);
            text-decoration: none;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .pagination a:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: var(--secondary);
            color: white;
            font-weight: bold;
        }
        
        .patient-info-card {
            background: var(--lighter);
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .patient-info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .patient-info-label {
            font-weight: 500;
            color: var(--primary);
            min-width: 120px;
        }
        
        .patient-info-value {
            color: var(--dark);
        }
        
        .motif-box {
            background: #f0f7ff;
            border-left: 4px solid var(--secondary);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
        }
        
        .motif-title {
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .user-card {
                flex-direction: column;
                text-align: center;
            }
            
            .certificat-header {
                flex-direction: column;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content animate-fade">
            <header>
                <div class="header-left">
                    <h1>Gestion des Certificats Médicaux</h1>
                    <p>Valider et signer les certificats en attente</p>
                </div>
                
                <div class="user-card">
                    <div class="user-avatar">
                        <?= strtoupper(substr($medecin['nom'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <strong>Dr. <?= htmlspecialchars($medecin['nom']) ?></strong>
                        <span>N° RPPS: <?= htmlspecialchars($medecin['numero_professionnel']) ?></span>
                        <span><?= htmlspecialchars($medecin['specialite'] ?? 'Médecine générale') ?></span>
                    </div>
                </div>
            </header>
            
            <?php if (!empty($message)): ?>
                <div class="message <?= htmlspecialchars($message['type']) ?> animate-fade">
                    <i class="fas <?= $message['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    <div><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                </div>
            <?php endif; ?>
            
            <section class="certificat-list">
                <h2><i class="fas fa-file-medical"></i> Certificats en attente</h2>
                
                <?php if (empty($certificats)): ?>
                    <div class="empty-state animate-fade">
                        <i class="fas fa-file-import"></i>
                        <h3>Aucun certificat en attente</h3>
                        <p>Tous les certificats ont été traités pour le moment.</p>
                    </div>
                <?php else: ?>
                    <p class="certificat-count"><?= $totalCertificats ?> certificat(s) en attente - Page <?= $page ?> sur <?= $totalPages ?></p>
                    
                    <?php foreach ($certificats as $cert): ?>
                        <div class="certificat animate-fade">
                            <div class="certificat-header">
                                <div>
                                    <h3 class="certificat-title">Certificat Médical</h3>
                                    <p class="certificat-patient">
                                        <i class="fas fa-user"></i> 
                                        <?= htmlspecialchars($cert['patient_nom']) ?> 
                                    </p>
                                </div>
                                <span class="badge">En attente</span>
                            </div>
                            
                            <div class="certificat-meta">
                                <span class="certificat-meta-item">
                                    <i class="far fa-calendar-alt"></i> 
                                    Demandé le <?= date('d/m/Y à H:i', strtotime($cert['date_creation'])) ?>
                                </span>
                            </div>
                            
                            <div class="patient-info-card">
                                <div class="patient-info-row">
                                    <span class="patient-info-label">Patient :</span>
                                    <span class="patient-info-value">
                                        <?= htmlspecialchars($cert['patient_nom']) ?> <?= htmlspecialchars($cert['patient_prenom']) ?>
                                    </span>
                                </div>
                                <div class="patient-info-row">
                                    <span class="patient-info-label">Date de naissance :</span>
                                    <span class="patient-info-value">
                                        <?= date('d/m/Y', strtotime($cert['date_naissance'])) ?>
                                        (<?= calculerAge($cert['date_naissance']) ?> ans)
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (!empty($cert['motif'])): ?>
                                <div class="motif-box">
                                    <div class="motif-title">
                                        <i class="fas fa-stethoscope"></i> Motif de consultation
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($cert['motif'])) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="certificat-form">
                                <input type="hidden" name="certificat_id" value="<?= htmlspecialchars($cert['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                
                                <div class="form-group">
                                    <label for="contenu_<?= $cert['id'] ?>" class="form-label">
                                        <i class="fas fa-edit"></i> Rédiger le certificat médical :
                                    </label>
                                    <textarea 
                                        id="contenu_<?= $cert['id'] ?>" 
                                        name="contenu" 
                                        class="form-control" 
                                        required
                                        placeholder="Rédigez ici le contenu du certificat médical..."
                                    ><?= htmlspecialchars($cert['contenu'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="commentaire_<?= $cert['id'] ?>">📝 Commentaire du médecin :</label>
                                    <textarea 
                                        id="commentaire_<?= $cert['id'] ?>" 
                                        name="commentaire_medecin" 
                                        class="form-control" 
                                        placeholder="Ajoutez un commentaire..."
                                    ><?= htmlspecialchars($cert['commentaire_medecin'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" name="valider_certificat" class="btn btn-primary btn-block">
                                    <i class="fas fa-check-circle"></i> Valider et signer le certificat
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
    
    <script>
        // Amélioration de l'expérience utilisateur
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion de la soumission des formulaires
            const forms = document.querySelectorAll('.certificat-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const textarea = this.querySelector('textarea[name="contenu"]');
                    
                    if (textarea.value.trim().length < 20) {
                        alert('Le certificat doit comporter au moins 20 caractères.');
                        textarea.focus();
                        return;
                    }
                    
                    if (!confirm('Confirmez-vous la validation de ce certificat ? Cette action est définitive.')) {
                        return;
                    }
                    
                    // Envoi AJAX
                    fetch(this.action || window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Certificat validé avec succès !');
                            window.location.reload();
                        } else {
                            throw new Error(data.error || 'Erreur inconnue');
                        }
                    })
                    .catch(error => {
                        alert('Erreur: ' + error.message);
                    });
                });
            });
            
            // ... reste du code JavaScript ...
        });
    </script>
</body>
</html>

