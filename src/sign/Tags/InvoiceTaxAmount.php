<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class InvoiceTaxAmount extends Tag
{
    public function __construct($value)
    {
        parent::__construct(5, $value);
    }
}
