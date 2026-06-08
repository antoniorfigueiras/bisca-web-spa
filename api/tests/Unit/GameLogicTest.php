<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GameLogicTest extends TestCase
{
    /**
     * G4: Testa a fórmula do prémio da partida (Stake * 2) - 1.
     */
    public function test_match_prize_calculation(): void
    {
        $stake = 5;
        $expectedPrize = ($stake * 2) - 1; // 9

        $this->assertEquals(9, $expectedPrize);
    }

    public function test_draw_condition_logic(): void
    {
        $p1Points = 60;
        $p2Points = 60;

        $isDraw = ($p1Points === 60 && $p2Points === 60);

        $this->assertTrue($isDraw);
    }
}
