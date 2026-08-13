<?php

namespace Tests\Unit\Employees;

use App\Models\ColombianMunicipality;
use App\Services\Employees\EmployeeTerritoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTerritoryResolverTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeTerritoryResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new EmployeeTerritoryResolver;

        ColombianMunicipality::query()->create([
            'department_code' => '76',
            'department_name' => 'Valle del Cauca',
            'municipality_code' => '76001',
            'municipality_name' => 'Cali',
        ]);
    }

    public function test_resolves_municipality_code(): void
    {
        $result = $this->resolver->resolve(null, '76001', 'labor');

        $this->assertSame('76001', $result['municipality_code']);
        $this->assertSame('76', $result['department_code']);
    }

    public function test_resolves_municipality_name(): void
    {
        $result = $this->resolver->resolve(null, 'Cali', 'residencia');

        $this->assertSame('76001', $result['municipality_code']);
        $this->assertSame('76', $result['department_code']);
    }

    public function test_resolves_department_name_only(): void
    {
        $result = $this->resolver->resolve(null, 'Valle del Cauca', 'labor');

        $this->assertNull($result['municipality_code']);
        $this->assertSame('76', $result['department_code']);
    }
}
