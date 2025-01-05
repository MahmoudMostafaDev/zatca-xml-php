<?php

$tax_total = <<<XML
<cac:TaxTotal>
        <cbc:TaxAmount currencyID="SAR">__158.67</cbc:TaxAmount>__TaxSubtotal
    </cac:TaxTotal>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="SAR">___tax_amount</cbc:TaxAmount>
    </cac:TaxTotal>
XML;

$tax_sub_total = <<<XML

        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="SAR">__46.00</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="SAR">__6.89</cbc:TaxAmount>
            <cac:TaxCategory>
                <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5305">__S</cbc:ID>
                <cbc:Percent>__15.00</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
XML;

return [
    'tax_total' => $tax_total,
    'tax_sub_total' => $tax_sub_total,
];