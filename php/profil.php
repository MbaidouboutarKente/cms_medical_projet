<?php
session_start();
include "db.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// session_start();

// Vérification de session
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== "etudiant") {
    header("Location: auth.php");
    exit;
}

// Récupération des données
$userID = intval(str_replace("MED_", "", $_SESSION['user_id']));

$queryUser = $pdoCampus->prepare("
    SELECT u.id, u.nom,  u.email, u.image, u.telephone, 
           e.filiere, e.matricule, e.niveau, e.date_inscription, 
           e.statut_etudiant, e.statut
    FROM campus_db.etudiants e
    JOIN medical_db.utilisateurs u ON e.matricule = u.matricule
    WHERE u.id = ?
");
$queryUser->execute([$userID]);
$userData = $queryUser->fetch();

if (!$userData) {
    echo "Utilisateur introuvable.";
    exit;
}

// Détermination année scolaire
$moisActuel = date("m");
$anneeScolaire = ($moisActuel >= 9) ? date("Y")."-".(date("Y")+1) : (date("Y")-1)."-".date("Y");

// Récupération des notes (exemple)
// $queryNotes = $pdoCampus->prepare("SELECT cours, note FROM notes WHERE matricule = ? ORDER BY date_examen DESC LIMIT 5");
// $queryNotes->execute([$userData['matricule']]);
// $dernieresNotes = $queryNotes->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --warning: #f72585;
            --muted: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7ff;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .profile-header {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 30px;
            border-radius: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.2);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        }
        
        .profile-info {
            flex: 1;
            min-width: 300px;
            position: relative;
            z-index: 1;
        }
        
        .profile-info h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .profile-info .title {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .meta-item {
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .profile-avatar {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
        }
        
        /* Sections */
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.4rem;
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        /* Grid Layout */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* List Styles */
        .info-list {
            list-style: none;
        }
        
        .info-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
        }
        
        .info-list li:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--muted);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        /* Progress Bars */
        .progress-container {
            margin-top: 15px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            height: 8px;
            background: #f0f2f5;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), var(--primary));
            border-radius: 4px;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(67, 97, 238, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-meta {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="profile-header">
            <div class="profile-info">
                <h1><?= htmlspecialchars($userData['nom'])  ?></h1>
                <p class="title">Étudiant en <?= htmlspecialchars($userData['filiere']) ?></p>
                
                <div class="profile-meta">
                    <span class="meta-item">
                        <i class="fas fa-id-card"></i> <?= htmlspecialchars($userData['matricule']) ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($anneeScolaire) ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-level-up-alt"></i> <?= htmlspecialchars($userData['niveau']) ?>
                    </span>
                </div>
            </div>
            
            <img src="<?= !empty($userData['image']) ? '../img/uploads/' . htmlspecialchars($userData['image']) : '../img/profile.png' ?>" 
                 alt="Photo de profil" 
                 class="profile-avatar"
                 id="profileImage">
        </header>
        
        <!-- Main Content -->
        <div class="grid-container">
            <!-- Personal Info Section -->
            <section class="section">
                <h2 class="section-title"><i class="fas fa-user-circle"></i> Informations Personnelles</h2>
                
                <ul class="info-list">
                    <li>
                        <span class="info-label">Nom complet</span>
                        <span><?= htmlspecialchars( $userData['nom']) ?></span>
                    </li>
                    <li>
                        <span class="info-label">Email</span>
                        <span><?= htmlspecialchars($userData['email']) ?></span>
                    </li>
                    <li>
                        <span class="info-label">Téléphone</span>
                        <span><?= htmlspecialchars($userData['telephone'] ?? 'Non renseigné') ?></span>
                    </li>
                    <li>
                        <span class="info-label">Date d'inscription</span>
                        <span><?= date('d/m/Y', strtotime($userData['date_inscription'])) ?></span>
                    </li>
                    <li>
                        <span class="info-label">Statut</span>
                        <span class="badge badge-success"><?= htmlspecialchars($userData['statut_etudiant']) ?></span>
                    </li>
                </ul>
                
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Progression académique</span>
                        <span>75%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 75%"></div>
                    </div>
                </div>
            </section>
            
            <!-- Academic Info Section -->
            <section class="section">
                <h2 class="section-title"><i class="fas fa-graduation-cap"></i> Informations Académiques</h2>
                
                <ul class="info-list">
                    <li>
                        <span class="info-label">Filière</span>
                        <span><?= htmlspecialchars($userData['filiere']) ?></span>
                    </li>
                    <li>
                        <span class="info-label">Niveau</span>
                        <span><?= htmlspecialchars($userData['niveau']) ?></span>
                    </li>
                    <li>
                        <span class="info-label">Matricule</span>
                        <span><?= htmlspecialchars($userData['matricule']) ?></span>
                    </li>
                    <li>
                        <span class="info-label">Année scolaire</span>
                        <span><?= htmlspecialchars($anneeScolaire) ?></span>
                    </li>
                </ul>
                
                <!-- <button class="btn btn-outline" style="margin-top: 20px;">
                    <i class="fas fa-download"></i> Télécharger mon relevé
                </button> -->
            </section>
        </div>
        
        <!-- Recent Grades Section -->
        <!-- <section class="section">
            <h2 class="section-title"><i class="fas fa-chart-line"></i> Dernières Notes</h2>
            
            <?php if (!empty($dernieresNotes)): ?>
                <div class="grid-container">
                    <?php foreach ($dernieresNotes as $note): ?>
                        <div class="card">
                            <h3 class="card-title"><?= htmlspecialchars($note['cours']) ?></h3>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Note</span>
                                    <span><?= htmlspecialchars($note['note']) ?>/20</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($note['note']/20)*100 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucune note disponible pour le moment.</p>
            <?php endif; ?>
        </section>
         -->
        <!-- Actions Section -->
        <section class="section">
            <h2 class="section-title"><i class="fas fa-cog"></i> Actions</h2>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <!-- <button class="btn btn-primary">
                    <i class="fas fa-edit"></i> Modifier mon profil
                </button> -->
                
                <form action="upload.php" method="post" enctype="multipart/form-data" style="display: none;">
                    <input type="file" name="image" id="imageInput" accept="image/*">
                    <button type="submit" id="submitImage"></button>
                </form>
                
                <button class="btn btn-primary" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-camera"></i> Changer de photo
                </button>
                
                <a href="paiement.php" class="btn btn-outline">
                    <i class="fas fa-credit-card"></i> Paiements
                </a>
                
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </section>
    </div>
    
    <script>
        // Image upload handling
        document.getElementById('imageInput').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
                document.getElementById('submitImage').click();
            }
        });
        
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>