<?php

use ZATCA\EGS;

const ROOT_PATH = __DIR__;

enum Mode
{
    case Dev;
    case Sim;
    case Pro;
}

const STORAGE_PATH = ROOT_PATH . '/storage';

require ROOT_PATH . '/src/EGS.php';
require ROOT_PATH . '/src/InvoiceBuilder.php';
require ROOT_PATH . '/src/API.php';
require ROOT_PATH . '/src/Logger.php';
require ROOT_PATH . '/src/helpers/helper.php';

load_helpers('qr', 'invoice');

$line_item = [
    'id' => '1',
    'name' => 'قلم رصاص',
    'quantity' => 1,
    'tax_exclusive_price' => 10,
    'VAT_percent' => 0.15,
    'other_taxes' => [
        //['percent_amount' => 0.5]
    ],
    'discounts' => [
        ['amount' => 0, 'reason' => 'A discount'],
    ],
];

$egs_unit = [
    'uuid' => EGS::uuid(),
    'custom_id' => 'EGS1-886431145',
    'model' => 'IOS',
    'CRN_number' => 4031006635,
    'VAT_name' => 'Jabbar Nasser Al-Bishi Co. W.L.L',
    'VAT_number' => 399999999900003, // 310048293400003 399999999900003
    'location' => [
        'city' => 'Khobar',
        'city_subdivision' => 'West',
        'street' => 'الامير سلطان | Prince Sultan',
        'plot_identification' => '0000',
        'building' => '2322',
        'postal_zone' => '23333',
    ],
    'branch_name' => 'My Branch Name',
    'branch_industry' => 'Food',
    'cancelation' => [
        'cancelation_type' => 'INVOICE',
        'canceled_invoice_number' => 'SME00002',
    ],
    'AccountingCustomerParty' => [
        '__id' => 1010010000,
        '__street_name' => 'الامير سلطان | Prince Sultan',
        '__building_number' => '2322',
        '__plotIdentification' => '0000',
        '__city_subdivision_name' => 'West',
        '__city_name' => 'Khobar',
        '__postal_zone' => '23333',
        '__company_id' => 301121965500003,
        '__tax_scheme_id' => 'VAT',
        '__registration_name' => 'Wesam Alzahir',
    ],
];

$invoice = [
    'invoice_counter_number' => 23,
    'invoice_serial_number' => 'SME00023',
    'issue_date' => '2022-09-07',
    'issue_time' => '12:21:28',
    'previous_invoice_hash' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==', // AdditionalDocumentReference/PIH
    'line_items' => [
        $line_item,
    ],
];

$egs = new EGS($egs_unit, Mode::Dev);

// New Keys & CSR for the EGS
list($private_key, $csr) = $egs->generateNewKeysAndCSR('Jabbar Nasser Al-Bishi Co. W.L.L');
$binary_security_token = "-----BEGIN CERTIFICATE-----MIICPTCCAeOgAwIBAgIGAYzzgEhNMAoGCCqGSM49BAMCMBUxEzARBgNVBAMMCmVJbnZvaWNpbmcwHhcNMjQwMTEwMTMxMTU0WhcNMjkwMTA5MjEwMDAwWjB1MQswCQYDVQQGEwJTQTEWMBQGA1UECwwNUml5YWRoIEJyYW5jaDEmMCQGA1UECgwdTWF4aW11bSBTcGVlZCBUZWNoIFN1cHBseSBMVEQxJjAkBgNVBAMMHVRTVC04ODY0MzExNDUtMzk5OTk5OTk5OTAwMDAzMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEoWCKa0Sa9FIErTOv0uAkC1VIKXxU9nPpx2vlf4yhMejy8c02XJblDq7tPydo8mq0ahOMmNo8gwni7Xt1KT9UeKOBwTCBvjAMBgNVHRMBAf8EAjAAMIGtBgNVHREEgaUwgaKkgZ8wgZwxOzA5BgNVBAQMMjEtVFNUfDItVFNUfDMtZWQyMmYxZDgtZTZhMi0xMTE4LTliNTgtZDlhOGYxMWU0NDVmMR8wHQYKCZImiZPyLGQBAQwPMzk5OTk5OTk5OTAwMDAzMQ0wCwYDVQQMDAQxMTAwMREwDwYDVQQaDAhSUlJEMjkyOTEaMBgGA1UEDwwRU3VwcGx5IGFjdGl2aXRpZXMwCgYIKoZIzj0EAwIDSAAwRQIhAIF8jIcxzvCyqUDTp5Omv72UpxPALmoRyt9DY24jWmBQAiA0baZ6Yrpp5yJ4ahoooW3+Oa8kkb31evAoHdvgD8063w==-----END CERTIFICATE-----";
// // Issue a new compliance cert for the EGS
// list($request_id, $binary_security_token, $secret) = $egs->issueComplianceCertificate('272826', $csr);

// build invoice
list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice01_388($invoice, $egs_unit, $binary_security_token, $private_key);
// $egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

// list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_388($invoice, $egs_unit, $binary_security_token, $private_key);
// $egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;
// invoice_write($invoice_string, 'invoice');

// list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice01_381($invoice, $egs_unit, $binary_security_token, $private_key);
// $egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

// list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_381($invoice, $egs_unit, $binary_security_token, $private_key);
// $egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

// list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice01_383($invoice, $egs_unit, $binary_security_token, $private_key);
// $egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

// list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_383($invoice, $egs_unit, $binary_security_token, $private_key);
// $egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

// // Issue production certificate
// list($pro_request_id, $pro_binary_security_token, $pro_secret) = $egs->issueProductionCertificate($binary_security_token, $secret, $request_id);
// // var_dump($egs->productionCSIDRenewal($csr, '123456'));

// print_r($egs->reportInvoice($invoice_string, $invoice_hash, $pro_binary_security_token, $pro_secret));

// echo PHP_EOL;
 
$handle = fopen('invoice.xml', 'w');
fwrite($handle, $invoice_string);
fclose($handle);

printf($invoice_string);