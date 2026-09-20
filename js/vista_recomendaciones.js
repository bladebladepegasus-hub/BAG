'use strict';
const formulario=document.querySelector('#recomendar-form');
async function recomendar(){const b=formulario.querySelector('button');b.disabled=true;document.querySelector('#resultados').replaceChildren();BAG.aviso('Consultando catálogos para tus recomendaciones…');try{const d=await BAG.api('recomendaciones.php?'+new URLSearchParams(new FormData(formulario)));BAG.fuentes(d.sources);BAG.tarjetas(d.games,document.querySelector('#resultados'));BAG.aviso(d.message);}catch(e){BAG.aviso(e.message,true);}finally{b.disabled=false;}}
formulario.addEventListener('submit',e=>{e.preventDefault();recomendar();});
BAG.ready.then(s=>{if(!s)return;for(const k of ['clasificacion_maxima','plataforma','genero','modo_juego'])formulario.elements[k].value=s.profile[k]??(k==='clasificacion_maxima'?'A':'');recomendar();});
