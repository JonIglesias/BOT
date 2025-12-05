# Unificación de Estilos CSS - PHSBOT

## Resumen

Se ha implementado un sistema de CSS unificado para todos los módulos de administración del chatbot, siguiendo exactamente la estructura y colores de GeoWriter.

## Archivos Creados/Modificados

### CSS Unificado
- **`/core/assets/modules-unified.css`** - Sistema CSS centralizado (618 líneas)
  - Estructura de módulos estándar (`.phsbot-module-wrap`, `.phsbot-module-header`, etc.)
  - Headers con fondo gris (#dddddd) como GeoWriter
  - Sidebar negro con texto blanco
  - Tarjetas blancas con sombras sutiles
  - Botones negros (sin colores de marca)
  - Formularios estandarizados
  - Tablas de datos profesionales
  - Badges, alertas y utilidades

### Módulos Actualizados
- **`estadisticas/estadisticas.php`** - Carga CSS unificado
- **`estadisticas/stats.css`** - Colores cambiados a negro/gris/blanco
- **`estadisticas/stats-ui.php`** - Gradiente líquido cambiado a negro
- **`config/config.php`** - Carga CSS unificado
- **`leads/leads.php`** - Carga CSS unificado
- **`kb/kb.php`** - Carga CSS unificado

## Paleta de Colores

### ANTES (Colores de Marca - Verde)
- Header: `linear-gradient(135deg, #667a3a, #4c5e27)`
- Botones primarios: `#667a3a`
- Acentos: `#4c5e27`

### AHORA (Estilo GeoWriter - Gris/Negro/Blanco)
- **Header**: `#dddddd` (gris claro)
- **Sidebar**: `#000000` (negro sólido)
- **Botones primarios**: `#000000` (negro)
- **Botones secundarios**: `#f1f5f9` (gris claro)
- **Cards/Contenido**: `#ffffff` (blanco)
- **Bordes y acentos**: `#000000` (negro)
- **Backgrounds secundarios**: Tonos de gris (#f9fafb, #fafafa, #e5e7eb)

## Estructura de Clases CSS

### Layout Principal
```html
<div class="phsbot-module-wrap">
    <div class="phsbot-module-header">
        <h1>Título del Módulo</h1>
        <div>
            <button class="button button-primary">Acción</button>
        </div>
    </div>

    <div class="phsbot-module-container has-sidebar">
        <div class="phsbot-module-content">
            <!-- Contenido principal -->
        </div>
        <div class="phsbot-module-sidebar">
            <!-- Ayuda/información -->
        </div>
    </div>
</div>
```

### Secciones y Cards
```html
<div class="phsbot-section">
    <h2 class="phsbot-section-title">Título de Sección</h2>
    <!-- Contenido -->
</div>

<div class="phsbot-mega-card">
    <!-- Gran contenedor blanco -->
</div>
```

### Formularios
```html
<div class="phsbot-field">
    <label class="phsbot-label">Etiqueta</label>
    <input type="text" class="phsbot-input-field" />
    <p class="phsbot-description">Texto de ayuda</p>
</div>

<div class="phsbot-grid-2">
    <!-- Grid de 2 columnas -->
</div>
```

### Botones
```html
<button class="phsbot-btn-primary">Primario</button>
<button class="phsbot-btn-secondary">Secundario</button>
<button class="phsbot-btn-save">Guardar</button>
```

### Tablas
```html
<table class="phsbot-data-table">
    <thead>
        <tr>
            <th>Columna</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Dato</td>
        </tr>
    </tbody>
</table>
```

### Estados
```html
<div class="phsbot-loading">Cargando...</div>
<div class="phsbot-empty-state">
    <div class="phsbot-empty-state-text">No hay datos</div>
</div>
<div class="phsbot-error">Error al cargar</div>

<span class="phsbot-badge phsbot-badge-success">Activo</span>
<span class="phsbot-badge phsbot-badge-warning">Pendiente</span>
<span class="phsbot-badge phsbot-badge-error">Error</span>
```

### Alertas
```html
<div class="phsbot-alert phsbot-alert-success">
    Operación exitosa
</div>
<div class="phsbot-alert phsbot-alert-warning">
    Advertencia
</div>
<div class="phsbot-alert phsbot-alert-error">
    Error crítico
</div>
```

### Sidebar Help Items
```html
<div class="phsbot-help-item">
    <h4>Título de Ayuda</h4>
    <p>Descripción de la ayuda...</p>
</div>
```

## Módulos con CSS Unificado

✅ **Estadísticas** - Completamente actualizado
- Header gris
- Cards con bordes sutiles
- Liquid animation en negro
- Tabla de operaciones con pills negros

✅ **Configuración** - CSS cargado
- Mantiene funcionalidad de tabs
- Estilos unificados aplicados

✅ **Leads** - CSS cargado
- Mantiene estructura de tabs
- Estilos unificados aplicados

✅ **Base de Conocimiento** - CSS cargado
- Mantiene funcionalidad específica
- Estilos unificados aplicados

## Responsive Design

Breakpoints:
- **1024px**: Sidebar pasa a columna única
- **768px**: Padding reducido, tipografía ajustada

## Utilidades CSS

### Espaciado
- `.phsbot-mt-0` a `.phsbot-mt-4` (margin-top)
- `.phsbot-mb-0` a `.phsbot-mb-4` (margin-bottom)

### Tipografía
- `.phsbot-text-sm` (13px)
- `.phsbot-text-base` (14px)
- `.phsbot-text-lg` (16px)
- `.phsbot-text-xl` (18px)
- `.phsbot-text-muted` (color gris)
- `.phsbot-text-bold` (font-weight 600)

### Alineación
- `.phsbot-text-left`
- `.phsbot-text-center`
- `.phsbot-text-right`

### Flexbox
- `.phsbot-flex`
- `.phsbot-flex-col`
- `.phsbot-items-center`
- `.phsbot-justify-between`
- `.phsbot-gap-1` a `.phsbot-gap-3`

## Ventajas del Sistema

1. **Consistencia Visual**: Todos los módulos comparten el mismo look & feel
2. **Mantenibilidad**: Un solo archivo CSS para actualizar estilos globalmente
3. **Escalabilidad**: Fácil añadir nuevos módulos con el mismo estilo
4. **Responsive**: Diseño adaptativo para móvil y tablet
5. **Accesibilidad**: Focus states claros, contraste adecuado
6. **Profesional**: Estilo limpio y minimalista igual que GeoWriter

## Próximos Pasos (Opcional)

Para una integración completa, se pueden actualizar las vistas HTML de cada módulo para usar las clases CSS unificadas en lugar de sus estilos inline o clases propias. Esto requeriría:

1. Reescribir las vistas de config.php usando `.phsbot-field`, `.phsbot-section`, etc.
2. Actualizar leads UI para usar la estructura de cards
3. Actualizar KB UI para usar secciones estandarizadas
4. Remover CSS específico de cada módulo que ya está cubierto por el CSS unificado

El CSS unificado está listo y funcionando. Los módulos pueden adoptarlo progresivamente.
