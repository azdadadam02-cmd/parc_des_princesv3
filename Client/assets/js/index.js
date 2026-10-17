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
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

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

document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tiket-tab');
        const cards = document.querySelectorAll('.tiket-card');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const selectedCat = this.getAttribute('data-cat').trim().toUpperCase();
                cards.forEach(card => {
                    const cardCat = (card.getAttribute('data-cat') || '').trim().toUpperCase();
                    if (cardCat === selectedCat) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('ajout') === 'success') {
            const panier = document.getElementById('panierDropdown');
            if (panier) {
                panier.classList.add('show');
            }
        }
    });
    function ouvrirModalDetails(btn) {
    const titre = btn.getAttribute('data-titre');
    const desc = btn.getAttribute('data-desc');
    const jeuxStr = btn.getAttribute('data-jeux');

    document.getElementById('modalTicketTitre').textContent = titre;
    document.getElementById('modalTicketDesc').textContent = desc;

    const ul = document.getElementById('modalTicketJeux');
    ul.innerHTML = '';

    if (jeuxStr && jeuxStr.trim() !== '') {
        const jeuxArr = jeuxStr.split('|');
        jeuxArr.forEach(j => {
            const li = document.createElement('li');
            li.textContent = j;
            ul.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = 'Accès libre aux jeux de la catégorie';
        ul.appendChild(li);
    }

    document.getElementById('ticketDetailsModal').classList.add('show');
}

function fermerModalDetails(event) {
    if (event.target.id === 'ticketDetailsModal') {
        document.getElementById('ticketDetailsModal').classList.remove('show');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('afficheTrack');
    if (!track) return;

    const slides = track.querySelectorAll('.affiche-slide');
    const totalSlides = slides.length;

    if (totalSlides <= 1) return;

    const btnNext = document.getElementById('btnNextAffiche');
    const btnPrev = document.getElementById('btnPrevAffiche');
    const dots = document.querySelectorAll('.affiche-dot');

    let currentIndex = 0;
    let autoScrollTimer = null;

    function allerASlide(index) {
        currentIndex = (index + totalSlides) % totalSlides;
        track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === currentIndex);
        });
    }

    function demarrerAutoScroll() {
        arreterAutoScroll();
        autoScrollTimer = setInterval(function() {
            allerASlide(currentIndex + 1);
        }, 5000); 
    }

    function arreterAutoScroll() {
        if (autoScrollTimer) {
            clearInterval(autoScrollTimer);
        }
    }

    if (btnNext) {
        btnNext.addEventListener('click', function() {
            allerASlide(currentIndex + 1);
            demarrerAutoScroll(); 
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function() {
            allerASlide(currentIndex - 1);
            demarrerAutoScroll();
        });
    }

    dots.forEach(dot => {
        dot.addEventListener('click', function() {
            const idx = parseInt(this.getAttribute('data-index')) || 0;
            allerASlide(idx);
            demarrerAutoScroll();
        });
    });

    demarrerAutoScroll();
});
 