var panierActuel = window.panierActuel || {};

function refreshModalState(idJeu, capacite) {
    var select = document.getElementById("modalQuantite");
    var btn = document.querySelector(".btn-ticket");
    var deja = panierActuel[idJeu] ? parseInt(panierActuel[idJeu]) : 0;
    var reste = parseInt(capacite) - deja;

    select.innerHTML = "";
    btn.style.pointerEvents = "auto";
    btn.style.backgroundColor = "";

    if (reste <= 0) {
        select.disabled = true;
        btn.disabled = true;
        btn.classList.add("btn-max");
        btn.innerHTML = "<i class='fas fa-ban'></i> LIMITE ATTEINTE";
    } else {
        select.disabled = false;
        btn.disabled = false;
        btn.classList.remove("btn-max");
        btn.innerHTML = "<i class='fas fa-ticket-alt'></i> PRENDRE UN TICKET";
        for (let i = 1; i <= reste; i++) {
            var o = document.createElement("option");
            o.value = i;
            o.textContent = i + (i === 1 ? " ticket" : " tickets");
            select.appendChild(o);
        }
    }
}

function openModal(nom, desc, tarif, duree, id_jeu, img_url, age_min, capacite, tarif_promo) {
    document.getElementById("modalNom").textContent = nom;
    document.getElementById("modalDesc").textContent = desc;
    var prixNormal = Number(tarif);
    var prixPromo = tarif_promo === null || tarif_promo === undefined || tarif_promo === "" ? NaN : Number(tarif_promo);
    var enPromotion = Number.isFinite(prixPromo) && prixPromo >= 0 && prixPromo < prixNormal;
    var ancienTarif = document.getElementById("modalAncienTarif");
    ancienTarif.hidden = !enPromotion;
    ancienTarif.textContent = enPromotion ? prixNormal.toFixed(2) + " MAD" : "";
    document.getElementById("modalTarif").textContent = (enPromotion ? prixPromo : prixNormal).toFixed(2);
    document.getElementById("modalDuree").textContent = duree;
    document.getElementById("modalIdJeu").value = id_jeu;
    document.getElementById("modalImg").style.backgroundImage = "url('" + img_url + "')";
    document.getElementById("modalAge").textContent = age_min;
    document.getElementById("modalCapaciteText").textContent = capacite;

    refreshModalState(id_jeu, capacite);

    var modal = document.getElementById("gameModal");
    modal.classList.add("show");
    modal.style.display = "flex";

    updatePanierData().then(function () {
        var btn = document.querySelector(".btn-ticket");
        var enCours = btn.innerHTML.indexOf("EN COURS") !== -1 || btn.innerHTML.indexOf("AJOUT") !== -1;
        var ouvert = modal.style.display === "flex";
        var memeJeu = document.getElementById("modalIdJeu").value == id_jeu;
        if (ouvert && memeJeu && !enCours) {
            refreshModalState(id_jeu, capacite);
        }
    });
}

function openModalFromCard(el) {
    var j = JSON.parse(el.dataset.jeu);
    openModal(j.nom, j.description, j.tarif, j.duree_session,
              j.id_jeu, j.img_jeu, j.age_min, j.capacite, j.tarif_promo);
}

function closeModal() {
    var modal = document.getElementById("gameModal");
    modal.classList.remove("show");
    modal.style.display = "none";
}

window.onclick = function (event) {
    var modal = document.getElementById("gameModal");
    if (event.target == modal) {
        closeModal();
    }
};

function supprimerTicket(event, index) {
    event.preventDefault();

    fetch(CLIENT_URL + "/actions/supprimer_panier.php?index=" + index, { cache: "no-store" })
        .then(r => r.text())
        .then(html => {
            var doc = new DOMParser().parseFromString(html, "text/html");
            var newPanier = doc.getElementById("panierDropdown");
            if (newPanier) {
                document.getElementById("panierDropdown").innerHTML = newPanier.innerHTML;
            }
            return updatePanierData();
        });
}

function ajouterAuPanier(event, form) {
    event.preventDefault();

    var formData = new FormData(form);
    var btn = form.querySelector(".btn-ticket");
    var idJeu = formData.get("id_jeu");
    var qte = parseInt(formData.get("quantite")) || 0;
    var capacite = document.getElementById("modalCapaciteText").textContent;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> EN COURS...';
    btn.style.pointerEvents = "none";

    fetch(CLIENT_URL + "/actions/ajouter_panier.php", { method: "POST", body: formData, cache: "no-store" })
        .then(r => r.text())
        .then(html => {
            if (html.indexOf("Erreur") !== -1 && html.indexOf("<") === -1) {
                return updatePanierData().then(() => refreshModalState(idJeu, capacite));
            }

            var doc = new DOMParser().parseFromString(html, "text/html");
            var newPanier = doc.getElementById("panierDropdown");
            if (newPanier) {
                document.getElementById("panierDropdown").innerHTML = newPanier.innerHTML;
            }

            panierActuel[idJeu] = (panierActuel[idJeu] ? parseInt(panierActuel[idJeu]) : 0) + qte;
            window.panierActuel = panierActuel;

            btn.innerHTML = '<i class="fas fa-check"></i> AJOUTÉ !';
            btn.style.backgroundColor = "#28a745";

            setTimeout(function () {
                closeModal();
                refreshModalState(idJeu, capacite);
            }, 1000);

            return updatePanierData();
        })
        .catch(function () {
            refreshModalState(idJeu, capacite);
        });
}

function updatePanierData() {
    return fetch(CLIENT_URL + "/actions/get_panier_data.php?t=" + Date.now(), { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            panierActuel = Array.isArray(data) ? Object.assign({}, data) : data;
            window.panierActuel = panierActuel;
        })
        .catch(function () {});
}
