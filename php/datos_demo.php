<?php
// Catálogo educativo fijo: no representa disponibilidad ni valoraciones en tiempo real.
function datos_demo(): array {
    $filas = [
        ['Minecraft','Sandbox|Aventura','PC|Xbox|PlayStation|Nintendo Switch|Mobile','E10+',10,'2011-11-18',true,true,'Construye, explora y sobrevive en mundos de bloques.','bloques construccion creativo',[14,19]],
        ['Gears 5','Shooter|Acción','PC|Xbox','M17+',17,'2019-09-10',true,true,'Combate con coberturas y campaña cooperativa de ciencia ficción.','gears of war soldados cobertura',[3,4,5]],
        ['Gears of War Ultimate Edition','Shooter|Acción','PC|Xbox','M17+',17,'2015-08-25',true,true,'La primera aventura de Delta, remasterizada con combate táctico.','gears of war cobertura',[2,4,5]],
        ['Halo Infinite','Shooter|Acción','PC|Xbox','T13+',13,'2021-12-08',true,true,'Explora Zeta Halo y domina el combate del Jefe Maestro.','espacio alienigenas ciencia ficcion',[5,2]],
        ['Halo Master Chief Collection','Shooter|Acción','PC|Xbox','M17+',17,'2014-11-11',true,true,'Una colección de campañas y experiencias multijugador de Halo.','halo espacio jefe maestro',[4,2,3]],
        ['Forza Horizon 5','Carreras','PC|Xbox|PlayStation','E',0,'2021-11-09',true,true,'Conduce y explora un festival de carreras ambientado en México.','autos coches carreras mexico',[18]],
        ['Elden Ring','RPG|Acción','PC|Xbox|PlayStation','M17+',17,'2022-02-25',true,true,'Explora las Tierras Intermedias y afronta combates exigentes.','fantasia jefes almas mundo abierto',[8,13,21]],
        ['Dark Souls III','RPG|Acción','PC|Xbox|PlayStation','M17+',17,'2016-03-24',true,true,'Una aventura de fantasía oscura con combate metódico.','fantasia jefes almas',[7,21]],
        ['Resident Evil 4','Terror|Acción','PC|Xbox|PlayStation','M17+',17,'2023-03-24',false,true,'Remake de la misión de Leon con supervivencia y gestión de recursos.','zombis horror supervivencia remake',[10]],
        ['Resident Evil Village','Terror|Acción','PC|Xbox|PlayStation|Nintendo Switch|Mobile','M17+',17,'2021-05-07',false,true,'Investiga una aldea hostil en una aventura de terror.','horror monstruos supervivencia',[9]],
        ['DOOM Eternal','Shooter|Acción','PC|Xbox|PlayStation|Nintendo Switch','M17+',17,'2020-03-20',true,true,'Combate rápido que combina movimiento y gestión de recursos.','demonios ciencia ficcion',[4,2]],
        ['Cyberpunk 2077','RPG|Acción','PC|Xbox|PlayStation|Nintendo Switch','M17+',17,'2020-12-10',false,true,'Una historia de rol y decisiones en la ciudad de Night City.','futuro mundo abierto',[13,7]],
        ['The Witcher 3','RPG|Aventura','PC|Xbox|PlayStation|Nintendo Switch','M17+',17,'2015-05-19',false,true,'Acompaña a Geralt en una aventura de fantasía y decisiones.','fantasia monstruos mundo abierto',[7,12]],
        ['Terraria','Sandbox|Supervivencia|Aventura','PC|Xbox|PlayStation|Nintendo Switch|Mobile','T13+',13,'2011-05-16',true,true,'Excava, construye y explora un mundo de aventura en dos dimensiones.','bloques construccion creativo',[1,19]],
        ['Stardew Valley','RPG|Simulación','PC|Xbox|PlayStation|Nintendo Switch|Mobile','E10+',10,'2016-02-26',true,true,'Cuida tu granja, conoce a tus vecinos y explora las minas.','granja relajante agricultura',[1,14]],
        ['Among Us','Estrategia','PC|Xbox|PlayStation|Nintendo Switch|Mobile','E10+',10,'2018-06-15',true,false,'Completa tareas y descubre al impostor mediante deducción social.','amigos espacio impostor',[18,17]],
        ['Fortnite','Shooter|Acción','PC|Xbox|PlayStation|Nintendo Switch|Mobile','T13+',13,'2017-07-25',true,false,'Combate y construye en modos competitivos y experiencias creativas.','battle royale amigos construccion',[4,18]],
        ['Fall Guys','Plataformas|Carreras','PC|Xbox|PlayStation|Nintendo Switch|Mobile','E',0,'2020-08-04',true,false,'Supera circuitos de obstáculos coloridos con otros jugadores.','obstaculos amigos fiesta',[6,16]],
        ['ARK Survival Evolved','Supervivencia|Aventura','PC|Xbox|PlayStation|Nintendo Switch|Mobile','T13+',13,'2017-08-29',true,true,'Sobrevive, construye un refugio y domestica criaturas prehistóricas.','dinosaurios dinosaurio construccion',[20,1,14]],
        ['Jurassic World Evolution 2','Estrategia|Simulación','PC|Xbox|PlayStation','T13+',13,'2021-11-09',false,true,'Diseña y administra un parque con dinosaurios.','dinosaurios dinosaurio gestion parques',[19]],
        ['Hollow Knight','Aventura|Acción','PC|Xbox|PlayStation|Nintendo Switch','E10+',10,'2017-02-24',false,true,'Explora un reino subterráneo con combate y plataformas.','metroidvania insectos jefes',[22,8]],
        ['Cuphead','Acción|Plataformas','PC|Xbox|PlayStation|Nintendo Switch','E10+',10,'2017-09-29',true,true,'Enfrenta jefes con estética de animación clásica.','caricaturas jefes cooperativo',[21]]
    ];
    $juegos=[];
    foreach ($filas as $i=>$f) $juegos[] = ['id'=>$i+1,'nombre'=>$f[0],'generos'=>explode('|',$f[1]),'plataformas'=>explode('|',$f[2]),'clasificacion'=>$f[3],'edad_minima'=>$f[4],'fecha'=>$f[5],'multijugador'=>$f[6],'solo'=>$f[7],'descripcion'=>$f[8],'palabras_clave'=>$f[9],'similares'=>$f[10],'imagen'=>'../css/portada.svg','fuente'=>'DEMO','nota'=>'Datos de demostración. Ediciones, clasificación regional y modos disponibles pueden variar por plataforma.'];
    return $juegos;
}
