## Cuentas e inicio de sesión — 12/09/2026

La página `vistas/vista_cuenta.html` permite registrarse con nombre, correo y contraseña, iniciar sesión y cerrarla. Al registrarse se convierte el invitado actual en una cuenta y se conservan sus preferencias, clasificación, búsquedas y conversaciones. Al iniciar sesión en una cuenta existente se recuperan sus datos; no se fusionan los del invitado. Cerrar sesión conserva la cuenta y abre una sesión de invitado nueva.

Buscar juegos, comparar precios y consultar recomendaciones siguen disponibles como invitado. Historial y Preferencias indican si los datos pertenecen a una cuenta o a una sesión temporal y ofrecen el acceso al registro. La barra lateral incluye Mi cuenta. Los cambios de sesión actualizan las demás pestañas abiertas.

La migración `php/cuentas_esquema.php` ya se ejecutó; crea las tablas `cuentas` y `acceso_limites` sin borrar datos. También está incluida en el instalador. Las contraseñas se guardan mediante `password_hash`, con verificación en el servidor, renovación de sesión, protección CSRF y límites de intentos. Se exige un mínimo de 10 caracteres y un máximo de 72 bytes.

La recuperación de contraseña y la verificación de correo todavía no están implementadas. Actualmente funciona en localhost; para acceder desde otros dispositivos fuera del equipo se debe publicar la aplicación con su base de datos y HTTPS.

Verificación: 19 pruebas HTTP/MySQL correctas en `php/pruebas_cuentas.php`, incluidas conservación, recuperación, aislamiento entre usuarios y cierre de sesión. Formulario de registro revisado en el navegador.

---
## Preferencias por clasificación — 12/09/2026

El usuario selecciona `clasificacion_maxima`: A, B, B15, C o D. Los formularios ya no solicitan edad. Se guarda en `usuarios.clasificacion_maxima`, con A por defecto, y se muestra en la cabecera común. Las recomendaciones incluyen la categoría seleccionada y las inferiores; las búsquedas y los detalles mantienen avisos para contenido por encima del límite. Precios conserva la clasificación original de la ficha y su aviso.

ESRB se relaciona E→A, E10+→B, T→B15, M→C y AO→D. C y D son categorías diferentes, no el mismo umbral numérico. PEGI/edades de tienda usan agrupación orientativa de B.A.G (hasta 3: A; hasta 12: B; hasta 15: B15; superiores: C); no se presenta como certificación mexicana. Las etiquetas originales se conservan. Las recomendaciones excluyen clasificación desconocida.

La migración `php/migrar_clasificacion.php` ya se ejecutó y es idempotente. El instalador también prepara la columna. La antigua columna `edad` queda como dato heredado, sin utilizarse ni enviarse al navegador o al modelo; no se borraron datos anteriores. El chat no extrae ni guarda la edad para configurar preferencias y sus herramientas respetan la categoría guardada.

Verificación: 11 pruebas de categorías correctas (`php/pruebas_clasificaciones.php`), guardado/lectura HTTP de las cinco opciones y recomendación real con B (12 resultados, todos A/B). Sintaxis correcta en 13 archivos PHP y selector confirmado en navegador. Los informes anteriores basados en edades describen versiones antiguas.

---
## Versión multitienda — 12 de septiembre de 2026

Las páginas Buscar juegos, Comparar precios y Recomendaciones ahora consultan cinco fuentes: Steam, GOG, Xbox, Nintendo y Eneba. Incluyen selector de tienda, plataforma (incluido Switch 2), portadas, enlaces al producto y estado de cada fuente. No es un buscador universal de cualquier web: sólo se consultan los adaptadores conectados y un conjunto acotado de resultados de cada catálogo.

| Fuente | Datos y límites |
| --- | --- |
| Steam | Catálogo PC y precios para México, en MXN. Descubrimiento mediante los más vendidos publicados por Steam. |
| GOG | PC, región MX, importe en USD. |
| Xbox | Catálogo Microsoft y fichas completas en México, MXN. Se comprueban plataformas por oferta y se excluyen precios caducados, de prueba o condicionados a suscripción. Las variantes SKU mantienen formato y contenido. |
| Nintendo | Catálogo oficial España, Switch/Switch 2, EUR. El precio es el mínimo entre variantes de la ficha: se etiqueta «Desde» y puede ser una mejora que exige el juego base. No representa necesariamente el juego completo ni garantiza compra en México. |
| Eneba | Índice público de búsqueda utilizado por la web de Eneba (Algolia). Precio base en MXN, DRM, plataformas y región de activación; puede haber cargos adicionales al pagar. No se utilizan la API privada de vendedores ni cuentas de terceros. Los identificadores de búsqueda son públicos y podrían cambiar. |

Recomendaciones consulta catálogos actuales, filtra edad conocida, plataforma, género y modo; explica cada elección. La selección utiliza coincidencias y, cuando existen, valoraciones publicadas de Xbox. No es un ranking universal ni una recomendación generada por IA. Los juegos sin clasificación verificable se excluyen; la selección puede ser limitada o vacía. Descubrimiento Nintendo prioriza publicaciones recientes con precio publicado, no juegos futuros. Cada búsqueda y comparación se renueva a los 5 minutos mientras la página está visible. Recomendaciones se renueva al solicitarla.

No se elige un ganador entre monedas, plataformas, regiones, formatos o precios «desde» distintos. Las ofertas de clave y la edición de tienda no se fusionan sólo por parecido del nombre. El chat generativo mantiene su integración anterior y requiere cuota de OpenAI.

Comprobaciones: `pruebas_multitienda.ps1` (14), `pruebas_online.ps1` actualizada (14) y `php/pruebas_tiendas.php` (10). Las dos primeras consultan Internet; la tercera comprueba casos de precios condicionados, caducidad, región, promociones y Switch 2 mediante datos de prueba, sin OpenAI.

Los apartados siguientes describen versiones anteriores.

---
## Búsqueda y precios en vivo — 11 de septiembre de 2026

Buscar juegos y Comparar precios consultan directamente los servicios públicos de las tiendas Steam y GOG. No usan el catálogo demo, RAWG ni OpenAI. Cada consulta descarga datos actuales de la fuente; no hay fichas ni importes escritos a mano en este flujo. Las portadas y enlaces se toman de la respuesta de la tienda. La página renueva la última consulta cada 5 minutos mientras está visible; no ejecuta trabajos con el navegador cerrado.

Cobertura: PC. Steam se consulta con región MX y devuelve MXN; GOG se consulta para MX en USD. No se convierten monedas. Las ediciones se agrupan solamente por título normalizado idéntico; hay que verificar contenidos en cada ficha. Las búsquedas muestran los resultados que devuelven las tiendas (Steam hasta 10 coincidencias y GOG hasta 48), no un índice universal de todos los juegos. Usa el nombre oficial para afinar los resultados. DLC y ediciones pueden aparecer por separado. El filtro de género utiliza las categorías publicadas por las fuentes; no todos los géneros están etiquetados en todas las tiendas.

Si una tienda falla, se informa y se muestran sólo las fuentes disponibles. Si fallan ambas, se devuelve HTTP 502 sin catálogo demo. Un importe ausente sigue siendo desconocido; cero sólo se muestra si la tienda declara gratis o un importe cero. Los endpoints públicos de tienda pueden cambiar o limitar consultas y no tienen una garantía de estabilidad como una API contratada.

Implementación: `php/catalogo_online.php`, endpoints de búsqueda/precios/detalle y sus vistas. `pruebas_online.ps1` realiza 14 comprobaciones de datos en vivo sin usar OpenAI. Las pruebas anteriores del catálogo demo (`php/pruebas.php`) contienen expectativas que ya no aplican a estos endpoints; el informe antiguo de 30 pruebas no certifica esta nueva versión.

El chat y Recomendaciones conservan por ahora sus herramientas de catálogo previas (demo/RAWG). La configuración privada de OpenAI no se modifica con este cambio.

---
# B.A.G — Bot Assistant in Gaming

**Encuentra. Compara. Juega.** Chat de videojuegos conectado a una IA real de OpenAI, con contexto, herramientas del catálogo y búsqueda web con fuentes.

## Iniciar en XAMPP

1. Abre `D:\xampp 2\xampp-control.exe` y activa **Apache** y **MySQL**.
2. Abre [Crear o verificar la base](http://localhost/BAG/php/crear_base_datos.php). La base `bag` ya está creada; el instalador no borra ni duplica los datos existentes.
3. Configura la clave de OpenAI como se explica abajo.
4. Abre [el chat de B.A.G](http://localhost/BAG/vistas/vista_chat.html) o [la portada](http://localhost/BAG/vistas/vista_inicio.html).

La carpeta actual es `D:\xampp 2\htdocs\BAG`. Puedes moverla al `htdocs` de otra instalación y ajustar host, puerto y credenciales MySQL en `php/config.php`. No abras las vistas con `file://`; necesitan Apache/PHP.

## Activar la IA real

Abre **`php/config_local.php`**, descomenta esta asignación y coloca tu clave personal:

```php
$OPENAI_API_KEY = 'TU_CLAVE_REAL';
```

Guarda y recarga el chat. También puedes configurar `OPENAI_API_KEY` en el entorno de Apache, sin editar archivos. La clave no debe pegarse en el chat ni en JavaScript. El archivo local está excluido de Git y su acceso HTTP está bloqueado mediante Apache.

- Gestiona tu clave en [OpenAI API keys](https://platform.openai.com/api-keys).
- El modelo inicial es `gpt-5.6-terra`; puedes cambiar `$OPENAI_MODEL` por uno disponible en tu cuenta que admita Responses API, funciones y búsqueda web.
- `$OPENAI_WEB_SEARCH = false;` desactiva la búsqueda web, conservando la conversación y las herramientas locales.
- La API necesita acceso al modelo y cuota disponible. Generación y herramientas web pueden consumir saldo de tu proyecto API.
- No hay clave real incluida. **No se ha verificado una conversación autenticada con OpenAI.** Se comprobaron el protocolo con respuestas controladas y el estado real de clave ausente.

La cabecera distingue **IA sin configurar**, **IA lista para conectar**, **IA conectada** e **IA no disponible**. Tener una clave configurada no equivale a que esté validada. Sin clave o ante un error, el chat muestra el problema y conserva tu texto para reintentar; no sustituye la IA por respuestas basadas en reglas.

## Cómo funciona

1. JavaScript envía el mensaje por Fetch a `php/chat.php`.
2. PHP valida sesión, CSRF, mensaje y configuración.
3. Envía a Responses API hasta 20 mensajes recientes de la conversación actual, con un límite de contexto, y los filtros de juego del perfil.
4. El modelo puede llamar a `consultar_catalogo`, `consultar_precios` y `abrir_seccion`, además de la herramienta de búsqueda web de OpenAI.
5. PHP ejecuta únicamente las funciones permitidas y devuelve sus resultados al modelo. No ejecuta código del usuario ni del modelo.
6. Se muestra la respuesta generada, las citas clicables, las fuentes y las tarjetas de juegos disponibles.
7. El mensaje y la respuesta completa, incluidas fuentes y tarjetas, se guardan juntos en MySQL.

La conversación puede comprender referencias como «ese juego», «el segundo» o «¿y en Xbox?». El historial incluye los nombres de las tarjetas para conservar ese contexto. Un nuevo chat empieza sin el contexto del anterior y conserva su historial.

La IA recibe instrucciones de no inventar precios, disponibilidad o guías específicas. Puede buscar en la web para comprobar información. Los resultados generados pueden contener errores: el usuario puede abrir las fuentes para contrastarlos. Los filtros del servidor protegen las recomendaciones del catálogo por edad; sin edad indicada se usa el límite conservador de 7 años. Las consultas explícitas sobre títulos para adultos incluyen advertencia. Un filtro local adicional bloquea solicitudes de hacks o robo de cuentas y está identificado como filtro de seguridad, no como texto generado por IA.

## Juegos, precios y datos demo

La IA y el catálogo son fuentes separadas:

- **OpenAI:** conversación generativa, comprensión del contexto, selección de herramientas y búsqueda web cuando está habilitada.
- **Catálogo demo:** 22 videojuegos fijos, con placeholders locales y etiquetas DEMO.
- **RAWG opcional:** búsqueda, detalles y portadas de su catálogo al configurar `$RAWG_API_KEY` en `php/config.php` o en el archivo local. Si falla, se vuelve a los datos demo, identificados como tales.
- **Tabla de precios:** no tiene un proveedor de importes en vivo. Muestra tiendas y «Precio no disponible actualmente». La IA puede complementar la consulta con información web citada y debe especificar moneda, edición y región si encuentra un importe. La tabla local no se rellena con cifras inventadas.

Las pantallas de búsqueda y recomendaciones pueden demostrar el catálogo sin claves. **El chat sí exige una clave de IA**, conforme al requisito actualizado. Los antiguos archivos `detectar_intencion.php` y `consejos.php` se conservan por la estructura solicitada, pero no generan las respuestas del chat actual.

## Archivos principales

Se conservan las cuatro carpetas `css`, `js`, `php` y `vistas`, sin frameworks ni proceso de compilación. PHP 8.2 de XAMPP incluye las extensiones necesarias: PDO MySQL, cURL y mbstring.

Las ocho vistas `vista_inicio`, `vista_chat`, `vista_buscar`, `vista_recomendaciones`, `vista_precios`, `vista_historial`, `vista_preferencias` y `vista_ayuda` tienen su HTML, CSS y JavaScript. `css/comun.css` y `js/comun.js` comparten navegación, tarjetas, peticiones y representación segura de citas.

| Archivo | Responsabilidad |
| --- | --- |
| `php/config.php`, `php/config_local.php` | MySQL, claves y opciones de IA |
| `php/chat.php` | Controlador del chat, validación y persistencia |
| `php/ia_openai.php` | HTTPS con OpenAI, errores, contexto del modelo, ciclo de herramientas y citas |
| `php/ia_herramientas.php` | Definiciones de herramientas y ejecución con filtros del servidor |
| `php/catalogo.php`, `php/datos_demo.php` | RAWG y catálogo demo |
| `php/filtro_edad.php`, `php/filtro_seguridad.php` | Filtros adicionales del servidor |
| `php/conexion.php`, `php/comun.php` | PDO, sesión, validación y respuestas JSON |
| `php/crear_base_datos.php`, `php/base_datos.sql` | Instalación de MySQL |
| `php/historial.php`, `php/guardar_mensaje.php`, `php/nueva_conversacion.php` | Conversaciones y mensajes |
| `php/preferencias.php`, `php/guardar_preferencias.php` | Perfil y estado de configuración de la IA |
| `php/buscar_juego.php`, `php/detalle_juego.php`, `php/recomendaciones.php`, `php/comparar_precios.php` | Endpoints para formularios del catálogo |
| `php/pruebas.php`, `php/pruebas_ia.php` | Pruebas HTTP y pruebas controladas del protocolo de IA |

## Datos y privacidad

MySQL contiene `usuarios`, `preferencias`, `conversaciones`, `mensajes` y `busquedas`. Cada sesión PHP tiene su invitado; las consultas y borrados del historial comprueban el propietario.

Al usar el chat, OpenAI procesa tu consulta, el contexto reciente y los filtros de juego necesarios. No se envían la clave al navegador, las credenciales de MySQL ni conversaciones de otros invitados. Se solicita `store: false` a Responses API; esto no es una afirmación de retención cero de todos los datos del proveedor. El historial de B.A.G permanece en MySQL.

No hay autenticación entre dispositivos. Si pierdes las cookies o caduca la sesión, se crea otro invitado. Las consultas son preparadas, las escrituras usan CSRF y las respuestas se dibujan mediante nodos de texto y enlaces validados, sin ejecutar HTML del modelo.

## Pruebas

Desde BAG, con Apache y MySQL activos:

```powershell
& 'D:\xampp 2\php\php.exe' php/pruebas.php
& 'D:\xampp 2\php\php.exe' php/pruebas_ia.php
```

Las pruebas crean y eliminan exclusivamente sus propios invitados temporales. No llaman a OpenAI ni consumen saldo. `pruebas_ia.php` utiliza un transporte simulado solo en CLI: el navegador no puede activar simulaciones ni elegir un endpoint alternativo.

Resultados y límites de las pruebas: `PRUEBAS.md`.

## Errores de conexión

- **Sin clave:** configura `php/config_local.php` y recarga.
- **Clave rechazada:** comprueba que sea una clave API válida de tu proyecto.
- **Cuota o límite:** revisa saldo y límites del proyecto API.
- **Modelo o herramientas rechazados:** comprueba disponibilidad del modelo y permisos.
- **Certificado HTTPS:** configura `curl.cainfo` con certificados de confianza en el `php.ini` de XAMPP y reinicia Apache. La aplicación no desactiva TLS.
- **Tiempo agotado:** el texto queda disponible para reintentar; no se guarda una respuesta ficticia.

La conversación se limita a cuatro solicitudes al modelo y ocho llamadas a funciones por turno. No se mantiene una transacción MySQL abierta mientras se espera a OpenAI.

## Documentación oficial consultada

- [Responses y llamadas a funciones](https://developers.openai.com/api/docs/guides/function-calling)
- [Búsqueda web y citas](https://developers.openai.com/api/docs/guides/tools-web-search)
- [Modelo configurado](https://developers.openai.com/api/docs/models/gpt-5.6-terra)
- [RAWG](https://rawg.io/apidocs)
