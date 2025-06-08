# 🏥 Application de Suivi et Gestion des Visites Médicales  
## Centre Médical Universitaire - Université de Ngaoundéré  

### 📌 Description  
Cette application web permet de **suivre et gérer les visites médicales** des **étudiants** et du **personnel** au sein du **Centre Médical Universitaire (CMS)** de l’Université de Ngaoundéré.  
Elle repose sur **deux bases de données** :
1. **Base Campus** : Contient les informations académiques des étudiants.  
2. **Base Médicale** : Stocke les données des consultations et des traitements.  

Avant d’inscrire un étudiant dans la base médicale, le système **vérifie son existence** dans la base Campus pour garantir une **authenticité et une cohérence des données**.  

---

## 🎯 Objectifs du Projet  
✅ **Automatiser** la gestion des rendez-vous médicaux  
✅ **Centraliser** les dossiers médicaux et académiques  
✅ **Sécuriser** les données et garantir leur confidentialité  
✅ **Optimiser** la coordination entre campus et services médicaux  

---

## 🛠️ Fonctionnalités  

### 🔹 Gestion des Utilisateurs  
- **Authentification sécurisée** (GPI, CNS).  
- **Différents rôles** : Étudiants, Médecins, Administrateurs.  
- **Vérification automatique** dans la **base Campus** avant inscription en base médicale.  

### 🔹 Prise de Rendez-vous  
- Réservation en ligne avec choix du **médecin, date et heure**.  
- Validation automatique des **créneaux disponibles**.  
- Confirmation instantanée du rendez-vous dans l’interface utilisateur.  

### 🔹 Gestion des Consultations  
- **Tableau de bord** interactif pour les **médecins**.  
- **Accès aux dossiers médicaux** et historique des patients.  
- **Ajout des diagnostics, prescriptions et certificats médicaux**.  

### 🔹 Intégration des Bases de Données  
- Vérification de l’étudiant dans **la base Campus** avant l’ajout en base Médicale.  
- Synchronisation automatique des **données personnelles** et académiques.  

### 🔹 Notifications Internes  
- **Affichage des notifications** directement dans l’application lors de la connexion.  
- Chaque utilisateur voit ses **rappels de rendez-vous** et ses résultats médicaux **dans son tableau de bord**.  

### 🔹 Sécurité et Confidentialité  
- **Chiffrement des mots de passe** et protection des données sensibles.  
- **Validation préalable** avant toute inscription.  
- **Gestion des accès** basée sur les rôles.  

### 🔹 Rapports et Statistiques  
- Génération de **rapports PDF/Excel** pour suivi médical.  
- Analyse des **tendances et activité** du CMS.  

---

## 🚀 Architecture et Technologies  

### 🔹 Backend  
- **PHP** (avec XAMPP) : Gestion des traitements et des requêtes côté serveur.  
- **AJAX** : Communication asynchrone entre le client et le serveur.  
- **MySQL** :  
  - **Base Campus** : Données académiques des étudiants.  
  - **Base Médicale** : Informations sur les consultations et les traitements.  

### 🔹 Frontend  
- **HTML, CSS, JavaScript** : Interface utilisateur interactive.  
- **Bootstrap** : Design moderne et responsive.  

### 🔹 Sécurité  
- **Gestion des sessions** et authentification sécurisée.  
- **Vérification préalable** avant inscription en base médicale.  

### 🔹 Notifications  
- **Système de notifications internes** visible lors de la connexion.  

---

## 🔧 Installation et Déploiement  

### 💾 Prérequis  
- **XAMPP** installé (Apache + MySQL + PHP)  
- **Base de données MySQL** configurée


   # La liste des membres se trouve dans le fichier members.md

### 🏗️ Installation  
1️⃣ **Télécharger et copier les fichiers** dans `htdocs` :  
```bash
cd /opt/lampp/htdocs/cms_medical_project

