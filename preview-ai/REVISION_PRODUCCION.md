# 🔍 Revisión Pre-Producción - Preview AI Plugin

## ⚠️ CRÍTICO - Debe corregirse antes de producción

### 1. **URLs Hardcodeadas de Desarrollo/Prueba**

#### Problema 1.1: Endpoint de API hardcodeado
**Archivo:** `includes/class-preview-ai-api.php`
- **Línea 19:** `private $endpoint = 'http://backend_app:8000/api/';`
- **Línea 310:** `$endpoint = 'http://backend_app:8000/api/';`

**Problema:** URLs de desarrollo hardcodeadas que no funcionarán en producción.

**Solución:**
- Usar la opción `preview_ai_api_endpoint` que ya existe en el código
- Cambiar a: `$this->endpoint = get_option( 'preview_ai_api_endpoint', 'https://api.previewai.app/api/' );`
- El valor por defecto debe ser la URL de producción

#### Problema 1.2: URL de Stripe de Prueba
**Archivo:** `admin/js/preview-ai-admin.js`
- **Línea 48:** URL hardcodeada de Stripe de prueba: `https://billing.stripe.com/p/login/test_cNi4gyfXV4u2bnb8QHgIo00`

**Archivo:** `admin/partials/preview-ai-admin-display.php`
- **Línea 277:** Misma URL de prueba

**Problema:** URL de prueba de Stripe que no funcionará en producción.

**Solución:**
- Mover la URL a una constante o opción configurable
- Usar la URL de producción de Stripe
- Considerar usar variables de entorno o filtros de WordPress

### 2. **Código de Testing/Desarrollo**

#### Problema 2.1: Código comentado de testing
**Archivo:** `includes/class-preview-ai-api.php`
- **Líneas 259-260:** Código comentado para testing:
```php
# Solo enviar 3 productos para testing
#$products_data = array_slice( $products_data, 0, 3 );
```

**Problema:** Código de testing que puede confundir o activarse accidentalmente.

**Solución:** Eliminar el código comentado antes de producción.

#### Problema 2.2: Archivo de testing
**Archivo:** `test-uninstall.php`

**Problema:** Archivo de testing que no debería estar en producción.

**Solución:** Eliminar este archivo antes del despliegue.

### 3. **Seguridad y Validación**

#### ✅ Bien implementado:
- Uso de `check_ajax_referer()` para validar nonces
- Uso de `current_user_can( 'manage_woocommerce' )` para verificar permisos
- Sanitización de datos con `sanitize_text_field()`, `sanitize_key()`, `sanitize_email()`, etc.
- Escape de output con `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`
- Validación de tipos de archivo y tamaños en uploads

#### ⚠️ Puntos a revisar:

**3.1 Validación de límites de tamaño de archivo**
- **Archivo:** `includes/class-preview-ai-ajax.php`
- **Línea 141:** Límite de 5MB está hardcodeado
- **Recomendación:** Considerar hacerlo configurable o al menos documentarlo claramente

**3.2 Manejo de errores en producción**
- **Archivo:** `includes/class-preview-ai-logger.php`
- El logger usa `error_log()` que puede exponer información sensible en logs
- **Recomendación:** 
  - Deshabilitar logs de DEBUG en producción (ya está implementado con WP_DEBUG)
  - Considerar no loguear información sensible como API keys
  - Revisar que los logs no contengan datos personales

**3.3 Validación de imágenes**
- **Archivo:** `includes/class-preview-ai-ajax.php`
- La validación de tipo MIME se basa en `$file['type']` que puede ser manipulado
- **Recomendación:** Agregar validación adicional usando `getimagesize()` o `exif_imagetype()`

### 4. **Configuración y Opciones**

#### Problema 4.1: Endpoint por defecto
**Archivo:** `includes/class-preview-ai-activator.php`
- **Línea 55:** `update_option( 'preview_ai_api_endpoint', 'https://api.previewai.app' );`
- **Problema:** Falta el `/api/` al final
- **Solución:** Corregir a `'https://api.previewai.app/api/'`

#### Problema 4.2: Configuración de endpoint no se usa
**Archivo:** `includes/class-preview-ai-api.php`
- El endpoint está hardcodeado en lugar de usar la opción guardada
- **Solución:** Implementar el uso de `get_option( 'preview_ai_api_endpoint' )`

### 5. **Rendimiento y Optimización**

#### 5.1 Consultas a base de datos
- **Archivo:** `includes/class-preview-ai-tracking.php`
- Las consultas SQL usan `PreparedSQL.InterpolatedNotPrepared` (marcado con phpcs:ignore)
- **Recomendación:** Revisar si se pueden optimizar o si hay riesgo de SQL injection (aunque parece seguro)

#### 5.2 Procesamiento de imágenes
- **Archivo:** `includes/class-preview-ai-ajax.php`
- Las imágenes se convierten a base64 en memoria
- **Recomendación:** 
  - Considerar límites de memoria para productos con muchas imágenes
  - Implementar timeouts apropiados (ya está en 120s para generate, 10s para check)

### 6. **Compatibilidad y Dependencias**

#### 6.1 Dependencia de WooCommerce
- El plugin requiere WooCommerce pero no hay verificación explícita en el archivo principal
- **Recomendación:** Agregar verificación de dependencias en la activación

#### 6.2 Action Scheduler
- **Archivo:** `admin/class-preview-ai-admin.php`
- Se usa Action Scheduler para procesamiento en background
- **Recomendación:** Verificar que Action Scheduler esté disponible (WooCommerce lo incluye)

### 7. **Internacionalización (i18n)**

#### ✅ Bien implementado:
- Uso de funciones de traducción `__()`, `esc_html__()`, etc.
- Text domain consistente: `'preview-ai'`
- Archivo `.pot` presente en `languages/`

#### ⚠️ Puntos a revisar:
- Verificar que todas las cadenas de texto estén traducibles
- Revisar que los mensajes de error también estén traducidos

### 8. **Manejo de Errores y UX**

#### 8.1 Mensajes de error
- Los mensajes de error son técnicos en algunos casos
- **Recomendación:** Asegurar mensajes user-friendly para usuarios finales

#### 8.2 Estados de carga
- El frontend tiene estados de carga bien implementados
- **Recomendación:** Verificar que los timeouts sean apropiados para conexiones lentas

### 9. **Limpieza y Mantenimiento**

#### 9.1 Archivo uninstall.php
- ✅ Bien implementado: Limpia opciones, transitorios, tablas y metadatos
- ✅ Soporta multisite

#### 9.2 Archivos index.php de protección
- ✅ Presentes en todos los directorios para prevenir listado de directorios

### 10. **Checklist Final Pre-Producción**

- [ ] **Cambiar endpoint de API** de desarrollo a producción
- [ ] **Eliminar URL de Stripe de prueba** y usar producción
- [ ] **Eliminar código comentado** de testing
- [ ] **Eliminar archivo `test-uninstall.php`**
- [ ] **Corregir endpoint por defecto** en activator (agregar `/api/`)
- [ ] **Implementar uso de opción** `preview_ai_api_endpoint` en lugar de hardcode
- [ ] **Revisar logs** para asegurar que no se expone información sensible
- [ ] **Agregar validación adicional** de tipos de imagen (no solo MIME)
- [ ] **Verificar dependencias** (WooCommerce, Action Scheduler)
- [ ] **Probar flujo completo** en entorno de staging
- [ ] **Revisar permisos** y capacidades en multisite
- [ ] **Verificar traducciones** y completar archivo .pot
- [ ] **Revisar límites de memoria** para catálogos grandes
- [ ] **Documentar configuración** de producción
- [ ] **Revisar políticas de privacidad** (GDPR si aplica)
- [ ] **Configurar monitoreo** de errores y logs
- [ ] **Probar desinstalación** completa del plugin

### 11. **Recomendaciones Adicionales**

#### 11.1 Variables de Entorno
- Considerar usar constantes de WordPress para configuración sensible
- Ejemplo: `define( 'PREVIEW_AI_API_ENDPOINT', 'https://api.previewai.app/api/' );`

#### 11.2 Rate Limiting
- El backend maneja rate limiting (código 429)
- **Recomendación:** Verificar que el frontend maneje correctamente estos casos

#### 11.3 Caché
- Considerar caché para el estado de cuenta (ya implementado con opciones)
- **Recomendación:** Revisar tiempos de expiración apropiados

#### 11.4 Testing
- Implementar tests unitarios para funciones críticas
- Tests de integración para flujos principales
- Tests de seguridad para validaciones

### 12. **Documentación**

#### Archivos presentes:
- ✅ README.txt
- ✅ LICENSE.txt
- ✅ AGENTS.md
- ✅ GUIDELINES.md

#### Recomendación:
- Agregar CHANGELOG.md para tracking de versiones
- Documentar configuración de producción
- Documentar troubleshooting común

---

## 📝 Notas Finales

El plugin está **bien estructurado** y sigue las mejores prácticas de WordPress en su mayoría. Los problemas principales son:

1. **URLs hardcodeadas** que deben cambiarse a producción
2. **Código de testing** que debe eliminarse
3. **Configuración de endpoint** que no se está usando correctamente

Una vez corregidos estos puntos, el plugin estará listo para producción.

**Prioridad de correcciones:**
1. 🔴 **CRÍTICO:** Cambiar endpoints de API
2. 🔴 **CRÍTICO:** Eliminar URL de Stripe de prueba
3. 🟡 **IMPORTANTE:** Eliminar código de testing
4. 🟡 **IMPORTANTE:** Corregir uso de opción de endpoint
5. 🟢 **RECOMENDADO:** Mejoras de seguridad y validación

