<?php

namespace Tests\Unit;

use App\Models\Certification;
use App\Support\AutoResultRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoResultRuleServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutoResultRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutoResultRuleService::class);
    }

    /** @test */
    public function it_evaluates_exact_name_match(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => [
                'rules' => [
                    [
                        'name_pattern' => 'John',
                        'last_name_pattern' => 'Doe',
                        'decision' => 'pass',
                    ],
                ],
            ],
        ]);

        $result = $this->service->evaluate($certification, 'John', 'Doe');

        $this->assertEquals('pass', $result['decision']);
    }

    /** @test */
    public function it_evaluates_wildcard_name_match(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => [
                'rules' => [
                    [
                        'name_pattern' => 'J*',
                        'decision' => 'pass',
                    ],
                ],
            ],
        ]);

        $result = $this->service->evaluate($certification, 'John', 'Doe');

        $this->assertEquals('pass', $result['decision']);
    }

    /** @test */
    public function it_evaluates_apellido_match(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => [
                'rules' => [
                    [
                        'last_name_pattern' => 'Smith',
                        'decision' => 'pass',
                    ],
                ],
            ],
        ]);

        $result = $this->service->evaluate($certification, 'John', 'Smith');

        $this->assertEquals('pass', $result['decision']);
    }

    /** @test */
    public function it_returns_false_when_no_match(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => [
                'rules' => [
                    [
                        'name_pattern' => 'Jane',
                        'decision' => 'pass',
                    ],
                ],
            ],
        ]);

        $result = $this->service->evaluate($certification, 'John', 'Doe');

        $this->assertEquals('none', $result['decision']);
    }

    /** @test */
    public function it_applies_rules_in_order(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => [
                'rules' => [
                    ['name_pattern' => 'A*', 'decision' => 'fail'],
                    ['name_pattern' => 'Alice', 'decision' => 'pass'],
                ],
            ],
        ]);

        $result = $this->service->evaluate($certification, 'Alice', 'Smith');

        // First matching rule applies
        $this->assertEquals('fail', $result['decision']); // A* fails, so Alice fails
    }

    /** @test */
    public function it_returns_none_when_mode_is_none(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'none',
        ]);

        $result = $this->service->evaluate($certification, 'John', 'Doe');
        $this->assertEquals('none', $result['decision']);
    }

    /** @test */
    public function it_returns_none_when_config_has_no_rules(): void
    {
        $certification = Certification::factory()->create([
            'auto_result_rule_mode' => 'name_rule',
            'auto_result_rule_config' => ['rules' => []],
        ]);

        $result = $this->service->evaluate($certification, 'John', 'Doe');
        $this->assertEquals('none', $result['decision']);
    }

    /** @test */
    public function it_validates_config_structure(): void
    {
        $validConfig = [
            'rules' => [
                [
                    'name_pattern' => 'test',
                    'decision' => 'pass',
                ],
            ],
        ];

        $result = $this->service->validateConfig($validConfig);
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_creates_empty_config(): void
    {
        $config = $this->service->createEmptyConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('rules', $config);
        $this->assertIsArray($config['rules']);
        $this->assertEmpty($config['rules']);
    }

    /** @test */
    public function it_gets_mode_name(): void
    {
        $name = $this->service->getModeName('name_rule');
        $this->assertIsString($name);

        $name = $this->service->getModeName('none');
        $this->assertIsString($name);
    }

    /** @test */
    public function it_adds_rule_to_config(): void
    {
        $config = $this->service->createEmptyConfig();

        $updated = $this->service->addRule(
            $config,
            'John*',
            'Doe*',
            'pass',
            'Regla de prueba'
        );

        $this->assertCount(1, $updated['rules']);
        $this->assertEquals('John*', $updated['rules'][0]['name_pattern']);
        $this->assertEquals('Doe*', $updated['rules'][0]['last_name_pattern']);
        $this->assertEquals('pass', $updated['rules'][0]['decision']);
    }
}
