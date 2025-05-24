<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class InvoiceTotalAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(4, $value);
    }
}
