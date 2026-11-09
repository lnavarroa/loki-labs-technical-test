# ConstrucKIT · API de equipos, combustible y mantenciones

Prueba técnica para Loki Labs (Desarrollador/a Full Stack).

API REST en Laravel para que una empresa registre sus equipos (camiones, excavadoras, generadores), las cargas de combustible y las mantenciones de cada uno, con un reporte de consumo y gasto por rango de fechas y un listado de equipos que necesitan mantención preventiva.

## Contenido

- [Stack](#stack)
- [Cómo levantar el proyecto](#cómo-levantar-el-proyecto)
- [Cómo probar la API](#cómo-probar-la-api)
- [Endpoints](#endpoints)
- [Datos de ejemplo y resultados esperados](#datos-de-ejemplo-y-resultados-esperados)
- [Estructura del código](#estructura-del-código)
- [Modelo de datos](#modelo-de-datos)
- [Reglas de negocio](#reglas-de-negocio)
- [Decisiones tomadas](#decisiones-tomadas)
- [Lo que dejé fuera y por qué](#lo-que-dejé-fuera-y-por-qué)
- [Parte 2 · Preguntas cortas](#parte-2--preguntas-cortas)
- [Uso de herramientas de IA](#uso-de-herramientas-de-ia)

## Stack

- PHP 8.4 + Laravel 13
- MySQL 8.4
- Laravel Sanctum (autenticación por token Bearer)
- Docker Compose (app + MySQL)
- PHPUnit (tests contra MySQL real)

## Cómo levantar el proyecto

**Requisitos:** Docker con Docker Compose. No hace falta tener PHP ni MySQL instalados.

```bash
git clone https://github.com/lnavarroa/loki-labs-technical-test.git
cd loki-labs-technical-test
cp .env.example .env
docker compose up -d --build
```

La primera vez tarda unos minutos: construye la imagen, instala las dependencias con Composer y corre las migraciones al arrancar. Para seguir el avance:

```bash
docker compose logs -f app
```

Cuando aparezca `Server running on [http://0.0.0.0:8000]`, en otra terminal:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan db:seed
```

- `key:generate` crea la clave de la aplicación (`APP_KEY`), que no viene en `.env.example`.
- `db:seed` carga el usuario de prueba y 6 equipos con historial.

Comprobación rápida: `curl -i http://localhost:8000/up` debe responder `200`.

**Usuario de prueba:** `admin@construckit.test` / `password`

**Puertos:** la API en `8000` y MySQL en `3307` del host (para no chocar con un MySQL local). Se pueden cambiar con `APP_PORT` y `DB_FORWARD_PORT` en el `.env`.

### Tests

```bash
docker compose exec app php artisan test
```

Corren contra una base MySQL separada (`construckit_test`), que el script `docker/mysql/init.sql` crea al inicializar el volumen. No usan SQLite porque las migraciones tienen restricciones `CHECK` y el bloqueo de filas debe probarse contra el motor real.

### Comandos útiles

```bash
docker compose ps                         # estado de los contenedores
docker compose exec app php artisan ...   # comandos artisan
docker compose up -d                      # iniciar
docker compose stop                       # pausar
docker compose down                       # apagar (conserva los datos de MySQL)
docker compose down -v                    # apagar y borrar los datos de MySQL
docker compose exec app php artisan migrate:fresh --seed   # recrear la base con datos de ejemplo
```

## Cómo probar la API

**Postman:** importar `docs/postman_collection.json` (`File → Import`). Ejecutar primero *Autenticación → Login*, que guarda el token en una variable y lo usa en el resto de las solicitudes. Cada solicitud trae un test con el código HTTP esperado, y la colección completa se puede correr con *Run collection*.

**curl:**

```bash
# 1. Obtener el token
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@construckit.test","password":"password"}' \
  | sed 's/.*"token":"\([^"]*\)".*/\1/')

# 2. Registrar una carga de combustible (actualiza el horómetro del equipo)
curl -i -X POST http://localhost:8000/api/equipos/1/cargas-combustible \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"fecha_hora":"2026-11-09 08:00:00","litros":560,"monto_clp":644000,"horometro":12600}'

# 3. Horómetro menor al último registrado: 422 con un mensaje claro
curl -i -X POST http://localhost:8000/api/equipos/1/cargas-combustible \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"fecha_hora":"2026-11-09 09:00:00","litros":50,"monto_clp":60000,"horometro":100}'

# 4. Reporte por equipo y rango de fechas
curl -s "http://localhost:8000/api/equipos/1/reporte?desde=2026-08-01&hasta=2026-11-07" \
  -H "Authorization: Bearer $TOKEN"

# 5. Equipos que necesitan mantención preventiva
curl -s http://localhost:8000/api/equipos/mantencion-preventiva -H "Authorization: Bearer $TOKEN"
```

## Endpoints

Todos, salvo el login, requieren el header `Authorization: Bearer <token>`. La API responde siempre JSON.

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/login` | Devuelve un token Bearer |
| POST | `/api/logout` | Revoca el token usado |
| GET | `/api/equipos` | Lista equipos (15 por página; filtros `estado` y `tipo`) |
| POST | `/api/equipos` | Crea un equipo |
| GET | `/api/equipos/{id}` | Consulta un equipo |
| PUT/PATCH | `/api/equipos/{id}` | Edita un equipo (envíos parciales) |
| DELETE | `/api/equipos/{id}` | Elimina un equipo (`409` si tiene historial) |
| GET | `/api/equipos/{id}/cargas-combustible` | Lista las cargas del equipo |
| POST | `/api/equipos/{id}/cargas-combustible` | Registra una carga |
| GET | `/api/equipos/{id}/mantenciones` | Lista las mantenciones del equipo |
| POST | `/api/equipos/{id}/mantenciones` | Registra una mantención |
| GET | `/api/equipos/{id}/reporte?desde=&hasta=` | Reporte por equipo y rango de fechas (formato `Y-m-d`, ambas inclusivas) |
| GET | `/api/equipos/mantencion-preventiva` | Equipos con más de 250 h desde su última preventiva |

**Códigos de respuesta:** `200`/`201`/`204` éxito, `401` sin token o credenciales inválidas, `404` recurso inexistente, `409` equipo con historial que no se puede eliminar, `422` validación (incluye el horómetro menor al último registrado).

**Ejemplo del reporte** (CAM-01 con los datos de ejemplo):

```json
{
  "data": {
    "equipo": { "id": 1, "codigo_interno": "CAM-01" },
    "periodo": { "desde": "2026-08-01", "hasta": "2026-11-07" },
    "cargas_combustible": 14,
    "litros_totales": 7840,
    "gasto_combustible_clp": 9016000,
    "gasto_mantenciones_clp": 500000,
    "horas_de_uso": 520,
    "litros_considerados_rendimiento": 7280,
    "rendimiento_l_por_h": 14
  }
}
```

## Datos de ejemplo y resultados esperados

El seeder no usa azar: siempre genera los mismos datos, para poder verificar los resultados.

| Código | Situación | Horas desde la última preventiva |
|--------|-----------|----------------------------------|
| CAM-01 | Al día, con 2 preventivas | 160 |
| CAM-02 | Atrasado, con 1 preventiva | 495 |
| CAM-03 | Atrasado y en mantención. Su correctiva no reinicia el conteo | 300 |
| EXC-01 | Equipo nuevo, sin preventivas (justo bajo el límite) | 240 (desde cero) |
| EXC-02 | Fuera de servicio | 210 |
| GEN-01 | Generador sin patente | 96 |

- `GET /api/equipos/mantencion-preventiva` debe listar **CAM-02** (495 h) y **CAM-03** (300 h).
- El reporte de CAM-01 del 2026-08-01 al 2026-11-07 debe dar el JSON del ejemplo anterior. El rendimiento sale exactamente en 14 L/h porque el seeder genera cada carga con ese consumo.

## Estructura del código

```
app/
├── Enums/                  EstadoEquipo, TipoMantencion
├── Http/
│   ├── Controllers/Api/    controladores delgados (reciben, delegan y responden)
│   ├── Requests/           validación de entrada (FormRequest)
│   └── Resources/          formato estable del JSON de salida
├── Models/                 Equipo, CargaCombustible, Mantencion
└── Services/
    ├── HorometroService.php            registro con validación y actualización del horómetro
    ├── ReporteEquipoService.php        cálculo del reporte
    └── MantencionPreventivaService.php equipos que requieren preventiva
database/
├── migrations/
└── seeders/
docker/mysql/init.sql       crea la base de pruebas
docs/postman_collection.json
tests/Feature/              HorometroTest, ReporteEquipoTest, MantencionPreventivaTest
```

La lógica de negocio vive en `app/Services/` y no en los controladores, para que los dos controladores de registro compartan la misma regla del horómetro y para poder probarla sin pasar por HTTP.

## Modelo de datos

**`equipos`**

| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint | PK |
| codigo_interno | varchar(50) | UNIQUE, NOT NULL |
| tipo | varchar(50) | NOT NULL |
| patente | varchar(10) | NULL, UNIQUE |
| marca, modelo | varchar(100) | NOT NULL |
| horometro_actual | decimal(10,1) | NOT NULL, default 0, CHECK >= 0 |
| estado | varchar(20) | NOT NULL, default `operativo` |
| created_at, updated_at | timestamps | |

**`cargas_combustible`**

| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint | PK |
| equipo_id | FK → equipos | NOT NULL, ON DELETE RESTRICT |
| fecha_hora | datetime | NOT NULL |
| litros | decimal(8,2) | NOT NULL, CHECK > 0 |
| monto_clp | unsigned int | NOT NULL |
| horometro | decimal(10,1) | NOT NULL, CHECK >= 0 |

Índice: `(equipo_id, fecha_hora)`.

**`mantenciones`**

| Columna | Tipo | Restricciones |
|---|---|---|
| id | bigint | PK |
| equipo_id | FK → equipos | NOT NULL, ON DELETE RESTRICT |
| tipo | varchar(20) | NOT NULL (`preventiva` / `correctiva`) |
| fecha | date | NOT NULL |
| horometro | decimal(10,1) | NOT NULL, CHECK >= 0 |
| costo_clp | unsigned int | NOT NULL |
| descripcion | text | NULL |

Índices: `(equipo_id, fecha)` y `(equipo_id, tipo, horometro)`.

**Decisiones de diseño:**

- **Dinero en CLP como entero.** El peso chileno no usa decimales. Nunca `float` para dinero.
- **Horómetro y litros como `decimal`, no `float`.** Los `float` acumulan errores de redondeo, y la regla "horómetro menor se rechaza" es una comparación exacta. El horómetro tiene 1 decimal (décimas de hora).
- **`estado` y `tipo` como `varchar` con Enum de PHP**, no `ENUM` de MySQL. Agregar un valor a un `ENUM` de MySQL exige alterar la tabla; con un Enum de PHP el cambio vive en el código.
- **`equipos.tipo` es texto libre.** El enunciado da ejemplos, no una lista cerrada.
- **`patente` UNIQUE y NULL.** MySQL admite varios NULL en una columna única, así que varios generadores sin patente no chocan.
- **Claves foráneas con `RESTRICT`.** Un equipo con historial no se puede borrar: el historial de gasto es dato contable y un `DELETE` no debería borrarlo en silencio. La API responde `409`.
- **Restricciones `CHECK`** como segunda barrera después de la validación de Laravel.
- **Índices compuestos** pensados para las consultas reales: el reporte filtra por equipo y rango de fechas, y la preventiva busca el `MAX(horometro)` de las preventivas de cada equipo.
- **`equipos.horometro_actual` está desnormalizado a propósito**: guarda el máximo entre todas las cargas y mantenciones, para validar un registro sin consultar dos tablas.
- **Lo derivado no se guarda.** Rendimiento, gasto total y horas desde la última mantención se calculan en la consulta; los datos derivados almacenados se desincronizan.

## Reglas de negocio

- Al registrar una carga o mantención con un horómetro **mayor** al actual, se actualiza el horómetro del equipo.
- Un horómetro **menor** al último registrado se rechaza con `422` y un mensaje claro. Uno **igual** se acepta.
- Un equipo necesita mantención preventiva si lleva **más de 250 horas** (estricto) desde su última mantención preventiva, o desde cero si nunca tuvo una. Las correctivas no reinician el conteo.
- El reporte entrega litros totales, gasto en combustible, gasto en mantenciones y rendimiento promedio en litros por hora.

## Decisiones tomadas

El enunciado pedía explicar las decisiones razonables cuando algo no estaba claro. Estas son las mías.

**Horómetro**

- **Registro atómico.** Todo ocurre en una transacción con `lockForUpdate()` sobre la fila del equipo. Si dos supervisores registran a la vez, el segundo espera al primero y ve el horómetro ya actualizado; sin el bloqueo, ambas peticiones podrían leer el mismo valor, pasar la validación y dejar el horómetro en un valor equivocado.
- **Comparación exacta con `bccomp`**, aritmética decimal que evita los errores de punto flotante.
- **La validación compara contra el horómetro actual del equipo, sin mirar la fecha.** Así nunca se acepta una lectura que retroceda. El costo es que no se pueden ingresar registros históricos con horómetros intermedios.
- **Horómetro inicial:** se puede fijar al **crear** un equipo (una máquina existente llega con horas acumuladas), pero **no al editarlo** (`422`): el horómetro solo avanza registrando cargas o mantenciones, para que la regla de no retroceder no tenga un atajo.

**Reporte**

- **Rendimiento por método de tanque lleno:** `(litros totales − litros de la primera carga) ÷ (horómetro de la última carga − horómetro de la primera)`. Cada carga repone el combustible gastado *desde la carga anterior*, así que los litros de la primera carga del rango corresponden a horas que quedan fuera de él; contarlos inflaría el consumo.
- La primera y la última carga se determinan **por horómetro, no por fecha**, así las horas de uso nunca salen negativas aunque alguien cargue un registro con una fecha antigua.
- Con menos de 2 cargas, o con 0 horas de diferencia, `rendimiento_l_por_h` es `null` en vez de dividir por cero.
- Las horas de uso se calculan solo con cargas de combustible, no con mantenciones.
- El reporte devuelve además `horas_de_uso` y `litros_considerados_rendimiento`, para que se vea de dónde sale el rendimiento y por qué `litros_totales` no divide exacto entre las horas.
- Las fechas del rango son inclusivas: se usa `startOfDay` y `endOfDay`. Un `whereBetween` solo con fechas dejaría fuera casi todo el último día (hay un test de los extremos).
- Limitación del método: un rango que parte a mitad de un tramo no cuenta el consumo de ese primer tramo.

**Mantención preventiva**

- El límite de 250 h es **estricto**: un equipo con exactamente 250 h no aparece.
- El listado incluye equipos en cualquier estado, también los "fuera de servicio" (tomado literal del enunciado).
- Se resuelve en **una sola consulta** con una subconsulta `MAX(horometro)` que usa el índice `(equipo_id, tipo, horometro)`: sin N+1 y sin traer mantenciones a PHP.
- La ruta `/equipos/mantencion-preventiva` está declarada **antes** del `apiResource` de equipos; si no, Laravel leería "mantencion-preventiva" como el `{equipo}` de `GET /equipos/{equipo}`.

**Errores y validación**

- Toda ruta `/api/*` responde JSON, aunque el cliente no envíe `Accept: application/json`.
- Los `404` de la API devuelven un mensaje genérico (`Recurso no encontrado.`), sin exponer nombres de clases internas.
- `APP_DEBUG=false` en `.env.example`, para que los errores no filtren rutas ni trazas.
- El login devuelve el mismo `401` si el correo no existe o la clave es incorrecta, para no revelar qué correos están registrados.
- El `logout` revoca solo el token usado, no todos los del usuario.
- `codigo_interno` y `patente` se normalizan a mayúsculas antes de validar la unicidad.
- Eliminar un equipo con historial responde `409` con un mensaje claro, no un error `500` de la base de datos. La clave foránea es la segunda barrera.

**Docker**

- **Un solo contenedor de aplicación con `php artisan serve`** (no nginx + php-fpm): menos piezas y más fácil de levantar y explicar. Es para esta prueba, no para producción (ver pregunta 1 de la Parte 2).
- **El healthcheck de MySQL verifica por TCP** (`127.0.0.1`) y no por socket. Con el volumen vacío, la imagen de MySQL arranca primero un servidor temporal solo por socket para ejecutar `init.sql`; un healthcheck por socket pasa durante ese momento y la app intentaba conectarse a una base que aún no escuchaba en red. Lo encontré probando el arranque desde cero.
- El contenedor usa el mismo UID/GID que el usuario del host (`HOST_UID`/`HOST_GID`, por defecto 1000), para que los archivos creados desde el contenedor no queden como `root`.
- Los tests usan una base separada para que `RefreshDatabase` no toque los datos de desarrollo.

**Zona horaria:** `APP_TIMEZONE=America/Santiago`, porque los reportes dependen de dónde cae el límite del día. Las fechas sin zona horaria que envíe el cliente se interpretan como hora de Chile.

## Lo que dejé fuera y por qué

Con 3 a 4 horas preferí menos funcionalidades bien hechas.

- **Vista web.** Era opcional; prioricé la API, los tests y el Docker.
- **Edición y eliminación de cargas y mantenciones.** El enunciado pide registrarlas; hoy se registran y se listan. Cambiar un registro obligaría a recalcular el horómetro del equipo.
- **Registros históricos con horómetros intermedios** (ver decisiones del horómetro).
- **Cambio automático del estado del equipo** al registrar una mantención.
- **Validación de que `fecha_hora` no sea futura.**
- **Tabla `tipos_equipo`.** El tipo es texto libre: una tabla aparte sería sobreingeniería para este alcance.
- **Umbral de 250 h configurable** por equipo o tipo: hoy es una constante del servicio.
- **Excluir equipos "fuera de servicio" de la lista de preventiva.** Lo tomé literal; es una condición más en la consulta.
- **Roles, registro de usuarios y multiempresa.** Hay un único usuario de ejemplo creado por el seeder.
- **Traducción de los mensajes de validación de Laravel**, que salen en inglés; solo los mensajes propios (horómetro, `409`, `404`) están en español.
- **Imagen de producción, CI/CD y despliegue real.** Se describen en la pregunta 1 de la Parte 2.
- **Paginación en el endpoint de preventiva:** devuelve la lista completa.

## Parte 2 · Preguntas cortas

### 1. Despliegue en Google Cloud

Como la API ya corre en Docker, lo más simple sería subir esa misma imagen a Cloud Run, que se encarga de levantarla y escalarla sin que yo administre un servidor. Antes ajustaría la imagen para producción: sin artisan serve, sin las dependencias de desarrollo y con APP_DEBUG=false. Para la base de datos usaría Cloud SQL con MySQL, para no tener que mantener yo el servidor de la base, y la dejaría accesible solo desde la app, no desde internet.

Las credenciales no van en el repositorio ni dentro de la imagen: las guardaría en Secret Manager y se las pasaría a la app como variables de entorno. Para los respaldos, Cloud SQL hace copias automáticas todos los días y permite volver a un momento exacto, y dejaría una retención de un par de semanas. De vez en cuando probaría restaurar una copia en otra instancia, porque un respaldo que nunca se restauró no sirve de mucho.

### 2. Incidente: la app móvil no carga los equipos un lunes a las 8:00

Si la app al menos abre y lo que no aparece son los equipos, lo primero que haría es entrar a la base de datos y ver si los datos están o no. Eso separa el problema en dos casos.

Si los datos están en la base, se están guardando bien y lo que falla es mostrarlos. Probaría yo mismo GET /api/equipos, miraría el log de errores y revisaría si algo cambió, como un despliegue reciente o un token vencido. Si hubo un cambio, vuelvo a la versión anterior para que puedan trabajar y lo reviso con calma después.

Si los datos no están, o dejaron de llegar desde cierta hora, el problema viene antes: la base caída o sin espacio, la app sin poder conectarse, o la app móvil sin lograr enviarlos. Ahí revisaría el servidor, la conexión a la base y los logs.

Mientras tanto aviso a los supervisores de que ya lo estoy viendo, y al final dejo anotado qué pasó para que no se repita.

### 3. Rendimiento: el reporte tarda 15 segundos con dos años de datos

Primero miraría qué es lo que realmente se demora, con EXPLAIN y viendo cuántas filas procesa la consulta, en vez de adivinar. Si el sistema ya está funcionando con muchísimos datos, tocar índices o reestructurar a esa altura puede ser tarde o arriesgado. En Starkoms me pasó algo muy parecido y lo que mejor funcionó fue paginar: no traer todo de una vez, sino por partes. En el reporte lo aplicaría devolviendo el detalle paginado y dividiendo el rango de fechas en tramos (por ejemplo, por mes), en vez de procesar los dos años juntos. Así el usuario ve resultados rápido y la base trabaja menos en cada consulta.

En esta API los índices ya quedaron pensados desde el diseño (por equipo y fecha), así que si igual tarda, lo primero sería confirmar con EXPLAIN que realmente se están usando. Y si hace falta más, una tabla de resumen por mes que se actualice sola.

### 4. Soporte TI: un desarrollo con fecha, un correo bloqueado y un notebook que no enciende

Priorizo por **bloqueo e impacto**, y hago primero lo corto que deja a otros sin poder trabajar, para no quedar atrapado.

1. **Correo (10 min):** la persona está totalmente bloqueada y suele resolverse con un reseteo de clave o MFA desde el panel de administración. Le digo: *"Lo resuelvo ahora, en unos 10 minutos."*
2. **Notebook (diagnóstico de 10 min):** cargador, otro enchufe, reseteo de energía. Si no es trivial, le presto un equipo hoy para que pueda trabajar y lo derivo a garantía o servicio técnico. Le digo: *"Te dejo un equipo hoy y reviso el tuyo con calma."*
3. **Desarrollo con fecha:** con lo anterior despachado, bloqueo el resto de la mañana para el desarrollo. Aviso a quien espera la entrega: *"Me tomaron una hora de soporte; avanzo desde las X y te confirmo a las Y qué alcance asegurado hay."* Si la fecha queda ajustada, propongo recortar alcance antes de que sea un problema.

## Uso de herramientas de IA

Usé Claude como apoyo para planificar, escribir y revisar el código. Revisé cada parte y probé los endpoints a mano, con curl y con los tests automatizados.