<?php

namespace AurorasLive;

class AzAltDist extends AzAlt
{
    public float $dist;

    public function __construct(float $az, float $alt, float $dist)
    {
        parent::__construct($az, $alt);
        $this->dist = $dist;
    }

    public function getDist(): float
    {
        return $this->dist;
    }
}
