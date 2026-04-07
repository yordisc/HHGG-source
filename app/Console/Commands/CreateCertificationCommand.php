<?php

namespace App\Console\Commands;

use App\Models\Certification;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateCertificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'certification:create
                            {--slug= : Slug único de la certificación}
                            {--name= : Nombre de la certificación}
                            {--questions=30 : Cantidad de preguntas requeridas}
                            {--pass-score=66.67 : Porcentaje de aprobación}
                            {--cooldown=30 : Cooldown en días}
                            {--interactive : Modo interactivo}';

    /**
     * The description of the command.
     */
    protected $description = 'Crea una nueva certificación/curso con preguntas';

    public function handle(): int
    {
        $this->line('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('   Asistente para crear Certificaciones');
        $this->info('═══════════════════════════════════════════════');
        $this->line('');

        // Determinar si es modo interactivo
        $interactive = $this->option('interactive') || !$this->option('slug');

        if ($interactive) {
            return $this->interactiveMode();
        }

        return $this->quickMode();
    }

    /**
     * Modo interactivo con preguntas
     */
    private function interactiveMode(): int
    {
        $slug = $this->getSlugInput();
        if (!$slug) {
            return self::FAILURE;
        }

        $name = $this->getNameInput();
        if (!$name) {
            return self::FAILURE;
        }

        $description = $this->ask('Descripción (opcional)', '');
        $questions_required = (int) $this->ask('Preguntas requeridas', '30');
        $pass_score = (float) $this->ask('Porcentaje de aprobación', '66.67');
        $cooldown_days = (int) $this->ask('Cooldown en días', '30');

        $this->showSummary([
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'questions_required' => $questions_required,
            'pass_score_percentage' => $pass_score,
            'cooldown_days' => $cooldown_days,
        ]);

        if (!$this->confirm('¿Crear esta certificación?')) {
            $this->warn('Operación cancelada');
            return self::SUCCESS;
        }

        // Crear certificación
        try {
            $certification = Certification::create([
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'questions_required' => $questions_required,
                'pass_score_percentage' => $pass_score,
                'cooldown_days' => $cooldown_days,
                'active' => true,
                'home_order' => 100,
            ]);

            $this->info("✓ Certificación creada: {$certification->name} (ID: {$certification->id})");

            // Preguntar si desea agregar preguntas
            if ($this->confirm('¿Deseas agregar preguntas ahora?')) {
                $this->addQuestionsInteractive($certification, $questions_required);
            } else {
                $this->info('Puedes agregar preguntas después usando el panel admin o un seeder.');
            }

            $this->successMessage($certification);
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Modo rápido sin preguntas interactivas
     */
    private function quickMode(): int
    {
        $slug = $this->option('slug');
        $name = $this->option('name');
        $questions_required = (int) $this->option('questions');
        $pass_score = (float) $this->option('pass-score');
        $cooldown_days = (int) $this->option('cooldown');

        if (!$slug || !$name) {
            $this->error('--slug y --name son requeridos en modo no interactivo');
            return self::FAILURE;
        }

        if (!$this->validateSlug($slug)) {
            $this->error('Slug inválido o ya existe');
            return self::FAILURE;
        }

        try {
            $certification = Certification::create([
                'slug' => $slug,
                'name' => $name,
                'questions_required' => $questions_required,
                'pass_score_percentage' => $pass_score,
                'cooldown_days' => $cooldown_days,
                'active' => true,
                'home_order' => 100,
            ]);

            $this->info("✓ Certificación creada: {$certification->name}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Obtener slug y validar
     */
    private function getSlugInput(): ?string
    {
        while (true) {
            $slug = $this->ask('Slug (identificador único, ej: marketing, python, design)');

            if (!$this->validateSlug($slug)) {
                $this->error('Slug inválido. Solo letras minúsculas, números, guiones (3-60 caracteres)');
                continue;
            }

            if (!Certification::where('slug', $slug)->count() === 0) {
                $this->error('Este slug ya existe');
                continue;
            }

            return $slug;
        }
    }

    /**
     * Obtener nombre y validar
     */
    private function getNameInput(): ?string
    {
        while (true) {
            $name = $this->ask('Nombre de la certificación');

            if (strlen($name) < 3) {
                $this->error('El nombre debe tener al menos 3 caracteres');
                continue;
            }

            if (strlen($name) > 120) {
                $this->error('El nombre no debe exceder 120 caracteres');
                continue;
            }

            return $name;
        }
    }

    /**
     * Validar formato de slug
     */
    private function validateSlug(string $slug): bool
    {
        if (!preg_match('/^[a-z0-9_-]{3,60}$/', $slug)) {
            return false;
        }

        return Certification::where('slug', $slug)->count() === 0;
    }

    /**
     * Agregar preguntas interactivamente
     */
    private function addQuestionsInteractive(Certification $certification, int $total): void
    {
        $useStandard = $this->confirm('¿Usar opciones estándar? (Siempre, A veces, Raramente, Nunca)', true);

        $questions = [];
        $this->info("Ingresa {$total} preguntas:");
        $this->line('');

        for ($i = 1; $i <= $total; $i++) {
            $this->line("<info>Pregunta $i de $total</info>");

            $prompt = $this->ask('Texto de la pregunta');
            if (!$prompt) {
                $this->error('Pregunta vacía, intenta de nuevo');
                $i--;
                continue;
            }

            if ($useStandard) {
                $options = ['Siempre', 'A veces', 'Raramente', 'Nunca'];
            } else {
                $options = [
                    $this->ask('Opción 1'),
                    $this->ask('Opción 2'),
                    $this->ask('Opción 3'),
                    $this->ask('Opción 4'),
                ];
            }

            while (true) {
                $correct = $this->ask('Opción correcta (1-4)');
                if (in_array($correct, ['1', '2', '3', '4'])) {
                    break;
                }
                $this->error('Debe ser un número entre 1 y 4');
            }

            $questions[] = [
                'prompt' => $prompt,
                'option_1' => $options[0],
                'option_2' => $options[1],
                'option_3' => $options[2],
                'option_4' => $options[3],
                'correct_option' => (int) $correct,
                'certification_id' => $certification->id,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->info("✓ Pregunta $i agregada");

            if ($i >= 5 && $i < $total && !$this->confirm('¿Continuar?', true)) {
                break;
            }

            $this->line('');
        }

        if (!empty($questions)) {
            Question::insert($questions);
            $this->info("✓ {$total} preguntas creadas");
        }
    }

    /**
     * Mostrar resumen
     */
    private function showSummary(array $data): void
    {
        $this->line('');
        $this->info('📋 RESUMEN');
        $this->line('─────────────────────────────────');
        $this->line("Slug:                {$data['slug']}");
        $this->line("Nombre:              {$data['name']}");
        $this->line("Descripción:         " . (strlen($data['description']) > 30 ? substr($data['description'], 0, 30) . '...' : $data['description']));
        $this->line("Preguntas requeridas: {$data['questions_required']}");
        $this->line("% de aprobación:     {$data['pass_score_percentage']}%");
        $this->line("Cooldown:            {$data['cooldown_days']} días");
        $this->line('─────────────────────────────────');
        $this->line('');
    }

    /**
     * Mensaje de éxito
     */
    private function successMessage(Certification $certification): void
    {
        $this->line('');
        $this->info('════════════════════════════════════════════════');
        $this->info('   ¡ÉXITO! Certificación creada');
        $this->info('════════════════════════════════════════════════');
        $this->line('');
        $this->line("Nombre:   <fg=green>{$certification->name}</>");
        $this->line("Slug:     <fg=green>{$certification->slug}</>");
        $this->line("ID:       <fg=green>{$certification->id}</>");
        $this->line('');
        $this->line('Próximos pasos:');
        $this->line('1. Accede al panel admin: <fg=cyan>/admin/certifications</>');
        $this->line('2. Verifica la certificación creada');
        $this->line('3. Agrega más preguntas si es necesario');
        $this->line('');
    }
}
