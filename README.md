# 🏥 Application de Suivi et Gestion des Visites Médicales  
## Centre Médical Universitaire - Université de Ngaoundéré  

### 📌 Description  
Ce projet vise à concevoir une **application web complète** permettant la gestion et le suivi des **visites médicales** des **étudiants** et du **personnel** au sein du **Centre Médical Universitaire (CMS)** de l’Université de Ngaoundéré.  
L'application repose sur **deux bases de données** :  
1. **Base Campus** : Contient les informations académiques et administratives des étudiants.  
2. **Base Médicale** : Stocke les données des visites médicales, résultats d'examens et traitements.  

Avant l'inscription d'un étudiant dans le système médical, la plateforme vérifie **s'il existe déjà** dans la base Campus avant de créer son profil médical.

---

## 🎯 Objectifs du Projet  
✅ **Numériser** le processus de gestion des rendez-vous médicaux  
✅ **Centraliser** les dossiers médicaux et les suivis de consultations  
✅ **Sécuriser** les données médicales et garantir leur confidentialité  
✅ **Intégrer** les données académiques pour une validation préalable avant l'inscription  
✅ **Analyser** l’activité médicale grâce à des **statistiques et rapports détaillés**  

---

## 🛠️ Fonctionnalités  

### 🔹 Gestion des Utilisateurs  
- **Authentification sécurisée** via identifiants universitaires (GPI, CNS).  
- **Différents rôles** : Étudiants, Médecins, Administrateurs.  
- **Vérification automatique** de l’existence d’un étudiant avant son inscription dans la base médicale.  

### 🔹 Prise de Rendez-vous  
- Réservation en ligne avec **choix du médecin, de la date et de l’heure**.  
- Vérification automatique des **plages horaires disponibles**.  
- Confirmation instantanée du rendez-vous par **e-mail**.  

### 🔹 Gestion des Consultations  
- Tableau de bord interactif pour les **médecins** avec accès aux **dossiers médicaux des patients**.  
- Ajout et modification des **diagnostics médicaux** et traitements prescrits.  
- Enregistrement des **tests effectués** et **observations médicales**.  
- Génération et stockage des **certificats médicaux**.  

### 🔹 Suivi des Antécédents Médicaux  
- Sauvegarde sécurisée des résultats des examens et consultations passées.  
- Historique médical consultable pour chaque **patient**.  
- Mise à jour des dossiers avec les nouvelles **prescriptions** et recommandations.  

### 🔹 Notifications et Rappels  
- **Envoi automatique de notifications** par e-mail/SMS pour :  
  ✅ Confirmation de rendez-vous  
  ✅ Rappel avant consultation  
  ✅ Notification des **résultats médicaux**  

### 🔹 Sécurité et Confidentialité  
- **Chiffrement des mots de passe** et stockage sécurisé des données.  
- **Validation des identités** avec la base Campus avant inscription.  
- Protocoles de **gestion d’accès** pour protéger les informations sensibles.  

### 🔹 Rapports et Statistiques  
- Affichage des **statistiques globales** des visites et consultations.  
- Génération de **rapports PDF/Excel** pour suivi administratif.  
- Indicateurs clés pour améliorer **la gestion des ressources**.  

---

## 🚀 Architecture et Technologies  

### 🔹 Backend  
- **Python** (Flask) : Gestion des traitements et des requêtes côté serveur.  
- **AJAX** : Communication asynchrone entre le client et le serveur.  
- **SQLite / PostgreSQL** :  
  - **Base Campus** : Données académiques des étudiants.  
  - **Base Médicale** : Informations sur les consultations et les dossiers médicaux.  

### 🔹 Frontend  
- **HTML, CSS, JavaScript** : Interface utilisateur interactive et réactive.  
- **Bootstrap** : Design moderne et responsive pour les navigateurs et mobiles.  

### 🔹 Sécurité  
- Gestion des **sessions utilisateur** et authentification.  
- **Chiffrement** des mots de passe et données sensibles.  
- **Vérification préalable** avant toute inscription dans la base médicale.  

### 🔹 Notifications  
- **SMTP** : Envoi d’e-mails sécurisés.  
- Intégration d’API pour **envoi de SMS** aux étudiants et personnel médical.  

---

## 🔧 Installation et Déploiement  

### 💾 Prérequis  
- **Python 3.x** installé  
- **Flask** et les dépendances (`pip install flask`)  
- Bases de données **SQLite / PostgreSQL** configurées  

### 🏗️ Installation  
1️⃣ **Cloner le dépôt**  
```bash
git clone https://github.com/monprojet.git
cd monprojet
