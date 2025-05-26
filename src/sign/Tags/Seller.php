<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class Seller extends Tag
{
    public function __construct($value)
    {
        parent::__construct(1, $value);
    }
}
