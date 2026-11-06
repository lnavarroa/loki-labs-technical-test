# Loki Labs Technical Test

API REST desarrollada en Laravel para la gestión de productos.

## Descripción

Este proyecto corresponde a la prueba técnica para Loki Labs. La aplicación permite administrar productos mediante una API REST, implementando operaciones CRUD, validaciones, persistencia en base de datos y documentación para facilitar su despliegue y evaluación.

# ConstrucKIT · API de equipos, combustible y mantenciones
 
Prueba técnica para Loki Labs
 
API REST en Laravel para que una empresa registre sus equipos (camiones, excavadoras, generadores), las cargas de combustible y las mantenciones de cada uno, con reportes de consumo, gasto y mantención preventiva.
 
> **Estado:** en desarrollo. Las secciones marcadas con `TODO` se completan a medida que avanza el proyecto.
 
## Stack
 
- PHP 8.x + Laravel (versión: `TODO`)
- MySQL 8
- Laravel Sanctum (autenticación por token)
- Docker Compose (app + base de datos)
## Cómo levantar el proyecto
 
`TODO: completar y probar desde cero cuando el Docker Compose esté listo.`
 
Requisitos: Docker y Docker Compose.
 
```bash
git clone <url-del-repo>
cd <carpeta>
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```
 
La API queda disponible en `http://localhost:<puerto>` (`TODO`).
 
### Ejecutar los tests
 
```bash
docker compose exec app php artisan test
```
 
## Cómo probar la API
 
- Colección de Postman: `docs/postman_collection.json` (`TODO`)
- Ejemplos con `curl`: ver sección [Endpoints](#endpoints).
- Usuario de ejemplo (seeder): `TODO`
## Endpoints
 
Todos los endpoints, salvo el login, requieren el header `Authorization: Bearer <token>`.
 
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/login` | Obtiene un token (`TODO`) |
| GET | `/api/equipos` | Lista equipos |
| POST | `/api/equipos` | Crea un equipo |
| GET | `/api/equipos/{id}` | Consulta un equipo |
| PUT/PATCH | `/api/equipos/{id}` | Edita un equipo |
| DELETE | `/api/equipos/{id}` | Elimina un equipo |
| POST | `/api/equipos/{id}/cargas-combustible` | Registra una carga de combustible |
| POST | `/api/equipos/{id}/mantenciones` | Registra una mantención |
| GET | `/api/equipos/{id}/reporte?desde=&hasta=` | Reporte por equipo y rango de fechas |
| GET | `/api/equipos/mantencion-preventiva` | Equipos que requieren mantención preventiva |
 
`TODO: ajustar rutas definitivas y agregar ejemplos de request/response.`
 
## Modelo de datos
 
`TODO: diagrama o descripción de tablas, relaciones, índices y restricciones.`
 
- **equipos:** código interno (único), tipo, patente (opcional), marca, modelo, horómetro actual, estado (operativo / en mantención / fuera de servicio).
- **cargas_combustible:** equipo, fecha y hora, litros, monto en CLP, horómetro al cargar.
- **mantenciones:** equipo, tipo (preventiva / correctiva), fecha, horómetro, costo en CLP, descripción.
## Reglas de negocio
 
- Al registrar una carga o mantención con horómetro mayor al actual del equipo, se actualiza el horómetro del equipo.
- Un horómetro menor al último registrado se rechaza con un error claro.
- Un equipo necesita mantención preventiva si lleva más de 250 horas de horómetro desde su última mantención preventiva (o desde cero, si nunca tuvo una).
- El reporte entrega: litros totales, gasto total en combustible, gasto total en mantenciones y rendimiento promedio en litros por hora de uso.
## Decisiones tomadas
 
Cuando algo no estaba claro en el enunciado, tomé una decisión razonable y la dejo documentada aquí.
 
`TODO: ir completando durante el desarrollo.` Puntos a definir:
 
- Qué pasa si el horómetro es igual al último registrado (¿se acepta o se rechaza?).
- Cómo se calcula el rendimiento en L/h dentro de un rango de fechas.
- Qué ocurre con las cargas y mantenciones al eliminar un equipo.
- Cómo se valida el horómetro si se registran datos con fechas anteriores a otros ya existentes.
- Formato de errores de la API.
- Si el estado del equipo cambia automáticamente al registrar una mantención.


## Lo que dejé fuera y por qué
 
`TODO: completar al final.`


####
## Parte 2 · Preguntas cortas
 
### 1. Despliegue en Google Cloud
 
`TODO`
 
### 2. Incidente: la app móvil no carga los equipos un lunes a las 8:00
 
`TODO`
 
### 3. Rendimiento: el reporte tarda 15 segundos con dos años de datos
 
`TODO`
 
### 4. Soporte TI: priorización en una mañana con varias urgencias
 
`TODO`

## Decisiones técnicas

- Laravel como framework principal.
- Arquitectura orientada a servicios para desacoplar la lógica de negocio.
- Validaciones mediante Form Requests.
- Base de datos MySQL.
- Contenedorización mediante Docker Compose.
- Seeders para datos de prueba.
- Pruebas automatizadas para los endpoints principales.

## Autor

Luis Alfonso Navarro Arenas