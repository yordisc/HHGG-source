<?php

namespace Tests\Unit;

use App\Support\WeightedScoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeightedScoringServiceTest extends TestCase
{
    private WeightedScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WeightedScoringService::class);
    }

    #[Test]
    public function it_calculates_weighted_score_correctly(): void
    {
        $answers = [
            ['question_id' => 1, 'correct' => true, 'weight' => 1.0],
            ['question_id' => 2, 'correct' => true, 'weight' => 2.0],
            ['question_id' => 3, 'correct' => false, 'weight' => 1.5],
        ];

        // Correct weight: 1.0 + 2.0 = 3.0
        // Total weight: 1.0 + 2.0 + 1.5 = 4.5
        // Score: (3.0 / 4.5) * 100 = 66.67%
        $score = $this->service->calculateWeightedScore($answers);

        $this->assertIsFloat($score);
        $this->assertGreaterThan(66, $score);
        $this->assertLessThan(67, $score);
    }

    #[Test]
    public function it_handles_zero_weight(): void
    {
        $answers = [
            ['question_id' => 1, 'correct' => true, 'weight' => 0],
        ];

        $score = $this->service->calculateWeightedScore($answers);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_handles_all_correct_answers(): void
    {
        $answers = [
            ['question_id' => 1, 'correct' => true, 'weight' => 1.0],
            ['question_id' => 2, 'correct' => true, 'weight' => 1.0],
            ['question_id' => 3, 'correct' => true, 'weight' => 1.0],
        ];

        $score = $this->service->calculateWeightedScore($answers);

        $this->assertEquals(100, $score);
    }

    #[Test]
    public function it_handles_all_incorrect_answers(): void
    {
        $answers = [
            ['question_id' => 1, 'correct' => false, 'weight' => 1.0],
            ['question_id' => 2, 'correct' => false, 'weight' => 1.0],
            ['question_id' => 3, 'correct' => false, 'weight' => 1.0],
        ];

        $score = $this->service->calculateWeightedScore($answers);

        $this->assertEquals(0, $score);
    }

    #[Test]
    public function it_calculates_scoring_statistics(): void
    {
        $answers = [
            ['question_id' => 1, 'correct' => true, 'weight' => 1.5],
            ['question_id' => 2, 'correct' => true, 'weight' => 2.0],
            ['question_id' => 3, 'correct' => false, 'weight' => 1.0],
        ];

        $stats = $this->service->calculateScoringStatistics($answers);

        $this->assertArrayHasKey('score_percentage', $stats);
        $this->assertArrayHasKey('questions_correct', $stats);
        $this->assertArrayHasKey('questions_incorrect', $stats);
        $this->assertEquals(2, $stats['questions_correct']);
        $this->assertEquals(1, $stats['questions_incorrect']);
    }

    #[Test]
    public function it_normalizes_weights_to_sum_100(): void
    {
        $answers = [
            ['question_id' => 1, 'weight' => 2.0],
            ['question_id' => 2, 'weight' => 3.0],
            ['question_id' => 3, 'weight' => 5.0],
        ];

        $normalized = $this->service->normalizeWeights($answers);

        $sum = array_sum(array_column($normalized, 'normalized_weight'));
        $this->assertEquals(100, $sum);
        $this->assertEquals(20, $normalized[0]['normalized_weight']);
        $this->assertEquals(30, $normalized[1]['normalized_weight']);
        $this->assertEquals(50, $normalized[2]['normalized_weight']);
    }

    #[Test]
    public function it_validates_weights(): void
    {
        $validAnswers = [
            ['weight' => 1.0],
            ['weight' => 0.5],
            ['weight' => 100.0],
        ];

        $result = $this->service->validateWeights($validAnswers);
        $this->assertTrue($result['valid']);

        $invalidAnswers = [
            ['weight' => -1.0],
        ];

        $result = $this->service->validateWeights($invalidAnswers);
        $this->assertFalse($result['valid']);
    }

    #[Test]
    public function it_gets_weight_distribution(): void
    {
        $answers = [
            ['question_id' => 1, 'weight' => 1.0],
            ['question_id' => 2, 'weight' => 2.0],
            ['question_id' => 3, 'weight' => 1.5],
        ];

        $distribution = $this->service->getWeightDistribution($answers);

        $this->assertArrayHasKey('total', $distribution);
        $this->assertEquals(4.5, $distribution['total']);
    }
}
