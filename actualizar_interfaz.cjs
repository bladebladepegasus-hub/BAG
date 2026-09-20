const fs=require('fs');
function edit(p,fn){fs.writeFileSync(p,fn(fs.readFileSync(p,'utf8')));}
const campos=(prefix)=>`<div class="field"><label for="${prefix}-region">País / región de tu cuenta</label><select id="${prefix}-region" name="region" data-config="region" required></select></div><div class="field"><label for="${prefix}-idioma">Idioma de la interfaz</label><select id="${prefix}-idioma" name="idioma" data-config="idioma" required></select></div><div class="field"><label for="${prefix}-tema">Apariencia</label><select id="${prefix}-tema" name="tema" data-config="tema" required></select></div>`;
edit('vistas/vista_preferencias.html',s=>s.replace('<div class="form-grid">','<div class="form-grid">'+campos('config')).replace('<button class="btn primary" type="submit">','<p>Elige el país donde compras y activas tus juegos. Sólo mostramos ofertas cuya disponibilidad podemos comprobar para ese país.</p><p>El idioma de la interfaz no cambia el idioma de los juegos. Las fichas conservan el texto publicado por las tiendas.</p><button class="btn primary" type="submit">'));
edit('vistas/vista_cuenta.html',s=>s.replace('<button class="btn primary" type="submit">Crear mi cuenta',`<div class="form-grid">${campos('registro')}</div><p>Elige el país donde compras y activas tus juegos. Sólo mostramos ofertas cuya disponibilidad podemos comprobar para ese país.</p><button class="btn primary" type="submit">Crear mi cuenta`));
for(const file of fs.readdirSync('vistas').filter(n=>n.endsWith('.html'))){edit('vistas/'+file,s=>s.replace('<script defer src="../js/comun.js','<script src="../js/configuracion.js?v=20260913.region"></script><link rel="stylesheet" href="../css/temas.css?v=20260913.region"><script defer src="../js/comun.js').replaceAll('20260912.accounts','20260913.region'));}
for(const file of fs.readdirSync('css').filter(n=>n.endsWith('.css')))edit('css/'+file,s=>s.replaceAll('20260912.accounts','20260913.region'));
edit('js/comun.js',s=>s.replace("const ready=api('preferencias.php').then(d=>{csrf=d.csrf;","const ready=api('preferencias.php').then(d=>{csrf=d.csrf;BAGConfig.aplicar(d.profile);const region=el('a','status region-status',BAGConfig.regiones[d.profile.region]);region.href='vista_preferencias.html';region.id='region-actual';top.append(region);")
 .replaceAll("'es-MX'","BAGConfig.locale")
 .replace("if(e.key==='bag-account-change')","if(['bag-account-change','bag-settings-change'].includes(e.key))")
);
edit('js/vista_preferencias.js',s=>s.replace("'modo_juego']","'modo_juego','region','idioma','tema']")
 .replace("BAG.aviso(d.message);","BAGConfig.aplicar(Object.fromEntries(new FormData(formulario)));try{localStorage.setItem('bag-settings-change',String(Date.now()));}catch{}document.querySelector('#region-actual').textContent=BAGConfig.regiones[formulario.elements.region.value];BAG.aviso(d.message);")
 +"\nfor(const k of ['tema','idioma'])formulario.elements[k].addEventListener('change',()=>BAGConfig.aplicar({[k]:formulario.elements[k].value},false));\n");
edit('js/vista_cuenta.js',s=>s.replace("else modo(new URLSearchParams", "else {for(const k of ['region','idioma','tema'])registro.elements[k].value=s.profile[k];modo(new URLSearchParams")
 .replace("==='registro');});","==='registro');}});")
 +"\nfor(const k of ['tema','idioma'])registro.elements[k].addEventListener('change',()=>BAGConfig.aplicar({[k]:registro.elements[k].value},false));\n");
for(const file of ['js/vista_buscar.js','js/vista_precios.js','js/vista_historial.js','js/vista_chat.js'])edit(file,s=>s.replaceAll("'es-MX'","BAGConfig.locale"));
edit('vistas/vista_historial.html',s=>s.replace('Historial del invitado de esta sesión de navegador.','Historial de tu sesión actual.'));
edit('vistas/vista_inicio.html',s=>s.replace('Catálogo demo disponible · Conoce nuestras fuentes ↗','Conoce nuestras fuentes ↗'));
