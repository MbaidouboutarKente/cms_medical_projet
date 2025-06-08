# 🏥 Application de Suivi et Gestion des Visites Médicales  
## Centre Médical Universitaire - Université de Ngaoundéré  

### 📌 Description  
L’application **CMS Médical** permet de **suivre et gérer les visites médicales** des **étudiants** et du **personnel** du Centre Médical Universitaire de l’Université de Ngaoundéré.  

Elle repose sur **deux bases de données** :  
1️⃣ **Base Campus** : Données académiques des étudiants.  
2️⃣ **Base Médicale** : Informations sur les consultations et traitements.  

Le système **vérifie l’existence** d’un étudiant dans la base Campus avant son inscription en base Médicale, garantissant une **authenticité et une cohérence des données**.  

---

## 🎯 Objectifs du Projet  
✅ **Automatisation** de la gestion des consultations médicales  
✅ **Centralisation** des dossiers médicaux et académiques  
✅ **Sécurisation** des données et contrôle des accès  
✅ **Optimisation** de la coordination entre campus et services médicaux  

---

## 🛠️ Fonctionnalités  

### 🔹 Gestion des Utilisateurs  
- **Authentification sécurisée** (GPI, CNS).  
- Différents **rôles** : Étudiants, Médecins, Administrateurs.  
- Vérification dans la **Base Campus** avant inscription en base Médicale.  

### 🔹 Prise de Rendez-vous  
- Réservation avec choix du **médecin, date et heure**.  
- Validation automatique des **créneaux disponibles**.  
- Confirmation instantanée du rendez-vous.  

### 🔹 Gestion des Consultations  
- **Tableau de bord interactif** pour les **médecins**.  
- **Accès aux dossiers médicaux** et historique des patients.  
- **Ajout de diagnostics, prescriptions et certificats médicaux**.  

### 🔹 Gestion des Certifications  
- **Génération automatique** des **certificats médicaux**.  
- **Téléchargement des certificats** en **format PDF**.  
- **Validation médicale facultative** selon le type de certification.  

### 🔹 Tests Médicaux sans Intervention  
- **Possibilité de simuler un test médical** sans l’intervention d’un médecin.  
- **Affichage des résultats** sans enregistrement définitif.  
- **Option pour enregistrer les résultats** si souhaité.  

### 🔹 Intégration des Bases de Données  
- Vérification des étudiants dans **Base Campus** avant leur ajout en **Base Médicale**.  
- **Synchronisation automatique** des informations académiques et médicales.  

### 🔹 Notifications Internes  
- **Affichage des notifications** lors de la connexion.  
- Rappels de **rendez-vous et consultations** affichés dans le tableau de bord.  

### 🔹 Sécurité et Confidentialité  
- **Chiffrement des mots de passe** et protection des données sensibles.  
- **Gestion des accès** basée sur les rôles.  

### 🔹 Rapports et Statistiques  
- Génération de **rapports PDF/Excel** pour suivi médical.  
- Analyse des **tendances et activité** du CMS.  

---

## 🚀 Architecture et Technologies  

### 🔹 Backend  
- **PHP** (avec XAMPP) : Gestion des traitements et requêtes serveur.  
- **AJAX** : Communication asynchrone entre client et serveur.  
- **MySQL** :  
  - **Base Campus** : Données académiques des étudiants.  
  - **Base Médicale** : Dossiers médicaux et consultations.  

### 🔹 Frontend  
- **HTML, CSS, JavaScript** : Interface utilisateur interactive.  
- **Bootstrap** : Design moderne et responsive.  

### 🔹 Sécurité  
- **Gestion des sessions et authentification sécurisée**.  
- **Protection des données et validation d’accès**.  

### 🔹 Notifications  
- **Système de notifications internes** visible lors de la connexion.

- 
📢 **Un fichier `members.md` présente les membres du projet.**

## 🔧 Installation et Déploiement  

### 💾 Prérequis  
- **XAMPP** installé (Apache + MySQL + PHP).  
- **Base de données MySQL** configurée.  

### 🏗️ Installation  
1️⃣ **Télécharger et copier les fichiers** dans `htdocs` :  
```bash
cd /opt/lampp/htdocs/cms_medical_project
