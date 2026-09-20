## Cuentas — 12/09/2026

`php/pruebas_cuentas.php`: 19 comprobaciones HTTP/MySQL aprobadas. Se verificaron registro, conservación del historial y preferencias del invitado, almacenamiento mediante hash, correo duplicado, contraseña incorrecta, CSRF, rotación del token, recuperación desde otra sesión, aislamiento del historial, cierre de sesión y límite de intentos. La prueba elimina únicamente sus propios datos de cuenta y archivos temporales.

La migración de cuentas se ejecutó correctamente. La pantalla de registro se comprobó en el navegador; los flujos completos se probaron por HTTP. No se envían correos ni se incluye recuperación de contraseña.

---
## Preferencias por clasificación — 12/09/2026

El usuario selecciona `clasificacion_maxima`: A, B, B15, C o D. Los formularios ya no solicitan edad. Se guarda en `usuarios.clasificacion_maxima`, con A por defecto, y se muestra en la cabecera común. Las recomendaciones incluyen la categoría seleccionada y las inferiores; las búsquedas y los detalles mantienen avisos para contenido por encima del límite. Precios conserva la clasificación original de la ficha y su aviso.

ESRB se relaciona E→A, E10+→B, T→B15, M→C y AO→D. C y D son categorías diferentes, no el mismo umbral numérico. PEGI/edades de tienda usan agrupación orientativa de B.A.G (hasta 3: A; hasta 12: B; hasta 15: B15; superiores: C); no se presenta como certificación mexicana. Las etiquetas originales se conservan. Las recomendaciones excluyen clasificación desconocida.

La migración `php/migrar_clasificacion.php` ya se ejecutó y es idempotente. El instalador también prepara la columna. La antigua columna `edad` queda como dato heredado, sin utilizarse ni enviarse al navegador o al modelo; no se borraron datos anteriores. El chat no extrae ni guarda la edad para configurar preferencias y sus herramientas respetan la categoría guardada.

Verificación: 11 pruebas de categorías correctas (`php/pruebas_clasificaciones.php`), guardado/lectura HTTP de las cinco opciones y recomendación real con B (12 resultados, todos A/B). Sintaxis correcta en 13 archivos PHP y selector confirmado en navegador. Los informes anteriores basados en edades describen versiones antiguas.

---
## Verificación multitienda — 12/09/2026

- 14 comprobaciones multitienda en vivo correctas: cinco fuentes, Nintendo España, Eneba y detalles por ID, Xbox México, recomendaciones por edad, motivos y Switch 2.
- 14 comprobaciones de regresión en vivo correctas: Monster Hunter, ARK, precios multifuente, detalles, inexistentes y consulta vacía.
- 10 pruebas controladas correctas: Xbox descarta precios condicionados/caducados/de otra región; Nintendo diferencia mínimos de variantes y valores desconocidos; Eneba conserva región, centavos y Switch 2, y excluye promociones sin precio base verificable.
- Sintaxis PHP correcta en los ocho archivos de producción modificados. No se ha probado una generación de OpenAI ni realizado compras.
- Cobertura funcional: cinco fuentes conectadas; no se certifica acceso universal a tiendas no integradas.

---
## Verificación de búsqueda y precios en vivo — 11/09/2026

- `./pruebas_online.ps1`: 14 comprobaciones correctas con peticiones reales desde localhost a Steam/GOG: Monster Hunter, ARK sin Dark Souls, Cyberpunk 2077 en dos tiendas, moneda, enlaces de producto, detalle de Steam/GOG, inexistentes y cobertura.
- Sintaxis PHP correcta en el nuevo proveedor y los tres endpoints modificados.
- Navegador: Monster Hunter cargó 9 fichas con elementos de portada y fuente Steam. Comparación de Cyberpunk verificada con importes y enlaces originales.
- La actualización cada 5 minutos está implementada; no se ha esperado un ciclo completo en esta revisión. No se prueba la generación OpenAI, que sigue pendiente de cuota.
- Los informes que siguen describen versiones anteriores; sus pruebas con expectativas de catálogo demo no aplican a la nueva búsqueda.

---
# Actualización: chat con IA real — 11 de septiembre de 2026

El motor de reglas fue reemplazado en `chat.php` por OpenAI Responses API con herramientas y búsqueda web. La sección del 10 de septiembre que aparece más abajo es un registro histórico de la versión anterior, no una descripción del motor actual.

- **30 pruebas HTTP/MySQL aprobadas:** catálogo, preferencias, límites, CSRF, separación entre invitados, fuentes guardadas, nuevo chat, archivos privados bloqueados y respuesta explícita cuando falta la clave de IA.
- **37 pruebas de protocolo/herramientas aprobadas:** esquemas estrictos, ciclo de funciones, contexto de conversación, tarjetas en memoria, filtro infantil, llamadas no permitidas, continuación con razonamiento cifrado, citas y fuentes, argumentos inválidos, errores HTTP, límites de rondas y configuración ausente.
- El protocolo se prueba con respuestas controladas inyectadas exclusivamente desde CLI. **No se ha probado una respuesta auténtica de OpenAI porque no hay clave configurada.** Estas pruebas no certifican la calidad de generación, el acceso al modelo ni la disponibilidad de búsqueda web de la cuenta.
- Sintaxis PHP revisada. Los errores 401, 429, 400/403/404, 500, JSON inválido y respuesta incompleta se comprueban con entradas controladas; no se provocan en la cuenta del usuario.
- Navegador: se verificó «IA sin configurar», envío con Enter, error de clave ausente y conservación exacta del texto para reintentar. El enlace «Cómo activar la IA» abre las instrucciones correctas. No apareció ninguna respuesta simulada ni se registró una respuesta de IA inexistente.
- No hay fallback silencioso al motor anterior. Si falla la IA, el mensaje se conserva para reintentar y no se guarda una respuesta simulada en el historial.

Correcciones durante esta actualización: se corrigió una captura por valor en la prueba del límite de rondas; el flujo de producción no tenía ese fallo. La prueba CLI necesitó acceso al directorio de sesiones de XAMPP y se ejecutó con ese permiso. Los antiguos 37 casos de reglas se sustituyeron por pruebas de las responsabilidades actuales.

---
# Verificación de B.A.G

Fecha: 10 de septiembre de 2026.

## Entorno y resultado

- XAMPP de `D:\xampp 2`, Apache en el puerto 80 y MySQL en el 3306.
- PHP 8.2.12, PDO MySQL, cURL y mbstring disponibles.
- Base `bag` creada por el instalador HTTP; segunda ejecución comprobada sin duplicación.
- **37 pruebas de integración HTTP/MySQL: 37 correctas, 0 fallos.**
- Sintaxis de todos los archivos PHP comprobada con `php -l`.
- Enlaces locales de HTML y referencias a CSS, JavaScript y PHP comprobados contra archivos existentes.
- Las ocho pantallas cargaron sin errores de JavaScript registrados en el navegador.

## Cobertura automatizada

El archivo `php/pruebas.php` comprueba los 12 casos solicitados: saludo, búsqueda, plataformas, precios sin importes inventados, recomendaciones de terror para un adulto, filtro por edad, similares, Xbox, consejos, bloqueo de hacks, historial y conversación nueva.

También comprueba filtros combinados, gustos de dinosaurios, advertencia de edad al buscar directamente, detalle, juego inexistente, mensajes vacíos y demasiado largos, edad/plataforma/género/modo inválidos, rechazo de CSRF, consulta con caracteres de SQL injection, aislamiento entre invitados, prevención del borrado de una conversación ajena, eliminación propia y bloqueo HTTP del archivo de configuración.

Los usuarios temporales creados por el script se eliminan al terminar mediante sus identificadores. Los datos ajenos a esas pruebas se conservan.

## Verificación en navegador

- Portada de escritorio revisada visualmente.
- Chat: envío con Enter, estado de búsqueda, respuesta, tarjeta y ventana de detalle.
- Búsqueda: Minecraft desde el formulario y enlace a la tabla de precios.
- Precios: tabla por plataforma, enlaces de tiendas, falta explícita de precios y fecha de consulta.
- Preferencias: guardado desde la interfaz de edad 12, Xbox y carreras; recomendaciones resultantes compatibles. Al terminar se restablecieron esos valores de prueba al perfil sin preferencias.
- Ayuda: un ejemplo abrió el chat y envió la pregunta.
- Nuevo chat: volvió a mostrar el saludo y conservó la conversación anterior.
- Historial: apertura de la conversación anterior con sus mensajes y la tarjeta guardada.
- Menú móvil: apertura y navegación verificadas.
- Revisión de las ocho páginas a 320, 768 y 1280 píxeles. Se detectó un desbordamiento en la portada; después de corregirlo se volvió a comprobar a 320 y 768 sin desbordamiento horizontal del documento.
- Chat también revisado visualmente a 390 × 844 y en su versión final a 320 × 900. La tabla de precios se desplaza horizontalmente dentro de su contenedor, sin ensanchar la página.

## Problemas encontrados y correcciones

1. **Búsqueda demasiado amplia:** la coincidencia con cualquier palabra aislada podía devolver juegos irrelevantes. Se exige coincidencia de todas las palabras significativas, manteniendo la búsqueda de nombres y filtros.
2. **Expectativa incorrecta en una prueba de similares:** se esperaba una lista vacía para un menor, aunque existían alternativas con género compartido y clasificación apta. Se corrigió la prueba para verificar la edad de todos los resultados.
3. **Validación de filtros:** se añadió rechazo explícito de género, plataforma o modo no incluidos en las opciones permitidas.
4. **Ruta de RAWG desde el chat:** los títulos reconocidos en el catálogo demo también consultan RAWG cuando hay clave configurada.
5. **Desbordamiento de portada en móvil/tablet:** se ajustaron columnas y límites de la ilustración. El encabezado del chat también se compactó para móvil.
6. **Caché de estilos durante el desarrollo:** se añadieron versiones a las referencias de recursos y revalidación mediante Apache para que los cambios se reflejen al navegar.

## Límites de la verificación

No se proporcionó una clave RAWG, por lo que no se certifica una consulta autenticada en vivo ni el comportamiento ante todos los errores posibles del proveedor. Se implementó la vuelta a demo ante una clave ausente, errores HTTP o conexión fallida. No hay una API de precios conectada: se comprobó el estado «Precio no disponible actualmente», no importes reales.

El asistente funciona con reglas e información demo. Los consejos específicos para cada jefe o misión y la conversación libre de un modelo generativo no forman parte de este motor; los consejos generales y las preguntas de contexto sí funcionan.

La inspección responsive utilizó un navegador de escritorio con tamaños de viewport definidos; no sustituye pruebas en dispositivos físicos de todas las marcas.
