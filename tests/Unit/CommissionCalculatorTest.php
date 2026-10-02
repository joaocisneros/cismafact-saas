<?php

namespace Tests\Unit;

use App\Services\CommissionCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculator();
    }

    #[Test]
    public function solo_meta_entrega_el_bono_al_cumplir_el_objetivo(): void
    {
        $result = $this->calculator->calculate('goal', 1200, 1000, 2, 100);

        $this->assertSame(0.0, $result['commission']);
        $this->assertSame(100.0, $result['bonus']);
        $this->assertSame(100.0, $result['total']);
    }

    #[Test]
    public function solo_comision_calcula_el_porcentaje_de_las_ventas(): void
    {
        $result = $this->calculator->calculate('commission', 1200, 1000, 2, 100);

        $this->assertSame(24.0, $result['commission']);
        $this->assertSame(0.0, $result['bonus']);
        $this->assertSame(24.0, $result['total']);
    }

    #[Test]
    public function meta_mas_comision_suma_porcentaje_y_bono(): void
    {
        $result = $this->calculator->calculate('both', 1200, 1000, 2, 100);

        $this->assertSame(24.0, $result['commission']);
        $this->assertSame(100.0, $result['bonus']);
        $this->assertSame(124.0, $result['total']);
    }

    #[Test]
    public function no_entrega_bono_si_no_alcanza_la_meta(): void
    {
        $result = $this->calculator->calculate('both', 900, 1000, 2, 100);

        $this->assertFalse($result['goal_reached']);
        $this->assertSame(18.0, $result['commission']);
        $this->assertSame(0.0, $result['bonus']);
    }
}
