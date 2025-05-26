<?php

namespace  ZATCA\sign\Tags;

use ZATCA\sign\Tag;

class CertificateSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(9, $value);
    }
}
