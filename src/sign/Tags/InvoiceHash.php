<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class InvoiceHash extends Tag
{
    public function __construct($value)
    {
        parent::__construct(6, $value);
    }
}
