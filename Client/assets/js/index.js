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
