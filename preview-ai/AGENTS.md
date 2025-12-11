# AGENTS.md - Preview AI Plugin

## 📋 Descripción del Proyecto

**Preview AI** es un plugin de WordPress para WooCommerce que proporciona un widget en la página de detalle del producto (PDP) permitiendo a los usuarios subir fotografías personales para generar una simulación visual del usuario con el producto mediante inteligencia artificial.

### Funcionalidad Principal
- Widget frontend en la PDP de WooCommerce
- Upload de imágenes del usuario
- Envío de imágenes a backend externo de IA
- Visualización de la simulación generada (usuario + producto)

---

## 🏗️ Arquitectura del Plugin

```
preview-ai/
├── admin/                          # Área de administración
│   ├── class-preview-ai-admin.php  # Clase principal admin
│   ├── css/                        # Estilos admin
│   ├── js/                         # Scripts admin
│   └── partials/                   # Vistas admin
├── includes/                       # Core del plugin
│   ├── class-preview-ai.php        # Clase principal
│   ├── class-preview-ai-loader.php # Gestor de hooks
│   ├── class-preview-ai-i18n.php   # Internacionalización
│   ├── class-preview-ai-activator.php
│   └── class-preview-ai-deactivator.php
├── public/                         # Frontend público
│   ├── class-preview-ai-public.php # Clase principal public
│   ├── css/                        # Estilos frontend
│   ├── js/                         # Scripts frontend (widget)
│   └── partials/                   # Vistas frontend (widget HTML)
├── languages/                      # Archivos de traducción
├── preview-ai.php                  # Bootstrap del plugin
├── uninstall.php                   # Limpieza al desinstalar
└── README.txt                      # Readme para wordpress.org
```

---

## 📐 Estándares de WordPress (Obligatorios para Publicación)

### 1. WordPress Coding Standards (WPCS)
```bash
# Instalar PHP_CodeSniffer con reglas de WordPress
composer require --dev wp-coding-standards/wpcs
phpcs --standard=WordPress preview-ai/
```

### 2. Nomenclatura
- **Clases**: `Preview_AI_Nombre` (PascalCase con prefijo)
- **Funciones**: `preview_ai_nombre_funcion()` (snake_case con prefijo)
- **Hooks**: `preview_ai_nombre_hook`
- **Opciones DB**: `preview_ai_option_name`
- **Nonces**: `preview_ai_nonce_action`
- **Constantes**: `PREVIEW_AI_CONSTANTE`

### 3. Prefijos Obligatorios
**TODOS** los elementos globales DEBEN usar el prefijo `preview_ai_` o `PREVIEW_AI_`:
- Funciones
- Clases  
- Constantes
- Variables globales
- Opciones de base de datos
- Handles de scripts/estilos
- Post types, taxonomías
- Nonces y capabilities

### 4. Escapado y Sanitización (CRÍTICO)
```php
// OUTPUT - SIEMPRE escapar
esc_html()      // Texto plano
esc_attr()      // Atributos HTML
esc_url()       // URLs
esc_js()        // JavaScript inline
wp_kses()       // HTML permitido
wp_kses_post()  // HTML de post

// INPUT - SIEMPRE sanitizar
sanitize_text_field()
sanitize_email()
sanitize_file_name()
absint()
wp_kses()

// EJEMPLOS
echo esc_html( $variable );
echo esc_attr( $atributo );
echo esc_url( $url );
$clean = sanitize_text_field( $_POST['field'] );
```

### 5. Nonces para Seguridad
```php
// Crear nonce
wp_nonce_field( 'preview_ai_action', 'preview_ai_nonce' );

// Verificar nonce (OBLIGATORIO en forms y AJAX)
if ( ! wp_verify_nonce( $_POST['preview_ai_nonce'], 'preview_ai_action' ) ) {
    wp_die( esc_html__( 'Security check failed', 'preview-ai' ) );
}
```

### 6. Verificación de Capacidades
```php
// SIEMPRE verificar permisos antes de acciones admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access', 'preview-ai' ) );
}
```

### 7. Internacionalización (i18n)
```php
// Strings traducibles
__( 'Text', 'preview-ai' )           // Retorna string
_e( 'Text', 'preview-ai' )           // Echo string
esc_html__( 'Text', 'preview-ai' )   // Escapado + traducido
esc_html_e( 'Text', 'preview-ai' )   // Echo escapado + traducido
sprintf( __( 'Hello %s', 'preview-ai' ), $name )
```

### 8. Enqueue de Assets (NO hardcodear)
```php
// CSS
wp_enqueue_style(
    'preview-ai-public',
    plugin_dir_url( __FILE__ ) . 'css/preview-ai-public.css',
    array(),
    PREVIEW_AI_VERSION
);

// JS
wp_enqueue_script(
    'preview-ai-public',
    plugin_dir_url( __FILE__ ) . 'js/preview-ai-public.js',
    array( 'jquery' ),
    PREVIEW_AI_VERSION,
    true // En footer
);

// Pasar datos a JS
wp_localize_script( 'preview-ai-public', 'previewAiData', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'preview_ai_ajax' ),
    'i18n'    => array(
        'uploading' => esc_html__( 'Uploading...', 'preview-ai' ),
        'error'     => esc_html__( 'Error occurred', 'preview-ai' ),
    ),
) );
```

---

## 🎯 Principios de Código Minimalista

### 1. Single Responsibility
- Cada clase/función hace UNA sola cosa
- Archivos pequeños y enfocados
- No mezclar lógica de admin con public

### 2. No Over-Engineering - IMPORTANT
```php
// ❌ MAL - Abstracción innecesaria
class Preview_AI_Abstract_Base_Handler_Factory { }

// ✅ BIEN - Directo y simple
class Preview_AI_Image_Handler {
    public function upload( $file ) { /* ... */ }
}
```

### 3. Evitar Dependencias Innecesarias
- Usar APIs nativas de WordPress
- No incluir librerías si WP ya lo hace
- jQuery disponible en WP core

### 4. Código Autoexplicativo
```php
// ❌ MAL
$x = $this->proc( $d, 1 );

// ✅ BIEN
$simulation_result = $this->process_image( $user_photo, $product_id );
```

---

## 🔌 Hooks Principales del Plugin

### Hooks de WooCommerce para PDP
```php
// Posiciones disponibles en PDP
add_action( 'woocommerce_before_single_product', ... );
add_action( 'woocommerce_before_single_product_summary', ... );
add_action( 'woocommerce_single_product_summary', ... );        // Prioridad 5-60
add_action( 'woocommerce_after_single_product_summary', ... );
add_action( 'woocommerce_after_single_product', ... );

// Recomendado para widget
add_action( 'woocommerce_single_product_summary', 'preview_ai_render_widget', 35 );
```

### AJAX Handlers
```php
// Para usuarios logueados
add_action( 'wp_ajax_preview_ai_upload', 'preview_ai_handle_upload' );

// Para usuarios no logueados (si aplica)
add_action( 'wp_ajax_nopriv_preview_ai_upload', 'preview_ai_handle_upload' );
```

---

## 📤 Flujo de Upload de Imágenes

```
1. Usuario selecciona foto en widget (input type="file")
2. JavaScript valida formato/tamaño cliente
3. FormData envía a wp-admin/admin-ajax.php
4. PHP valida nonce + sanitiza archivo
5. wp_handle_upload() guarda temporalmente
6. Envío a API externa de IA
7. Recepción de imagen simulada
8. Respuesta JSON al frontend
9. Widget muestra resultado
```

### Validación de Uploads
```php
// Tipos permitidos
$allowed_types = array( 'image/jpeg', 'image/png', 'image/webp' );

// Tamaño máximo (en bytes)
$max_size = 5 * 1024 * 1024; // 5MB

// Verificar con wp_check_filetype()
$file_type = wp_check_filetype( $filename );
if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
    // Rechazar
}
```

---

## 🔐 Seguridad (Checklist)

- [ ] Todos los inputs sanitizados
- [ ] Todos los outputs escapados
- [ ] Nonces en todos los forms/AJAX
- [ ] Verificación de capabilities
- [ ] Prepared statements para queries DB
- [ ] Validación de uploads (tipo, tamaño)
- [ ] No exponer rutas del servidor
- [ ] No eval() ni código dinámico
- [ ] HTTPS para API externa
- [ ] Rate limiting en uploads

---

## 🗂️ Opciones de Base de Datos

```php
// Guardar opciones
update_option( 'preview_ai_api_endpoint', $url );
update_option( 'preview_ai_api_key', $encrypted_key );

// Obtener opciones
$endpoint = get_option( 'preview_ai_api_endpoint', '' );

// Eliminar en uninstall.php
delete_option( 'preview_ai_api_endpoint' );
delete_option( 'preview_ai_api_key' );
```

---

## 📁 Archivos Clave a Implementar

### 1. Widget Frontend (public/partials/preview-ai-widget.php)
```php
<div id="preview-ai-widget" class="preview-ai-container">
    <button type="button" id="preview-ai-trigger">
        <?php esc_html_e( 'Try it on', 'preview-ai' ); ?>
    </button>
    <div id="preview-ai-modal" class="preview-ai-modal" style="display:none;">
        <input type="file" id="preview-ai-upload" accept="image/*" />
        <div id="preview-ai-result"></div>
    </div>
</div>
```

### 2. JavaScript Principal (public/js/preview-ai-public.js)
- Manejo del modal
- Validación cliente de imágenes
- Llamada AJAX con FormData
- Mostrar loading/resultado/errores

### 3. Admin Settings (admin/partials/preview-ai-admin-display.php)
- Campo para API key
- Opciones de posición del widget
- Productos habilitados (categorías/todos)

---

## ✅ Requisitos para Publicar en WordPress.org

1. **Licencia GPL v2+** ✓ (incluida)
2. **Sin código ofuscado**
3. **Sin llamadas a recursos externos no autorizados** (excepto API documentada)
4. **readme.txt válido** con headers correctos
5. **Prefijos únicos** en todo el código
6. **Sin errores PHP** (tested con WP_DEBUG)
7. **Compatible con últimas 3 versiones de WP**
8. **Internacionalización completa**
9. **Código seguro** (sanitización/escapado)
10. **Sin funcionalidades premium ocultas**

---

## 🧪 Testing

```bash
# PHP Linting
php -l preview-ai.php

# WordPress Coding Standards
phpcs --standard=WordPress --extensions=php preview-ai/

# Corregir automáticamente
phpcbf --standard=WordPress preview-ai/
```

### WP_DEBUG en wp-config.php
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'SCRIPT_DEBUG', true );
```

---

## 📝 Commits y Versionado

- Usar Semantic Versioning: `MAJOR.MINOR.PATCH`
- Actualizar `PREVIEW_AI_VERSION` en preview-ai.php
- Actualizar `Stable tag` en README.txt
- Mantener CHANGELOG actualizado

---

## 🚫 Prohibido

- `eval()`, `create_function()`
- Queries SQL sin `$wpdb->prepare()`
- `$_GET/$_POST` sin sanitizar
- Echo sin escapar
- Hardcodear URLs/paths
- Incluir archivos por URL (`include('http://...')`)
- Base64 para ofuscar código
- Timthumb u otras librerías vulnerables conocidas

---

## 📚 Referencias

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Security](https://developer.wordpress.org/plugins/security/)
- [WooCommerce Hook Reference](https://woocommerce.github.io/code-reference/hooks/hooks.html)
- [Plugin Review Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

