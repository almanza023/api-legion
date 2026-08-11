# PROJECT CONTEXT - Laravel API REST (GYM App / API Legion)

Este documento proporciona el contexto técnico completo, detallado y preciso sobre la arquitectura y funcionamiento del backend de la API REST desarrollada en Laravel. Está diseñado para servir como mapa técnico para desarrolladores e Inteligencias Artificiales que necesiten mantener, extender, corregir o agregar funcionalidades al sistema sin vulnerar las convenciones ni la arquitectura existente.

---

## 1. Descripción del proyecto

La **API REST Legion (GYM App)** es el sistema backend de gestión integral para gimnasios y centros de acondicionamiento físico. Su objetivo principal es centralizar la gestión operativa, médica, deportiva y administrativa del gimnasio a través de los siguientes pilares:

* **Autenticación y Seguridad:** Control de acceso mediante JSON Web Tokens (JWT) utilizando el documento de identidad como credencial principal de usuario.
* **Gestión de Clientes y Membresías:** Registro de clientes, asignación y seguimiento de membresías activas, histórico de pagos, fidelización y proyecciones de flujo de caja.
* **Asistencia de Clientes:** Control y registro de asistencias presenciales masivas o mediante documento/QR, además de estadísticas de concurrencia mensual.
* **Valoraciones Físicas y Antropométricas:** Captura y seguimiento de medidas corporales, pliegues, IMC y evaluaciones de salud (Valoración v1 y Valoración v2 con normas SST-F-15 y cuestionario PAR-Q), con generación de reportes en PDF.
* **Remisiones Médicas:** Mantenimiento de médicos tratantes, diagnósticos según el catálogo internacional CIE-10, definición de objetivos médicos/deportivos y restricciones, con exportación oficial en PDF.
* **Prescripción de Ejercicio y Rutinas:** Elaboración de plantillas de entrenamiento, rutinas maestras estructuradas por días de la semana y orden de ejecución, así como registro del progreso del usuario (pesos y repeticiones).
* **Gestión de Equipamiento y Catálogos:** Catálogo de ejercicios, grupos musculares, máquinas de gimnasio y registro de sus mantenimientos preventivos y correctivos.
* **Notificaciones por SMS:** Integración con el proveedor externo **HÁBLAME SMS (v5)** para el envío automatizado de avisos de vencimiento de membresía.
* **Reportes Dinámicos y Banners:** Configuración de reportes ejecutables exportables a PDF y administración de anuncios o banners publicitarios vigentes.

---

## 2. Stack tecnológico

| Componente | Tecnología / Paquete | Versión | Descripción / Uso |
| :--- | :--- | :--- | :--- |
| **Framework Backend** | Laravel Framework | `^11.31` | Framework principal de desarrollo en PHP. |
| **Lenguaje de Programación** | PHP | `^8.2` | Lenguaje de ejecución del servidor. |
| **Base de Datos** | MySQL / MariaDB | N/A | Sistema gestor de base de datos relacional (`DB_CONNECTION=mysql`). |
| **Autenticación JWT** | `tymon/jwt-auth` | `^2.1` | Manejo de autenticación basada en tokens JWT para la API. |
| **Autenticación Token** | `laravel/sanctum` | `^4.0` | Paquete oficial de tokens (instalado en el proyecto). |
| **Generación de PDF** | `setasign/fpdf` | `^1.8` | Librería FPDF para maquetar y generar PDFs de valoraciones y remisiones. |
| **Mensajería SMS** | HÁBLAME API SMS v5 | API HTTP | Servicio externo para envío masivo de mensajes SMS a clientes. |
| **Entorno Dev & Utilities** | `laravel/tinker`, `laravel/pail`, `laravel/pint`, `laravel/sail` | Diversas | Herramientas auxiliares de línea de comandos, formateo y debugging. |

---

## 3. Arquitectura del sistema

La aplicación sigue una **arquitectura en capas basada en Controladores Eloquent directos** (sin implementación de capas adicionales de Services ni Repositories). La lógica de negocio, validación y respuesta JSON se encuentra centralizada en los **Controllers**, mientras que la persistencia y relaciones corresponden a los modelos **Eloquent ORM**.

### Mapa de Arquitectura

```text
               Cliente / Frontend / Aplicación Móvil
                                │
                                ▼
                       routes/api.php
                                │
                 ┌──────────────┴──────────────┐
                 ▼                             ▼
       Rutas Públicas / Anon           Rutas Protegidas
       (login, public/asistencia,      (IsUserAuth Middleware)
        sms cron, etc.)                        │
                 │                             ▼
                 │                   Tymon JWTAuth Facade
                 │                   (Verificación Bearer Token)
                 └──────────────┬──────────────┘
                                │
                                ▼
                           Controllers
           (Lógica de Negocio, Validaciones inline,
            Calculos de IMC, Generación FPDF)
                                │
                                ▼
                           Models (Eloquent)
            (Relaciones, Scopes, Appends, Casts, SoftDeletes)
                                │
                                ▼
                        Base de Datos (MySQL)
```

---

## 4. Estructura del proyecto

```text
apilegion/
├── app/
│   ├── Auth/
│   │   └── CustomUserProvider.php    # Proveedor de autenticación por documento_identidad
│   ├── Http/
│   │   ├── Controllers/             # 40 controladores con la lógica de negocio de la API
│   │   └── Middleware/
│   │       └── IsUserAuth.php       # Middleware de verificación de token JWT
│   ├── Models/                      # 36 modelos Eloquent que representan las tablas de la BD
│   └── Providers/
│       └── AppServiceProvider.php   # Registro de custom_eloquent en Auth::provider
├── bootstrap/
│   └── app.php                      # Configuración central de rutas, middleware y excepciones JSON
├── config/                          # Configuración de auth, jwt, database, app, cors, etc.
├── database/
│   ├── migrations/                  # 28 archivos de migración del esquema de base de datos
│   └── seeders/                     # Seeders (DiagnosticoCie10Seeder, PreguntasEvaluacionSeeder, etc.)
├── routes/
│   ├── api.php                      # Punto de entrada de la API REST
│   └── modules/                     # 32 archivos de rutas organizados por dominio
├── PROJECT_CONTEXT.md               # Este documento de contexto técnico
└── composer.json                    # Dependencias y scripts de Composer
```

---

## 5. Módulos principales

1. **Módulo de Autenticación y Seguridad:** Inicio de sesión por número de documento, cierre de sesión, perfil del usuario autenticado, cambio de contraseña y proveedor de usuario personalizado.
2. **Módulo de Usuarios y Roles:** Administración de usuarios del sistema (entrenadores, administradores, recepcionistas) y su asignación de roles.
3. **Módulo de Clientes:** CRUD de clientes, búsqueda rápida por documento, sincronización con sistemas externos y estado de clientes.
4. **Módulo de Membresías y Facturación:** Definición de planes de membresía, suscripción de clientes a membresías, reportes de cartera, ventas por rango de fechas, fidelización y proyecciones de caja.
5. **Módulo de Control de Asistencias:** Registro de entradas de clientes por documento/QR, historial de asistencias por cliente y por fecha, e indicadores de clientes más frecuentes.
6. **Módulo de Valoraciones Físicas (v1 y v2):**
   * **v1:** Medidas antropométricas básicas, IMC y generación de ficha en PDF.
   * **v2:** Evaluación de salud de la guía SST-F-15, cuestionario PAR-Q (7 preguntas), antecedentes cardiovasculares personal/familiar, perfil lipídico y generación de PDF estructurado.
7. **Módulo de Remisiones Médicas:** Gestión de médicos tratantes, catálogo de diagnósticos CIE-10, prescripción de objetivos y restricciones médicas con exportador PDF oficial.
8. **Módulo de Prescripción de Entrenamiento (Rutinas y Planes):**
   * **Plantillas de Planes:** Diseños preestablecidos de planes de entrenamiento.
   * **Rutinas Maestras:** Rutinas personalizadas asignadas a clientes por entrenador, organizadas por día de la semana (`detallesPorDia`).
   * **Progreso de Rutina:** Registro puntual de series, repeticiones y cargas alcanzadas por el cliente.
9. **Módulo de Catálogos y Mantenimiento:** Administración de ejercicios, grupos musculares, máquinas y registro de mantenimientos técnicos.
10. **Módulo de Notificaciones SMS:** Configuración del servicio HÁBLAME SMS, envío automático de SMS para membresías por vencer y registro en `sms_envio_logs`.
11. **Módulo de Evaluación y Encuestas:** Configuración de preguntas de evaluación clínica o de satisfacción y captura de respuestas del cliente.
12. **Módulo de Reportes Ejecutables y Dashboard:** Ejecución de consultas reportables en formato JSON/PDF y métricas consolidadas para el tablero de control.

---

## 6. Endpoints REST

A continuación se detallan las principales rutas reales encontradas en el proyecto (prefijo `/api`):

| Método | Endpoint | Controller@Método | Middleware | Autenticación | Descripción |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **POST** | `/api/login` | `AuthController@login` | Ninguno | No | Autentica un usuario por `documento_identidad` y `password`, retornando el JWT. |
| **POST** | `/api/logout` | `AuthController@logout` | `IsUserAuth` | Sí | Invalida el token JWT del usuario actual. |
| **GET** | `/api/me` | `AuthController@getUser` | `IsUserAuth` | Sí | Retorna la información del usuario autenticado. |
| **POST** | `/api/change-password` | `AuthController@changePassword` | `IsUserAuth` | Sí | Permite al usuario cambiar su contraseña actual. |
| **GET** | `/api/users` | `AuthController@index` | `IsUserAuth` | Sí | Obtiene el listado de usuarios del sistema. |
| **POST** | `/api/users` | `AuthController@store` | `IsUserAuth` | Sí | Registra un nuevo usuario en el sistema. |
| **GET** | `/api/users/{id}` | `AuthController@show` | `IsUserAuth` | Sí | Muestra el detalle de un usuario específico. |
| **PUT** | `/api/users/{id}` | `AuthController@update` | `IsUserAuth` | Sí | Actualiza los datos de un usuario. |
| **DELETE** | `/api/users/{id}` | `AuthController@destroy` | `IsUserAuth` | Sí | Realiza soft delete de un usuario. |
| **GET/POST** | `/api/clientes` | `ClienteAsynController` (apiResource) | Ninguno | No | Rutas apiResource estándar para clientes. |
| **GET** | `/api/clientes-async` | `ClienteAsynController@index` | `IsUserAuth` | Sí | Lista clientes en modo asíncrono para administración. |
| **POST** | `/api/clientes-async/store` | `ClienteAsynController@store` | `IsUserAuth` | Sí | Registra un nuevo cliente desde el panel interno. |
| **GET** | `/api/clientes/documento/{doc}` | `ClienteAsynController@getByDocumento` | `IsUserAuth` | Sí | Busca un cliente por su número de documento de identidad. |
| **POST** | `/api/public/cliente/register` | `PublicClienteController@createOrUpdateCliente` | Ninguno | No | Endpoint público para registro o actualización rápida de clientes. |
| **POST** | `/api/public/asistencias-clientes/registrar-por-documento` | `AsistenciaClienteController@registrarPublicoPorDocumento` | Ninguno | No | Marca la asistencia de un cliente desde una terminal pública mediante su documento. |
| **GET** | `/api/asistencias-clientes` | `AsistenciaClienteController@index` | `IsUserAuth` | Sí | Obtiene el histórico de asistencias. |
| **GET** | `/api/asistencias-clientes-by-cliente/{id}` | `AsistenciaClienteController@getByCliente` | `IsUserAuth` | Sí | Consulta las asistencias de un cliente en particular. |
| **GET** | `/api/cliente-membresias` | `ClienteMembresiaAsynController@index` | `IsUserAuth` | Sí | Lista las membresías asignadas a clientes. |
| **POST** | `/api/cliente-membresias` | `ClienteMembresiaAsynController@store` | `IsUserAuth` | Sí | Vende/asigna una membresía a un cliente. |
| **GET** | `/api/cliente-membresias-activas` | `ClienteMembresiaAsynController@getActivas` | `IsUserAuth` | Sí | Obtiene el listado de membresías activas vigentes. |
| **POST** | `/api/cliente-membresias/resumen-por-rango-fechas` | `ClienteMembresiaAsynController@resumenPorRangoFechas` | `IsUserAuth` | Sí | Reporte financiero de ventas en un rango de fechas. |
| **POST** | `/api/cliente-membresias/proyeccion-flujo-caja` | `ClienteMembresiaAsynController@proyeccionFlujoCaja` | `IsUserAuth` | Sí | Genera una proyección financiera de ingresos por membresías. |
| **GET** | `/api/valoraciones` | `ValoracionController@index` | `IsUserAuth` | Sí | Lista las valoraciones físicas v1. |
| **POST** | `/api/valoraciones` | `ValoracionController@store` | `IsUserAuth` | Sí | Guarda una valoración física v1. |
| **GET** | `/api/valoraciones/{id}/pdf` | `ValoracionPdfController@generarPdf` | `IsUserAuth` | Sí | Genera y descarga el reporte PDF de una valoración v1. |
| **GET** | `/api/valoraciones-v2` | `ValoracionV2Controller@index` | `IsUserAuth` | Sí | Lista las valoraciones médicas/antropométricas v2. |
| **POST** | `/api/valoraciones-v2` | `ValoracionV2Controller@store` | `IsUserAuth` | Sí | Registra una valoración v2 (con PAR-Q y SST-F-15). |
| **GET** | `/api/valoraciones-v2/{id}/pdf` | `ValoracionV2PdfController@generarPdf` | `IsUserAuth` | Sí | Genera el documento oficial en PDF de la valoración v2. |
| **GET** | `/api/medicos` | `MedicoController@index` | `IsUserAuth` | Sí | Muestrario de médicos registrados para remisiones. |
| **POST** | `/api/medicos` | `MedicoController@store` | `IsUserAuth` | Sí | Registra un nuevo médico tratante. |
| **GET** | `/api/diagnosticos-cie10` | `DiagnosticoCie10Controller@index` | `IsUserAuth` | Sí | Catálogo de diagnósticos médicos CIE-10. |
| **GET** | `/api/remisiones-medicas` | `RemisionMedicaController@index` | `IsUserAuth` | Sí | Consulta de remisiones médicas registradas. |
| **POST** | `/api/remisiones-medicas` | `RemisionMedicaController@store` | `IsUserAuth` | Sí | Crea una orden de remisión médica con sus restricciones. |
| **GET** | `/api/remisiones-medicas/cliente/{clienteId}` | `RemisionMedicaController@historicoCliente` | `IsUserAuth` | Sí | Histórico de remisiones emitidas para un cliente. |
| **GET** | `/api/remisiones-medicas/{id}/pdf` | `RemisionMedicaPdfController@generarPdf` | `IsUserAuth` | Sí | Descarga en PDF la remisión médica firmada. |
| **GET** | `/api/rutinas-maestras` | `RutinaMaestraAsynController@index` | `IsUserAuth` | Sí | Lista las rutinas maestras registradas. |
| **POST** | `/api/rutinas-maestras/completa` | `RutinaMaestraAsynController@storeCompleta` | `IsUserAuth` | Sí | Guarda una rutina maestra con todos sus detalles diarios. |
| **PUT** | `/api/rutinas-maestras/{id}/completa` | `RutinaMaestraAsynController@updateCompleta` | `IsUserAuth` | Sí | Actualiza una rutina maestra y sus ejercicios asignados. |
| **POST** | `/api/rutinas-maestras/by-cliente` | `RutinaMaestraAsynController@showByCliente` | `IsUserAuth` | Sí | Obtiene la rutina activa de un cliente específico. |
| **GET** | `/api/plantillas-planes` | `PlantillaPlanEntrenamientoController@index` | `IsUserAuth` | Sí | Consulta de plantillas de planes de entrenamiento. |
| **POST** | `/api/progresos-rutina` | `ProgresoRutinaDetalleController@store` | `IsUserAuth` | Sí | Registra el seguimiento del entrenamiento diario del cliente. |
| **GET** | `/api/notificaciones/sms/logs` | `NoitificacioControoller@historial` | `IsUserAuth` | Sí | Consulta el historial de envíos de SMS. |
| **GET** | `/api/notificaciones/sms/membresias-por-vencer` | `NoitificacioControoller@enviarSmsMembresiasPorVencer` | Ninguno | No | Disparador (cron job) que procesa y envía SMS a membresías próximas a vencer. |
| **POST** | `/api/notificaciones/sms` | `NoitificacioControoller@enviarSms` | Ninguno | No | Envío manual o directo de un SMS a un teléfono determinado. |
| **GET** | `/api/dashboard` | `DashboardController@index` | `IsUserAuth` | Sí | Métricas e indicadores consolidados del tablero principal. |
| **GET** | `/api/dashboard/top-asistencias-mes` | `DashboardController@topAsistenciasMes` | `IsUserAuth` | Sí | Top de clientes con mayor número de asistencias en el mes. |

---

## 7. Controllers

El directorio `app/Http/Controllers/` contiene 40 controladores. Sus responsabilidades principales se resumen por dominio:

1. **`AuthController.php`:** Maneja el flujo de login (autenticación contra `CustomUserProvider`), emisión de JWT, lectura del perfil (`me`), logout, cambio de contraseña y CRUD de usuarios administrativos.
2. **`ClienteAsynController.php` & `PublicClienteController.php`:** Administración de datos personales de clientes, sincronizaciones externas y consultas por documento.
3. **`ClienteMembresiaAsynController.php` & `MembresiaAsynController.php`:** Control de membresías, vencimientos, activaciones, cálculo de fidelización y proyecciones financieras.
4. **`AsistenciaClienteController.php`:** Lógica de marcado de asistencias por documento (público e interno), filtros por fecha y reportes.
5. **`ValoracionController.php` & `ValoracionPdfController.php`:** Procesamiento y generación de PDFs para la valoración física v1.
6. **`ValoracionV2Controller.php` & `ValoracionV2PdfController.php`:** Lógica clínica avanzada de valoración v2, cálculo automático de IMC, procesamiento del cuestionario PAR-Q y renderizado de PDF oficial de dos páginas.
7. **`MedicoController.php`, `ObjetivoRemisionController.php`, `DiagnosticoCie10Controller.php`, `RemisionMedicaController.php` & `RemisionMedicaPdfController.php`:** Módulo completo de remisión médica, gestión del personal de salud, búsqueda de códigos CIE-10 y maquetación de PDF de remisiones.
8. **`RutinaMaestraAsynController.php`, `RutinaAsynController.php` & `PlantillaPlanEntrenamientoController.php`:** Elaboración, duplicación y actualización de programas de entrenamiento estructurados por días y ejercicios.
9. **`ProgresoRutinaDetalleController.php`:** Control del rendimiento físico del cliente (cargas y repeticiones ejecutadas).
10. **`NoitificacioControoller.php` & `SmsConfiguracionController.php`:** Integración con la API de HÁBLAME SMS, filtrado de destinatarios con membresía por vencer y registro en base de datos.
11. **`EjercicioAsynController.php`, `GrupoMuscularAsynController.php`, `MaquinaAsynController.php`, `MantenimientoMaquinaAsynController.php` & `VideoAsynController.php`:** Mantenimiento de catálogos deportivos, equipamiento técnico y biblioteca multimedia.
12. **`DashboardController.php`, `ReporteController.php` & `ReporteEjecutarController.php`:** Consolidación de estadísticas globales del gimnasio y ejecución de reportes dinámicos.

---

## 8. Services y Repositories

* **Services:** `No identificado`
* **Repositories:** `No identificado`

**Patrón Utilizado:** El proyecto utiliza el patrón directo **Fat Controller - Eloquent Model**. Las consultas Eloquent, filtros de búsqueda, operaciones de agregación y transformaciones de datos están programadas directamente en los métodos de los **Controllers**, apoyándose en **Eloquent Relationships** y **Accessors/Mutators** definidos en los **Models**.

---

## 9. Models

El proyecto cuenta con **36 modelos Eloquent** en `app/Models/`. Los modelos principales son:

### 1. `User.php`
* **Tabla:** `users`
* **Características:** Implementa `JWTSubject`, usa `HasFactory`, `Notifiable`, `SoftDeletes`.
* **Fillable:** `name`, `email`, `password`, `signature`, `status`, `rol_id`, `documento_identidad`, `ultimo_acceso`, `change_password`.
* **Casts:** `email_verified_at` => `datetime`, `password` => `hashed`, `ultimo_acceso` => `datetime`, `change_password` => `boolean`.
* **Relaciones:** `belongsTo(Rol::class, 'rol_id')`.
* **Métodos JWT:** `getJWTIdentifier()` retorna la clave primaria; `username()` retorna `'documento_identidad'`.

### 2. `Cliente.php`
* **Tabla:** `clientes`
* **Fillable:** `nombre`, `apellidos`, `tipo_documento`, `numero_documento`, `sexo`, `telefono`, `correo`, `fecha_nacimiento`, `sincronizado`.
* **Relaciones:** `hasMany(Rutina::class)`, `hasMany(ClienteMembresia::class)`, `hasMany(AsistenciaCliente::class)`, `hasMany(RemisionMedica::class)`.
* **Métodos útiles:** `tieneMembresiaActiva()` valida si el cliente posee una membresía en estado activo con `fecha_fin >= hoy`.

### 3. `ValoracionV2.php`
* **Tabla:** `valoraciones_v2`
* **Fillable:** `cliente_id`, `entrenador_id`, `imc_clasificacion_id`, `convenio`, `nombre_empresa`, `estatura`, `peso`, `edad`, `sexo`, medidas corporales (`pecho`, `brazo_izquierdo`, `brazo_derecho`, `perimetro_abdominal`, `pierna_izquierda`, `pierna_derecha`), variables cardiovasculares (`hipertension`, `fuma`, `lesion`, `tension_arterial`, `antecedentes_cardio_personales`, `antecedentes_cardio_familiares`, `colesterol_alto`, `trigliceridos_altos`, `toma_medicamento`), respuestas PAR-Q (`parq_q1` a `parq_q7`), etc.
* **Casts:** Múltiples variables numéricas casteadas a `decimal:2` y preguntas PAR-Q/antecedentes a `boolean`.
* **Relaciones:** `belongsTo(Cliente::class)`, `belongsTo(Entrenador::class)`, `belongsTo(ImcClasificacion::class)`.
* **Método útil:** `calcularImc()` calcula el IMC como `peso / (estatura * estatura)`.

### 4. `RemisionMedica.php`
* **Tabla:** `remisiones_medicas`
* **Fillable:** `cliente_id`, `medico_id`, `cie10_id`, `diagnostico`, `codigo_cie10`, `objetivos`, `objetivo_otro`, `restricciones`, `vigencia`, `vigencia_otra_descripcion`, `estado`, `fecha_registro`.
* **Casts:** `'objetivos' => 'array'`, `'fecha_registro' => 'date'`.
* **Relaciones:** `belongsTo(Cliente::class)`, `belongsTo(Medico::class)`, `belongsTo(DiagnosticoCie10::class, 'cie10_id')`.

### 5. `RutinaMaestra.php`
* **Tabla:** `rutinas_maestras` (usa `SoftDeletes`)
* **Fillable:** `nombre`, `descripcion`, `objetivo`, `estado`, `cliente_id`, `entrenador_id`, `valoracion_id`, `fecha_inicio`, `fecha_fin`, `trimestre`.
* **Appends:** `['cliente_info', 'entrenador_info']` (propiedades calculadas dinámicas para las respuestas API).
* **Relaciones:** `hasMany(DetalleRutinaMaestra::class, 'rutina_maestra_id')`, `belongsTo(Cliente::class)`, `belongsTo(Entrenador::class)`, `belongsTo(Valoracion::class)`.

---

## 10. Base de datos

* **Motor:** MySQL / MariaDB (`DB_CONNECTION=mysql`).
* **Base de datos predeterminada:** `u855016712_gymapp`.
* **Migraciones principales (`database/migrations/`):**
  * `0001_01_01_000000_create_users_table.php`: Estructura inicial de usuarios, tokens de recuperación y sesiones.
  * `2025_02_08_133640_create_document_types_table.php`: Tipos de documentos de identidad.
  * `2025_02_08_141700_create_rutinas_table.php`: Estructura de rutinas simples y detalles.
  * `2025_11_16_190000_create_empresas_table.php`: Catálogo de empresas para convenios corporativos.
  * `2025_11_29_014800_create_plantillas_planes_entrenamiento_tables.php`: Tablas para plantillas predefinidas.
  * `2025_12_20_104508_create_cliente_membresia_table.php`: Vinculación de clientes con planes de membresía.
  * `2025_12_20_164700_create_publicidad_table.php`: Banners y anuncios comerciales.
  * `2026_03_26_000000_create_sms_configuraciones_table.php` y `010000_create_sms_envio_logs_table.php`: Configuración e historial de SMS.
  * `2026_03_26_100000_create_preguntas_evaluacion_table.php` y `respuestas_preguntas_cliente`: Sistema dinámico de encuestas/evaluaciones.
  * `2026_03_26_120001_create_asistencia_clientes_table.php`: Registro de accesos diarios.
  * `2026_07_22_000000_create_valoraciones_v2_table.php`: Tabla amplia con campos médicos, antropométricos y PAR-Q.
  * `2026_08_05_000001` a `000004`: Creación de las tablas `medicos`, `objetivos_remision`, `diagnosticos_cie10` y `remisiones_medicas`.

---

## 11. Autenticación y autorización

### Autenticación JWT (`tymon/jwt-auth`)
* **Guard por defecto:** `api` (configurado en `config/auth.php` con driver `jwt`).
* **Credencial Principal:** La autenticación se realiza mediante la columna `documento_identidad` en lugar del correo electrónico habitual.
* **Proveedor de Usuario Personalizado (`CustomUserProvider`):** Se creó la clase `App\Auth\CustomUserProvider` (que extiende de `EloquentUserProvider`) para buscar al usuario por `documento_identidad` durante el login. Este proveedor está registrado en `AppServiceProvider.php` mediante `Auth::provider('custom_eloquent', ...)`.
* **Middleware `IsUserAuth` (`app/Http/Middleware/IsUserAuth.php`):**
  * Intercepta la petición HTTP y evalúa el token Bearer en el encabezado `Authorization`.
  * Invoca `JWTAuth::parseToken()->authenticate()`.
  * Si el token es inválido, ha expirado o no se encuentra el usuario, retorna una respuesta JSON con código HTTP `401`.

### Roles y Permisos
* El sistema almacena el campo `rol_id` en la tabla `users` relacionándolo con el modelo `Rol.php` (`roles`).
* **Policies & Gates:** `No identificado` (No existen clases Policy en `app/Policies` ni Gates declarados explícitamente en `AppServiceProvider`).

---

## 12. Validaciones

* **Form Requests (`app/Http/Requests`):** `No identificado` (No se emplean clases FormRequest separadas).
* **Mecanismo de validación:** Se realiza la validación directa dentro de los métodos de los controladores utilizando `$request->validate([...])` o la fachada `Validator::make($request->all(), [...])`.
* **Manejo de errores de validación:** Cuando se lanza una excepción de validación (`ValidationException`), el manejador central de excepciones registrado en `bootstrap/app.php` la captura automáticamente y responde con código HTTP `422` y la estructura estandarizada de errores JSON.

---

## 13. API Resources

* **API Resources (`app/Http/Resources`):** `No identificado` (No existen clases de recursos API).
* **Formato de Respuestas:** Los controladores devuelven directamente colecciones/modelos Eloquent mediante `response()->json(...)`. La personalización de los atributos expuestos en el JSON se logra mediante:
  1. Propiedades `$fillable` y `$hidden` en los modelos.
  2. Atributos calculados y añadidos con `$appends` (por ejemplo, `cliente_info` y `entrenador_info` en `RutinaMaestra.php`).
  3. Formateo manual mediante arrays asociativos dentro del controlador.

---

## 14. Manejo de errores

El manejador global de excepciones se encuentra configurado en `bootstrap/app.php` utilizando la API de configuración de excepciones de Laravel 11 (`->withExceptions(...)`).

Cualquier excepción dentro de rutas `/api/*` o que solicite JSON (`expectsJson()`) genera una respuesta estandarizada:

```json
{
  "status": "fail",
  "msg": "Descripción breve del error",
  "code": 401,
  "path": "/api/users",
  "timestamp": "2026-08-09T17:56:53.000000Z",
  "errors": {
    "campo": ["Detalle de validación"]
  }
}
```

### Códigos HTTP comunes mapeados:
* `401`: Token JWT inválido, expirado o credenciales erróneas (`AuthenticationException`).
* `403`: Acción no autorizada.
* `404`: Recurso o ruta no encontrada.
* `405`: Método HTTP no permitido.
* `422`: Errores de validación de entradas (`ValidationException`).
* `500`: Error interno del servidor (con detalles en `exception` y `detail` si `APP_DEBUG=true`).

---

## 15. Variables de entorno

Nombres de las variables de entorno utilizadas por el backend (`.env`):

### Configuración General del Sistema
* `APP_NAME`
* `APP_ENV`
* `APP_KEY`
* `APP_DEBUG`
* `APP_TIMEZONE`
* `APP_URL`
* `APP_LOCALE`
* `APP_FALLBACK_LOCALE`
* `APP_FAKER_LOCALE`

### Logging y Almacenamiento
* `LOG_CHANNEL`
* `LOG_STACK`
* `LOG_LEVEL`
* `SESSION_DRIVER`
* `SESSION_LIFETIME`
* `CACHE_STORE`
* `QUEUE_CONNECTION`

### Base de Datos MySQL
* `DB_CONNECTION`
* `DB_HOST`
* `DB_PORT`
* `DB_DATABASE`
* `DB_USERNAME`
* `DB_PASSWORD`

### Autenticación JWT
* `JWT_SECRET`

### Proveedor SMS (HÁBLAME SMS API)
* `HABLAME_API_KEY`
* `HABLAME_SMS_URL`
* `HABLAME_SMS_CAMPAIGN_NAME`
* `HABLAME_SMS_FROM`

---

## 16. Dependencias principales

| Paquete | Versión | Propósito / Uso |
| :--- | :--- | :--- |
| `laravel/framework` | `^11.31` | Motor central del backend. |
| `tymon/jwt-auth` | `^2.1` | Autenticación con Tokens JWT para API REST. |
| `laravel/sanctum` | `^4.0` | Infraestructura alternativa de tokens para Laravel. |
| `setasign/fpdf` | `^1.8` | Generación e impresión de reportes y certificaciones en PDF. |
| `laravel/tinker` | `^2.9` | Consola interactiva REPL de Laravel. |
| `fakerphp/faker` | `^1.23` | Generador de datos de prueba para factories y seeders. |

---

## 17. Archivos críticos

Antes de realizar modificaciones en el código, una IA debe revisar cuidadosamente los siguientes archivos:

1. [bootstrap/app.php](file:///Applications/MAMP/htdocs/apilegion/bootstrap/app.php): Define el comportamiento global de las rutas API, registro de middleware y renderizado estandarizado de excepciones JSON.
2. [routes/api.php](file:///Applications/MAMP/htdocs/apilegion/routes/api.php): Punto de entrada que agrupa y carga los 32 módulos de rutas en `routes/modules/`.
3. [app/Auth/CustomUserProvider.php](file:///Applications/MAMP/htdocs/apilegion/app/Auth/CustomUserProvider.php): Lógica de consulta de usuario por `documento_identidad`.
4. [app/Providers/AppServiceProvider.php](file:///Applications/MAMP/htdocs/apilegion/app/Providers/AppServiceProvider.php): Registra `custom_eloquent` como el proveedor de autenticación.
5. [app/Http/Middleware/IsUserAuth.php](file:///Applications/MAMP/htdocs/apilegion/app/Http/Middleware/IsUserAuth.php): Middleware crítico que valida los tokens JWT en todas las peticiones privadas.
6. [app/Models/User.php](file:///Applications/MAMP/htdocs/apilegion/app/Models/User.php): Modelo de usuario que implementa `JWTSubject` y define el campo de autenticación.
7. [app/Http/Controllers/AuthController.php](file:///Applications/MAMP/htdocs/apilegion/app/Http/Controllers/AuthController.php): Emisión de JWT, autenticación y manejo de sesión.
8. [config/auth.php](file:///Applications/MAMP/htdocs/apilegion/config/auth.php): Configuración de los guards de autenticación (`api` -> `jwt`).

---

## 18. Convenciones del proyecto

* **Organización de rutas:** Cada módulo tiene su propio archivo dentro de `routes/modules/` (ejemplo: `valoracion_v2.php`, `remision-medica-async.php`) y se importa dentro de `routes/api.php`.
* **Nomenclatura de controladores:** La mayoría de controladores asíncronos o de API incluyen el sufijo `AsynController` (ejemplo: `ClienteAsynController`, `RutinaMaestraAsynController`).
* **Autenticación por Documento:** El campo `documento_identidad` reemplaza al `email` como identificador principal para el inicio de sesión.
* **Borrado Lógico (Soft Deletes):** Se utiliza `SoftDeletes` en modelos clave como `User` y `RutinaMaestra`.
* **Generación Directa de PDF:** Para reportes complejos (como remisiones médicas o valoraciones), se utiliza la librería `FPDF` instanciada directamente desde controladores especializados (`ValoracionV2PdfController`, `RemisionMedicaPdfController`).
* **Validación Directa:** Las validaciones se ejecutan en los controladores vía `$request->validate()` o `Validator::make()`.

---

## 19. Reglas para IA

Cuando una Inteligencia Artificial vaya a implementar nuevas funciones o realizar modificaciones en este backend, debe cumplir obligatoriamente con las siguientes reglas:

1. **Respetar el módulo de rutas:** Si crea un nuevo dominio, añada un archivo `.php` en `routes/modules/` e impórtelo en `routes/api.php`.
2. **Proteger rutas con `IsUserAuth`:** Todas las rutas privadas deben agruparse o protegerse con `Route::middleware([IsUserAuth::class])`.
3. **Preservar el Login por `documento_identidad`:** No alterar la lógica de `CustomUserProvider` ni la implementación de `username()` en `User.php`.
4. **No forzar capas innecesarias:** No cree capas `Service` ni `Repository` a menos que sea un requerimiento explícito; mantenga la lógica dentro de controladores y modelos según la convención del proyecto.
5. **Formato de Respuestas y Excepciones:** No altere la captura de excepciones en `bootstrap/app.php`. Asegúrese de que las validaciones fallen de forma natural mediante excepciones de Laravel para que el manejador capture y devuelva la estructura JSON esperada.
6. **Uso de SoftDeletes:** Si se agrega una nueva entidad crítica, recuerde añadir la columna `$table->softDeletes()` en la migración y el trait `use SoftDeletes;` en el modelo correspondiente.
7. **No revelar secretos ni credenciales:** Nunca incluya llaves privadas, passwords ni tokens reales en el código fuente.

---

## 20. Resumen técnico (AI QUICK CONTEXT)

| Concepto | Valor |
| :--- | :--- |
| **Proyecto** | API REST Backend GYM App (API Legion) |
| **Framework** | Laravel 11 (`^11.31`) |
| **PHP** | `^8.2` |
| **Autenticación API** | JWT (`tymon/jwt-auth: ^2.1`) + Middleware `IsUserAuth` |
| **Campo de Login** | `documento_identidad` (vía `CustomUserProvider`) |
| **Base de Datos** | MySQL (`u855016712_gymapp`) |
| **Generador PDF** | FPDF (`setasign/fpdf: ^1.8`) |
| **Servicio SMS** | HÁBLAME SMS API v5 |
| **Estructura de Rutas** | Carga modular desde `routes/modules/*.php` |
| **Arquitectura** | Layered API REST (Controller - Eloquent Model) |
| **Services / Repositories** | `No identificado` (Lógica en Controllers) |
| **Form Requests / API Resources**| `No identificado` (Validación y JSON en Controllers) |
| **Policies / Gates** | `No identificado` |
| **Manejo de Errores** | Estandarizado en JSON desde `bootstrap/app.php` |
