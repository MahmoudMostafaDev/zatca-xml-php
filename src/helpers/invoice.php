<?php

function invoice_write(string $content, string $file_name = null)
{
    $invoice = fopen(STORAGE_PATH . '/' . $file_name . '.xml', "w");
    fwrite($invoice, $content);
    fclose($invoice);
}

function invoice_get_01_388(string $invoice_str, ?string $file_name = null)
{
    $signed_invoice = new DOMDocument();
    $signed_invoice->loadXML($invoice_str);

    // Get the InvoiceTypeCode element
    $invoice_type_code = $signed_invoice->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'InvoiceTypeCode')->item(0);

    // Set the new name attribute value
    $invoice_type_code->setAttribute('name', '0100000');

    // Set the new text content
    $invoice_type_code->nodeValue = '388';

    // Save the modified XML to a string
    $modified_invoice_str = $signed_invoice->saveXML();

    if ($file_name)
        invoice_write($file_name, $file_name . '_tax');

    return $modified_invoice_str;
}

function invoice_get_02_388(string $invoice_str, ?string $file_name = null)
{
    $signed_invoice = new DOMDocument();
    $signed_invoice->loadXML($invoice_str);

    // Get the InvoiceTypeCode element
    $invoice_type_code = $signed_invoice->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'InvoiceTypeCode')->item(0);

    // Set the new name attribute value
    $invoice_type_code->setAttribute('name', '0200000');

    // Set the new text content
    $invoice_type_code->nodeValue = '388';

    // Save the modified XML to a string
    $modified_invoice_str = $signed_invoice->saveXML();

    if ($file_name)
        invoice_write($file_name, $file_name . '_simplified');

    return $modified_invoice_str;
}

function invoice_get_01_383(string $invoice_str, ?string $file_name = null)
{
    $signed_invoice = new DOMDocument();
    $signed_invoice->loadXML($invoice_str);

    // Get the InvoiceTypeCode element
    $invoice_type_code = $signed_invoice->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'InvoiceTypeCode')->item(0);

    // Set the new name attribute value
    $invoice_type_code->setAttribute('name', '0100000');

    // Set the new text content
    $invoice_type_code->nodeValue = '383';

    // Save the modified XML to a string
    $modified_invoice_str = $signed_invoice->saveXML();

    if ($file_name)
        invoice_write($file_name, $file_name . '_tax_debit');

    return $modified_invoice_str;
}

function invoice_get_02_383(string $invoice_str, ?string $file_name = null)
{
    $signed_invoice = new DOMDocument();
    $signed_invoice->loadXML($invoice_str);

    // Get the InvoiceTypeCode element
    $invoice_type_code = $signed_invoice->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'InvoiceTypeCode')->item(0);

    // Set the new name attribute value
    $invoice_type_code->setAttribute('name', '0200000');

    // Set the new text content
    $invoice_type_code->nodeValue = '383';

    // Save the modified XML to a string
    $modified_invoice_str = $signed_invoice->saveXML();

    if ($file_name)
        invoice_write($file_name, $file_name . '_simplified_debit');

    return $modified_invoice_str;
}


function invoice_get_01_381(string $invoice_str, ?string $file_name = null)
{
    $signed_invoice = new DOMDocument();
    $signed_invoice->loadXML($invoice_str);

    // Get the InvoiceTypeCode element
    $invoice_type_code = $signed_invoice->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'InvoiceTypeCode')->item(0);

    // Set the new name attribute value
    $invoice_type_code->setAttribute('name', '0100000');

    // Set the new text content
    $invoice_type_code->nodeValue = '381';

    // Save the modified XML to a string
    $modified_invoice_str = $signed_invoice->saveXML();

    if ($file_name)
        invoice_write($file_name, $file_name . '_tax_credit');

    return $modified_invoice_str;
}

function invoice_get_02_381(string $invoice_str, ?string $file_name = null)
{
    $signed_invoice = new DOMDocument();
    $signed_invoice->loadXML($invoice_str);

    // Get the InvoiceTypeCode element
    $invoice_type_code = $signed_invoice->getElementsByTagNameNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'InvoiceTypeCode')->item(0);

    // Set the new name attribute value
    $invoice_type_code->setAttribute('name', '0200000');

    // Set the new text content
    $invoice_type_code->nodeValue = '381';

    // Save the modified XML to a string
    $modified_invoice_str = $signed_invoice->saveXML();

    if ($file_name)
        invoice_write($file_name, $file_name . '_simplified_credit');

    return $modified_invoice_str;
}
