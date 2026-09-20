'use strict';
BAG.ready.then(async sesion=>{if(!sesion)return;try{const d=await BAG.api('recomendaciones.php');BAG.tarjetas(d.games.slice(0,3),document.querySelector('#destacados'));document.querySelector('#criterio').textContent=d.message;}catch(e){BAG.aviso(e.message,true);}});
