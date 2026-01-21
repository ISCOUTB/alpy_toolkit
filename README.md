# Alpy Toolkit — Herramientas para el Formato Alpy

**Alpy Toolkit** es un plugin local complementario diseñado para potenciar y facilitar la gestión de cursos que utilizan el formato **Alpy** (`format_alpy`).

Este plugin elimina la necesidad de gestionar manualmente las etiquetas (tags) de Moodle, proporcionando una interfaz gráfica integrada para clasificar las actividades educativas según el modelo pedagógico de Alpy.

## Contenido

- [Funcionalidades](#funcionalidades)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Uso](#uso)
- [API para Desarrolladores](#api-para-desarrolladores)

---

## Funcionalidades

### 1) Selector de Tipo de Recurso
Agrega una nueva sección "Tipo de Recurso Alpy" en el formulario de configuración de cualquier actividad o recurso.
- Permite seleccionar el tipo de recurso mediante un menú desplegable (ej. Lectura, Videotutorial, Mapa Conceptual, etc...).
- Muestra los nombres traducidos al idioma del usuario.

### 2) Etiquetado Automático
Al guardar una actividad:
- El plugin gestiona automáticamente los **Tags estándar de Moodle**.
- Elimina tags antiguos del formato Alpy y asigna el nuevo según la selección.
- Mantiene la consistencia de datos necesaria para los cálculos de estilos de aprendizaje del formato de curso.

### 3) API de Iconos
Proporciona servicios web para desarrolladores que necesiten recuperar la información visual de las actividades de forma dinámica.

### 4) Reemplazo Visual de Iconos (CSS Dinámico)
El plugin incluye un endpoint de estilos dinámicos que reemplaza los iconos de actividades pertenecientes a cursos **Alpy** en las siguientes vistas:
- **Bloque de Línea de Tiempo (Timeline)**
- **Bloque de Elementos Accedidos Recientemente**
- **Bloque de Próximos Eventos**
- **Cabecera de la Actividad Individual**

---

## Requisitos

- **Moodle 4.0** o superior.
- Plugin **Format Alpy** ([`format_alpy`](https://github.com/ISCOUTB/alpy)) instalado y configurado.

---

## Instalación

1. Descargar el plugin desde las *releases* del repositorio oficial: https://github.com/ISCOUTB/alpy_toolkit/releases
2. En Moodle (como administrador):
   - Ir a **Administración del sitio → Extensiones → Instalar plugins**.
   - Subir el archivo ZIP.
   - Completar el asistente de instalación.
3. Listo, el plugin estará disponible para su uso.

---

## Uso

1. Navegar a cualquier curso que tenga configurado el formato **Alpy**.
2. Activar el **Modo de Edición**.
3. Crear una nueva actividad o editar una existente.
4. Localizar la sección **"Herramientas Alpy"** (o "Alpy Toolkit") en el formulario.
5. Seleccionar el tipo de recurso correspondiente (ej. *Simulación*, *Debate*, *Lectura*).
6. Guardar cambios.
   - *El icono de la actividad se actualizará automáticamente en el curso.*
   - *La actividad será reordenada dinámicamente según el perfil de aprendizaje de cada estudiante.*

---

## API para Desarrolladores

### Servicios Web (AJAX)

El plugin expone un endpoint externo útil para interfaces dinámicas (React, Vue, etc.) que necesiten renderizar los iconos correctos.

**Servicio:** `local_alpy_toolkit_get_activity_icons`

- **Parámetros:**
  - `cmids` (array de int): Lista de IDs de módulos del curso.
- **Retorno:**
  - Array de objetos con la URL del icono personalizado (si aplica) o vacío si no es un curso Alpy.

Mantiene compatibilidad con la resolución de alias (ej. convierte tag `lectura` -> icon `reading.svg`).

---

## Contribuciones

¡Las contribuciones son bienvenidas! Si deseas mejorar este bloque, por favor sigue estos pasos:

1. Haz un fork del repositorio.
2. Crea una nueva rama para tu característica o corrección de errores.
3. Realiza tus cambios y asegúrate de que todo funcione correctamente.
4. Envía un pull request describiendo tus cambios.

---

## Equipo de desarrollo

- Jairo Enrique Serrano Castañeda
- Yuranis Henriquez Núñez
- Isaac David Sánchez Sánchez
- Santiago Andrés Orejuela Cueter
- María Valentina Serna González

<div align="center">
<strong>Desarrollado con ❤️ para la Universidad Tecnológica de Bolívar</strong>
</div>
