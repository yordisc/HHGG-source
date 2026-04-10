# Template para Banco de Preguntas

## 1. CSV minimo
Columnas requeridas:
- `cert_type`
- `prompt`
- `option_1`
- `option_2`
- `option_3`
- `option_4`
- `correct_option`

Columnas opcionales:
- `question_id`
- `language`
- `active`

## 2. Ejemplo
```csv
cert_type,prompt,option_1,option_2,option_3,option_4,correct_option,language,active
marketing-2026,Que es branding?,Logo,Promesa de marca,Canal de venta,Anuncio,2,en,1
marketing-2026,Que es branding?,Logo,Promesa de marca,Canal de venta,Anuncio,2,es,1
```

## 3. Reglas de calidad
1. Cada pregunta debe tener `correct_option` entre 1 y 4.
2. Evitar preguntas ambiguas.
3. Mantener longitud de opciones similar.
4. Cargar primero `language=en` como base, luego traducciones.

## 4. Flujo de importacion
1. Admin -> Preguntas -> Plantilla.
2. Completar CSV.
3. Importar CSV.
4. Validar resumen (creadas, actualizadas, omitidas).
