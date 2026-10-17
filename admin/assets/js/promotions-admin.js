(() => {
 const data=document.getElementById('promo-games-data');if(!data)return;
 const games=JSON.parse(data.textContent);
 const field=id=>document.getElementById(id);
 const fmt=n=>n.toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' DH';
 function update(){
  const list=games[field('id_offre').value]||[];const box=field('jeux-inclus');box.replaceChildren();
  if(!list.length){box.textContent='Aucun jeu. Sélectionnez une annonce contenant des jeux.';field('tarif-apercu').textContent='Aperçu indisponible.';return;}
  list.forEach(g=>{const tag=document.createElement('span');tag.className='promo-game';tag.textContent=g.nom+' · '+fmt(Number(g.tarif));box.append(tag);});
  const base=list.reduce((sum,g)=>sum+Math.round(Number(g.tarif)*100),0);
  const v=Math.max(0,Number(field('valeur').value)||0),min=Math.max(1,Number(field('condition_min').value)||1);
  const type=field('type_promo').value;
  let cents=type==='forfait'?Math.round(v*100):type==='pourcentage'?Math.round(base*(100-Math.min(100,v))/100):Math.max(0,base-Math.round(v*100));
  field('tarif-apercu').textContent='Aperçu : '+fmt(cents/100)+' par personne · '+fmt(cents*min/100)+' pour '+min+' personne(s).';
 }
 ['id_offre','type_promo','valeur','condition_min'].forEach(id=>{field(id).addEventListener('input',update);field(id).addEventListener('change',update);});update();
})();
