function afficherSection(sectionID) {
    document.querySelectorAll('.section').forEach(section => section.classList.add('hidden'));
    document.getElementById(sectionID).classList.remove('hidden');

    // Modification du titre de la page
    document.getElementById('titrePage').innerText = 
        sectionID === 'rdvEnAttente' ? "📅 Gestion des Rendez-vous (En attente)" : "📅 Gestion des Rendez-vous (Confirmés)";
}

function annulerRdv(id) {
    if (confirm("Voulez-vous vraiment annuler ce rendez-vous ?")) {
        fetch("liste_rdv.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `annuler_rdv=1&rdv_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Rendez-vous annulé !");
                location.reload(); // Actualisation rapide
            } else {
                alert("Erreur lors de l'annulation : " + data.error);
            }
        });
    }
}
