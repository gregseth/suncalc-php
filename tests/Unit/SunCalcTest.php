<?php

namespace Unit;

use AurorasLive\AzAltDist;
use AurorasLive\SunCalc;
use DateTime;
use PHPUnit\Framework\TestCase;

class SunCalcTest extends TestCase
{

    public function testSunTimes(): void
    {
        $sunCalc = new SunCalc(new DateTime('2022-01-01 00:00:00'), 48.85, 2.35);

        self::assertEquals('2022-01-01T11:55:13+00:00', ($sunCalc->getSunTimes()['solarNoon'])->format('c'));
        self::assertEquals('2021-12-31T23:55:13+00:00', ($sunCalc->getSunTimes()['nadir'])->format('c'));
        self::assertEquals('2022-01-01T07:45:17+00:00', ($sunCalc->getSunTimes()['sunrise'])->format('c'));
        self::assertEquals('2022-01-01T16:05:10+00:00', ($sunCalc->getSunTimes()['sunset'])->format('c'));
        self::assertEquals('2022-01-01T07:49:16+00:00', ($sunCalc->getSunTimes()['sunriseEnd'])->format('c'));
        self::assertEquals('2022-01-01T16:01:10+00:00', ($sunCalc->getSunTimes()['sunsetStart'])->format('c'));
        self::assertEquals('2022-01-01T07:08:17+00:00', ($sunCalc->getSunTimes()['dawn'])->format('c'));
        self::assertEquals('2022-01-01T16:42:10+00:00', ($sunCalc->getSunTimes()['dusk'])->format('c'));
        self::assertEquals('2022-01-01T06:28:02+00:00', ($sunCalc->getSunTimes()['nauticalDawn'])->format('c'));
        self::assertEquals('2022-01-01T17:22:24+00:00', ($sunCalc->getSunTimes()['nauticalDusk'])->format('c'));
        self::assertEquals('2022-01-01T05:49:40+00:00', ($sunCalc->getSunTimes()['nightEnd'])->format('c'));
        self::assertEquals('2022-01-01T18:00:46+00:00', ($sunCalc->getSunTimes()['night'])->format('c'));
        self::assertEquals('2022-01-01T08:39:59+00:00', ($sunCalc->getSunTimes()['goldenHourEnd'])->format('c'));
        self::assertEquals('2022-01-01T15:10:27+00:00', ($sunCalc->getSunTimes()['goldenHour'])->format('c'));

        self::assertEquals([
            'fraction' => 0.04031441897153637,
            'phase' => 0.9356508960440411,
            'angle' => 1.6045345559741575,
        ], $sunCalc->getMoonIllumination());
        self::assertEquals([
            'moonrise' => new DateTime('2022-01-01T06:39:54.000000+0000'),
            'moonset' => new DateTime('2022-01-01T14:35:39.000000+0000'),
        ], $sunCalc->getMoonTimes());
        self::assertEquals(
            new AzAltDist(
                0.8503632561412419,
                0.002434454309848922,
                364237.0253201312
            ),
            $sunCalc->getMoonPosition(new DateTime('2022-01-01T14:35:39')));
    }

    public function testGetMoonIllumination(): void
    {
        $sunCalc = new SunCalc(new DateTime('2022-01-01 00:00:00'), 48.85, 2.35);

        self::assertEquals([
            'fraction' => 0.04031441897153637,
            'phase' => 0.9356508960440411,
            'angle' => 1.6045345559741575,
        ], $sunCalc->getMoonIllumination());
    }

    public function testGetMoonTimes(): void
    {
        $sunCalc = new SunCalc(new DateTime('2022-01-01 00:00:00'), 48.85, 2.35);

        self::assertEquals([
            'moonrise' => new DateTime('2022-01-01T06:39:54.000000+0000'),
            'moonset' => new DateTime('2022-01-01T14:35:39.000000+0000'),
            ],
            $sunCalc->getMoonTimes()
        );
    }

    public function testGetMoonPosition(): void
    {
        $sunCalc = new SunCalc(new DateTime('2022-01-01 00:00:00'), 48.85, 2.35);

        self::assertEquals(
            new AzAltDist(
                -2.2667265989491434,
                -1.0092001033222662,
                364101.2634286354
            ),
            $sunCalc->getMoonPosition(new DateTime('2022-01-01 00:00:00'))
        );
    }

}