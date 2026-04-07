# Herramienta: Convertir CSV a Seeder

Esta herramienta te permite convertir un archivo CSV con preguntas a un Seeder de Laravel.

## Uso

```bash
php scripts/csv-to-seeder.php <slug> <archivo-csv> [output]
```

## Requisitos

1. **Archivo CSV** con este formato:
```
prompt,option_1,option_2,option_3,option_4,correct_option
"Pregunta 1","Opción A","Opción B","Opción C","Opción D",1
"Pregunta 2","Opción A","Opción B","Opción C","Opción D",2
```

2. **Columnas requeridas:**
   - `prompt` - Texto de la pregunta
   - `option_1` - Primera opción
   - `option_2` - Segunda opción
   - `option_3` - Tercera opción
   - `option_4` - Cuarta opción
   - `correct_option` - Número 1-4 indicando cuál es correcta

## Ejemplos

### Ejemplo 1: Convertir y crear seeder

```bash
php scripts/csv-to-seeder.php marketing_101 database/templates/marketing-questions.csv
```

Esto crea: `database/seeders/Marketing101Seeder.php`

### Ejemplo 2: Con ruta personalizada

```bash
php scripts/csv-to-seeder.php python_course ./questions.csv database/seeders/PythonCourseSeeder.php
```

## Plantilla CSV

Puedes descargar la plantilla de ejemplo:

```bash
database/templates/questions-example.csv
```

O crear la tuya manualmente. Recuerda:
- Usar comillas en los textos si contienen comas
- correct_option debe ser 1, 2, 3 o 4
- Los textos pueden contener caracteres especiales

## Cómo usar el seeder generado

1. **Ejecutar el seeder:**
```bash
php artisan db:seed --class=MarketingQuestionsSeeder
```

2. **O registrarlo en DatabaseSeeder.php:**

Abre `database/seeders/DatabaseSeeder.php` y agrega:

```php
public function run(): void
{
    $this->call([
        MarketingQuestionsSeeder::class,
        // otros seeders...
    ]);
}
```

Luego ejecuta:
```bash
php artisan migrate:fresh --seed
```

## Solución de Problemas

### Error: "CSV file not found"
Verifica que la ruta al CSV es correcta:
```bash
ls database/templates/questions-example.csv
```

### Error: "Invalid column count"
El CSV tiene un número diferente de columnas en algunas filas.
Verifica que todos tengan 6 columnas.

### Caracteres especiales no funcionan
Asegúrate que el archivo CSV está en UTF-8:
```bash
file -i questions.csv
```

Si no es UTF-8, conviértelo:
```bash
iconv -f ISO-8859-1 -t UTF-8 questions.csv > questions-utf8.csv
```

## Estructura del Seeder Generado

El seeder generado se verá así:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MarketingQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        // Primero asegúrate de crear la certificación:
        // php artisan certification:create --slug=marketing_101 --name="Marketing 101"

        $certification = \App\Models\Certification::where('slug', 'marketing_101')->first();

        if (!$certification) {
            $this->command->error('Certificación marketing_101 no encontrada. Créala primero.');
            return;
        }

        \Database\Seeders\QuestionSeederHelper::seedQuestionsForCertification(
            'marketing_101',
            [
                ['prompt' => 'Pregunta 1', 'correct' => 1],
                // ... más preguntas
            ]
        );
    }
}
```

## Tips

✅ **Usa comillas** en textos largos o con comas:
```csv
"¿Es esto una pregunta, verdad?","Sí, es correcto","No es correcto","Tal vez","Quizás",1
```

✅ **Escapa comillas internas** con doble comilla:
```csv
"Pregunta sobre ""comillas""","Opción A","Opción B","Opción C","Opción D",1
```

✅ **Verifica el CSV** antes de convertir:
```bash
head -5 questions.csv
wc -l questions.csv  # Ver cantidad de líneas
```

✅ **Mantén los slugs simples:**
- Sin espacios
- Sin caracteres especiales
- Solo: a-z, 0-9, -, _

## Automatizar

Puedes crear un alias en `.bash_profile` o `.zshrc`:

```bash
alias create-cert='php scripts/csv-to-seeder.php'
```

Luego:
```bash
create-cert marketing_101 questions.csv
php artisan db:seed --class=Marketing101Seeder
```
