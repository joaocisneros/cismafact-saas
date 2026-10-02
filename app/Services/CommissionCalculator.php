<?php

namespace App\Services;

class CommissionCalculator
{
    public function calculate(string $mode, float $sales, float $goal, float $percentage, float $bonus): array
    {
        $goalReached = $goal > 0 && $sales >= $goal;
        $commission = in_array($mode, ['commission', 'both'], true)
            ? round($sales * $percentage / 100, 2)
            : 0.0;
        $goalBonus = in_array($mode, ['goal', 'both'], true) && $goalReached
            ? round($bonus, 2)
            : 0.0;

        return [
            'goal_reached' => $goalReached,
            'commission' => $commission,
            'bonus' => $goalBonus,
            'total' => round($commission + $goalBonus, 2),
        ];
    }
}
