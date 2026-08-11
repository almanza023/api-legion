# Documentación de Servicios de API: Módulo de Remisiones Médicas

Este documento contiene la especificación completa de los servicios REST API, parámetros, validaciones y ejemplos de carga útil (JSON payload) necesarios para integrar el módulo de **Remisiones Médicas** e **Histórico** con el Frontend o cualquier cliente externo.

---

## Índice de Contenidos
1. [Autenticación e Indicaciones Generales](#1-autenticación-e-indicaciones-generales)
2. [Servicios de Remisiones Médicas e Histórico](#2-servicios-de-remisiones-médicas-e-histórico)
   - [Crear Remisión Médica](#21-crear-remisión-médica)
   - [Consultar Histórico por Cliente](#22-consultar-histórico-por-cliente)
   - [Listar Remisiones Médicas](#23-listar-remisiones-médicas)
   - [Ver Detalle de una Remisión](#24-ver-detalle-de-una-remisión)
   - [Actualizar Remisión Médica](#25-actualizar-remisión-médica)
   - [Eliminar Remisión Médica](#26-eliminar-remisión-médica)
   - [Generar y Descargar PDF Oficial](#27-generar-y-descargar-pdf-oficial)
3. [Servicios de Médicos (Mantenimiento)](#3-servicios-de-médicos-mantenimiento)
4. [Servicios de Diagnósticos CIE-10 (Mantenimiento)](#4-servicios-de-diagnósticos-cie-10-mantenimiento)
5. [Servicios de Objetivos de Remisión (Mantenimiento)](#5-servicios-de-objetivos-de-remisión-mantenimiento)

---

## 1. Autenticación e Indicaciones Generales

Todas las peticiones a estos servicios requieren autenticación mediante encabezados HTTP estándar del sistema (Bearer Token / Cookie Session):

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <TOKEN>
```

> ⚠️ **REGLA DE NEGOCIO CRÍTICA**: 
> Para poder crear una remisión médica, **el cliente debe contar con una membresía activa y vigente** (`estado = 'activo'` y `fecha_fin >= fecha_actual`). De lo contrario, el servicio retornará código HTTP `422 Unprocessable Entity`.

---

## 2. Servicios de Remisiones Médicas e Histórico

### 2.1. Crear Remisión Médica
* **Método:** `POST`
* **URL:** `/api/remisiones-medicas`
* **Descripción:** Crea un nuevo registro de remisión médica. Valida que el cliente tenga una membresía activa.

#### Ejemplo de Request Body (JSON):
```json
{
  "cliente_id": 15,
  "medico_id": 2,
  "cie10_id": 4,
  "diagnostico": "Paciente presenta dolor lumbar recurrente y sobrepeso grado 1.",
  "codigo_cie10": "M54.5",
  "objetivos": [
    "Recuperación Funcional",
    "Control de peso",
    "Fortalecimiento muscular"
  ],
  "objetivo_otro": "",
  "restricciones": "Evitar ejercicios de alto impacto axial. No realizar sentadillas pesadas sin supervisión.",
  "vigencia": "6_meses",
  "vigencia_otra_descripcion": "",
  "estado": "activa",
  "fecha_registro": "2026-08-04"
}
```

#### Respuesta Exitosa (`201 Created`):
```json
{
  "status": "success",
  "msg": "Remisión médica registrada exitosamente",
  "data": {
    "id": 1,
    "cliente_id": 15,
    "medico_id": 2,
    "cie10_id": 4,
    "diagnostico": "Paciente presenta dolor lumbar recurrente y sobrepeso grado 1.",
    "codigo_cie10": "M54.5",
    "objetivos": [
      "Recuperación Funcional",
      "Control de peso",
      "Fortalecimiento muscular"
    ],
    "objetivo_otro": null,
    "restricciones": "Evitar ejercicios de alto impacto axial. No realizar sentadillas pesadas sin supervisión.",
    "vigencia": "6_meses",
    "vigencia_otra_descripcion": null,
    "estado": "activa",
    "fecha_registro": "2026-08-04",
    "created_at": "2026-08-04T21:30:00.000000Z",
    "updated_at": "2026-08-04T21:30:00.000000Z",
    "cliente": {
      "id": 15,
      "nombre": "JUAN CARLOS",
      "apellidos": "PEREZ GOMEZ",
      "numero_documento": "1098765432"
    },
    "medico": {
      "id": 2,
      "nombre": "DRA. SANDRA DONADO G",
      "especialidad": "MEDICO GENERAL / DEPORTOLOGO",
      "registro_retheus": "RM. 23413"
    },
    "cie10": {
      "id": 4,
      "codigo": "M54.5",
      "descripcion": "Lumbago no especificado"
    }
  }
}
```

#### Respuesta Error por Membresía Inactiva (`422 Unprocessable Entity`):
```json
{
  "status": "fail",
  "msg": "No es posible registrar la remisión médica. El cliente no cuenta con una membresía activa."
}
```

---

### 2.2. Consultar Histórico por Cliente
* **Método:** `GET`
* **URL:** `/api/remisiones-medicas/cliente/{clienteId}`
* **Descripción:** Obtiene la lista ordenada cronológicamente de todas las remisiones médicas registradas para el cliente especificado.

#### Respuesta Exitosa (`200 OK`):
```json
{
  "status": "success",
  "cliente": {
    "id": 15,
    "nombre_completo": "JUAN CARLOS PEREZ GOMEZ",
    "numero_documento": "1098765432",
    "fecha_nacimiento": "1992-05-14",
    "sexo": "Hombre"
  },
  "total_remisiones": 2,
  "data": [
    {
      "id": 2,
      "cliente_id": 15,
      "medico_id": 2,
      "cie10_id": 4,
      "diagnostico": "Control de evolución lumbalgia.",
      "codigo_cie10": "M54.5",
      "objetivos": [
        "Fortalecimiento muscular"
      ],
      "restricciones": "Cargas moderadas.",
      "vigencia": "12_meses",
      "estado": "activa",
      "fecha_registro": "2026-08-04",
      "medico": {
        "id": 2,
        "nombre": "DRA. SANDRA DONADO G"
      }
    },
    {
      "id": 1,
      "cliente_id": 15,
      "medico_id": 1,
      "cie10_id": 1,
      "diagnostico": "Evaluación inicial.",
      "codigo_cie10": "E66.9",
      "objetivos": [
        "Control de peso"
      ],
      "restricciones": "Sin restricciones graves.",
      "vigencia": "6_meses",
      "estado": "vencida",
      "fecha_registro": "2025-08-01",
      "medico": {
        "id": 1,
        "nombre": "DR. CARLOS MENDOZA"
      }
    }
  ]
}
```

---

### 2.3. Consultar Remisiones por Médico
* **Método:** `GET`
* **URL:** `/api/remisiones-medicas/medico/{medicoId}`
* **Parámetros Opcionales (Query Params):**
  - `estado` (string): `activa`, `vencida`, `anulada`.
* **Descripción:** Obtiene la lista de todas las remisiones médicas emitidas por el médico especificado.

#### Respuesta Exitosa (`200 OK`):
```json
{
  "status": "success",
  "medico": {
    "id": 2,
    "nombre": "DRA. SANDRA DONADO G",
    "cedula": "50946977",
    "especialidad": "MEDICO GENERAL / DEPORTOLOGO",
    "registro_retheus": "RM. 23413"
  },
  "total_remisiones": 5,
  "data": [
    {
      "id": 2,
      "cliente_id": 15,
      "medico_id": 2,
      "cie10_id": 4,
      "diagnostico": "Control de evolución lumbalgia.",
      "codigo_cie10": "M54.5",
      "objetivos": [
        "Fortalecimiento muscular"
      ],
      "restricciones": "Cargas moderadas.",
      "vigencia": "12_meses",
      "estado": "activa",
      "fecha_registro": "2026-08-04",
      "cliente": {
        "id": 15,
        "nombre": "JUAN CARLOS",
        "apellidos": "PEREZ GOMEZ",
        "numero_documento": "1098765432"
      }
    }
  ]
}
```

---

### 2.4. Listar Remisiones Médicas
* **Método:** `GET`
* **URL:** `/api/remisiones-medicas`
* **Parámetros Opcionales (Query Params):**
  - `cliente_id` (int): Filtrar por ID de cliente.
  - `medico_id` (int): Filtrar por ID de médico.
  - `estado` (string): `activa`, `vencida`, `anulada`.

#### Ejemplo de Petición:
```http
GET /api/remisiones-medicas?medico_id=2&estado=activa
```

---

### 2.4. Ver Detalle de una Remisión
* **Método:** `GET`
* **URL:** `/api/remisiones-medicas/{id}`

---

### 2.5. Actualizar Remisión Médica
* **Método:** `PUT`
* **URL:** `/api/remisiones-medicas/{id}`

#### Ejemplo Body (JSON):
```json
{
  "diagnostico": "Diagnóstico actualizado.",
  "estado": "anulada"
}
```

---

### 2.6. Eliminar Remisión Médica
* **Método:** `DELETE`
* **URL:** `/api/remisiones-medicas/{id}`

---

### 2.7. Generar y Descargar PDF Oficial
* **Método:** `GET`
* **URL:** `/api/remisiones-medicas/{id}/pdf`
* **Descripción:** Retorna el documento PDF idéntico al formato impreso oficial (Content-Type: `application/pdf`).

---

## 3. Servicios de Médicos (Mantenimiento)

| Método | URL | Descripción | Content-Type |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/medicos` | Listar médicos (`?search=sandra&activo=true`) | `application/json` |
| `POST` | `/api/medicos` | Crear nuevo médico (Soporta archivo de firma y genera usuario con clave = cédula) | `multipart/form-data` |
| `GET` | `/api/medicos/{id}` | Consultar detalle de médico | `application/json` |
| `POST` / `PUT` | `/api/medicos/{id}` | Actualizar datos del médico (Soporta actualización de archivo de firma) | `multipart/form-data` |
| `DELETE` | `/api/medicos/{id}` | Eliminar registro de médico | `application/json` |

---

### 📷 Guía de Integración para el Frontend (Carga del Archivo de Firma)

Tanto en la **Creación (`POST`)** como en la **Edición (`PUT` / `POST` con `_method: 'PUT'`)**, el Frontend debe enviar la información utilizando **`FormData`** (`multipart/form-data`) para transmitir el archivo de la imagen de la firma (PNG, JPG, SVG).

#### Campo aceptado para el archivo:
- `firma` o `firma_file` (File Object de un `<input type="file">` o Canvas/Blob).

#### Ejemplo de integración en Frontend (JavaScript / Axios):

```javascript
// Ejemplo para Crear o Editar un Médico con Archivo de Firma
async function guardarMedico(datosMedico, archivoFirmaFile, medicoId = null) {
    const formData = new FormData();

    formData.append('nombre', datosMedico.nombre);
    formData.append('especialidad', datosMedico.especialidad || '');
    formData.append('registro_retheus', datosMedico.registro_retheus || '');
    formData.append('cedula', datosMedico.cedula);
    formData.append('correo', datosMedico.correo || '');
    formData.append('telefono', datosMedico.telefono || '');
    formData.append('activo', datosMedico.activo ? '1' : '0');

    // Adjuntar archivo de firma si el usuario seleccionó/dibujó una nueva imagen
    if (archivoFirmaFile) {
        formData.append('firma', archivoFirmaFile); // Campo 'firma' o 'firma_file'
    }

    let url = '/api/medicos';
    let method = 'post';

    if (medicoId) {
        url = `/api/medicos/${medicoId}`;
        // Si usas FormData en Laravel para PUT, se recomienda enviar POST con _method='PUT'
        formData.append('_method', 'PUT');
    }

    const response = await axios.post(url, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    });

    return response.data;
}
```

#### Respuesta de Éxito al Crear Médico (`201 Created`):
```json
{
  "status": "success",
  "msg": "Médico y usuario creados exitosamente",
  "data": {
    "id": 3,
    "nombre": "DRA. SANDRA DONADO G",
    "especialidad": "MEDICO GENERAL / DEPORTOLOGO",
    "registro_retheus": "RM. 23413",
    "cedula": "50946977",
    "correo": "sandra.donado@gmail.com",
    "telefono": "3001234567",
    "firma_path": "firmas/AbC123Xyz_firma.png",
    "activo": true,
    "created_at": "2026-08-04T22:00:00.000000Z",
    "updated_at": "2026-08-04T22:00:00.000000Z"
  }
}
```

> 💡 **Notas Importantes:**
> 1. Al enviar la petición `POST /api/medicos`, el sistema creará el médico y automáticamente su usuario de sistema (`User`) con `documento_identidad = cedula` y `password = cedula`.
> 2. Si se actualiza el médico (`PUT /api/medicos/{id}`) enviando un nuevo archivo en el campo `firma`, la imagen antigua almacenada en el servidor se eliminará automáticamente para reemplazarla por la nueva.

---

## 4. Servicios de Diagnósticos CIE-10 (Mantenimiento)

| Método | URL | Descripción |
| :--- | :--- | :--- |
| `GET` | `/api/diagnosticos-cie10` | Listar y buscar códigos CIE-10 (`?search=M54`) |
| `POST` | `/api/diagnosticos-cie10` | Crear nuevo código CIE-10 |
| `GET` | `/api/diagnosticos-cie10/{id}` | Ver detalle del código |
| `PUT` | `/api/diagnosticos-cie10/{id}` | Actualizar código CIE-10 |
| `DELETE` | `/api/diagnosticos-cie10/{id}` | Eliminar código |

#### JSON Payload para `POST /api/diagnosticos-cie10`:
```json
{
  "codigo": "M54.5",
  "descripcion": "Lumbago no especificado",
  "activo": true
}
```

---

## 5. Servicios de Objetivos de Remisión (Mantenimiento)

| Método | URL | Descripción |
| :--- | :--- | :--- |
| `GET` | `/api/objetivos-remision` | Listar catálogo de objetivos (`?activo=true`) |
| `POST` | `/api/objetivos-remision` | Crear nuevo objetivo catálogo |
| `PUT` | `/api/objetivos-remision/{id}` | Editar objetivo catálogo |
| `DELETE` | `/api/objetivos-remision/{id}` | Eliminar objetivo catálogo |

#### JSON Payload para `POST /api/objetivos-remision`:
```json
{
  "nombre": "Rehabilitación Cardiaca",
  "descripcion": "Programa especial para pacientes cardiopatías",
  "activo": true
}
```
