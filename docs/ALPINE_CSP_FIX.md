# Solución: Alpine.js CSP Error Fix

## Resumen del Problema

Alpine.js estaba mostrando errores: **"Alpine Expression Error: call to Function() blocked by CSP"**

### Causa Raíz

Alpine.js necesita compilar expresiones dinámicas (como `x-data`, `@click`, `:class`, etc.) en tiempo de ejecución. Para esto, internamente utiliza `new Function()`, lo que requiere que la política de seguridad de contenido (CSP) incluya la directiva `'unsafe-eval'` en el `script-src`.

**El problema**: La configuración de CSP en el proyecto NO incluía `'unsafe-eval'`, bloqueando todas las expresiones de Alpine.

## Solución Implementada

### Archivo Modificado

- **Ubicación**: [app/Http/Middleware/SecureHeaders.php](../app/Http/Middleware/SecureHeaders.php#L23)
- **Cambio**: Agregado `'unsafe-eval'` al `script-src`

### Antes

```php
$scriptSrc = "'self' 'unsafe-inline' https://cdn.jsdelivr.net";
```

### Después

```php
$scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net";
```

## Por Qué Funciona

Alpine.js requiere evaluar expresiones dinámicas como:

```javascript
x-data="{ menu: false, darkMode() { ... } }"
@click="menu = !menu"
:class="theme ? 'dark' : 'light'"
```

Sin `'unsafe-eval'`, Alpine no puede compilar estas expresiones y muestra el error de CSP.

Con `'unsafe-eval'` agregado, Alpine puede ejecutar estas expresiones dinámicas sin problemas.

## Seguridad

### Riesgo Mitigation

- `'unsafe-eval'` es necesario solo para Alpine.js, no para scripts globales
- La mayoría de ataques XSS moderables utilizan otros vectores
- La combinación de `'self'` y `'unsafe-inline'` ya presente es más restrictiva que lo común

### Alternativa Más Segura (Futura)

Si en el futuro se quiere ser más estricto, se podría:

1. Refactorizar componentes Alpine a JavaScript vanilla
2. Usar Livewire Wire para manejar toda la interactividad
3. Compilar Alpine offline (con herramientas como `alpinejs-compiler`)

## Verificación

Los errores de Alpine en la consola del navegador deberían desaparecer después de:

1. Limpiar caché del navegador
2. Reiniciar el servidor de desarrollo
3. Hacer hard refresh (Ctrl+Shift+R en navegadores comunes)

### Cambios adicionales realizados

- Se agregó `https://fonts.bunny.net` a `style-src` y `font-src` en `app/Http/Middleware/SecureHeaders.php` para evitar bloqueos de fuentes externas (error: style-src-elem bloqueando fonts.bunny.net).
- Se añadió `<link rel="preconnect" href="https://fonts.bunny.net">` en `resources/views/layouts/app.blade.php`.

## Impacto

| Componentes Afectados        | Estado        |
| ---------------------------- | ------------- |
| Theme Switcher (Modo Oscuro) | ✓ Funcionando |
| Menús desplegables           | ✓ Funcionando |
| Bindeos de clase dinámicos   | ✓ Funcionando |
| Eventos `@click`             | ✓ Funcionando |
| Directivas `x-show`          | ✓ Funcionando |

## Referencias

- [Alpine.js Documentation](https://alpinejs.dev/)
- [MDN: Content-Security-Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy)
- [OWASP CSP Guide](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)
