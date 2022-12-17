<?php


namespace Unit;

use AurorasLive\Utils;
use DateTime;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{

    public function testDaysCountFromDate()
    {
        self::assertSame(0.0, Utils::toDays(new DateTime('2000-01-01 12:00:00')));
    }

}