<?php
session_start();
require_once "db.php";
include "functions.php";

// Configuration du débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Vérifier si un utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: " . getRedirectPage($_SESSION['role']));
    exit;
}


$sectionActive = "login"; // Section active par défaut
$messageErreurInscription = "";
$messageErreurConnexion = "";

// Vérification des valeurs en session
$valeursInscription = [
    'matricule' => $_SESSION['form_data']['matricule'] ?? "",
    'nom' => $_SESSION['form_data']['nom'] ?? "",
    'email' => $_SESSION['form_data']['email'] ?? "",
    'role' => $_SESSION['form_data']['role'] ?? "etudiant"
];

$valeursConnexion = [
    'email' => $_SESSION['form_data']['login_email'] ?? ""
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? "";

    // 🔹 Gestion de l'inscription
    if ($action == "register") {
        $sectionActive = "register";
        $valeursInscription = [
            'matricule' => filter_input(INPUT_POST, "matricule", FILTER_SANITIZE_SPECIAL_CHARS),
            'nom' => filter_input(INPUT_POST, "nom", FILTER_SANITIZE_SPECIAL_CHARS),
            'email' => filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL),
            'role' => filter_input(INPUT_POST, "role", FILTER_SANITIZE_SPECIAL_CHARS) ?? "etudiant"
        ];
        $mot_de_passe = $_POST['mot_de_passe'] ?? "";

        $_SESSION['form_data'] = $valeursInscription;

        // 🚨 Vérification des champs obligatoires
        if (empty($valeursInscription['matricule']) || empty($valeursInscription['nom']) || 
            empty($valeursInscription['email']) || empty($mot_de_passe)) {
            $messageErreurInscription = "🔴 Tous les champs sont obligatoires.";
        } elseif (!$valeursInscription['email']) {
            $messageErreurInscription = "🔴 Format d'email invalide.";
        }

        // 🔍 Vérification étudiant dans la table `etudiants`
        if (empty($messageErreurInscription) && $valeursInscription['role'] === "etudiant") {
            $queryCampus = $pdoCampus->prepare("SELECT id, nom FROM etudiants WHERE matricule = ?");
            $queryCampus->execute([$valeursInscription['matricule']]);
            $etudiant = $queryCampus->fetch();

            if (!$etudiant || strtolower($etudiant['nom']) !== strtolower($valeursInscription['nom'])) {
                $messageErreurInscription = "🔴 Matricule ou nom incorrect.";
            }
        }

        if (empty($messageErreurInscription)) {
            $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $queryMedical = $pdoMedical->prepare("
                INSERT INTO utilisateurs (matricule, nom, email, mot_de_passe, role, last_login) VALUES (?, ?, ?, ?, ?, NOW())
            ");

            if ($queryMedical->execute([
                $valeursInscription['matricule'], 
                $valeursInscription['nom'], 
                $valeursInscription['email'], 
                $hashedPassword, 
                $valeursInscription['role']
            ])) {
                $_SESSION['user_id'] = "MED_" . $pdoMedical->lastInsertId();
                $_SESSION['role'] = $valeursInscription['role'];
                $_SESSION['email'] = $valeursInscription['email'];

                enregistrerActivite($pdoMedical, $_SESSION['user_id'], "Inscription réussie", "Nouvel utilisateur enregistré.");
                
                unset($_SESSION['form_data']);
                header("Location: " . getRedirectPage($valeursInscription['role']));
                exit;
            }
        }
    } elseif ($action == "login") {
        $sectionActive = "login";
        $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
        $mot_de_passe = $_POST['mot_de_passe'] ?? "";

        $_SESSION['form_data']['login_email'] = $email;

        if (empty($email) || empty($mot_de_passe)) {
            $messageErreurConnexion = "🔴 Email et mot de passe obligatoires.";
        }

        if (empty($messageErreurConnexion)) {
            $queryMedical = $pdoMedical->prepare("SELECT id, email, mot_de_passe, role FROM utilisateurs WHERE email = ?");
            $queryMedical->execute([$email]);
            $utilisateur = $queryMedical->fetch();

            if (!$utilisateur || !password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                $messageErreurConnexion = "🔴 Identifiants incorrects.";
            } else {

                   // Mise à jour du last_login avant de créer la session
                $queryUpdate = $pdoMedical->prepare("UPDATE utilisateurs SET last_login = NOW() WHERE id = ?");
                $queryUpdate->execute([$utilisateur['id']]);


                $_SESSION['user_id'] = "MED_" . $utilisateur['id'];
                $_SESSION['role'] = $utilisateur['role'];
                $_SESSION['email'] = $email;

                enregistrerActivite($pdoMedical, $utilisateur['id'], "Connexion réussie", "Utilisateur connecté.");
                
                unset($_SESSION['form_data']);
                header("Location: " . getRedirectPage($utilisateur['role']));
                exit;
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    unset($_SESSION['form_data']);
}
?>
    

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/auth.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion & Inscription</title>
</head>
<body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        showSection("<?php echo $sectionActive; ?>");
        
        // Surveillance dynamique des champs avec stockage local
        document.querySelectorAll('input, select').forEach(element => {
            // Récupération des valeurs stockées localement
            const savedValue = localStorage.getItem(`form_${element.name}`);
            if (savedValue !== null && element.value === "") {
                element.value = savedValue;
            }
            
            // Écoute des changements
            element.addEventListener('input', function() {
                localStorage.setItem(`form_${this.name}`, this.value);
            });
        });
        
        // Gestion spéciale pour le mot de passe (ne pas le stocker)
        document.querySelectorAll('input[type="password"]').forEach(element => {
            element.addEventListener('input', function() {
                localStorage.removeItem(`form_${this.name}`);
            });
        });
    });

    function showSection(section) {
        document.querySelectorAll(".section").forEach(div => div.style.display = "none");
        document.getElementById(section).style.display = "block";
    }
    
    function clearLocalStorage() {
        document.querySelectorAll('input, select').forEach(element => {
            if (element.type !== 'password') {
                localStorage.removeItem(`form_${element.name}`);
            }
        });
    }
</script>

<div class="container">
    <h2>Connexion & Inscription</h2>

    <div class="tabs">
        <button onclick="showSection('register')">Inscription</button>
        <button onclick="showSection('login')">Connexion</button>
    </div>

    <div id="register" class="section">
        <h3>Inscription</h3>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <input type="text" name="matricule" required placeholder="Matricule" value="<?php echo htmlspecialchars($valeursInscription['matricule']); ?>">
            <input type="text" name="nom" required placeholder="Nom" value="<?php echo htmlspecialchars($valeursInscription['nom']); ?>">
            <input type="email" name="email" required placeholder="Email" value="<?php echo htmlspecialchars($valeursInscription['email']); ?>">
            <input type="password" name="mot_de_passe" required placeholder="Mot de passe">
            <select name="role">
                <option value="etudiant" <?php echo $valeursInscription['role'] === 'etudiant' ? 'selected' : ''; ?>>Étudiant</option>
                <option value="infirmier" <?php echo $valeursInscription['role'] === 'infirmier' ? 'selected' : ''; ?>>Infirmier</option>
                <option value="medecin" <?php echo $valeursInscription['role'] === 'medecin' ? 'selected' : ''; ?>>Médecin</option>
                <!-- <option value="admin" <?php echo $valeursInscription['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                <option value="super_admin" <?php echo $valeursInscription['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option> -->
            </select>
            <button type="submit">S'inscrire</button>
            <?php if (!empty($messageErreurInscription)): ?>
                <p style="color:red;"><?php echo $messageErreurInscription; ?></p>
            <?php endif; ?>
        </form>
    </div>

    <div id="login" class="section">
        <h3>Connexion</h3>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="email" name="email" required placeholder="Email" value="<?php echo htmlspecialchars($valeursConnexion['email']); ?>">
            <input type="password" name="mot_de_passe" required placeholder="Mot de passe">
            <button type="submit">Se connecter</button>
            <?php if (!empty($messageErreurConnexion)): ?>
                <p style="color:red;"><?php echo $messageErreurConnexion; ?></p>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>