<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Aide - CMS Médical</title>
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
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 2rem;
        }
        
        #searchBar {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            font-size: 1rem;
            background-color: white;
            padding-left: 50px;
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 3rem;
        }
        
        .help-item {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .help-item:hover {
            transform: translateY(-5px);
        }
        
        .help-item h2 {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .faq-content {
            padding: 20px;
            background-color: white;
            border-top: 1px solid var(--light);
            display: none;
        }
        
        .faq-content p {
            margin-bottom: 1rem;
        }
        
        .faq-content ul {
            padding-left: 20px;
            margin-bottom: 1rem;
        }
        
        .contact-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-top: 30px;
            text-align: center;
        }
        
        .contact-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .contact-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .contact-link:hover {
            color: var(--primary-light);
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }
        
        @media (max-width: 768px) {
            .help-grid {
                grid-template-columns: 1fr;
            }
            
            header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1><i class="fas fa-question-circle"></i> Centre d'Aide</h1>
        <p>Trouvez des réponses à toutes vos questions sur notre plateforme médicale</p>
    </div>
</header>

<div class="container">
    <!-- Barre de recherche améliorée -->
    <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchBar" placeholder="Rechercher dans l'aide..." onkeyup="filterHelp()">
    </div>
    
    <!-- Système de FAQ amélioré -->
    <div id="helpSections" class="help-grid">
        <div class="help-item">
            <h2 onclick="toggleFAQ(this)"><i class="fas fa-file-medical"></i> Certificats médicaux</h2>
            <div class="faq-content">
                <p><strong>Comment demander un certificat médical ?</strong></p>
                <ol>
                    <li>Accédez à l'onglet "Certificats"</li>
                    <li>Sélectionnez un médecin dans la liste</li>
                    <li>Remplissez le formulaire avec les détails de votre demande</li>
                    <li>Soumettez votre demande</li>
                </ol>
                <p><strong>Délai d'obtention :</strong> Généralement 48h après validation par le médecin.</p>
            </div>
        </div>

        <div class="help-item">
            <h2 onclick="toggleFAQ(this)"><i class="fas fa-calendar-check"></i> Rendez-vous</h2>
            <div class="faq-content">
                <p><strong>Prendre un rendez-vous :</strong></p>
                <ul>
                    <li>Consultez les disponibilités des médecins</li>
                    <li>Sélectionnez un créneau horaire</li>
                    <li>Confirmez votre rendez-vous</li>
                </ul>
                <p><strong>Annulation :</strong> Possible jusqu'à 24h avant le rendez-vous.</p>
            </div>
        </div>

        <div class="help-item">
            <h2 onclick="toggleFAQ(this)"><i class="fas fa-file-invoice-dollar"></i> Paiements</h2>
            <div class="faq-content">
                <p><strong>Modes de paiement acceptés :</strong></p>
                <ul>
                    <li>Express Union</li>
                    <li>Mobile money</li>
                    <li>Espèces (uniquement sur place)</li>
                </ul>
                <p><strong>Factures :</strong> Disponibles dans votre espace personnel après paiement.</p>
            </div>
        </div>

        <div class="help-item">
            <h2 onclick="toggleFAQ(this)"><i class="fas fa-user-cog"></i> Profil utilisateur</h2>
            <div class="faq-content">
                <p><strong>Modifier vos informations :</strong></p>
                <ol>
                    <li>Cliquez sur votre photo de profil</li>
                    <li>Sélectionnez "Modifier le profil"</li>
                    <li>Mettez à jour les informations nécessaires</li>
                    <li>Enregistrez les modifications</li>
                </ol>
                <p>Les informations médicales sensibles ne peuvent être modifiées que par un médecin.</p>
            </div>
        </div>

        <div class="help-item">
            <h2 onclick="toggleFAQ(this)"><i class="fas fa-lock"></i> Sécurité et confidentialité</h2>
            <div class="faq-content">
                <p><strong>Protection des données :</strong></p>
                <p>Toutes vos données médicales sont cryptées et protégées conformément à la réglementation en vigueur.</p>
                <p><strong>Mot de passe oublié :</strong> Utilisez la fonction "Mot de passe oublié" sur la page de connexion.</p>
            </div>
        </div>

        <div class="help-item">
            <h2 onclick="toggleFAQ(this)"><i class="fas fa-mobile-alt"></i> Application mobile</h2>
            <div class="faq-content">
                <p><strong>Disponibilité :</strong></p>
                <p>Notre application n'est pas encore disponible mais sera dans bientot sur iOS et Android. Téléchargez-la depuis les stores officiels.</p>
                <p><strong>Fonctionnalités :</strong></p>
                <ul>
                    <li>Prise de rendez-vous</li>
                    <li>Consultation des résultats</li>
                    <li>Messagerie sécurisée</li>
                </ul>
        
            </div>
        </div>
    </div>
    
    <!-- Carte de contact -->
    <div class="contact-card">
        <h2><i class="fas fa-headset"></i> Besoin d'aide supplémentaire ?</h2>
        <p>Notre équipe est disponible pour répondre à vos questions.</p>
        
        <div class="contact-links">
            <a href="contact.php" class="contact-link">
                <i class="fas fa-envelope"></i> Nous contacter
            </a>
            <a href="tel:+237 6 99 96 96 54" class="contact-link">
            <a href="tel:+237 6 99 33 02 02" class="contact-link">
                <i class="fas fa-phone"></i> Appeler le support
            </a>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2025 CMS Médical - Université de Ngaoundéré. Tous droits réservés.</p>
    </div>
</footer>

<script>
    // Filtrage amélioré
    function filterHelp() {
        const input = document.getElementById("searchBar").value.toLowerCase();
        const items = document.querySelectorAll(".help-item");
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const isMatch = text.includes(input);
            
            item.style.display = isMatch ? "block" : "none";
            
            if (isMatch && input.length > 2) {
                // Mise en évidence des résultats
                const content = item.querySelector(".faq-content");
                if (content) {
                    content.style.display = "block";
                    item.style.boxShadow = "0 0 10px rgba(37, 99, 235, 0.3)";
                }
            } else {
                item.style.boxShadow = "";
            }
        });
    }
    
    // FAQ améliorée
    function toggleFAQ(element) {
        const content = element.nextElementSibling;
        const allContents = document.querySelectorAll(".faq-content");
        
        // Fermer tous les autres éléments
        allContents.forEach(item => {
            if (item !== content) {
                item.style.display = "none";
            }
        });
        
        // Basculer l'élément actuel
        content.style.display = content.style.display === "block" ? "none" : "block";
        
        // Animation
        if (content.style.display === "block") {
            content.style.animation = "fadeIn 0.3s ease-out";
        }
    }
    
    // Ouvrir le premier élément par défaut
    document.addEventListener("DOMContentLoaded", function() {
        const firstItem = document.querySelector(".help-item h2");
        if (firstItem) {
            toggleFAQ(firstItem);
        }
    });
</script>

</body>
</html>