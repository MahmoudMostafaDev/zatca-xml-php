<?php

use ZATCA\EGS;
use GuzzleHttp\Client;

const ROOT_PATH = __DIR__;

enum Mode
{
    case Dev;
    case Sim;
    case Pro;
}

const STORAGE_PATH = ROOT_PATH . '/storage';
require_once __DIR__ . '/vendor/autoload.php';
require ROOT_PATH . '/src/EGS.php';
require ROOT_PATH . '/src/InvoiceBuilder.php';
require ROOT_PATH . '/src/API.php';
require ROOT_PATH . '/src/Logger.php';
require ROOT_PATH . '/src/helpers/helper.php';

load_helpers('qr', 'invoice');

$line_item = [
    'id' => '1',
    'name' => 'قلم رصاص',
    'quantity' => 2,
    'tax_exclusive_price' => 60,
    'VAT_percent' => 0.15,
    'discount' =>
    ['percentage' => 0.20, 'reason' => 'A discount'],

];
$line_item2 = [
    'id' => '1',
    'name' => 'قلم رصاص',
    'quantity' => 1,
    'tax_exclusive_price' => 30,
    'VAT_percent' => 0.15,
    'other_taxes' => [
        //['percent_amount' => 0.5]
    ],
    'discount' =>
    ['percentage' => 0.15, 'reason' => 'A discount'],

];
$line_item3 = [
    'id' => '1',
    'name' => 'قلم رصاص',
    'quantity' => 1,
    'tax_exclusive_price' => 30,
    'VAT_percent' => 0.15,
    'other_taxes' => [
        //['percent_amount' => 0.5]
    ],
    'discount' =>
    ['percentage' => 0.15, 'reason' => 'A discount'],

];

$egs_unit = [
    'egs_serial_number' => EGS::uuid(), // will be defined in the microservice
    'custom_id' => 'EGS1-886431145', // CSR common name 
    'model' => 'IOS', // CSR model
    'CRN_number' => 4031006635, // سجل التجاري from Application
    'VAT_name' => 'Jabbar Nasser Al-Bishi Co. W.L.L', // Organization.name from Application 
    'VAT_number' => 399999999900003, // 310048293400003 399999999900003 // Organization.vatId from Application
    'location' => [ // From application
        'city' => 'Khobar',
        'city_subdivision' => 'West',
        'street' => 'الامير سلطان | Prince Sultan',
        'plot_identification' => '0000',
        'building' => '2322',
        'postal_zone' => '23333',
    ],
    'branch_name' => 'My Branch Name', // Branch.name from Application
    'branch_industry' => 'Thobe Tailoring',  // Constant Thobe Tailoring
    'cancelation' => [ // FOR DEBIT/CREDIT Task
        'cancelation_type' => 'INVOICE',
        'canceled_invoice_number' => 'SME00002',
    ],
    'AccountingCustomerParty' => [
        '__registration_name' => 'Wesam Alzahir', // Customer.name from Application
    ],
];

$invoice = [
    "uuid" => EGS::uuid(),
    'invoice_counter_number' => 23,
    'issue_date' => '2025-05-24',
    'issue_time' => '01:21:28',
    "delivery_date" => '2025-05-25',
    'previous_invoice_hash' => 'NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==', // AdditionalDocumentReference/PIH
    'line_items' => [
        $line_item,
        $line_item2,
        $line_item3
        // $line_item,
    ],
    'billingReferences' => [
        'id' => 'SME00098'
    ],
    'paymentMeans' => [
        'code' => '42',
        'note' => 'Returned items'
    ],
];

$egs = new EGS($egs_unit, Mode::Dev);

// New Keys & CSR for the EGS
list($private_key, $csr) = $egs->generateNewKeysAndCSR('Jabbar Nasser Al-Bishi Co. W.L.L');
// // Issue a new compliance cert for the EGS
list($request_id, $binary_security_token, $secret) = $egs->issueComplianceCertificate('272826', $csr);
// build invoice

echo 'one ' . $binary_security_token . PHP_EOL;
echo "\n";
echo 'two ' . $secret . PHP_EOL;
echo 'private_key ' . $secret . PHP_EOL;

list($request_id2, $binary_security_token2, $secret2) = $egs->issueProductionCertificate($binary_security_token, $secret, $request_id);


list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_388($invoice, $egs_unit, $binary_security_token, $private_key, $secret);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret, $invoice["uuid"]) . PHP_EOL;





// $res = $egs->reportInvoice(invoice_string: $invoice_string, $invoice_hash, $invoice["uuid"], $binary_security_token, $secret);



$client = new Client();

$response = $client->post('https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/invoices/reporting/single', [
    "http_errors" => false,
    'headers' => [
        'accept'          => 'application/json',
        'Accept-Language' => 'en',
        'Accept-Version'  => 'V2',
        'Authorization'   => 'Basic ' . base64_encode(base64_encode($binary_security_token) . ":" . $secret),
        'Content-Type'    => 'application/json'
    ],
    'json' => [
        'invoiceHash' => $invoice_hash,
        'uuid' => $invoice['uuid'],
        'invoice' => base64_encode($invoice_string)
    ]
]);



$handle = fopen('invoicee.xml', 'w');
fwrite($handle, $invoice_string);
fclose($handle);
