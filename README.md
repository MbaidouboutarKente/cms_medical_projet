# Plateforme de Gestion des Visites Médicales - CMS Université de Ngaoundéré

## 1. Introduction

### 1.1 Contexte
Le **Centre Médico-Social** (CMS) de l’université de Ngaoundéré souhaite moderniser la gestion des visites médicales des étudiants afin de :
- **Optimiser** l’efficacité et l'accessibilité des services.
- **Faciliter** le suivi des examens médicaux.
- **Réduire** les délais liés à la documentation papier.

### 1.2 Objectifs
- Développement d'une **application web** permettant la gestion des visites médicales.
- Automatisation du processus médical en **trois phases** : laboratoire, soins, consultation.
- Création d'un **tableau de bord interactif** pour le personnel médical.
- **Notification par e-mail** des résultats pour les étudiants.

---

## 2. Fonctionnalités Principales

### 2.1 Accès et authentification
- Connexion sécurisée pour **étudiants** et **personnel médical**.
- Validation du paiement **CNS** et **GPI** avant accès.
- Gestion des rôles : **administrateurs**, **médecins**, **assistants**.

### 2.2 Gestion des étudiants
- Création et mise à jour des **profils étudiants** (nom, GPI, CNS…).
- Historique des **visites médicales** avec suivi en ligne.

### 2.3 Suivi des visites médicales
#### Phases des examens :
1. **Laboratoire** : Enregistrement et sauvegarde des tests médicaux.
2. **Soins** : Analyse des maladies contagieuses et recommandations.
3. **Consultation médicale** : Diagnostic et prescription de traitements.

### 2.4 Notifications et communication
- **Notification par e-mail** après chaque étape.
- Envoi des **résultats médicaux** et **certificats de validation**.

### 2.5 Tableau de bord
- Suivi **en temps réel** des examens en cours.
- **Statistiques** et exportation des données en PDF/Excel.

---

## 3. Technologies et Architecture

### 3.1 Backend
- **Django** : Framework principal.
- **SQLite/PostgreSQL** : Base de données.

### 3.2 Frontend
- **HTML, CSS, JavaScript**, avec **Bootstrap**.

### 3.3 Notifications
- Envoi d’e-mails via **django.core.mail**.

### 3.4 Sécurité
- **Authentification** sécurisée et **gestion des sessions**.
- **Chiffrement des mots de passe** pour protection des données.

---

## 4. Installation et Déploiement

### 4.1 Prérequis
- **Python** installé
- **Django** installé (`pip install django`)
- Base de données **SQLite/PostgreSQL** configurée

### 4.2 Installation
1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/monprojet.git
   cd monprojet
