<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminQuestionsPhase3FieldsTest extends TestCase
{
    use RefreshDatabase;

    private Certification $certification;
    private Question $question;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->certification = Certification::factory()->create(['active' => true]);
        $this->question = Question::factory()->create([
            'certification_id' => $this->certification->id,
        ]);
    }

    #[Test]
    public function admin_can_update_question_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->put(route('admin.questions.update', $this->question), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Updated prompt',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => '',
                'option_4' => '',
                'correct_option' => 1,
                'type' => 'mcq_2',
            ]);

        $response->assertRedirect();

        $this->question->refresh();
        $this->assertEquals('mcq_2', $this->question->type);
    }

    #[Test]
    public function admin_can_update_question_weight(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->put(route('admin.questions.update', $this->question), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Updated prompt',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => 'Option 3',
                'option_4' => 'Option 4',
                'correct_option' => 1,
                'type' => 'mcq_4',
                'weight' => 2.5,
            ]);

        $response->assertRedirect();

        $this->question->refresh();
        $this->assertEquals(2.5, $this->question->weight);
    }

    #[Test]
    public function admin_can_update_question_sudden_death_mode(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->put(route('admin.questions.update', $this->question), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Updated prompt',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => 'Option 3',
                'option_4' => 'Option 4',
                'correct_option' => 1,
                'type' => 'mcq_4',
                'sudden_death_mode' => 'fail_if_wrong',
            ]);

        $response->assertRedirect();

        $this->question->refresh();
        $this->assertEquals('fail_if_wrong', $this->question->sudden_death_mode);
    }

    #[Test]
    public function admin_can_create_question_with_weight(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->post(route('admin.questions.store'), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'New question',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => 'Option 3',
                'option_4' => 'Option 4',
                'correct_option' => 2,
                'type' => 'mcq_4',
                'weight' => 1.5,
                'sudden_death_mode' => 'pass_if_correct',
            ]);

        $response->assertRedirect();

        $question = Question::query()->latest('id')->first();
        $this->assertEquals(1.5, $question->weight);
        $this->assertEquals('pass_if_correct', $question->sudden_death_mode);
    }

    #[Test]
    public function admin_can_create_mcq2_question(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->post(route('admin.questions.store'), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'True or False question',
                'option_1' => 'True',
                'option_2' => 'False',
                'correct_option' => 1,
                'type' => 'mcq_2',
            ]);

        $response->assertRedirect();

        $question = Question::query()->latest('id')->first();
        $this->assertEquals('mcq_2', $question->type);
    }

    #[Test]
    public function validation_rejects_invalid_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->put(route('admin.questions.update', $this->question), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Updated prompt',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => 'Option 3',
                'option_4' => 'Option 4',
                'correct_option' => 1,
                'type' => 'invalid_type',
            ]);

        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function validation_rejects_invalid_weight(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->put(route('admin.questions.update', $this->question), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Updated prompt',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => 'Option 3',
                'option_4' => 'Option 4',
                'correct_option' => 1,
                'type' => 'mcq_4',
                'weight' => -1.0,
            ]);

        $response->assertSessionHasErrors('weight');
    }

    #[Test]
    public function validation_rejects_invalid_sudden_death_mode(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->put(route('admin.questions.update', $this->question), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Updated prompt',
                'option_1' => 'Option 1',
                'option_2' => 'Option 2',
                'option_3' => 'Option 3',
                'option_4' => 'Option 4',
                'correct_option' => 1,
                'type' => 'mcq_4',
                'sudden_death_mode' => 'invalid_mode',
            ]);

        $response->assertSessionHasErrors('sudden_death_mode');
    }

    #[Test]
    public function mcq2_questions_have_options_3_and_4_as_null(): void
    {
        $this->actingAs($this->adminUser)
            ->withSession(['admin_authenticated' => true])
            ->post(route('admin.questions.store'), [
                'cert_type' => $this->certification->slug,
                'prompt' => 'Two option question',
                'option_1' => 'Yes',
                'option_2' => 'No',
                'correct_option' => 1,
                'type' => 'mcq_2',
            ]);

        $question = Question::query()->latest('id')->first();
        $this->assertEquals('', $question->option_3);
        $this->assertEquals('', $question->option_4);
    }
}
