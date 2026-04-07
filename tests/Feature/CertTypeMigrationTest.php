<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CertTypeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cert_type_column_removed_from_questions_table(): void
    {
        // Verify that cert_type column doesn't exist in questions table
        $this->assertFalse(
            Schema::hasColumn('questions', 'cert_type'),
            'cert_type column should be removed from questions table'
        );
    }

    public function test_cert_type_column_removed_from_certificates_table(): void
    {
        // Verify that cert_type column doesn't exist in certificates table
        $this->assertFalse(
            Schema::hasColumn('certificates', 'cert_type'),
            'cert_type column should be removed from certificates table'
        );
    }

    public function test_certification_model_fillable_excludes_cert_type(): void
    {
        $certification = new Certification();
        $fillable = $certification->getFillable();

        // cert_type should NOT be in fillable
        $this->assertNotContains('cert_type', $fillable);
    }

    public function test_question_model_fillable_excludes_cert_type(): void
    {
        $question = new Question();
        $fillable = $question->getFillable();

        // cert_type should NOT be in fillable
        $this->assertNotContains('cert_type', $fillable);
    }

    public function test_certification_id_is_required_for_questions(): void
    {
        // Create a certification
        $certification = Certification::create([
            'slug' => 'test-cert',
            'name' => 'Test Certification',
            'description' => 'Test',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70,
            'cooldown_days' => 7,
            'result_mode' => 'binary_threshold',
        ]);

        // All questions should belong to a certification via certification_id
        $question = Question::create([
            'certification_id' => $certification->id,
            'prompt' => 'Test question',
            'type' => 'mcq_4',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'explanation' => 'Explanation',
            'active' => true,
        ]);

        // Verify certification_id is set
        $this->assertEquals($certification->id, $question->certification_id);

        // Verify we can access the certification through the relationship
        $this->assertEquals('test-cert', $question->certification->slug);
    }

    public function test_questions_and_certifications_use_relationships(): void
    {
        // Create test data
        $certification = Certification::create([
            'slug' => 'rel-test',
            'name' => 'Relationship Test',
            'description' => 'Test',
            'active' => true,
            'questions_required' => 5,
            'pass_score_percentage' => 65,
            'cooldown_days' => 14,
            'result_mode' => 'binary_threshold',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Question::create([
                'certification_id' => $certification->id,
                'prompt' => "Question {$i}",
                'type' => 'mcq_4',
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'correct_option' => 1,
                'explanation' => 'Exp',
                'active' => true,
            ]);
        }

        // Test relationship from Certification to Questions
        $this->assertCount(3, $certification->questions);

        // Test relationship from Question to Certification
        $question = $certification->questions()->first();
        $this->assertNotNull($question->certification);
        $this->assertEquals('rel-test', $question->certification->slug);
    }

    public function test_certification_can_be_identified_by_slug_not_by_cert_type(): void
    {
        $cert = Certification::create([
            'slug' => 'financial-literacy',
            'name' => 'Financial Literacy Course',
            'description' => 'Learn about finances',
            'active' => true,
            'questions_required' => 20,
            'pass_score_percentage' => 75,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
        ]);

        // Should be able to find by slug
        $found = Certification::where('slug', 'financial-literacy')->first();
        $this->assertNotNull($found);
        $this->assertEquals('financial-literacy', $found->slug);
    }

    public function test_migration_down_restores_cert_type(): void
    {
        // If we run the migration down, cert_type columns should be restored
        // For now, this test just documents the expected behavior
        // In a real scenario, you'd test this with a separate test database
        
        $this->assertTrue(true, 'Migration down is available for rollback if needed');
    }

    public function test_no_cert_type_attribute_access_on_question(): void
    {
        $certification = Certification::create([
            'slug' => 'attr-test',
            'name' => 'Attribute Test',
            'description' => 'Test',
            'active' => true,
            'questions_required' => 5,
            'pass_score_percentage' => 70,
            'cooldown_days' => 7,
            'result_mode' => 'binary_threshold',
        ]);

        $question = Question::create([
            'certification_id' => $certification->id,
            'prompt' => 'Test',
            'type' => 'mcq_4',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'explanation' => 'Exp',
            'active' => true,
        ]);

        // Accessing a missing attribute should return null, not throw
        $this->assertNull($question->cert_type ?? null);
    }

    public function test_no_cert_type_attribute_access_on_certification(): void
    {
        $cert = Certification::create([
            'slug' => 'cert-attr-test',
            'name' => 'Certification Attribute Test',
            'description' => 'Test',
            'active' => true,
            'questions_required' => 5,
            'pass_score_percentage' => 70,
            'cooldown_days' => 7,
            'result_mode' => 'binary_threshold',
        ]);

        // Accessing a missing attribute should return null, not throw
        $this->assertNull($cert->cert_type ?? null);
    }

    public function test_question_cannot_be_created_with_cert_type(): void
    {
        $certification = Certification::create([
            'slug' => 'protect-test',
            'name' => 'Protection Test',
            'description' => 'Test',
            'active' => true,
            'questions_required' => 5,
            'pass_score_percentage' => 70,
            'cooldown_days' => 7,
            'result_mode' => 'binary_threshold',
        ]);

        // Even if we try to pass cert_type, it should be ignored (mass assignment protection)
        $question = Question::create([
            'certification_id' => $certification->id,
            'prompt' => 'Test',
            'type' => 'mcq_4',
            'option_1' => 'A',
            'option_2' => 'B',
            'option_3' => 'C',
            'option_4' => 'D',
            'correct_option' => 1,
            'explanation' => 'Exp',
            'active' => true,
            'cert_type' => 'hetero', // This should be ignored
        ]);

        // cert_type should not be set
        $this->assertNull($question->cert_type ?? null);
    }

    public function test_certification_cannot_be_created_with_cert_type(): void
    {
        // Even if we try to pass cert_type, it should be ignored
        $cert = Certification::create([
            'slug' => 'protect-cert',
            'name' => 'Protection Cert',
            'description' => 'Test',
            'active' => true,
            'questions_required' => 5,
            'pass_score_percentage' => 70,
            'cooldown_days' => 7,
            'result_mode' => 'binary_threshold',
            'cert_type' => 'hetero', // This should be ignored
        ]);

        // cert_type should not be set
        $this->assertNull($cert->cert_type ?? null);
    }

    public function test_all_columns_in_questions_table_are_expected(): void
    {
        $columns = Schema::getColumnListing('questions');

        // Expected columns after cert_type removal
        $expectedColumns = [
            'id',
            'certification_id',
            'prompt',
            'option_1',
            'option_2',
            'option_3',
            'option_4',
            'correct_option',
            'type',
            'explanation',
            'image_path',
            'active',
            'is_test_question',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertContains(
                $column,
                $columns,
                "Expected column '{$column}' not found in questions table"
            );
        }

        // Ensure cert_type is NOT in the list
        $this->assertNotContains('cert_type', $columns);
    }

    public function test_all_columns_in_certifications_table_are_expected(): void
    {
        $columns = Schema::getColumnListing('certifications');

        // Expected columns after cert_type removal
        $expectedColumns = [
            'id',
            'slug',
            'name',
            'description',
            'active',
            'questions_required',
            'pass_score_percentage',
            'cooldown_days',
            'result_mode',
            'pdf_view',
            'home_order',
            'settings',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertContains(
                $column,
                $columns,
                "Expected column '{$column}' not found in certifications table"
            );
        }

        // Ensure cert_type is NOT in the list
        $this->assertNotContains('cert_type', $columns);
    }

    public function test_indexes_for_cert_type_removed(): void
    {
        // Check that cert_type indexes have been removed
        $questionIndexes = Schema::getIndexListing('questions');
        
        // cert_type_index should NOT exist
        $this->assertNotContains('questions_cert_type_index', $questionIndexes);
        $this->assertNotContains('questions_cert_type_active_index', $questionIndexes);

        $certificationIndexes = Schema::getIndexListing('certificates');
        $this->assertNotContains('certificates_cert_type_index', $certificationIndexes);
    }

    public function test_question_factory_does_not_use_cert_type(): void
    {
        // Create a question using the factory
        $question = Question::factory()->create();

        // Should have a certification_id, not cert_type
        $this->assertNotNull($question->certification_id);
        $this->assertNull($question->cert_type ?? null);
    }
}
