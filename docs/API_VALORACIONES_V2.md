# 🚀 Documentación de la API: Valoraciones v2 (SST-F-15 + PAR-Q)

## 📌 Base URL
```text
/api/valoraciones-v2
```
> **Headers requeridos:**
> - `Content-Type: application/json`
> - `Authorization: Bearer {token}` *(si se requiere autenticación)*

---

## 📋 Lista de Endpoints disponibles

| Método | Endpoint | Descripción |
| :--- | :--- | :--- |
| `GET` | `/api/valoraciones-v2` | Obtener todas las valoraciones v2 (Soporta paginación: `?paginate=true&per_page=12`) |
| `POST` | `/api/valoraciones-v2` | Crear nueva valoración v2 (Formulario + Cuestionario PAR-Q) |
| `GET` | `/api/valoraciones-v2/{id}` | Obtener detalle de una valoración v2 por ID |
| `PUT` | `/api/valoraciones-v2/{id}` | Actualizar datos de una valoración v2 por ID |
| `DELETE` | `/api/valoraciones-v2/{id}` | Eliminar una valoración v2 por ID |
| `POST` | `/api/valoraciones-v2/por-cliente` | Listar valoraciones v2 de un cliente específico |
| `POST` | `/api/valoraciones-v2/ultima-cliente` | Obtener la última valoración v2 de un cliente |
| `POST` | `/api/valoraciones-v2/por-fecha` | Filtrar valoraciones v2 por rango de fechas |
| `POST` | `/api/valoraciones-v2/por-entrenador` | Filtrar valoraciones v2 por entrenador |
| `POST` | `/api/valoraciones-v2/comparar-avances` | Comparar avances (inicial vs final) de un trimestre |
| `GET` | `/api/valoraciones-v2/{id}/pdf` | Generar y descargar el PDF de 2 páginas (Formulario + PAR-Q) |

---

## 📝 1. Crear Valoración v2 (`POST /api/valoraciones-v2`)

### 📤 JSON Payload (Ejemplo completo de envío):
```json
{
  "cliente_id": 1,
  "entrenador_id": 2,
  "convenio": true,
  "nombre_empresa": "Legion Gym S.A.S.",
  
  "estatura": 1.75,
  "peso": 75.50,
  "edad": 28,
  "sexo": "Hombre",
  "pecho": 98.00,
  "brazo_izquierdo": 34.50,
  "brazo_derecho": 35.00,
  "perimetro_abdominal": 82.00,
  "pierna_izquierda": 55.00,
  "pierna_derecha": 55.50,

  "frecuencia1": 70,
  "frecuencia2": 140,
  "frecuencia3": 90,

  "push_up": 30,
  "abdominales": 45,

  "porcentaje_grasa": 18.50,
  "porcentaje_musculo": 42.00,
  "grasa_visceral": 4.00,
  "masa_musculo_esqueletica": 33.50,

  "hipertension": false,
  "fuma": false,
  "lesion": true,
  "tension_arterial": "120/80",
  "antecedentes_cardio_personales": false,
  "antecedentes_cardio_familiares": false,
  "colesterol_alto": false,
  "trigliceridos_altos": false,
  "toma_medicamento": false,
  "toma_foto": true,
  "descripcion_lesion": "Molestia leve en la rodilla derecha al flexionar a más de 90°",

  "observaciones": "Cliente en buena condición física general. Se recomienda enfocarse en fuerza de miembros inferiores.",

  "plan_tipo": "Fuerza e Hipertrofia",
  "plan_hombre_bp": false,
  "plan_hombre_gm": true,
  "plan_mujer_bp": false,
  "plan_mujer_gm": false,

  "parq_q1": false,
  "parq_q2": false,
  "parq_q3": false,
  "parq_q4": false,
  "parq_q5": true,
  "parq_q6": false,
  "parq_q7": false,

  "fecha_registro": "2026-07-22 10:00:00",
  "fecha_inicio": "2026-07-22",
  "fecha_fin": "2026-10-22"
}
```

### 📥 Respuesta API (`200 OK`):
```json
{
  "status": "ok",
  "msg": "Valoración v2 creada exitosamente",
  "data": {
    "valoracion_creada": {
      "id": 1,
      "cliente_id": 1,
      "entrenador_id": 2,
      "trimestre": 1,
      "tipo_valoracion": "inicial",
      "imc": 24.65,
      "imc_clasificacion_id": 2,
      "convenio": true,
      "nombre_empresa": "Legion Gym S.A.S.",
      "estatura": "1.75",
      "peso": "75.50",
      "edad": 28,
      "sexo": "Hombre",
      "pecho": "98.00",
      "brazo_izquierdo": "34.50",
      "brazo_derecho": "35.00",
      "perimetro_abdominal": "82.00",
      "pierna_izquierda": "55.00",
      "pierna_derecha": "55.50",
      "frecuencia1": 70,
      "frecuencia2": 140,
      "frecuencia3": 90,
      "push_up": 30,
      "abdominales": 45,
      "porcentaje_grasa": "18.50",
      "porcentaje_musculo": "42.00",
      "grasa_visceral": "4.00",
      "masa_musculo_esqueletica": "33.50",
      "hipertension": false,
      "fuma": false,
      "lesion": true,
      "tension_arterial": "120/80",
      "antecedentes_cardio_personales": false,
      "antecedentes_cardio_familiares": false,
      "colesterol_alto": false,
      "trigliceridos_altos": false,
      "toma_medicamento": false,
      "toma_foto": true,
      "descripcion_lesion": "Molestia leve en la rodilla derecha",
      "observaciones": "Cliente en buena condición física general.",
      "plan_tipo": "Fuerza e Hipertrofia",
      "plan_hombre_bp": false,
      "plan_hombre_gm": true,
      "plan_mujer_bp": false,
      "plan_mujer_gm": false,
      "parq_q1": false,
      "parq_q2": false,
      "parq_q3": false,
      "parq_q4": false,
      "parq_q5": true,
      "parq_q6": false,
      "parq_q7": false,
      "fecha_registro": "2026-07-22T10:00:00.000000Z",
      "fecha_inicio": "2026-07-22",
      "fecha_fin": "2026-10-22",
      "created_at": "2026-07-22T16:10:00.000000Z",
      "updated_at": "2026-07-22T16:10:00.000000Z",
      "cliente": {
        "id": 1,
        "nombre": "Juan Pérez",
        "numero_documento": "1098765432"
      },
      "entrenador": {
        "id": 2,
        "nombres": "Carlos",
        "apellidos": "Gómez"
      }
    }
  }
}
```

---

## 🔍 2. Consultas Personalizadas

### A. Valoraciones por Cliente (`POST /api/valoraciones-v2/por-cliente`)
```json
{
  "cliente_id": 1
}
```

### B. Última Valoración de un Cliente (`POST /api/valoraciones-v2/ultima-cliente`)
```json
{
  "cliente_id": 1
}
```

### C. Valoraciones por Fecha (`POST /api/valoraciones-v2/por-fecha`)
```json
{
  "cliente_id": 1,
  "fecha_inicio": "2026-01-01",
  "fecha_fin": "2026-12-31"
}
```

### D. Comparativa de Avances en Trimestre (`POST /api/valoraciones-v2/comparar-avances`)
```json
{
  "cliente_id": 1,
  "trimestre": 1
}
```

---

## 📄 3. PDF de Valoración (`GET /api/valoraciones-v2/{id}/pdf`)

- **URL:** `GET /api/valoraciones-v2/1/pdf`
- **Response Header:** `Content-Type: application/pdf`
- **Contenido:** Genera automáticamente el reporte de 2 páginas (Página 1: Formulario SST-F-15, Página 2: Cuestionario PAR-Q).
