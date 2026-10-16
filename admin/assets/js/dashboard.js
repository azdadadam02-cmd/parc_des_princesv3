/* Pilotage BI — montants réels, périodes vides complétées côté serveur. */
(() => {
  'use strict';
  const node = document.getElementById('bi-data');
  if (!node) return;
  const data = JSON.parse(node.textContent);
  const empty = (id, message) => {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const panel = document.createElement('p');
    panel.className = 'chart-empty'; panel.textContent = message;
    canvas.replaceWith(panel);
  };
  if (typeof Chart === 'undefined') {
    ['financeChart','reservationsChart','etatChart','topChart'].forEach(id => empty(id, 'Graphique indisponible. Consultez les données détaillées ci-dessous.'));
    document.querySelectorAll('.chart-data').forEach(el => { el.open = true; });
    return;
  }
  const money = new Intl.NumberFormat('fr-MA', {maximumFractionDigits:2});
  const labels = data.serie.map(row => {
    const [year,month,day] = row.periode.split('-');
    return data.journalier ? `${day}/${month}` : new Intl.DateTimeFormat('fr-FR', {month:'short',year:'2-digit',timeZone:'UTC'}).format(new Date(Date.UTC(+year,+month-1,1)));
  });
  const recettes = data.serie.map(row => Number(row.recettes));
  const depenses = data.serie.map(row => Number(row.depenses));
  const res = data.serie.map(row => Number(row.reservations));
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  Chart.defaults.color = '#94a3b8';
  Chart.defaults.font.family = "'Segoe UI', Tahoma, sans-serif";
  Chart.defaults.font.size = 11;
  Chart.defaults.borderColor = 'rgba(255,255,255,.055)';
  const options = (currency = false) => ({
    responsive:true, maintainAspectRatio:false, animation:reduced ? false : {duration:550},
    interaction:{mode:'index',intersect:false},
    plugins:{legend:{display:false},tooltip:{backgroundColor:'#211b42',titleColor:'#fff',bodyColor:'#e2e8f0',padding:12,cornerRadius:10,displayColors:true,
      callbacks:{label:ctx => `${ctx.dataset.label}: ${money.format(ctx.parsed.y)}${currency?' DH':''}`}}},
    scales:{x:{grid:{display:false},border:{display:false},ticks:{maxRotation:0,autoSkip:true,maxTicksLimit:12}},
      y:{beginAtZero:true,border:{display:false},ticks:{padding:10, ...(currency ? {callback:v=>money.format(v)+' DH'} : {precision:0})}}}
  });
  const hasFinance = recettes.some(v=>v!==0)||depenses.some(v=>v!==0);
  if (hasFinance) new Chart(document.getElementById('financeChart'), {
    type:'bar', data:{labels,datasets:[
      {label:'Recettes',data:recettes,backgroundColor:'rgba(34,211,238,.8)',borderRadius:5,maxBarThickness:32,order:2},
      {label:'Dépenses',data:depenses,backgroundColor:'rgba(251,113,133,.8)',borderRadius:5,maxBarThickness:32,order:2},
      {type:'line',label:'Résultat net',data:recettes.map((v,i)=>+(v-depenses[i]).toFixed(2)),borderColor:'#a78bfa',backgroundColor:'#a78bfa',borderWidth:2.5,pointRadius:labels.length>20?0:4,pointHoverRadius:6,tension:0.25,order:1}
    ]},options:options(true)
  }); else empty('financeChart','Aucune recette ni dépense enregistrée sur cette période.');
  if (res.some(v=>v>0)) new Chart(document.getElementById('reservationsChart'),{
    type:'line',data:{labels,datasets:[{label:'Réservations',data:res,borderColor:'#818cf8',backgroundColor:'rgba(129,140,248,.12)',fill:true,borderWidth:2.5,tension:.3,pointRadius:labels.length>20?0:4,pointHoverRadius:6}]},options:options()
  }); else empty('reservationsChart','Aucune réservation créée sur cette période.');
  const stateColor = state => {
    const s = state.toLocaleLowerCase('fr');
    if (s.includes('maintenance')) return '#fbbf24';
    if (s.includes('hors') || s.includes('fermé') || s.includes('indisponible')) return '#fb7185';
    if (s.includes('disponible')) return '#34d399';
    if (s.includes('fonctionnement')) return '#38bdf8';
    return '#a78bfa';
  };
  if (data.etats.length) new Chart(document.getElementById('etatChart'),{
    type:'doughnut',data:{labels:data.etats.map(x=>x.etat),datasets:[{data:data.etats.map(x=>Number(x.total)),backgroundColor:data.etats.map(x=>stateColor(x.etat)),borderWidth:4,borderColor:'#13112c',hoverOffset:5}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'72%',animation:reduced?false:{duration:550},plugins:{legend:{position:'bottom',labels:{padding:16,usePointStyle:true,pointStyle:'circle',boxWidth:7}},tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${ctx.parsed} jeu(x)`}}}}
  }); else empty('etatChart','Aucun jeu enregistré.');
  if (data.top.length && data.top.some(x=>Number(x.montant)>0)) {
    const opts = options(true); opts.indexAxis='y';
    opts.interaction={mode:'nearest',intersect:false};
    opts.scales={x:{beginAtZero:true,border:{display:false},ticks:{callback:v=>money.format(v)+' DH',maxTicksLimit:6}},y:{grid:{display:false},border:{display:false},ticks:{callback:function(v){const label=this.getLabelForValue(v);return label.length>30?label.slice(0,28)+'…':label;}}}};
    opts.plugins.tooltip.callbacks.label=ctx=>`Ventes attribuées: ${money.format(ctx.parsed.x)} DH`;
    new Chart(document.getElementById('topChart'),{type:'bar',data:{labels:data.top.map(x=>x.nom),datasets:[{label:'Ventes attribuées',data:data.top.map(x=>Number(x.montant)),backgroundColor:['#818cf8','#9081e9','#a78bfa','#b7a2f5','#c4b5fd'],borderRadius:6,maxBarThickness:26}]},options:opts});
  } else empty('topChart','Aucune vente de billet associée à un jeu sur cette période.');
})();
