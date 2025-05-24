<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class PublicKey extends Tag
{
    public function __construct($value)
    {
        parent::__construct(8, $value);
    }
}
