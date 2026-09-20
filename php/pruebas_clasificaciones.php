<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/clasificaciones.php';
$total=0;
function check_cl(bool $ok,string $label):void{global $total;if(!$ok){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "OK $label\n";$total++;}
foreach(['E'=>'A','E10+'=>'B','ESRB:T'=>'B15','M17+'=>'C','AO18+'=>'D'] as $original=>$categoria)check_cl(categoria_contenido(['clasificacion'=>$original])===$categoria,'Equivalencia '.$original);
check_cl(!admite_clasificacion(['clasificacion'=>'AO18+'],'C'),'C excluye D aunque ambos sean para adultos');
check_cl(admite_clasificacion(['clasificacion'=>'M17+'],'D'),'D incluye categorías inferiores');
check_cl(!admite_clasificacion(['clasificacion'=>'Sin clasificación','edad_minima'=>null],'D'),'Desconocido no se inventa');
check_cl(categoria_contenido(['clasificacion'=>'PEGI 7','edad_minima'=>7])==='B','PEGI conserva agrupación orientativa');
check_cl(advertir_clasificacion([['clasificacion'=>'ESRB:T']],'A')[0]['advertencia']==='Supera tu clasificación máxima seleccionada: A.','Aviso se refiere al contenido, no a edad personal');
try{clasificacion_valida('18');check_cl(false,'Clasificación inválida');}catch(InvalidArgumentException $e){check_cl(true,'Rechaza edad numérica como clasificación');}
echo "$total comprobaciones correctas.\n";
