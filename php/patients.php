<?php
session_start();
require_once "db.php";

// Activation du débogage complet
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification rôle médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header("Location: auth.php");
    exit;
}

// Extraction ID numérique
$medecinID = intval(preg_replace('/^[A-Z]+_/', '', $_SESSION['user_id']));


// Fonction de sécurisation
function secure($data) {
    return htmlspecialchars(trim($data));
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter_patient'])) {
        // Ajout d'un nouveau patient
        $data = array_map('secure', $_POST);
        
        try {
            $stmt = $pdoMedical->prepare("INSERT INTO patients 
                                  (nom, prenom, date_naissance, telephone, email, adresse, allergies, antecedents) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['nom'],
                $data['prenom'],
                $data['date_naissance'],
                $data['telephone'],
                $data['email'],
                $data['adresse'],
                $data['allergies'],
                $data['antecedents']
            ]);
            $_SESSION['message'] = "Patient ajouté avec succès!";
            header("Location: dossier_patient.php");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    } elseif (isset($_POST['modifier_patient'])) {
        // Modification d'un patient existant
        $id = (int)$_GET['id'];
        $data = array_map('secure', $_POST);
        
        try {
            $stmt = $pdoMedical->prepare("UPDATE utilisateurs SET 
                                  nom = ?, prenom = ?, date_naissance = ?, telephone = ?, email = ?, 
                                  adresse = ?, allergies = ?, antecedents = ? 
                                  WHERE id = ?");
            $stmt->execute([
                $data['nom'],
                $data['prenom'],
                $data['date_naissance'],
                $data['telephone'],
                $data['email'],
                $data['adresse'],
                $data['allergies'],
                $data['antecedents'],
                $id
            ]);
            $_SESSION['message'] = "Patient mis à jour avec succès!";
            header("Location: dossier_patient.php");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Traitement de la suppression
// if (isset($_GET['supprimer'])) {
//     $id = (int)$_GET['supprimer'];
//     try {
//         $pdoMedical->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
//         $_SESSION['message'] = "Patient supprimé avec succès!";
//         header("Location: dossier_patient.php");
//         exit;
//     } catch (PDOException $e) {
//         $error = "Erreur lors de la suppression : " . $e->getMessage();
//     }
// }

// Récupération des patients
$patients = $pdoMedical->query("SELECT * FROM utilisateurs ORDER BY nom")->fetchAll();

// Récupération d'un patient spécifique pour modification
$patient_edit = null;
if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] === 'editer') {
    $id = (int)$_GET['id'];
    $patient_edit = $pdo->query("SELECT * FROM utilisateurs WHERE id = $id")->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Dossiers Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .patient-card {
            transition: all 0.3s ease;
            border-left: 4px solid #0d6efd;
        }
        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .info-badge {
            background-color: #e9ecef;
            color: #495057;
        }
        .modal-header {
            background-color: #0d6efd;
            color: white;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .detail-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-heart-pulse me-2"></i>Dossiers Patients
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Messages de notification -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="bi bi-people-fill me-2"></i>Liste des Patients</h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#patientModal">
                    <i class="bi bi-plus-circle"></i> Nouveau Patient
                </button>
            </div>
        </div>

        <!-- Liste des patients -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Date Inscriptions.</th>
                        <th>Téléphone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?= $patient['id'] ?></td>
                            <td><?= $patient['nom'] ?></td>
                            <td><?= $patient['date_inscription'] ? date('d/m/Y', strtotime($patient['date_inscription'])) : '--' ?></td>
                            <td><?= $patient['telephone'] ?? '--' ?></td>
                            <td>
                                <a href="#" 
                                   class="btn btn-sm btn-info me-1">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="#" 
                                   class="btn btn-sm btn-warning me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Détail du patient (si action=voir) -->
        <?php if (isset($_GET['action']) && $_GET['action'] === 'voir' && isset($_GET['id'])): 
            $patient_id = (int)$_GET['id'];
            $patient = $pdo->query("SELECT * FROM patients WHERE id = $patient_id")->fetch();
            if ($patient):
        ?>
            <div class="detail-section mt-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>
                        <i class="bi bi-person-badge me-2"></i>
                        Dossier Patient: <?= $patient['prenom'] ?> <?= $patient['nom'] ?>
                    </h3>
                    <a href="dossier_patient.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>

                <ul class="nav nav-tabs mb-4" id="patientTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="infos-tab" data-bs-toggle="tab" 
                                data-bs-target="#infos" type="button" role="tab">
                            Informations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="medical-tab" data-bs-toggle="tab" 
                                data-bs-target="#medical" type="button" role="tab">
                            Dossier Médical
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rdv-tab" data-bs-toggle="tab" 
                                data-bs-target="#rdv" type="button" role="tab">
                            Rendez-vous
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="patientTabsContent">
                    <!-- Onglet Informations -->
                    <div class="tab-pane fade show active" id="infos" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5>Informations Personnelles</h5>
                                    <hr>
                                    <p><strong>Nom :</strong> <?= $patient['nom'] ?></p>
                                    <p><strong>Prénom :</strong> <?= $patient['prenom'] ?></p>
                                    <p><strong>Date de naissance :</strong> 
                                        <?= $patient['date_naissance'] ? date('d/m/Y', strtotime($patient['date_naissance'])) : '--' ?>
                                    </p>
                                    <p><strong>Âge :</strong> 
                                        <?= $patient['date_naissance'] ? 
                                            date_diff(date_create($patient['date_naissance']), date_create('today'))->y . ' ans' : '--' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5>Coordonnées</h5>
                                    <hr>
                                    <p><strong>Téléphone :</strong> <?= $patient['telephone'] ?? '--' ?></p>
                                    <p><strong>Email :</strong> <?= $patient['email'] ?? '--' ?></p>
                                    <p><strong>Adresse :</strong> 
                                        <?= !empty($patient['adresse']) ? nl2br($patient['adresse']) : '--' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Dossier Médical -->
                    <div class="tab-pane fade" id="medical" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5>Allergies</h5>
                                    <hr>
                                    <p><?= !empty($patient['allergies']) ? nl2br($patient['allergies']) : 'Aucune allergie connue' ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5>Antécédents Médicaux</h5>
                                    <hr>
                                    <p><?= !empty($patient['antecedents']) ? nl2br($patient['antecedents']) : 'Aucun antécédent notable' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Rendez-vous -->
                    <div class="tab-pane fade" id="rdv" role="tabpanel">
                        <h5>Historique des Rendez-vous</h5>
                        <hr>
                        <?php
                        $rdvs = $pdo->query("
                            SELECT * FROM rendezvous 
                            WHERE patient_id = $patient_id 
                            ORDER BY date_rdv DESC
                        ")->fetchAll();
                        
                        if (count($rdvs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Motif</th>
                                            <th>Statut</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rdvs as $rdv): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($rdv['date_rdv'])) ?></td>
                                                <td><?= $rdv['motif'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $rdv['statut'] === 'confirmé' ? 'success' : 
                                                        ($rdv['statut'] === 'annulé' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($rdv['statut']) ?>
                                                    </span>
                                                </td>
                                                <td><?= !empty($rdv['notes']) ? substr($rdv['notes'], 0, 50) . '...' : '--' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Aucun rendez-vous enregistré pour ce patient.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; endif; ?>

        <!-- Modal pour ajouter/modifier un patient -->
        <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="dossier_patient.php<?= isset($patient_edit) ? '?id='.$patient_edit['id'] : '' ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="patientModalLabel">
                                <?= isset($patient_edit) ? 'Modifier Patient' : 'Nouveau Patient' ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom*</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?= isset($patient_edit) ? $patient_edit['nom'] : '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="prenom" class="form-label">Prénom*</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?= isset($patient_edit) ? $patient_edit['prenom'] : '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="date_naissance" name="date_naissance" 
                                           value="<?= isset($patient_edit) ? $patient_edit['date_naissance'] : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" 
                                           value="<?= isset($patient_edit) ? $patient_edit['telephone'] : '' ?>">
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= isset($patient_edit) ? $patient_edit['email'] : '' ?>">
                                </div>
                                <div class="col-12">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="adresse" name="adresse" rows="2"><?= isset($patient_edit) ? $patient_edit['adresse'] : '' ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="allergies" class="form-label">Allergies</label>
                                    <textarea class="form-control" id="allergies" name="allergies" rows="3"><?= isset($patient_edit) ? $patient_edit['allergies'] : '' ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="antecedents" class="form-label">Antécédents médicaux</label>
                                    <textarea class="form-control" id="antecedents" name="antecedents" rows="3"><?= isset($patient_edit) ? $patient_edit['antecedents'] : '' ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" name="<?= isset($patient_edit) ? 'modifier_patient' : 'ajouter_patient' ?>" 
                                    class="btn btn-primary">
                                <?= isset($patient_edit) ? 'Modifier' : 'Enregistrer' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Afficher la modal si on est en mode édition
        <?php if (isset($patient_edit)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('patientModal'));
                modal.show();
            });
        <?php endif; ?>

        // Confirmation avant suppression
        function confirmDelete(id) {
            if (confirm("Voulez-vous vraiment supprimer ce patient ? Cette action est irréversible.")) {
                window.location.href = 'dossier_patient.php?supprimer=' + id;
            }
        }
    </script>
</body>
</html>