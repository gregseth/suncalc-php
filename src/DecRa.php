<?php

namespace AurorasLive;

class DecRa
{
    public float $dec;
    public float $ra;

    public function __construct(float $d, float $r)
    {
        $this->dec = $d;
        $this->ra = $r;
    }
}
