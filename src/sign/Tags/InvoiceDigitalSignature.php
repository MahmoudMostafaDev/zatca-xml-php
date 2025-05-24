<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class InvoiceDigitalSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(7, $value);
    }
}
