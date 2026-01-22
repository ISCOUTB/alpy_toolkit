# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto sigue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-01-21

### Agregado
- Cambio de iconos en la vista de calendario en actividades relacionadas a formato Alpy para mantener consistencia visual.

### Corregido
- Nuevos estilos CSS para asegurar que los iconos personalizados mantengan el color original sin filtros aplicados por Moodle y con fondo adecuado.
- Bloque de "Upcoming events" ahora muestran correctamente los iconos personalizados de actividades Alpy.

## [1.0.0] — 2026-01-20

### Agregado
- **Selector de Recursos Alpy**: Integración en el formulario de edición de actividades para seleccionar fácilmente el tipo de recurso (Lectura, Video, Mapa, etc.).
- **Gestión Automática de Tags**: Asignación y actualización automática de etiquetas del sistema (core tags) al guardar la actividad.
- **API Externa de Iconos**: Nuevo servicio web `local_alpy_toolkit_get_activity_icons` para recuperar iconos personalizados vía AJAX.
- **Reemplazo de iconos en bloques**: Estilos dinámicos para reemplazar iconos en **Timeline**, **Recently accessed items**, **Upcoming events** y la **cabecera de la actividad**.
- **Soporte Multiidioma**: Traducciones completas al español e inglés para todos los tipos de recursos definidos en el formato Alpy.
- **Validación de Contexto**: Funcionalidades restringidas exclusivamente a cursos que utilicen el formato `format_alpy`.
