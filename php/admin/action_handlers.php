<?php
session_start();
require_once "../db.php";

// Vérifier que l'utilisateur est connecté et est un infirmier
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Accès non autorisé");
}

// Extraire l'ID infirmier de manière sécurisée
$infirmierID = intval(str_replace("MED_", "", $_SESSION['user_id']));

// Vérifier que l'ID est valide
if ($infirmierID <= 0) {
    header("HTTP/1.1 400 Bad Request");
    exit("ID utilisateur invalide");
}

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Fonction pour vérifier les permissions sur un RDV
function checkRdvPermission($pdo, $rdvID, $infirmierID) {
    $stmt = $pdo->prepare("SELECT id FROM rendez_vous WHERE id = ? AND infirmier_id = ?");
    $stmt->execute([$rdvID, $infirmierID]);
    return $stmt->fetch() !== false;
}

// Gestion des différentes actions
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier le token CSRF (à implémenter selon votre système)
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            header("HTTP/1.1 403 Forbidden");
            exit("Token CSRF invalide");
        }

        // Gestion des actions sur les rendez-vous
        if (isset($_POST['action'])) {
            $rdvID = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
            
            if (!$rdvID) {
                sendJsonResponse(false, "ID de rendez-vous invalide");
            }

            // Vérifier que l'infirmier a le droit de modifier ce RDV
            if (!checkRdvPermission($pdoMedical, $rdvID, $infirmierID)) {
                sendJsonResponse(false, "Vous n'avez pas la permission de modifier ce rendez-vous");
            }

            switch ($_POST['action']) {
                case 'confirmer':
                    $stmt = $pdoMedical->prepare("
                        UPDATE rendez_vous 
                        SET statut = 'confirmé', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$rdvID]);
                    
                    $_SESSION['notification'] = [
                        'type' => 'success',
                        'message' => 'Rendez-vous confirmé avec succès'
                    ];
                    sendJsonResponse(true, "Rendez-vous confirmé", ['statut' => 'confirmé']);
                    break;

                case 'annuler':
                    $stmt = $pdoMedical->prepare("
                        UPDATE rendez_vous 
                        SET statut = 'annulé', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$rdvID]);
                    
                    $_SESSION['notification'] = [
                        'type' => 'warning',
                        'message' => 'Rendez-vous annulé'
                    ];
                    sendJsonResponse(true, "Rendez-vous annulé", ['statut' => 'annulé']);
                    break;

                default:
                    header("HTTP/1.1 400 Bad Request");
                    exit("Action non reconnue");
            }
        }

        // Gestion des notes de rendez-vous
        if (isset($_POST['save_note'])) {
            $rdvID = filter_input(INPUT_POST, 'rdv_id', FILTER_VALIDATE_INT);
            $note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            
            if (!$rdvID) {
                sendJsonResponse(false, "ID de rendez-vous invalide");
            }

            // Vérifier que l'infirmier a le droit de modifier ce RDV
            if (!checkRdvPermission($pdoMedical, $rdvID, $infirmierID)) {
                sendJsonResponse(false, "Vous n'avez pas la permission de modifier ce rendez-vous");
            }

            $stmt = $pdoMedical->prepare("
                UPDATE rendez_vous 
                SET note = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$note, $rdvID]);
            
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Note enregistrée avec succès'
            ];
            sendJsonResponse(true, "Note enregistrée", ['note' => $note]);
        }

        // Gestion des autres actions (ajouter ici au besoin)
        // ...

    } else {
        header("HTTP/1.1 405 Method Not Allowed");
        exit("Méthode non autorisée");
    }

} catch (PDOException $e) {
    error_log("Erreur base de données: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit("Une erreur est survenue lors du traitement de votre demande");
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    header("HTTP/1.1 400 Bad Request");
    exit($e->getMessage());
}