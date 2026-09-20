'use strict';
const BAG = (() => {
    const iconos = {inicio:'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',chat:'M21 11a8 8 0 0 1-8 8H7l-5 3 2-6a8 8 0 1 1 17-5Z',buscar:'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',recomendaciones:'m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z',precios:'M20 12 12 20 3 11V3h8Zm-13-5h.01',historial:'M3 11a9 9 0 1 1 2 7M3 4v7h7m2-4v6l4 2',preferencias:'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2',ayuda:'M9 9a3 3 0 1 1 4 3c-1 .5-1 1-1 2m0 3h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',game:'M7 6h10c3 0 4 3 5 9 1 4-2 5-4 2l-2-2H8l-2 2c-2 3-5 2-4-2 1-6 2-9 5-9Zm0 3v5m-2-2h4m7-2h.01m3 3h.01',flecha:'M5 12h14m-6-6 6 6-6 6',escudo:'m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6Zm-4 9 3 3 5-5'};
    const icon = nombre => `<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="${iconos[nombre] || iconos.game}"/></svg>`;
    const nombres={inicio:'Inicio',chat:'Chat',buscar:'Buscar juegos',recomendaciones:'Recomendaciones',precios:'Comparar precios',historial:'Historial',preferencias:'Preferencias',ayuda:'Ayuda',cuenta:'Mi cuenta'};
    const pagina=document.body.dataset.page;
    const barra=document.createElement('aside'); barra.className='sidebar'; barra.id='sidebar';
    barra.innerHTML=`<a class="brand" href="vista_inicio.html"><span class="brand-icon">${icon('game')}</span><span>B.A.G<small>Bot Assistant in Gaming</small></span></a><button class="new-chat" id="nuevo-chat">＋ &nbsp; Nuevo chat</button><div class="nav-label">TU UNIVERSO GAMER</div><nav class="nav-links" aria-label="Navegación principal">${Object.entries(nombres).map(([id,n])=>`<a class="nav-link ${id===pagina?'active':''}" ${id===pagina?'aria-current="page"':''} href="vista_${id}.html">${icon(id)}${n}</a>`).join('')}</nav><div class="sidebar-bottom"><div class="tag">✦ &nbsp; ENCUENTRA. COMPARA. JUEGA.</div><a class="session-card" href="vista_cuenta.html"><span class="avatar-small">J</span><div><strong id="nombre-usuario">Jugador invitado</strong><small id="estado-cuenta">Iniciar sesión / Crear cuenta ↗</small></div></a></div>`;
    document.body.prepend(barra);
    const main=document.querySelector('main');
    const top=document.createElement('header');top.className='topbar';
    top.innerHTML=`<button class="mobile-menu" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">☰</button><div class="breadcrumbs">Workspace <span>/ &nbsp; ${nombres[pagina]}</span></div><a class="status" id="clasificacion-actual" href="vista_preferencias.html">Clasificación máxima: A</a>`;
    main.prepend(top);
    document.querySelector('.mobile-menu').addEventListener('click',()=>{const open=barra.classList.toggle('open');document.querySelector('.mobile-menu').setAttribute('aria-expanded',String(open));});
    document.addEventListener('click',e=>{if(barra.classList.contains('open')&&!barra.contains(e.target)&&!e.target.closest('.mobile-menu')) {barra.classList.remove('open');document.querySelector('.mobile-menu').setAttribute('aria-expanded','false');}});
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){barra.classList.remove('open');document.querySelector('.mobile-menu').setAttribute('aria-expanded','false');}});
    document.querySelectorAll('[data-icon]').forEach(e=>e.innerHTML=icon(e.dataset.icon));
    window.addEventListener('pageshow',e=>{if(e.persisted)location.reload();});
    window.addEventListener('storage',e=>{if(['bag-account-change','bag-settings-change'].includes(e.key))location.reload();});
    let csrf='';
    async function api(ruta, datos) {
        const opciones={credentials:'same-origin',headers:{Accept:'application/json'}};
        if(datos!==undefined){opciones.method='POST';opciones.headers['Content-Type']='application/json';opciones.headers['X-CSRF-Token']=csrf;opciones.body=JSON.stringify(datos);}
        let respuesta;
        try { respuesta=await fetch('../php/'+ruta,opciones); } catch { throw new Error('No hay conexión con B.A.G. Revisa que Apache esté iniciado e inténtalo de nuevo.'); }
        let json;
        try {json=await respuesta.json();} catch {throw new Error('No pude leer la respuesta. Abre B.A.G desde localhost con Apache y PHP.');}
        if(!respuesta.ok||!json.success){const error=new Error(json.message||'No pude completar la solicitud. Inténtalo de nuevo.');error.code=json.code;throw error;}
        return json;
    }
    function el(tag,clase,texto){const e=document.createElement(tag);if(clase)e.className=clase;if(texto!==undefined)e.textContent=texto;return e;}
    function aviso(texto,error=false,destino=document.querySelector('#aviso')){if(!destino){toast(texto);return;}destino.textContent=texto;destino.className='notice'+(error?' error':'');destino.hidden=!texto;}
    function toast(texto){document.querySelector('.toast')?.remove();const e=el('div','toast',texto);e.setAttribute('role','status');document.body.append(e);setTimeout(()=>e.remove(),6000);}
    const ready=api('preferencias.php').then(d=>{csrf=d.csrf;BAGConfig.aplicar(d.profile);const region=el('a','status region-status',BAGConfig.regiones[d.profile.region]);region.href='vista_preferencias.html';region.id='region-actual';top.append(region);document.querySelector('#clasificacion-actual').textContent='Clasificación máxima: '+d.profile.clasificacion_maxima;document.querySelector('#nombre-usuario').textContent=d.profile.nombre;document.querySelector('#estado-cuenta').textContent=d.account.authenticated?'Mi cuenta · Sesión iniciada ↗':'Iniciar sesión / Crear cuenta ↗';if(['historial','preferencias'].includes(pagina)){const nota=el('div','notice account-notice');nota.append(document.createTextNode(d.account.authenticated?'Guardado en tu cuenta.':'Estás en modo invitado. Crea una cuenta para conservar estos datos y recuperarlos en otra sesión. '));if(!d.account.authenticated){const a=el('a','','Crear cuenta ↗');a.href='vista_cuenta.html?modo=registro';nota.append(a);}document.querySelector('.page-heading').after(nota);}return d;}).catch(e=>{aviso(e.message,true);return null;});
    async function nuevoChat(){await ready;const d=await api('nueva_conversacion.php',{});if(pagina==='chat'){document.dispatchEvent(new CustomEvent('bag:nuevo',{detail:d}));}else location.href='vista_chat.html';}
    document.querySelector('#nuevo-chat').addEventListener('click',async e=>{const b=e.currentTarget;b.disabled=true;try{await nuevoChat();}catch(err){aviso(err.message,true);}finally{b.disabled=false;}});
    function imagen(j,clase=''){const img=el('img',clase);img.alt='Portada de '+j.nombre;img.loading='lazy';img.src=segura(j.imagen)?j.imagen:'../css/portada.svg';img.addEventListener('error',()=>{img.src='../css/portada.svg';},{once:true});return img;}
    function segura(url){if(typeof url!=='string')return false;if(url==='../css/portada.svg')return true;try{return new URL(url).protocol==='https:';}catch{return false;}}
    function tarjetas(juegos,destino){destino.replaceChildren();if(!juegos?.length){destino.append(el('div','empty','No encontré juegos con esos filtros. Prueba otro nombre, género o plataforma.'));return;}
        juegos.forEach(j=>{const card=el('article','game-card');const cover=el('div','cover');cover.append(imagen(j),el('span','tag '+(j.fuente==='DEMO'?'demo':''),j.fuente==='DEMO'?'DATOS DEMO':j.fuente));if(j.imagen==='../css/portada.svg')cover.append(el('span','cover-name',j.nombre));const body=el('div','game-body');body.append(el('h3','',j.nombre),el('div','game-meta',j.generos.join(' · ')+' / '+j.clasificacion+(j.tipo==='dlc'?' · Expansión / DLC':'')),el('p','',j.descripcion.length>125?j.descripcion.slice(0,125)+'…':j.descripcion));const platforms=el('div','platforms');j.plataformas.forEach(p=>platforms.append(el('span','',p)));body.append(platforms);if(j.categoria_filtro)body.append(el("p","game-meta","Categoría de filtro: "+j.categoria_filtro+" · Original: "+j.clasificacion));if(j.region)body.append(el("p","game-meta","Región: "+j.region));if(Number.isFinite(j.precio)&&j.moneda)body.append(el("strong","game-price",(j.precio_desde?"Desde ":"")+dinero(j.precio,j.moneda)));if(j.formato)body.append(el("p","game-meta",j.formato));if(j.fuente==="Nintendo")body.append(el("p","game-meta","El mínimo puede corresponder a una mejora que requiere el juego base."));if(j.motivo)body.append(el("p","recommend-reason",j.motivo));if(j.advertencia)body.append(el('p','warning',j.advertencia));const actions=el('div','card-actions');const det=el('button','btn small','Ver detalles');det.addEventListener('click',()=>detalle(j.id,j.nombre));const price=el('a','btn small ghost','Comparar precios ↗');price.href='vista_precios.html?q='+encodeURIComponent(j.nombre);actions.append(det,price);if(j.url&&segura(j.url)){const original=el("a","btn small ghost","Ver en "+j.fuente+" ↗");original.href=j.url;original.target="_blank";original.rel="noopener noreferrer";actions.append(original);}body.append(actions);card.append(cover,body);destino.append(card);});
    }
    async function detalle(id,nombre=""){try{const d=await api('detalle_juego.php?id='+encodeURIComponent(id)+'&q='+encodeURIComponent(nombre));const j=d.game;let modal=document.querySelector('#detalle');if(!modal){modal=el('dialog');modal.id='detalle';document.body.append(modal);}modal.replaceChildren();const cerrar=el('button','btn small dialog-close','Cerrar ✕');cerrar.addEventListener('click',()=>modal.close());const title=el('h2','',j.nombre);title.id='detalle-titulo';modal.setAttribute('aria-labelledby',title.id);modal.append(cerrar,title,imagen(j,'detail-image'),el('p','detail-description',j.descripcion));const meta=el('div','detail-meta');Object.entries({'Género':j.generos.join(', '),'Plataformas':j.plataformas.join(', '),'Clasificación':j.clasificacion,'Lanzamiento inicial':j.fecha,'Modo de juego':j.multijugador===null?'No disponible':((j.solo?'Un jugador':'')+(j.solo&&j.multijugador?' y ':'')+(j.multijugador?'Multijugador':'')),'Región':j.region||'México','Formato':j.formato||'Digital','Fuente':j.fuente==='DEMO'?'Datos de demostración':j.fuente}).forEach(([k,v])=>{const e=el('div');e.append(el('strong','',k),document.createTextNode(v));meta.append(e);});modal.append(meta,el('p','warning',j.advertencia||j.nota));if(j.fuente==='RAWG'){const a=el('a','btn small','Fuente: RAWG ↗');a.href='https://rawg.io/games/'+String(j.id).replace('rawg-','');a.target='_blank';a.rel='noopener noreferrer';modal.append(a);}if(j.url&&segura(j.url)){const a=el('a','btn small','Ver ficha original en '+j.fuente+' ↗');a.href=j.url;a.target='_blank';a.rel='noopener noreferrer';modal.append(a);}if(j.contenido)modal.append(el('p','detail-description',j.contenido));modal.showModal();}catch(e){toast(e.message);}}
    function dinero(valor,moneda){return new Intl.NumberFormat(BAGConfig.locale,{style:'currency',currency:moneda,currencyDisplay:'code'}).format(valor);}
    function renderPrecios(lista,destino){
        destino.replaceChildren();
        if(!lista?.length){destino.append(el('div','empty','No encontramos ese título en las fuentes consultadas con esos filtros. Prueba el nombre oficial u otra tienda.'));return;}
        lista.forEach(p=>{
            const panel=el('section','panel price-panel');
            if(p.imagen)panel.append(imagen({nombre:p.juego,imagen:p.imagen},'price-cover'));
            panel.append(el('h3','',p.juego),el('span','tag',p.fuente),el('p','',p.nota));if(p.clasificacion)panel.append(el('p','game-meta','Clasificación de la fuente: '+p.clasificacion));if(p.advertencia)panel.append(el('p','warning',p.advertencia));
            const wrap=el('div','table-wrap'),table=el('table'),head=el('thead'),row=el('tr');
            ['Plataforma','Tienda','Región / formato','Precio','Ficha original'].forEach(x=>row.append(el('th','',x)));head.append(row);table.append(head);
            const tbody=el('tbody');
            p.filas.forEach(f=>{
                const tr=el('tr');tr.append(el('td','',f.plataforma),el('td','',f.tienda),el('td','',(f.region||'No especificada')+' · '+(f.formato||'Digital')),el('td','',Number.isFinite(f.precio)&&f.moneda?(f.precio_desde?'Desde ':'')+dinero(f.precio,f.moneda):'Sin precio publicado para esta región'));
                const td=el('td');if(f.url&&segura(f.url)){const a=el('a','btn small','Ver en '+f.tienda+' ↗');a.href=f.url;a.target='_blank';a.rel='noopener noreferrer';td.append(a);}else td.textContent='—';tr.append(td);tbody.append(tr);if(f.precio_desde){const nota=el('tr');const celda=el('td','offer-note',f.condiciones);celda.colSpan=5;nota.append(celda);tbody.append(nota);}
            });table.append(tbody);wrap.append(table);
            const validas=p.filas.filter(f=>Number.isFinite(f.precio)&&f.moneda),monedas=[...new Set(validas.map(f=>f.moneda))];
            let resumen='Sin precio publicado';
            if(validas.length===1)resumen='Precio publicado en una tienda; no hay otra oferta comparable.';
            else if(validas.some(f=>f.precio_desde))resumen='Ofertas desde un importe mínimo: comprueba contenidos y cargos antes de comparar.';else if(monedas.length>1)resumen='Monedas diferentes: no se calcula un ganador sin conversión.';
            else if(new Set(validas.map(f=>[f.plataforma,f.region,f.formato].join('|'))).size>1)resumen='Plataformas, regiones o formatos diferentes: revisa cada oferta por separado.';else if(validas.length>1)resumen='Menor precio consultado: '+dinero(Math.min(...validas.map(f=>f.precio)),monedas[0]);
            const sum=el('div','price-summary');sum.append(el('span','',resumen),el('span','','Consultado: '+new Date(p.actualizacion).toLocaleString(BAGConfig.locale)));
            panel.append(wrap,sum);destino.append(panel);
        });
    }
    function enlaceFuente(url) {
        try { return ['https:', 'http:'].includes(new URL(url).protocol); } catch { return false; }
    }
    function textoRespuesta(texto, datos, clase='bubble') {
        const bloque=el('div',clase);
        const letras=Array.from(texto);
        let posicion=0;
        const citas=[...(datos?.citations || [])].sort((a,b)=>a.start-b.start);
        citas.forEach((c,i)=>{
            if(!Number.isInteger(c.start)||!Number.isInteger(c.end)||c.start<posicion||c.end<=c.start||c.end>letras.length||!enlaceFuente(c.url))return;
            bloque.append(document.createTextNode(letras.slice(posicion,c.start).join('')));
            const enlace=el('a','inline-citation','['+(i+1)+']');
            enlace.href=c.url;enlace.title=c.title;enlace.target='_blank';enlace.rel='noopener noreferrer';
            enlace.setAttribute('aria-label','Fuente: '+c.title);
            bloque.append(enlace);posicion=c.end;
        });
        bloque.append(document.createTextNode(letras.slice(posicion).join('')));
        return bloque;
    }
    function extras(datos,destino){
        if(datos?.sources?.length){
            const fuentes=el('div','response-sources');fuentes.append(el('small','','Fuentes consultadas'));
            datos.sources.forEach(s=>{if(!enlaceFuente(s.url))return;const a=el('a','source-link',s.title||'Ver fuente');a.href=s.url;a.target='_blank';a.rel='noopener noreferrer';fuentes.append(a);});
            destino.append(fuentes);
        }
        if(datos?.games?.length){const grid=el('div','grid');tarjetas(datos.games,grid);destino.append(grid);}
        if(datos?.prices?.length){const p=el('div');renderPrecios(datos.prices,p);destino.append(p);}
        if(datos?.action&&/^vista_(historial|preferencias)\.html$/.test(datos.action.url)){const a=el('a','btn small',datos.action.label);a.href=datos.action.url;destino.append(a);}
    }
    function fuentes(lista){const destino=document.querySelector('#fuentes');if(!destino)return;destino.replaceChildren();if(lista?.length)destino.append(el('p','results-caption','Fuentes: '+lista.map(s=>s.name+' ('+s.status+')').join(' · ')));}
    function opciones(){document.querySelectorAll('[data-options]').forEach(s=>{if(s.dataset.options==='clasificaciones'){Object.entries({A:'A · Todo público',B:'B · A partir de 12 años',B15:'B15 · A partir de 15 años',C:'C · Adultos (18+)',D:'D · Contenido extremo y adulto'}).forEach(([v,t])=>{const o=el('option','',t);o.value=v;s.append(o);});return;}const valores=s.dataset.options==='plataformas'?['PC','Xbox','PlayStation','Nintendo Switch','Nintendo Switch 2','Mobile']:['Acción','Aventura','RPG','Terror','Shooter','Carreras','Deportes','Estrategia','Sandbox','Supervivencia','Puzzle'];valores.forEach(v=>{const o=el('option','',v);o.value=v;s.append(o);});});}
    opciones();
    return {fuentes,api,ready,el,icon,aviso,toast,tarjetas,renderPrecios,extras,nuevoChat,textoRespuesta};
})();
