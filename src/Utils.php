<?php

namespace AurorasLive;

use DateInterval;
use DateTime;

class Utils
{
    public static int $daySec = 60 * 60 * 24;

    public static int $J1970 = 2440588;

    public static int $J2000 = 2451545;

    public static function toDays($date): float
    {
        return static::toJulian($date) - static::$J2000;
    }

    public static function rightAscension($l, $b): float
    {
        return atan2(sin($l) * cos(E) - tan($b) * sin(E), cos($l));
    }

    public static function declination($l, $b): float
    {
        return asin(sin($b) * cos(E) + cos($b) * sin(E) * sin($l));
    }

    public static function azimuth($H, $phi, $dec): float
    {
        return atan2(sin($H), cos($H) * sin($phi) - tan($dec) * cos($phi));
    }

    public static function altitude($H, $phi, $dec): float
    {
        return asin(sin($phi) * sin($dec) + cos($phi) * cos($dec) * cos($H));
    }

    public static function siderealTime($d, $lw): float
    {
        return RAD * (280.16 + 360.9856235 * $d) - $lw;
    }

    // calculations for sun times
    public static function julianCycle($d, $lw): float
    {
        return round($d - J0 - $lw / (2 * M_PI));
    }

    public static function approxTransit($Ht, $lw, $n): float
    {
        return J0 + ($Ht + $lw) / (2 * M_PI) + $n;
    }

    public static function solarTransitJ($ds, $M, $L): float
    {
        return static::$J2000 + $ds + 0.0053 * sin($M) - 0.0069 * sin(2 * $L);
    }

    public static function hourAngle($h, $phi, $d): float
    {
        return acos((sin($h) - sin($phi) * sin($d)) / (cos($phi) * cos($d)));
    }

    // returns set time for the given sun altitude
    public static function getSetJ($h, $lw, $phi, $dec, $n, $M, $L): float
    {
        $w = static::hourAngle($h, $phi, $dec);
        $a = static::approxTransit($w, $lw, $n);

        return static::solarTransitJ($a, $M, $L);
    }

    // general sun calculations
    public static function solarMeanAnomaly($d): float
    {
        return RAD * (357.5291 + 0.98560028 * $d);
    }

    public static function eclipticLongitude($M): float
    {

        $C = RAD * (1.9148 * sin($M) + 0.02 * sin(2 * $M) + 0.0003 * sin(3 * $M)); // equation of center
        $P = RAD * 102.9372; // perihelion of the Earth

        return $M + $C + $P + M_PI;
    }

    public static function hoursLater($date, $h): DateTime
    {
        $dt = clone $date;

        return $dt->add(new DateInterval('PT' . round($h * 3600) . 'S'));
    }

    public static function sunCoords($d): DecRa
    {

        $M = self::solarMeanAnomaly($d);
        $L = self::eclipticLongitude($M);

        return new DecRa(
            self::declination($L, 0),
            self::rightAscension($L, 0)
        );
    }

    public static function moonCoords($d): DecRaDist
    {
 // geocentric ecliptic coordinates of the moon

        $L = RAD * (218.316 + 13.176396 * $d); // ecliptic longitude
        $M = RAD * (134.963 + 13.064993 * $d); // mean anomaly
        $F = RAD * (93.272 + 13.229350 * $d);  // mean distance

        $l = $L + RAD * 6.289 * sin($M); // longitude
        $b = RAD * 5.128 * sin($F);     // latitude
        $dt = 385001 - 20905 * cos($M);  // distance to the moon in km

        return new DecRaDist(
            static::declination($l, $b),
            static::rightAscension($l, $b),
            $dt
        );
    }

    public static function toJulian(DateTime $date): float
    {
        return $date->getTimestamp() / static::$daySec - 0.5 + static::$J1970;
    }

    public static function fromJulian($j, $d): ?DateTime
    {
        if (!is_nan($j)) {
            $dt = new DateTime("@" . round(($j + 0.5 - static::$J1970) * static::$daySec));
            $dt->setTimezone($d->getTimezone());
            return $dt;
        }

        return null;
    }
}
