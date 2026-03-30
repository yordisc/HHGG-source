# Integracion Rapida del Disclaimer en Blade

## 1) Home o Footer

```blade
<p class="text-xs text-gray-500">
  {{ __('app.disclaimer_short') }}
</p>
```

## 2) Bloque completo (home/resultado)

```blade
<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
  {{ __('app.disclaimer_full') }}
</div>
```

## 3) Pantalla de resultado

```blade
<p class="mt-4 text-sm text-gray-600">
  {{ __('results.disclaimer') }}
</p>
```

## 4) Pie del PDF

```blade
<p style="font-size:10px;color:#666;margin-top:16px;">
  {{ __('cert.disclaimer_pdf') }}
</p>
```

## 5) Recomendacion de i18n

- Mantener ingles como fallback en `config/app.php`.
- Aplicar middleware de locale por `Accept-Language`.
- Permitir selector manual de idioma en header.
