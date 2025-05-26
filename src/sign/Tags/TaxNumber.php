<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class TaxNumber extends Tag
{
    public function __construct($value)
    {
        parent::__construct(2, $value);
    }
}
