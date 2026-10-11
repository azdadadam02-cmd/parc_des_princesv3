<div id="gameModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></span>
        <div class="modal-left" id="modalImg"></div>
        <div class="modal-right">
            <div>
                <h2 id="modalNom">Nom</h2>
                <div class="modal-info">
                    <p id="modalDesc" class="desc-text">...</p>
                    <div class="info-grid">
                        <div class="info-item"><i class="fas fa-clock"></i> <span><span id="modalDuree">0</span> min</span></div>
                        <div class="info-item"><i class="fas fa-coins"></i> <span><span id="modalTarif">0</span> MAD</span></div>
                        <div class="info-item"><i class="fas fa-user-check"></i> <span>+<span id="modalAge">0</span> ans</span></div>
                        <div class="info-item"><i class="fas fa-users"></i> <span>Max <span id="modalCapaciteText">0</span> pers.</span></div>
                    </div>
                </div>
            </div>
            <form action="actions/ajouter_panier.php" method="POST" class="ticket-form" onsubmit="ajouterAuPanier(event, this)">
                <input type="hidden" name="id_jeu" id="modalIdJeu" value="">
                <div class="ticket-selector">
                    <label for="quantite">Nombre de tickets :</label>
                    <select name="quantite" id="modalQuantite" required></select>
                </div>
                <button type="submit" class="btn-ticket"><i class="fas fa-ticket-alt"></i> PRENDRE UN TICKET</button>
            </form>
        </div>
    </div>
</div>
