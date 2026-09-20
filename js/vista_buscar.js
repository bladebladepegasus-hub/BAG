'use strict';
const formulario=document.querySelector('#buscar-form');
let buscando=false, ultimaConsulta='';
async function buscar(automatica=false){
 if(buscando)return;
 const q=automatica?ultimaConsulta:new URLSearchParams(new FormData(formulario)).toString();
 if(!q)return;
 buscando=true;const b=formulario.querySelector('button');b.disabled=true;
 if(!automatica){document.querySelector('#resultados').replaceChildren();document.querySelector('#cantidad').textContent='';}
 BAG.aviso('Consultando las tiendas conectadas…');
 try{const d=await BAG.api('buscar_juego.php?'+q);ultimaConsulta=q;BAG.fuentes(d.sources);BAG.tarjetas(d.games,document.querySelector('#resultados'));document.querySelector('#cantidad').textContent=d.games.length+' fichas encontradas en las tiendas'+(d.checked_at?' · Consultado: '+new Date(d.checked_at).toLocaleTimeString(BAGConfig.locale):'');BAG.aviso(d.warnings.join(' '));}
 catch(e){document.querySelector('#resultados').replaceChildren();document.querySelector('#cantidad').textContent='Consulta no disponible';BAG.aviso(e.message,true);}
 finally{b.disabled=false;buscando=false;}
}
formulario.addEventListener('submit',e=>{e.preventDefault();buscar();});
const inicial=new URLSearchParams(location.search);for(const k of ['q','plataforma','genero','tienda']){if(formulario.elements[k])formulario.elements[k].value=inicial.get(k)||'';}
BAG.ready.then(s=>{if(s&&formulario.elements.q.value)buscar();else BAG.aviso('Escribe un título. Fuentes conectadas: Steam, GOG, Xbox, Nintendo y Eneba.');});
setInterval(()=>{if(!document.hidden&&ultimaConsulta)buscar(true);},300000);