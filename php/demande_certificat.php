<?php
session_start();
require_once "db.php";

// Activation du debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Vérification de l'authentification et du rôle de l'utilisateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: auth.php");
    exit;
}

// Génération du token CSRF sécurisé
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupération de l'ID utilisateur en supprimant "MES_"
$patient_id = intval(str_replace("MED_", "", $_SESSION['user_id']));

// Vérification du patient
$stmt = $pdoMedical->prepare("
        SELECT u.id, u.nom, e.prenom, u.email, e.matricule, e.date_naissance, e.filiere 
        FROM utilisateurs u
        LEFT JOIN campus_db.etudiants e ON u.matricule = e.matricule
        WHERE u.id = ?
        ");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    throw new Exception("Utilisateur non trouvé.");
}

// Récupération des médecins disponibles
$stmt = $pdoMedical->prepare("SELECT id, nom, specialite FROM utilisateurs WHERE role = 'medecin'");
$stmt->execute();
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Récupération des certificats du patient
$stmt = $pdoMedical->prepare("
 SELECT c.id, c.date_creation, c.contenu, u.nom AS patient_nom, 
           c.commentaire_medecin, c.statut, c.type_certificat, date_validation, c.medecin_nom, u.numero_professionnel, u.specialite
    FROM certificats c
    JOIN utilisateurs u ON c.medecin_id = u.id
    WHERE c.patient_id = ?
    ORDER BY c.date_creation DESC
");

$stmt->execute([$patient_id]); // Maintenant l'exécution correspond à la requête
$certificats = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Erreur de sécurité : token invalide.");
        }

        // Filtrage et validation des entrées
        $contenu = trim(filter_var($_POST['contenu'], FILTER_SANITIZE_STRING));
        $medecin_id = isset($_POST['medecin_id']) ? intval($_POST['medecin_id']) : null;
        $type_certificat = isset($_POST['type_certificat']) ? trim(filter_var($_POST['type_certificat'], FILTER_SANITIZE_STRING)) : 'standard';

        if (empty($contenu) || empty($medecin_id)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }

        // Vérification du médecin
        $stmt = $pdoMedical->prepare("SELECT id, nom FROM utilisateurs WHERE id = ? AND role = 'medecin'");
        $stmt->execute([$medecin_id]);
        $medecin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medecin) {
            throw new Exception("Le médecin sélectionné est invalide.");
        }

        // Insertion de la demande
        $stmt = $pdoMedical->prepare("
            INSERT INTO certificats (patient_id, patient_nom, medecin_id, medecin_nom, type_certificat, contenu, statut, date_creation) 
            VALUES (?, ?, ?, ?, ?, ?, 'en attente', NOW())
        ");
        
        $stmt->execute([$patient['id'], $patient['nom'], $medecin['id'], $medecin['nom'], $type_certificat, $contenu]);

        $_SESSION['message'] = "✅ Votre demande a été enregistrée.";
        header("Location: demande_certificat.php");
        exit;

    } catch (Exception $e) {
        $message = "❌ Erreur : " . htmlspecialchars($e->getMessage());
    }
}
// Gestion sécurisée des messages flash
if (isset($_SESSION['flash_message'])) {
    $flash_message = [
        'type' => htmlspecialchars($_SESSION['flash_message']['type'] ?? '', ENT_QUOTES, 'UTF-8'),
        'message' => htmlspecialchars($_SESSION['flash_message']['message'] ?? '', ENT_QUOTES, 'UTF-8')
    ];
    unset($_SESSION['flash_message']);
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Certificat Médical</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --secondary-dark: #059669;
            --danger: #ef4444;
            --danger-light: #f87171;
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --info: #06b6d4;
            --light: #f8fafc;
            --lighter: #f1f5f9;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .main-content {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            padding: 30px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        
        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-light);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left p {
            color: var(--gray);
            font-size: 15px;
        }
        
        .user-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--gray-light);
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-info strong {
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-info span {
            font-size: 13px;
            color: var(--gray);
            margin-top: 2px;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius-sm);
            background-color: var(--lighter);
            border-left: 4px solid var(--primary);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(10px);
        }
        
        .message.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .message i {
            font-size: 20px;
            margin-top: 2px;
        }
        
        .message.success {
            background-color: #ecfdf5;
            border-color: var(--secondary);
            color: #065f46;
        }
        
        .message.error {
            background-color: #fef2f2;
            border-color: var(--danger);
            color: #991b1b;
        }
        
        .message.warning {
            background-color: #fffbeb;
            border-color: var(--warning);
            color: #92400e;
        }
        
        .message.info {
            background-color: #ecfeff;
            border-color: var(--info);
            color: #155e75;
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            padding-bottom: 10px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 3px;
        }
        
        h2 i {
            color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            transition: var(--transition);
            font-size: 15px;
            background-color: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
            line-height: 1.6;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 1em;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            text-decoration: none;
        }
        
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-success {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover:not(:disabled) {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray-light);
            color: var(--dark);
        }
        
        .btn-outline:hover {
            background-color: var(--lighter);
            border-color: var(--gray);
        }
        
        .btn-block {
            display: flex;
            width: 100%;
        }
        
        .btn-icon {
            padding: 10px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            justify-content: center;
        }
        
        .certificat-list {
            margin-top: 40px;
        }
        
        .certificat-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
            transition: var(--transition);
            border: 1px solid var(--gray-light);
            position: relative;
            overflow: hidden;
        }
        
        .certificat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .certificat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--gray-light);
        }
        
        .certificat-card.validé::before {
            background-color: var(--secondary);
        }
        
        .certificat-card.en-attente::before {
            background-color: var(--warning);
        }
        
        .certificat-card.refusé::before {
            background-color: var(--danger);
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
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: 600;
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
            color: var(--primary);
            font-size: 15px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            background: var(--gray-light);
            color: var(--dark);
        }
        
        .badge i {
            font-size: 12px;
        }
        
        .badge-success {
            background: var(--secondary);
            color: white;
        }
        
        .badge-warning {
            background: var(--warning);
            color: white;
        }
        
        .badge-danger {
            background: var(--danger);
            color: white;
        }
        
        .badge-info {
            background: var(--info);
            color: white;
        }
        
        .certificat-content {
            margin-top: 20px;
            padding: 20px;
            background: var(--lighter);
            border-radius: var(--border-radius-sm);
            border-left: 3px solid var(--primary);
        }
        
        .certificat-content h4 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .certificat-content p {
            color: var(--dark);
            line-height: 1.7;
        }
        
        .certificat-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 20px;
        }
        
        .empty-state i {
            font-size: 50px;
            color: var(--gray-light);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .empty-state p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 800px;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .close {
            position: absolute;
            right: 25px;
            top: 25px;
            color: var(--gray);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .close:hover {
            color: var(--danger);
            background-color: var(--lighter);
        }
        
        .certificat-print {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            font-family: 'Times New Roman', Times, serif;
        }
        
        .certificat-print-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }
        
        .certificat-print-header h2 {
            font-size: 24px;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: bold;
            padding: 0;
        }
        
        .certificat-print-header h2::after {
            display: none;
        }
        
        .certificat-print-header p {
            color: var(--gray);
            font-style: italic;
        }
        
        .certificat-print-body {
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .certificat-print-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid var(--gray-light);
        }
        
        .signature {
            text-align: center;
            margin-top: 50px;
        }
        
        .signature-line {
            width: 250px;
            border-top: 1px solid var(--dark);
            margin: 20px auto 0;
            padding-top: 5px;
        }
        
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: var(--shadow-lg);
            cursor: pointer;
            transition: var(--transition);
            z-index: 99;
            border: none;
        }
        
        .floating-action-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px) scale(1.05);
        }
        
        .tooltip {
            position: relative;
        }
        
        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--dark);
            color: white;
            padding: 5px 10px;
            border-radius: var(--border-radius-sm);
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            margin-bottom: 10px;
        }
        
        .tooltip:hover::after {
            opacity: 1;
            visibility: visible;
            margin-bottom: 5px;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .certificat-print, .certificat-print * {
                visibility: visible;
            }
            .certificat-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none;
                font-size: 14pt;
            }
            .no-print {
                display: none !important;
            }
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
                width: 100%;
            }
            
            .certificat-header {
                flex-direction: column;
            }
            
            .modal-content {
                width: 95%;
                margin: 20px auto;
                padding: 20px;
            }
            
            .certificat-print {
                padding: 20px;
            }
            
            .certificat-print-footer {
                flex-direction: column;
                gap: 30px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .animate-slide {
            animation: slideIn 0.5s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

         /* Organisation des certificats */
    .certificats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .certificat-card {
        margin-bottom: 0; /* Supprime la marge basse car gérée par le grid gap */
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .certificat-content {
        flex-grow: 1;
    }
    
    
    /* En-tête amélioré */
    .certificat-header {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .certificat-title-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }
    
    /* Barre de tri */
    .sorting-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        background: var(--lighter);
        padding: 12px 20px;
        border-radius: var(--border-radius-sm);
    }
    
    .sort-options {
        display: flex;
        gap: 15px;
    }
    
    .sort-btn {
        background: none;
        border: none;
        color: var(--gray);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
        transition: var(--transition);
    }
    
    .sort-btn:hover {
        color: var(--primary);
    }
    
    .sort-btn.active {
        color: var(--primary);
        font-weight: 500;
    }
    
    /* Animation de chargement améliorée */
    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }
    
    .loading-certificats {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }
    
    .loading-certificats i {
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    .btn-retour {
        background: linear-gradient(115deg,  var(--secondary), var(--primary));
        color: #fff;
        padding: 1px 20px;
        border: 1px dashed black;
        border-radius: 5px;
        cursor: pointer;
        position: fixed;
        top: 20px;
        left: 15px;
        font-size: 1.5rem;
    }


    </style>
</head>
<body>
    
    <button class="btn-retour" onclick="history.back()"><-</button>
    <div class="container">
        <div class="main-content">
            <header>
                <div class="header-left">
                    <h1><i class="fas fa-file-medical"></i> Gestion des Certificats Médicaux</h1>
                    <p>Demander et consulter vos certificats médicaux en ligne</p>
                </div>
                
                <div class="user-card animate-slide delay-1">
                    <div class="user-avatar">
                        <?= strtoupper(substr($patient['nom'], 0, 1) . substr($patient['prenom'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <strong><?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?></strong>
                        <span>Étudiant - <?= htmlspecialchars($patient['filiere'] ?? '') ?></span>
                    </div>
                </div>
            </header>
            
            <?php if (!empty($flash_message)): ?>
                <div class="message <?= htmlspecialchars($flash_message['type']) ?> show">
                    <i class="fas <?= 
                        $flash_message['type'] === 'error' ? 'fa-exclamation-circle' : 
                        ($flash_message['type'] === 'success' ? 'fa-check-circle' : 'fa-info-circle') 
                    ?>"></i>
                    <div><?= nl2br(htmlspecialchars($flash_message['message'])) ?></div>
                </div>
            <?php endif; ?>
            
            <section id="nouvelle-demande">
                <h2><i class="fas fa-file-medical-alt"></i> Nouvelle demande de certificat</h2>
                
                <form method="POST" id="certificatForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label for="medecin_id" class="form-label"><i class="fas fa-user-md"></i> Sélectionnez un médecin</label>
                        <select id="medecin_id" name="medecin_id" class="form-control" required>
                            <option value="">-- Sélectionnez un médecin --</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= htmlspecialchars($medecin['id']) ?>" 
                                    data-specialite="<?= htmlspecialchars($medecin['specialite']) ?>"
                                    data-rpps="<?= htmlspecialchars($medecin['numero_professionnel'] ?? '') ?>">
                                    Dr. <?= htmlspecialchars($medecin['nom']) ?> 
                                    (<?= htmlspecialchars($medecin['specialite']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="medecin-info" style="margin-top: 8px; font-size: 13px; color: var(--gray); display: none;">
                            <i class="fas fa-info-circle"></i> <span id="medecin-details"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="type_certificat" class="form-label"><i class="fas fa-tag"></i> Type de certificat</label>
                        <select id="type_certificat" name="type_certificat" class="form-control" required>
                            <option value="standard">Standard (consultation générale)</option>
                            <option value="urgence">Urgence médicale</option>
                            <option value="aptitude">Aptitude médicale</option>
                            <option value="absence">Certificat d'absence</option>
                            <option value="sport">Certificat médical sportif</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="contenu" class="form-label"><i class="fas fa-edit"></i> Décrivez votre demande</label>
                        <textarea 
                            id="contenu" 
                            name="contenu" 
                            class="form-control" 
                            required
                            placeholder="Décrivez en détail vos symptômes, la raison de votre demande, ou toute autre information utile pour le médecin..."
                        ></textarea>
                        <div style="font-size: 13px; color: var(--gray); margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Soyez aussi précis que possible pour faciliter le traitement de votre demande.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Envoyer la demande
                    </button>
                </form>
            </section>
            
            <!-- Modifiez la section historique comme ceci -->
            <section class="certificat-list" id="historique">
                <h2><i class="fas fa-history"></i> Historique de vos demandes</h2>
                
                <div class="sorting-bar">
                    <div class="results-info" id="results-info">
                        <?= count($certificats) ?> certificats trouvés
                    </div>
                    
                    <div class="sort-options">
                        <button class="sort-btn active" data-sort="date-desc">
                            <i class="fas fa-arrow-down-a-z"></i> Plus récent
                        </button>
                        <button class="sort-btn" data-sort="date-asc">
                            <i class="fas fa-arrow-up-a-z"></i> Plus ancien
                        </button>
                        <button class="sort-btn" data-sort="type">
                            <i class="fas fa-tag"></i> Par type
                        </button>
                        <button class="sort-btn" data-sort="statut">
                            <i class="fas fa-check-circle"></i> Par statut
                        </button>
                    </div>
                </div>
                
                <?php if (empty($certificats)): ?>
                    <div class="empty-state animate-fade delay-1">
                        <i class="fas fa-file-medical"></i>
                        <h3>Aucune demande de certificat</h3>
                        <p>Vous n'avez effectué aucune demande de certificat médical pour le moment.</p>
                        <a href="#nouvelle-demande" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Faire une demande
                        </a>
                    </div>
                <?php else: ?>
                    <div class="certificats-grid" id="certificats-container">
                        <?php foreach ($certificats as $index => $cert): ?>
                            <div class="certificat-card animate-fade delay-<?= ($index % 4) + 1 ?> <?= str_replace(' ', '-', htmlspecialchars($cert['statut'])) ?>">
                                <div class="certificat-header">
                                    <div class="certificat-title-container">
                                        <h3 class="certificat-title">
                                            <i class="fas fa-<?= 
                                                $cert['type_certificat'] === 'urgence' ? 'bolt' : 
                                                ($cert['type_certificat'] === 'aptitude' ? 'heart-pulse' : 
                                                ($cert['type_certificat'] === 'absence' ? 'calendar-times' : 'file-medical')) 
                                            ?>"></i>
                                            <?= htmlspecialchars(ucfirst($cert['type_certificat'])) ?>
                                        </h3>
                                        <span class="badge badge-<?= 
                                            $cert['statut'] === 'validé' ? 'success' : 
                                            ($cert['statut'] === 'en attente' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= htmlspecialchars(ucfirst($cert['statut'])) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="certificat-meta">
                                        <span class="certificat-meta-item">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= date('d/m/Y à H:i', strtotime($cert['date_creation'])) ?>
                                        </span>
                                        <?php if ($cert['date_validation']): ?>
                                            <span class="certificat-meta-item">
                                                <i class="far fa-check-circle"></i> 
                                                Validé
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($cert['medecin_nom']): ?>
                                    <div class="certificat-meta">
                                        <span class="certificat-meta-item">
                                            <i class="fas fa-user-md"></i> 
                                            Dr. <?= htmlspecialchars($cert['medecin_nom']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="certificat-content">
                                    <h4><i class="fas fa-comment-medical"></i> Motif :</h4>
                                    <p class="truncate-text"><?= nl2br(htmlspecialchars(mb_substr($cert['contenu'], 0, 150))) ?><?= mb_strlen($cert['contenu']) > 150 ? '...' : '' ?></p>
                                    
                                    <?php if ($cert['statut'] === 'validé' && !empty($cert['commentaire_medecin'])): ?>
                                        <div class="medical-comment">
                                            <h4><i class="fas fa-notes-medical"></i> Avis médical :</h4>
                                            <p class="truncate-text"><?= nl2br(htmlspecialchars(mb_substr($cert['commentaire_medecin'], 0, 100))) ?><?= mb_strlen($cert['commentaire_medecin']) > 100 ? '...' : '' ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="certificat-actions">
                                    <button 
                                        class="btn btn-outline tooltip" 
                                        data-tooltip="Voir les détails"
                                        onclick="openCertificatModal(<?= htmlspecialchars(json_encode($cert)) ?>)"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($cert['statut'] === 'validé'): ?>
                                        <button 
                                            class="btn btn-outline tooltip" 
                                            data-tooltip="Télécharger"
                                            onclick="generatePDF(<?= htmlspecialchars($cert['id']) ?>)"
                                        >
                                            <i class="fas fa-download"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button 
                                        class="btn btn-outline tooltip" 
                                        data-tooltip="Copier le lien"
                                        onclick="copyCertificatLink(<?= htmlspecialchars($cert['id']) ?>)"
                                    >
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($certificats) > 10): ?>
                        <div class="pagination" id="pagination">
                            <!-- Votre pagination existante -->
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
    
     <!-- Bouton flottant pour remonter en haut -->
    <button class="floating-action-btn tooltip" data-tooltip="Nouvelle demande" onclick="window.location.href='#nouvelle-demande'">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Modal pour afficher le certificat -->
    <div id="certificatModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            
            <div id="certificatPrintContent" class="certificat-print">
                <div class="certificat-print-header">
                    <h2>CERTIFICAT MÉDICAL</h2>
                    <p>Document établi conformément à l'article R.4127-76 du code de la santé publique</p>
                </div>
                
                <div class="certificat-print-body" id="certificatDetails">
                    <!-- Contenu dynamique injecté par JS -->
                </div>
                
                <div class="certificat-print-footer">
                    <div>
                        <p>Fait à : <strong><span id="certificatLieu">[Ville du cabinet]</span></strong></p>
                        <p>Le : <strong><span id="certificatDate"><?= date('d/m/Y') ?></span></strong></p>
                    </div>
                    
                    <div class="signature">
                        <div style="width: 250px; height: 100px; margin: 0 auto; position: relative;">
                            <div style="position: absolute; width: 100%; height: 100%; border: 1px dashed #ccc; border-radius: 5px;"></div>
                            <div style="position: absolute; bottom: 0; width: 100%; border-top: 1px solid #000; padding-top: 5px;">
                                <p style="font-size: 13px; margin: 0;">Signature et cachet du médecin</p>
                                <p id="medecinNom" style="font-weight: bold; margin-top: 3px; font-size: 14px;">[Nom du médecin]</p>
                                <p id="medecinRpps" style="font-size: 11px; margin-top: 2px;">N° RPPS: [numéro]</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="font-size: 11px; color: #95a5a6; text-align: center; margin-top: 30px;">
                    <p>Ce document est établi à l'attention exclusive du patient et ne peut être transmis à des tiers sans autorisation.</p>
                </div>
            </div>
            
            <div class="print-actions no-print">
            
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <button onclick="generatePDF()" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Télécharger PDF
                </button>
                <button onclick="closeModal()" class="btn btn-outline">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>

    
<script>
        // Fonction pour ouvrir la modal avec les détails du certificat
        function openCertificatModal(certificat) {
            const modal = document.getElementById('certificatModal');
            const contentDiv = document.getElementById('certificatDetails');
            
            // Construction du contenu HTML
            let html = `
                <div style="margin-bottom: 25px;">
                    <p>Je soussigné(e), <strong>Dr. ${certificat.medecin_nom || '[Nom du médecin]'}</strong>,</p>
                    ${certificat.numero_professionnel ? `<p>Médecin inscrit au tableau de l'Ordre sous le n° RPPS : ${certificat.numero_professionnel}</p>` : ''}
                    ${certificat.specialite ? `<p>Spécialité : ${certificat.specialite}</p>` : ''}
                </div>
                
                <div style="margin-bottom: 25px;">
                    <p>Certifie avoir examiné ce jour :</p>
                    <p><strong>${certificat.patient_nom || '[Nom du patient]'}</strong></p>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <h4 style="font-weight: bold; margin-bottom: 10px;">Motif de la consultation :</h4>
                    <p>${certificat.contenu.replace(/\n/g, '<br>')}</p>
                </div>
                
                ${certificat.commentaire_medecin ? `
                <div style="margin-bottom: 25px;">
                    <h4 style="font-weight: bold; margin-bottom: 10px;">Diagnostic et recommandations :</h4>
                    <p>${certificat.commentaire_medecin.replace(/\n/g, '<br>')}</p>
                </div>
                ` : ''}
                
                <div style="margin-top: 30px;">
                    <p>Le présent certificat est établi à la demande de l'intéressé(e) pour servir et valoir ce que de droit.</p>
                </div>
            `;
            
            contentDiv.innerHTML = html;
            document.getElementById('certificatLieu').textContent = certificat.ville || 'NGaoundere';
            document.getElementById('medecinNom').textContent = certificat.medecin_nom || 'Dr. [Nom]';
            document.getElementById('medecinRpps').textContent = certificat.numero_professionnel || 'N° RPPS non disponible';
            
            // Mise à jour de la date de validation
            if (certificat.date_validation) {
                const dateParts = certificat.date_validation.split(' ')[0].split('-');
                document.getElementById('certificatDate').textContent = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
            }
            
            // Affichage de la modal avec animation
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            
            // Ajouter le certificat actuel à l'historique pour le PDF
            currentCertificat = certificat;
        }
        
        // Fonction pour fermer la modal
        function closeModal() {
            const modal = document.getElementById('certificatModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        // Fermer la modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('certificatModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Afficher les détails du refus
        function showRefusDetails(btn) {
            const card = btn.closest('.certificat-card');
            const details = card.querySelector('.certificat-content p:last-child');
            
            if (details.style.display === 'none' || !details.style.display) {
                details.style.display = 'block';
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> Masquer les détails';
            } else {
                details.style.display = 'none';
                btn.innerHTML = '<i class="fas fa-info-circle"></i> Détails du refus';
            }
        }
        
        // Générer un PDF (fonction simulée)
        function generatePDF(certificatId) {
            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            
            // Simulation de chargement
            btn.innerHTML = '<span class="loading"></span> Génération en cours...';
            btn.disabled = true;
            
            // Simuler un délai de génération
            setTimeout(() => {
                alert('Fonctionnalité PDF: Cette fonction serait connectée à un générateur de PDF comme jsPDF ou une API backend.');
                btn.innerHTML = originalContent;
                btn.disabled = false;
                
                // Dans une vraie implémentation, on ferait:
                // window.location.href = `/generate-pdf/${certificatId}`;
            }, 1500);
        }
        
        // Validation du formulaire
        document.getElementById('certificatForm').addEventListener('submit', function (e) {
            const submitBtn = document.getElementById('submitBtn');
            const medecinSelect = document.getElementById('medecin_id');
            const contenuTextarea = document.getElementById('contenu');
            
            if (!medecinSelect.value) {
                alert('Veuillez sélectionner un médecin');
                medecinSelect.focus();
                e.preventDefault();
                return false;
            }
            
            if (contenuTextarea.value.trim().length < 10) {
                alert('Veuillez décrire votre demande (minimum 10 caractères)');
                contenuTextarea.focus();
                e.preventDefault();
                return false;
            }
            
            // Simulation de chargement
            submitBtn.innerHTML = '<span class="loading"></span> Envoi en cours...';
            submitBtn.disabled = true;
            
            return true;
        });
        
        // Afficher les infos du médecin sélectionné
        document.getElementById('medecin_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('medecin-info');
            const detailsSpan = document.getElementById('medecin-details');
            
            if (this.value) {
                const specialite = selectedOption.getAttribute('data-specialite');
                const rpps = selectedOption.getAttribute('data-rpps');
                
                let details = `Spécialité: ${specialite}`;
                if (rpps) details += ` | N° RPPS: ${rpps}`;
                
                detailsSpan.textContent = details;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
        
        // Animation pour les messages
        document.addEventListener('DOMContentLoaded', function() {
            // Faire apparaître progressivement les éléments
            const elements = document.querySelectorAll('.animate-fade, .animate-slide');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Afficher les messages flash après un délai
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                setTimeout(() => {
                    msg.classList.add('show');
                }, 100);
            });
        });
        
        // Détection du mobile pour masquer certains textes
        function checkMobile() {
            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            document.querySelectorAll('.hide-on-mobile').forEach(el => {
                el.style.display = isMobile ? 'none' : 'inline';
            });
        }
        
        window.addEventListener('resize', checkMobile);
        checkMobile();

        // Tri des certificats
    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Simuler le tri (dans une vraie implémentation, vous trieriez le tableau)
            const container = document.getElementById('certificats-container');
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';
            
            setTimeout(() => {
                // Ici vous implémenteriez le vrai tri
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
                
                // Animation de réorganisation
                anime({
                    targets: '.certificat-card',
                    translateY: [20, 0],
                    opacity: [0, 1],
                    delay: anime.stagger(50),
                    easing: 'easeOutQuad'
                });
            }, 500);
        });
    });
    
    // Fonction pour copier le lien
    function copyCertificatLink(id) {
        const url = `${window.location.origin}${window.location.pathname}?certificat=${id}`;
        navigator.clipboard.writeText(url).then(() => {
            alert('Lien copié dans le presse-papiers !');
        });
    }
    
    // Animation au chargement
    document.addEventListener('DOMContentLoaded', () => {
        anime({
            targets: '.certificat-card',
            translateY: [30, 0],
            opacity: [0, 1],
            delay: anime.stagger(100),
            duration: 600,
            easing: 'easeOutQuint'
        });
    });
</script>

</body>
</html>