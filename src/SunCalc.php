<?php

namespace AurorasLive;

/*
 SunCalc is a PHP library for calculating sun/moon position and light phases.
 https://github.com/gregseth/suncalc-php

 Based on Vladimir Agafonkin's JavaScript library.
 https://github.com/mourner/suncalc

 Sun calculations are based on http://aa.quae.nl/en/reken/zonpositie.html
 formulas.

 Moon calculations are based on http://aa.quae.nl/en/reken/hemelpositie.html
 formulas.

 Calculations for illumination parameters of the moon are based on
 http://idlastro.gsfc.nasa.gov/ftp/pro/astro/mphase.pro formulas and Chapter 48
 of "Astronomical Algorithms" 2nd edition by Jean Meeus (Willmann-Bell,
 Richmond) 1998.

 Calculations for moon rise/set times are based on
 http://www.stargazing.net/kepler/moonrise.html article.
*/


// shortcuts for easier to read formulas
use DateTime;

define('RAD', M_PI / 180);

// general calculations for position
define('E', RAD * 23.4397); // obliquity of the Earth
define('J0', 0.0009);


class SunCalc
{
    public DateTime $date;
    public float $lat;
    public float $lng;

    // sun times configuration (angle, morning name, evening name)
    private array $times = [
        [-0.833, 'sunrise', 'sunset'],
        [-0.3, 'sunriseEnd', 'sunsetStart'],
        [-6, 'dawn', 'dusk'],
        [-12, 'nauticalDawn', 'nauticalDusk'],
        [-18, 'nightEnd', 'night'],
        [6, 'goldenHourEnd', 'goldenHour']
    ];

    public function __construct(DateTime $date, float $lat, float $lng)
    {
        $this->date = $date;
        $this->lat = $lat;
        $this->lng = $lng;
    }

    // calculates sun position for a given date and latitude/longitude
    public function getSunPosition(): AzAlt
    {

        $lw = RAD * -$this->lng;
        $phi = RAD * $this->lat;
        $d = Utils::toDays($this->date);

        $c = Utils::sunCoords($d);
        $H = Utils::siderealTime($d, $lw) - $c->ra;

        return new AzAlt(
            Utils::azimuth($H, $phi, $c->dec),
            Utils::altitude($H, $phi, $c->dec)
        );
    }

    // calculates sun times for a given date and latitude/longitude
    public function getSunTimes(): array
    {

        $lw = RAD * -$this->lng;
        $phi = RAD * $this->lat;

        $d = Utils::toDays($this->date);
        $n = Utils::julianCycle($d, $lw);
        $ds = Utils::approxTransit(0, $lw, $n);

        $M = Utils::solarMeanAnomaly($ds);
        $L = Utils::eclipticLongitude($M);
        $dec = Utils::declination($L, 0);

        $Jnoon = Utils::solarTransitJ($ds, $M, $L);

        $result = [
            'solarNoon' => Utils::fromJulian($Jnoon, $this->date),
            'nadir' => Utils::fromJulian($Jnoon - 0.5, $this->date)
        ];

        for ($i = 0, $len = count($this->times); $i < $len; $i += 1) {
            $time = $this->times[$i];

            $Jset = Utils::getSetJ($time[0] * RAD, $lw, $phi, $dec, $n, $M, $L);
            $Jrise = $Jnoon - ($Jset - $Jnoon);

            $result[$time[1]] = Utils::fromJulian($Jrise, $this->date);
            $result[$time[2]] = Utils::fromJulian($Jset, $this->date);
        }

        return $result;
    }


    public function getMoonPosition(DateTime $date): AzAltDist
    {
        $lw = RAD * -$this->lng;
        $phi = RAD * $this->lat;
        $d = Utils::toDays($date);

        $c = Utils::moonCoords($d);
        $H = Utils::siderealTime($d, $lw) - $c->ra;
        $h = Utils::altitude($H, $phi, $c->dec);

        // altitude correction for refraction
        $h = $h + RAD * 0.017 / tan($h + RAD * 10.26 / ($h + RAD * 5.10));

        return new AzAltDist(
            Utils::azimuth($H, $phi, $c->dec),
            $h,
            $c->dist
        );
    }


    public function getMoonIllumination(): array
    {

        $d = Utils::toDays($this->date);
        $s = Utils::sunCoords($d);
        $m = Utils::moonCoords($d);

        $sdist = 149598000; // distance from Earth to Sun in km

        $phi = acos(sin($s->dec) * sin($m->dec) + cos($s->dec) * cos($m->dec) * cos($s->ra - $m->ra));
        $inc = atan2($sdist * sin($phi), $m->dist - $sdist * cos($phi));
        $angle = atan2(cos($s->dec) * sin($s->ra - $m->ra), sin($s->dec) * cos($m->dec) - cos($s->dec) * sin($m->dec) * cos($s->ra - $m->ra));

        return [
            'fraction' => (1 + cos($inc)) / 2,
            'phase' => 0.5 + 0.5 * $inc * ($angle < 0 ? -1 : 1) / M_PI,
            'angle' => $angle
        ];
    }

    public function getMoonTimes(bool $inUTC = false): array
    {
        $t = clone $this->date;
        if ($inUTC) {
            $t->setTimezone(new \DateTimeZone('UTC'));
        }

        $t->setTime(0, 0, 0);

        $hc = 0.133 * RAD;
        $h0 = $this->getMoonPosition($t)->altitude - $hc;
        $rise = 0;
        $set = 0;
        $x1 = 0;
        $x2 = 0;

        // go in 2-hour chunks, each time seeing if a 3-point quadratic curve crosses zero (which means rise or set)
        for ($i = 1; $i <= 24; $i += 2) {
            $h1 = $this->getMoonPosition(Utils::hoursLater($t, $i))->altitude - $hc;
            $h2 = $this->getMoonPosition(Utils::hoursLater($t, $i + 1))->altitude - $hc;

            $a = ($h0 + $h2) / 2 - $h1;
            $b = ($h2 - $h0) / 2;
            $xe = -$b / (2 * $a);
            $ye = ($a * $xe + $b) * $xe + $h1;
            $d = $b * $b - 4 * $a * $h1;
            $roots = 0;

            if ($d >= 0) {
                $dx = sqrt($d) / (abs($a) * 2);
                $x1 = $xe - $dx;
                $x2 = $xe + $dx;
                if (abs($x1) <= 1) {
                    $roots++;
                }
                if (abs($x2) <= 1) {
                    $roots++;
                }
                if ($x1 < -1) {
                    $x1 = $x2;
                }
            }

            if ($roots === 1) {
                if ($h0 < 0) {
                    $rise = $i + $x1;
                } else {
                    $set = $i + $x1;
                }
            } else if ($roots === 2) {
                $rise = $i + ($ye < 0 ? $x2 : $x1);
                $set = $i + ($ye < 0 ? $x1 : $x2);
            }

            if ($rise != 0 && $set != 0) {
                break;
            }

            $h0 = $h2;
        }

        $result = [];

        if ($rise != 0) {
            $result['moonrise'] = Utils::hoursLater($t, $rise);
        }
        if ($set != 0) {
            $result['moonset'] = Utils::hoursLater($t, $set);
        }

        if ($rise == 0 && $set == 0) {
            $result[$ye > 0 ? 'alwaysUp' : 'alwaysDown'] = true;
        }

        return $result;
    }
}
