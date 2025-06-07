<?php
session_start();
require_once "db.php";

// Activation du débogage complet
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification du rôle infirmier
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

// Extraction sécurisée de l'ID infirmier (supposant un préfixe "MED_")
$infirmierID = intval(str_replace("MED_", "", $_SESSION['user_id']));

// Récupération des informations de l'infirmier
try {
    $queryInfirmier = $pdoMedical->prepare("SELECT id, nom, email, image FROM utilisateurs WHERE id = ?");
    $queryInfirmier->execute([$infirmierID]);
    $infirmier = $queryInfirmier->fetch();
    
    if (!$infirmier) {
        throw new Exception("Infirmier non trouvé");
    }
} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

// Fonction pour récupérer les rendez-vous selon le statut
function getRendezVous($pdoMedical, $infirmierID, $statut) {
    $order = ($statut === 'annule') ? 'DESC' : 'ASC';
    $stmt = $pdoMedical->prepare("
        SELECT r.*, u.nom AS patient_nom, u.image 
        FROM rendez_vous r
        JOIN utilisateurs u ON r.patient_id = u.id
        WHERE r.professionnel_sante_id = ? AND r.statut = ?
        ORDER BY r.date_heure $order
    ");
    $stmt->execute([$infirmierID, $statut]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer quelques statistiques
function getStats($pdo, $infirmierID) {
    $stats = [];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE professionnel_sante_id = ?");
    $stmt->execute([$infirmierID]);
    $stats['total'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE professionnel_sante_id = ? AND statut = 'confirmé'");
    $stmt->execute([$infirmierID]);
    $stats['confirmes'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE professionnel_sante_id = ? AND statut = 'en attente'");
    $stmt->execute([$infirmierID]);
    $stats['en_attente'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT r.date_heure, u.nom 
        FROM rendez_vous r
        JOIN utilisateurs u ON r.patient_id = u.id
        WHERE r.professionnel_sante_id = ? AND r.statut = 'confirmé' AND r.date_heure >= NOW()
        ORDER BY r.date_heure ASC
        LIMIT 1
    ");
    $stmt->execute([$infirmierID]);
    $stats['prochain_rdv'] = $stmt->fetch(PDO::FETCH_ASSOC);

    return $stats;
}

// Récupération des rendez-vous et statistiques
try {
    $rdvsEnAttente = getRendezVous($pdoMedical, $infirmierID, 'en attente');
    $rdvsConfirmes = getRendezVous($pdoMedical, $infirmierID, 'confirmé');
    $rdvsAnnules   = getRendezVous($pdoMedical, $infirmierID, 'annule');
    $stats         = getStats($pdoMedical, $infirmierID);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Traitement des actions POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Gestion des mises à jour de statut (confirmer/annuler)
    if (isset($_POST['action']) && in_array($_POST['action'], ['confirmer', 'annuler'])) {
        $rdvID = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
        if ($rdvID) {
            try {
                $nouveauStatut = ($_POST['action'] === 'confirmer') ? 'confirmé' : 'annule';
                $stmt = $pdoMedical->prepare("
                    UPDATE rendez_vous 
                    SET statut = ?, updated_at = NOW() 
                    WHERE id = ? AND professionnel_sante_id = ?
                ");
                $stmt->execute([$nouveauStatut, $rdvID, $infirmierID]);
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => "Rendez-vous $nouveauStatut avec succès"
                ];
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => "Erreur lors de la mise à jour : " . $e->getMessage()
                ];
            }
        }
    }
    
    // Gestion de l'enregistrement/modification du diagnostic (notes)
    if (isset($_POST['save_note'])) {
        $rdvID = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
        // Utilisation de FILTER_SANITIZE_FULL_SPECIAL_CHARS pour éviter les warnings de dépréciation
        $diagnostic = filter_input(INPUT_POST, 'diagnostic', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
        if ($rdvID && $infirmierID && $diagnostic !== false) {
            try {
                $stmt = $pdoMedical->prepare("
                    UPDATE rendez_vous 
                    SET diagnostic_infirmier = ?, updated_at = NOW() 
                    WHERE id = ? AND professionnel_sante_id = ?
                ");
                $stmt->execute([$diagnostic, $rdvID, $infirmierID]);
    
                error_log("Mise à jour diagnostic: RDV ID $rdvID, Infirmier ID $infirmierID, Lignes affectées: " . $stmt->rowCount());
    
                if ($stmt->rowCount() > 0) {
                    $_SESSION['message'] = [
                        'type' => 'success',
                        'text' => 'Diagnostic enregistré avec succès'
                    ];
                } else {
                    $_SESSION['message'] = [
                        'type' => 'warning',
                        'text' => 'Aucune modification effectuée (vérifiez que vous êtes autorisé à modifier ce rendez-vous)'
                    ];
                }
    
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                error_log("Erreur SQL: " . $e->getMessage());
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Erreur technique lors de l’enregistrement'
                ];
            }
        } else {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Données invalides fournies'
            ];
        }
    }
}

// Quelques fonctions d'affichage utiles
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function getInitials($name) {
    $initials = '';
    $words = explode(' ', $name);
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 3);
}

function getAvatar($photo, $name, $size = 'md') {
    $sizes = ['sm' => 'w-8 h-8', 'md' => 'w-12 h-12', 'lg' => 'w-16 h-16'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    
    if (!empty($photo)) {
        return '<img src="../img/uploads/'.htmlspecialchars($photo).'" alt="'.htmlspecialchars($name).'" class="rounded-full object-cover '.$sizeClass.'">';
    } else {
        return '<div class="rounded-full bg-blue-500 text-white flex items-center justify-center '.$sizeClass.'" title="'.htmlspecialchars($name).'">'.getInitials($name).'</div>';
    }
}
?>


<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Infirmier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --light-gray: #e5e7eb;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            color: var(--dark);
            min-height: 100vh;
        }
        
        .card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .tab-btn.active {
            position: relative;
            color: var(--primary);
        }
        
        .tab-btn.active:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
        }
        
        .animate-bounce {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .note-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .wave {
            animation: wave 2s linear infinite;
        }
        
        @keyframes wave {
            0% { transform: rotate(0deg); }
            10% { transform: rotate(14deg); }
            20% { transform: rotate(-8deg); }
            30% { transform: rotate(14deg); }
            40% { transform: rotate(-4deg); }
            50% { transform: rotate(10deg); }
            60% { transform: rotate(0deg); }
            100% { transform: rotate(0deg); }
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation latérale -->
        <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
            <div class="flex-1 flex flex-col min-h-0 bg-white border-r border-gray-200">
                <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                    <div class="flex items-center justify-center px-4">
                        <div class="text-2xl font-bold text-indigo-600">
                            <i class="fas fa-heartbeat mr-2"></i>Infirmier
                        </div>
                    </div>
                    <nav class="mt-8 flex-1 px-4 space-y-2">
                        <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-indigo-50 text-indigo-700">
                            <i class="fas fa-calendar-alt mr-3 text-indigo-600"></i>
                            Rendez-vous
                        </a>
                        <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i class="fas fa-users mr-3 text-gray-400 group-hover:text-gray-500"></i>
                            Patients
                        </a>
                        <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i class="fas fa-file-medical mr-3 text-gray-400 group-hover:text-gray-500"></i>
                            Dossiers
                        </a>
                        <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i class="fas fa-chart-line mr-3 text-gray-400 group-hover:text-gray-500"></i>
                            Statistiques
                        </a>
                        <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <i class="fas fa-cog mr-3 text-gray-400 group-hover:text-gray-500"></i>
                            Paramètres
                        </a>
                    </nav>
                </div>
                <div class="p-4 border-t border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <?= getAvatar($infirmier['image'], $infirmier['nom'], 'md') ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($infirmier['nom']) ?></p>
                            <p class="text-xs font-medium text-gray-500">Infirmier</p>
                        </div>
                    </div>
                    <a href="logout.php" class="mt-4 w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="md:pl-64 flex flex-col">
            <!-- Barre de navigation mobile -->
            <div class="sticky top-0 z-10 md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 bg-white">
                <button type="button" class="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <main class="flex-1 p-6">
                <!-- En-tête -->
                <div class="md:flex md:items-center md:justify-between mb-8">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            Bonjour, <?= htmlspecialchars(explode(' ', $infirmier['nom'])[0]) ?> 
                            <span class="wave inline-block">👋</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Voici votre tableau de bord avec les rendez-vous à venir et les statistiques.
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4">
                        <button type="button" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                            <i class="fas fa-plus mr-2"></i> Nouveau RDV
                        </button>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                    <!-- Carte RDV total -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <i class="fas fa-calendar text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total RDV</dt>
                                        <dd>
                                            <div class="text-lg font-medium text-gray-900"><?= $stats['total'] ?></div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-indigo-700 hover:text-indigo-900">
                                    Voir tout
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carte RDV confirmés -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Confirmés</dt>
                                        <dd>
                                            <div class="text-lg font-medium text-gray-900"><?= $stats['confirmes'] ?></div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#confirmed" class="font-medium text-green-700 hover:text-green-900" onclick="openTab('confirmed')">
                                    Voir les confirmés
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carte RDV en attente -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">En attente</dt>
                                        <dd>
                                            <div class="text-lg font-medium text-gray-900"><?= $stats['en_attente'] ?></div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#pending" class="font-medium text-yellow-700 hover:text-yellow-900" onclick="openTab('pending')">
                                    Voir en attente
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carte Prochain RDV -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-calendar-day text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Prochain RDV</dt>
                                        <dd>
                                            <?php if ($stats['prochain_rdv']): ?>
                                                <div class="text-lg font-medium text-gray-900"><?= formatDate($stats['prochain_rdv']['date_heure']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($stats['prochain_rdv']['nom']) ?></div>
                                            <?php else: ?>
                                                <div class="text-lg font-medium text-gray-900">Aucun</div>
                                            <?php endif; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">
                                    Voir agenda
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Graphique -->
                <div class="mb-8">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Activité récente</h2>
                        <div class="h-64">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="rounded-md bg-<?= $_SESSION['message']['type'] === 'error' ? 'red' : 'green' ?>-50 p-4 mb-6 fade-in">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-<?= $_SESSION['message']['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?> text-<?= $_SESSION['message']['type'] === 'error' ? 'red' : 'green' ?>-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-<?= $_SESSION['message']['type'] === 'error' ? 'red' : 'green' ?>-800">
                                    <?= $_SESSION['message']['text'] ?>
                                </p>
                            </div>
                            <div class="ml-auto pl-3">
                                <div class="-mx-1.5 -my-1.5">
                                    <button type="button" class="inline-flex rounded-md p-1.5 text-<?= $_SESSION['message']['type'] === 'error' ? 'red' : 'green' ?>-500 hover:bg-<?= $_SESSION['message']['type'] === 'error' ? 'red' : 'green' ?>-100 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <!-- Onglets -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button class="tab-btn active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="pending">
                            <i class="fas fa-clock mr-2"></i>
                            En attente
                            <?php if (!empty($rdvsEnAttente)): ?>
                                <span class="badge badge-pending ml-2"><?= count($rdvsEnAttente) ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="confirmed">
                            <i class="fas fa-check-circle mr-2"></i>
                            Confirmés
                            <?php if (!empty($rdvsConfirmes)): ?>
                                <span class="badge badge-confirmed ml-2"><?= count($rdvsConfirmes) ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="cancelled">
                            <i class="fas fa-times-circle mr-2"></i>
                            Annulés
                            <?php if (!empty($rdvsAnnules)): ?>
                                <span class="badge badge-cancelled ml-2"><?= count($rdvsAnnules) ?></span>
                            <?php endif; ?>
                        </button>
                    </nav>
                </div>
                
                <!-- Section Rendez-vous en attente -->
                <div id="pending" class="tab-content active fade-in">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                <i class="fas fa-clock text-yellow-500 mr-2"></i>
                                Rendez-vous en attente de confirmation
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Liste des rendez-vous nécessitant votre action.
                            </p>
                        </div>
                        <?php if (empty($rdvsEnAttente)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-calendar-check text-gray-300 text-5xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900">Aucun rendez-vous en attente</h3>
                                <p class="mt-1 text-sm text-gray-500">Tous les rendez-vous sont traités.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($rdvsEnAttente as $rdv): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <?= getAvatar($rdv['image'], $rdv['patient_nom'], 'sm') ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rdv['patient_nom']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= formatDate($rdv['date_heure']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <?= htmlspecialchars(ucfirst($rdv['motif'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex space-x-2">
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                                        <button type="submit" name="action" value="confirmer" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                                                            <i class="fas fa-check mr-1"></i> Confirmer
                                                        </button>
                                                    </form>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                                        <button type="submit" name="action" value="annuler" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none">
                                                            <i class="fas fa-times mr-1"></i> Annuler
                                                        </button>
                                                    </form>
                                                    <button onclick="openNoteModal(<?= $rdv['id'] ?>, '<?= htmlspecialchars($rdv['note'] ?? '', ENT_QUOTES) ?>')" class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                                        <i class="fas fa-edit mr-1"></i> Note
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Section Rendez-vous confirmés -->
                <div id="confirmed" class="tab-content fade-in">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <!-- En-tête du tableau -->
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Rendez-vous confirmés
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Liste des rendez-vous à venir.
                            </p>
                        </div>

                        <!-- Si aucun rendez-vous confirmé n'est trouvé -->
                        <?php if (empty($rdvsConfirmes)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-calendar-plus text-gray-300 text-5xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900">Aucun rendez-vous confirmé</h3>
                                <p class="mt-1 text-sm text-gray-500">Les rendez-vous confirmés apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
                            <!-- Tableau récapitulatif -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($rdvsConfirmes as $rdv): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <!-- Colonne patient -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <?= getAvatar($rdv['image'], $rdv['patient_nom'], 'sm') ?>
                                                    </div>
                                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rdv['patient_nom']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <!-- Colonne date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= formatDate($rdv['date_heure']) ?></div>
                            </td>
                            <!-- Colonne type de rendez-vous -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars(ucfirst($rdv['motif'])) ?>
                                </span>
                            </td>
                            <!-- Colonne note -->
                            <td class="px-6 py-4">
                                <?php if (!empty($rdv['note'])): ?>
                                    <!-- Si la note existe, on affiche le contenu et un bouton pour modifier -->
                                    <div class="flex items-center">
                                        <div class="text-sm text-gray-500 note-preview max-w-xs">
                                            <?= htmlspecialchars($rdv['note']) ?>
                                        </div>
                                        <button onclick="openNoteModal(<?= $rdv['id'] ?>, '<?= htmlspecialchars($rdv['diagnostic_infirmier '], ENT_QUOTES) ?>')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <!-- Sinon, on affiche un bouton pour ajouter une note -->
                                    <button onclick="openNoteModal(<?= $rdv['id'] ?>, '')" class="text-sm text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-plus mr-1"></i> Ajouter note
                                    </button>
                                <?php endif; ?>
                            </td>
                            <!-- Colonne statut -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i> Confirmé
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

                
                <!-- Section Rendez-vous annulés -->
                <div id="cancelled" class="tab-content fade-in">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                <i class="fas fa-times-circle text-red-500 mr-2"></i>
                                Rendez-vous annulés
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Historique des rendez-vous annulés.
                            </p>
                        </div>
                        <?php if (empty($rdvsAnnules)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-calendar-times text-gray-300 text-5xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900">Aucun rendez-vous annulé</h3>
                                <p class="mt-1 text-sm text-gray-500">Les rendez-vous annulés apparaîtront ici.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date prévue</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($rdvsAnnules as $rdv): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <?= getAvatar($rdv['image'], $rdv['patient_nom'], 'sm') ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($rdv['patient_nom']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= formatDate($rdv['date_heure']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <?= htmlspecialchars(ucfirst($rdv['motif'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if (!empty($rdv['note'])): ?>
                                                    <div class="flex items-center">
                                                        <div class="text-sm text-gray-500 note-preview max-w-xs"><?= htmlspecialchars($rdv['note']) ?></div>
                                                        <button onclick="openNoteModal(<?= $rdv['id'] ?>, '<?= htmlspecialchars($rdv['note'], ENT_QUOTES) ?>')" class="ml-2 text-indigo-600 hover:text-indigo-900">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <button onclick="openNoteModal(<?= $rdv['id'] ?>, '')" class="text-sm text-indigo-600 hover:text-indigo-900">
                                                        <i class="fas fa-plus mr-1"></i> Ajouter note
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    <i class="fas fa-times mr-1"></i> Annulé
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal pour les notes -->
    <div id="noteModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-edit text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Notes du rendez-vous</h3>
                            <div class="mt-2">
                                <form method="post" id="noteForm">  
                                    <input type="hidden" name="rdv_id" id="modalRdvId">
                                    <input type="hidden" name="save_note" value="1">
                                    <textarea name="diagnostic" id="modalNote" rows="6" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Ajoutez votre diagnostic..."></textarea>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="document.getElementById('noteForm').submit()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Enregistrer
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des onglets avec localStorage
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer l'onglet actif depuis le stockage local
            const activeTab = localStorage.getItem('activeTab') || 'pending';
            openTab(activeTab);
            
            // Ajouter les écouteurs d'événements aux boutons d'onglet
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    openTab(tabId);
                    // Animation pour le bouton cliqué
                    this.classList.add('animate-bounce');
                    setTimeout(() => this.classList.remove('animate-bounce'), 1000);
                });
            });
            
            // Initialiser le graphique
            initChart();
        });

        function openTab(tabId) {
            // Masquer tous les contenus d'onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Désactiver tous les boutons d'onglets
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('border-indigo-500', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            
            // Activer l'onglet sélectionné
            document.getElementById(tabId).classList.add('active');
            const activeBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            activeBtn.classList.add('active', 'border-indigo-500', 'text-indigo-600');
            activeBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            
            // Sauvegarder l'onglet actif dans le stockage local
            localStorage.setItem('activeTab', tabId);
        }
        
        // Gestion de la modal pour les notes
        function openNoteModal(rdvId, note) {
            document.getElementById('modalRdvId').value = rdvId;
            document.getElementById('modalNote').value = note;
            document.getElementById('noteModal').classList.remove('hidden');
            // Focus sur le textarea
            setTimeout(() => document.getElementById('modalNote').focus(), 100);
        }
        
        function closeModal() {
            document.getElementById('noteModal').classList.add('hidden');
        }
        
        // Initialisation du graphique
        function initChart() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                    datasets: [
                        {
                            label: 'Rendez-vous',
                            data: [12, 19, 8, 15, 10, 5, 2],
                            backgroundColor: '#6366f1',
                            borderRadius: 4
                        },
                        {
                            label: 'Confirmés',
                            data: [10, 15, 6, 12, 8, 3, 1],
                            backgroundColor: '#10b981',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            }
                        }
                    }
                }
            });
        }
        
        // Fermer la modal avec ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>