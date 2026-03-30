# Siguiente Paso Tecnico (arranque inmediato)

Este repo ya tiene la planificacion. Para arrancar el codigo base de la app:

## 1) Ejecutar setup automatico

```bash
chmod +x scripts/setup-local.sh
./scripts/setup-local.sh
```

## 2) Levantar el proyecto

En terminal 1:

```bash
php artisan migrate
php artisan serve
```

En terminal 2:

```bash
npm run dev
```

## 3) Verificacion minima

- Abrir `http://127.0.0.1:8000`.
- Confirmar que la app Laravel responde.
- Ejecutar `php artisan route:list`.
- Definir y mostrar un disclaimer fijo en layout principal (header o footer).

## 4) Fase 1 inmediata

Cuando la app ya este arriba, crear estas migraciones:

```bash
php artisan make:migration create_certificates_table
php artisan make:migration create_questions_table
php artisan make:migration create_question_translations_table
php artisan make:migration create_rate_limits_table
php artisan cache:table
php artisan session:table
```

Luego te ayudo a completar el schema y los modelos uno por uno.

## 5) Regla editorial desde el dia 1

- Tono: humoristico/satirico.
- Limite: no lenguaje discriminatorio o degradante.
- Obligatorio: mensaje de "no certificacion real" + apoyo a todas las mentalidades
	en home, resultado y PDF.
