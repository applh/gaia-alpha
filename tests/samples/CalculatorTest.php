<?php

namespace GaiaAlpha\Tests\Samples;

use GaiaAlpha\Tests\Framework\TestCase;
use GaiaAlpha\Tests\Framework\Assert;

class CalculatorTest extends TestCase
{

    private $value;

    public function setUp()
    {
        $this->value = 0;
    }

    public function testAddition()
    {
        $result = 1 + 1;
        Assert::assertEquals(2, $result, "1+1 should be 2");
    }

    public function testSubtraction()
    {
        $this->value = 5;
        $result = $this->value - 3;
        Assert::assertEquals(2, $result, "5-3 should be 2");
    }

    public function testArray()
    {
        $arr = ['a' => 1, 'b' => 2];
        Assert::assertArrayHasKey('a', $arr);
        Assert::assertCount(2, $arr);
    }
}
