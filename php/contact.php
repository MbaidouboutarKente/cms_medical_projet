<?php
// contact.php

// Initialisation de la session
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once "db.php";

// Traitement du formulaire
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) {
        $error_message = 'Le nom est requis';
    } elseif (!$email) {
        $error_message = 'Email invalide';
    } elseif (empty($subject)) {
        $error_message = 'Le sujet est requis';
    } elseif (empty($message)) {
        $error_message = 'Le message est requis';
    } else {
        try {
            // Insertion dans la base de données
            $stmt = $pdoMedical->prepare("INSERT INTO contacts (name, email, subject, message, created_at) 
                                   VALUES (:name, :email, :subject, :message, NOW())");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subject,
                ':message' => $message
            ]);
            
            $success_message = 'Votre message a été envoyé avec succès !';
            
            // Réinitialisation du formulaire
            $_POST = [];
            
        } catch (PDOException $e) {
            $error_message = 'Une erreur est survenue. Veuillez réessayer plus tard.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contact - CMS Médical</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --secondary: #10b981;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --border-radius: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: #f5f7fa;
            color: var(--dark);
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .contact-form {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        
        button:hover {
            background-color: var(--primary-light);
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            text-align: center;
        }
        
        .info-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1><i class="fas fa-envelope"></i> Contactez-nous</h1>
        <p>Nous sommes là pour répondre à vos questions</p>
    </div>
</header>

<div class="container">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <div class="contact-form">
        <form method="POST" action="contact.php">
            <div class="form-group">
                <label for="name">Votre nom complet</label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="subject">Sujet</label>
                <select id="subject" name="subject" required>
                    <option value="">Sélectionnez un sujet</option>
                    <option value="Question générale" <?= ($_POST['subject'] ?? '') === 'Question générale' ? 'selected' : '' ?>>Question générale</option>
                    <option value="Problème technique" <?= ($_POST['subject'] ?? '') === 'Problème technique' ? 'selected' : '' ?>>Problème technique</option>
                    <option value="Rendez-vous" <?= ($_POST['subject'] ?? '') === 'Rendez-vous' ? 'selected' : '' ?>>Rendez-vous</option>
                    <option value="Facturation" <?= ($_POST['subject'] ?? '') === 'Facturation' ? 'selected' : '' ?>>Facturation</option>
                    <option value="Autre" <?= ($_POST['subject'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">Votre message</label>
                <textarea id="message" name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            
            <button type="submit">
                <i class="fas fa-paper-plane"></i> Envoyer le message
            </button>
        </form>
    </div>
    
    <div class="contact-info">
        <div class="info-card">
            <i class="fas fa-map-marker-alt"></i>
            <h3>Adresse</h3>
            <p>Université de Ngaoundéré<br>BP 454, Ngaoundéré, Cameroun</p>
        </div>
        
        <div class="info-card">
            <i class="fas fa-phone"></i>
            <h3>Téléphone</h3>
            <p>+237 6 96 33 02 02<br>Lundi-Vendredi, 8h-17h</p>
        </div>
        
        <div class="info-card">
            <i class="fas fa-envelope"></i>
            <h3>Email</h3>
            <p>support@cmsmedical.com<br>contact@cmsmedical.com</p>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 CMS Médical - Université de Ngaoundéré. Tous droits réservés.</p>
    </div>
</footer>

</body>
</html>