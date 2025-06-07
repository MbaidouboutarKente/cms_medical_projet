<?php
$host = "localhost";
$username = "root";
$password = "";
$campus_db = "campus_db";
$medical_db = "medical_db";

try {
    $pdoCampus = new PDO("mysql:host=$host;dbname=$campus_db", $username, $password);
    $pdoCampus->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connexion réussie à campus_db<br>";
} catch (PDOException $e) {
    die("Erreur campus_db : " . $e->getMessage());
}

try {
    $pdoMedical = new PDO("mysql:host=$host;dbname=$medical_db", $username, $password);
    $pdoMedical->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connexion réussie à medical_db<br>";
} catch (PDOException $e) {
    die("Erreur medical_db : " . $e->getMessage());
}

function enregistrerActivite($pdo, $user_id, $action, $details = null) {
    try {
        // Nettoyage des entrées
        $clean_user_id = $user_id ? (int)$user_id : null;
        $clean_action = substr(trim($action), 0, 255);
        $clean_details = $details ? substr(trim($details), 0, 1000) : null;
        
        // Préparation de la requête
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, details, ip_address, user_agent)
            VALUES (:user_id, :action, :details, :ip, :ua)
        ");
        
        // Exécution avec paramètres nommés
        $stmt->execute([
            ':user_id' => $clean_user_id,
            ':action' => $clean_action,
            ':details' => $clean_details,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Double système de fallback
        error_log("ERREUR LOG: ".$e->getMessage());
        
        // Fallback dans un fichier texte
        $logMessage = date('[Y-m-d H:i:s]')." - ";
        $logMessage .= "User: ".($clean_user_id ?? 'null')." - ";
        $logMessage .= "Action: ".$clean_action." - ";
        $logMessage .= "Details: ".($clean_details ?? 'null')."\n";
        
        file_put_contents(__DIR__.'/../logs/activity.log', $logMessage, FILE_APPEND);
        return false;
    }
}

?>
