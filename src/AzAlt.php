<?php

namespace AurorasLive;

class AzAlt
{
    public float $azimuth;
    public float $altitude;

    public function __construct(float $az, float $alt)
    {
        $this->azimuth = $az;
        $this->altitude = $alt;
    }

    public function getAzimuth(): float
    {
        return $this->azimuth;
    }

    public function getAltitude(): float
    {
        return $this->altitude;
    }
}
