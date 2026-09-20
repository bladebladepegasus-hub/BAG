'use strict';
const formulario=document.querySelector('#precios-form');
let consultando=false, ultimaConsulta='';
async function comparar(automatica=false){
 if(consultando)return;
 const q=automatica?ultimaConsulta:new URLSearchParams(new FormData(formulario)).toString();
 if(!q)return;
 consultando=true;const b=formulario.querySelector('button');b.disabled=true;
 if(!automatica)document.querySelector('#resultados').replaceChildren();
 BAG.aviso('Consultando precios, plataformas y regiones…');
 try{const d=await BAG.api('comparar_precios.php?'+q);ultimaConsulta=q;BAG.fuentes(d.sources);BAG.renderPrecios(d.prices,document.querySelector('#resultados'));BAG.aviso(d.message);}
 catch(e){document.querySelector('#resultados').replaceChildren();BAG.aviso(e.message,true);}
 finally{b.disabled=false;consultando=false;}
}
formulario.addEventListener('submit',e=>{e.preventDefault();comparar();});
const inicial=new URLSearchParams(location.search);for(const k of ['q','plataforma','genero','tienda']){if(formulario.elements[k])formulario.elements[k].value=inicial.get(k)||'';}
BAG.ready.then(s=>{if(s&&formulario.elements.q.value)comparar();});
setInterval(()=>{if(!document.hidden&&ultimaConsulta)comparar(true);},300000);