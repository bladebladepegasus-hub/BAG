'use strict';
const formulario=document.querySelector('#chat-form');
const input=document.querySelector('#mensaje');
const mensajes=document.querySelector('#mensajes');
const bienvenida=document.querySelector('#bienvenida').cloneNode(true);
const estado=document.querySelector('.chat-heading .status');
let ocupado=false;

function conectarRapidas() {
    document.querySelectorAll('[data-pregunta]').forEach(b=>b.addEventListener('click',()=>enviar(b.dataset.pregunta)));
}

function mostrarMensaje(emisor,texto,datos=null,fecha=new Date().toISOString()) {
    document.querySelector('#bienvenida')?.remove();
    const m=BAG.el('article','message '+(emisor==='usuario'?'user':'assistant'));
    const contenido=BAG.el('div','message-content');
    contenido.append(BAG.textoRespuesta(texto,datos));
    BAG.extras(datos,contenido);
    if(datos?.engine) contenido.append(BAG.el('small','engine-label',datos.engine==='openai'?'IA · '+datos.model+(datos.web_searched?' · Búsqueda web':''):'Filtro de seguridad de B.A.G'));
    const hora=new Date(fecha.includes('T')?fecha:fecha.replace(' ','T'));
    contenido.append(BAG.el('time','',hora.toLocaleTimeString(BAGConfig.locale,{hour:'2-digit',minute:'2-digit'})));
    if(emisor==='bag')m.append(BAG.el('span','message-avatar','🎮'));
    m.append(contenido);mensajes.append(m);mensajes.scrollTop=mensajes.scrollHeight;
    return m;
}

async function enviar(texto) {
    if(ocupado||!texto.trim())return;
    ocupado=true;
    const botones=[formulario.querySelector('button'),document.querySelector('#nuevo-chat'),document.querySelector('#chat-nuevo')];
    botones.forEach(b=>b.disabled=true);
    BAG.aviso('');
    const typing=BAG.el('div','typing','B.A.G está pensando y consultando información…');
    let pendiente=null;
    try {
        const s=await BAG.ready;
        if(!s)throw new Error('No se pudo iniciar la sesión. Revisa MySQL y recarga la página.');
        pendiente=mostrarMensaje('usuario',texto);
        mensajes.append(typing);mensajes.scrollTop=mensajes.scrollHeight;
        input.value='';
        const d=await BAG.api('chat.php',{message:texto});
        typing.remove();
        if(d.new_chat){mensajes.replaceChildren();mostrarMensaje('usuario',texto);}
        mostrarMensaje('bag',d.message,d,d.time);
        if(d.engine==='openai'){estado.textContent='IA conectada';estado.className='status';}
        input.style.height='44px';
    } catch(e) {
        typing.remove();pendiente?.remove();
        BAG.aviso(e.message,true);input.value=texto;
        if(!mensajes.children.length){mensajes.append(bienvenida.cloneNode(true));conectarRapidas();}
        if(e.code?.startsWith('IA_')){estado.textContent=e.code==='IA_SIN_CLAVE'?'IA sin configurar':'IA no disponible';estado.className='status offline';}
    } finally {
        ocupado=false;botones.forEach(b=>b.disabled=false);input.focus();
    }
}

formulario.addEventListener('submit',e=>{e.preventDefault();enviar(input.value);});
input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();enviar(input.value);}});
input.addEventListener('input',()=>{input.style.height='44px';input.style.height=Math.min(input.scrollHeight,130)+'px';});
document.addEventListener('bag:nuevo',()=>{mensajes.replaceChildren(bienvenida.cloneNode(true));conectarRapidas();BAG.aviso('');input.value='';input.focus();});
document.querySelector('#chat-nuevo').addEventListener('click',()=>document.querySelector('#nuevo-chat').click());
conectarRapidas();

BAG.ready.then(async s=>{
    if(!s){estado.textContent='Sin conexión';estado.className='status offline';return;}
    estado.textContent=s.ai.configured?'IA lista para conectar':'IA sin configurar';
    estado.className='status pending';
    if(!s.ai.configured){
        const aviso=BAG.el('div','notice ia-config','La IA necesita una clave de OpenAI para responder. ');
        const enlace=BAG.el('a','','Cómo activar la IA');enlace.href='vista_ayuda.html#activar-ia';
        aviso.append(enlace);document.querySelector('.chat-heading').after(aviso);
    }
    try {
        if(s.conversation_id){const d=await BAG.api('historial.php?id='+s.conversation_id);d.messages.forEach(m=>mostrarMensaje(m.emisor,m.mensaje,m.datos,m.fecha));}
        const q=new URLSearchParams(location.search).get('q');
        if(q){input.value=q;history.replaceState(null,'','vista_chat.html');await enviar(q);}
    } catch(e){BAG.aviso(e.message,true);}
});
