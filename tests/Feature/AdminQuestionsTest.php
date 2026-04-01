<?php

namespace Tests\Feature;

use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_questions_requires_authentication(): void
    {
        $response = $this->get(route('admin.questions.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_with_valid_key_allows_access(): void
    {
        $response = $this->post(route('admin.login.submit'), [
            'admin_key' => 'test-admin-key',
        ]);

        $response->assertRedirect(route('admin.questions.index'));

        $this->get(route('admin.questions.index'))
            ->assertOk();
    }

    public function test_admin_export_csv_contains_question_and_options(): void
    {
        Question::create([
            'cert_type' => 'hetero',
            'prompt' => 'Test question',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'active' => true,
        ]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->get(route('admin.questions.export.csv'));

        $response->assertOk();
        $response->assertStreamed();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('question_id,language,cert_type,prompt,option_1,option_2,option_3,option_4,correct_option,active', $content);
        $this->assertStringContainsString('Test question', $content);
        $this->assertStringContainsString('A,B,C,D', $content);
    }
}
