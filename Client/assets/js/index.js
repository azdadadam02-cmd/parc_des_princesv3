function toggleNotifications() {
    var dropdown = document.getElementById("notifDropdown");
    dropdown.classList.toggle("show");
    
    var dot = document.getElementById("redDot");
    if(dot) {
        dot.style.display = 'none';
        
        fetch(CLIENT_URL + '/actions/marquer_lu.php')
            .then(response => response.text())
            .then(data => console.log("Notifications lues."));
    }
}

document.getElementById('panierDropdown').addEventListener('click', function(event) {
    let btnSupprimer = event.target.closest('.btn-supprimer');

    if (btnSupprimer) {
        event.preventDefault();

        let index = btnSupprimer.getAttribute('data-index');

        fetch(CLIENT_URL + '/actions/supprimer_panier.php?index=' + index)
        .then(response => response.text())
        .then(html => {
            let parser = new DOMParser();
            let doc = parser.parseFromString(html, 'text/html');
            let newPanier = doc.getElementById('panierDropdown').innerHTML;
            document.getElementById('panierDropdown').innerHTML = newPanier;
        })
        .catch(error => {
            console.error('Erreur lors de la suppression :', error);
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tiket-tab');
    const cards = document.querySelectorAll('.tiket-card');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // 1. N7iydo s-ster (active) mn ga3 les tabs w n3tiwh l hada li cliquina 3lih
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // 2. N-filtriw les cartes 3la 7sab l'catégorie
            const selectedCat = this.getAttribute('data-cat');
            cards.forEach(card => {
                if (selectedCat === 'all' || card.getAttribute('data-cat') === selectedCat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
