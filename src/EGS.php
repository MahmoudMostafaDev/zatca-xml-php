<?php

namespace  ZATCA;


use DOMDocument;
use Exception;
use ZATCA\Mode;
use ZATCA\sign\Helpers\Certificate;
use ZATCA\sign\InvoiceSigner;

class EGS
{
    private array $egs_info;
    private API $api;
    private Mode $production;
    private ?DOMDocument $invoice = null;
    private ?string $invoice_hash = null;
    private ?string $qr = null;
    private ?string $invoice_time;
    private ?InvoiceBuilder $invoiceBuilder;

    public function __construct(array $egs_info, Mode $mode)
    {
        $this->egs_info = $egs_info;
        $this->production = $mode;
        $this->api = new API($this->production);
        $this->invoice_time = date('Y-m-d\TH:i:s\Z');
    }

    public function generateNewKeysAndCSR(string $solution_name)
    {
        $private_key = $this->generateSecp256k1KeyPair();
        $csr = $this->generateCSR($solution_name, $private_key);

        return [$private_key, $csr];
    }

    private function generateSecp256k1KeyPair()
    {
        $exe = shell_exec('openssl ecparam -name secp256k1 -genkey');

        $exe = explode('-----BEGIN EC PRIVATE KEY-----', $exe);

        if (!isset($exe[1]))
            throw new Exception('Error no private key found in OpenSSL output.');

        $exe = trim($exe[1]);

        $private_key = "-----BEGIN EC PRIVATE KEY-----\n$exe";
        return trim($private_key);
    }

    private function generateCSR(string $solution_name, $private_key)
    {
        if (!$private_key)
            throw new Exception('EGS has no private key');

        if (!is_dir('/tmp/')) {
            mkdir('/tmp/', 0775);
        }

        $private_key_file_name =  '/tmp/' . self::uuid() . '.pem';
        $csr_config_file_name =  '/tmp/' . self::uuid() . '.cnf';

        $private_key_file = fopen($private_key_file_name, 'w');
        $csr_config_file = fopen($csr_config_file_name, 'w');

        fwrite($private_key_file, $private_key);
        fwrite($csr_config_file, $this->defaultCSRConfig($solution_name));

        $result = shell_exec("openssl req -new -sha256 -key $private_key_file_name -config $csr_config_file_name");
        $result = explode('-----BEGIN CERTIFICATE REQUEST-----', $result);
        $result = $result[1];

        $csr = "-----BEGIN CERTIFICATE REQUEST-----$result";

        unlink($private_key_file_name);
        unlink($csr_config_file_name);

        return $csr;
    }

    private function defaultCSRConfig(string $solution_name)
    {
        $config = [
            'egs_model' => $this->egs_info['model'],
            'egs_serial_number' => $this->egs_info['egs_serial_number'],
            'solution_name' => $solution_name,
            'vat_number' => $this->egs_info['VAT_number'],
            'branch_location' => $this->egs_info['location']['building'] . ' ' . $this->egs_info['location']['street'] . ', ' . $this->egs_info['location']['city'],
            'branch_industry' => $this->egs_info['branch_industry'],
            'branch_name' => $this->egs_info['branch_name'],
            'taxpayer_name' => $this->egs_info['VAT_name'],
            'taxpayer_provided_id' => $this->egs_info['custom_id'],
            'production' => $this->production
        ];

        $template_csr = require  'templates/csr_template.php';

        $template_csr = str_replace('SET_PRIVATE_KEY_PASS', ($config['private_key_pass'] ?? 'SET_PRIVATE_KEY_PASS'), $template_csr);
        $template_csr = str_replace('SET_PRODUCTION_VALUE', ($config['production'] ? 'ZATCA-Code-Signing' : 'TSTZATCA-Code-Signing'), $template_csr);
        $template_csr = str_replace('SET_EGS_SERIAL_NUMBER', "1-{$config['solution_name']}|2-{$config['egs_model']}|3-{$config['egs_serial_number']}", $template_csr);
        $template_csr = str_replace('SET_VAT_REGISTRATION_NUMBER', $config['vat_number'], $template_csr);
        $template_csr = str_replace('SET_BRANCH_LOCATION', $config['branch_location'], $template_csr);
        $template_csr = str_replace('SET_BRANCH_INDUSTRY', $config['branch_industry'], $template_csr);
        $template_csr = str_replace('SET_COMMON_NAME', $config['taxpayer_provided_id'], $template_csr);
        $template_csr = str_replace('SET_BRANCH_NAME', $config['branch_name'], $template_csr);
        $template_csr = str_replace('SET_TAXPAYER_NAME', $config['taxpayer_name'], $template_csr);

        return $template_csr;
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public function issueComplianceCertificate(string $otp, string $csr): array
    {
        if (!$csr || !$otp)
            throw new Exception('EGS needs to generate a CSR first.');

        list($issueCertificate, $checkInvoiceCompliance) = $this->api->compliance();
        $issued_data = $issueCertificate($csr, $otp);

        return [$issued_data->requestID, $issued_data->binarySecurityToken, $issued_data->secret];
    }

    public function buildInvoice01_388(array $invoiceInfo, array $egs_unit, mixed $certificate, mixed $private_key, string $secretKey)
    {
        return $this->buildInvoice($invoiceInfo, $egs_unit, $certificate, $private_key, '01_388', $secretKey);
    }

    public function buildInvoice02_388(array $invoiceInfo, array $egs_unit, mixed $certificate, mixed $private_key, string $secretKey)
    {
        return $this->buildInvoice($invoiceInfo, $egs_unit, $certificate, $private_key, '02_388', $secretKey);
    }

    public function buildInvoice01_383(array $invoiceInfo, array $egs_unit, mixed $certificate, mixed $private_key, string $secretKey)
    {
        return $this->buildInvoice($invoiceInfo, $egs_unit, $certificate, $private_key, '01_383', $secretKey);
    }

    public function buildInvoice02_383(array $invoiceInfo, array $egs_unit, mixed $certificate, mixed $private_key, string $secretKey)
    {
        return $this->buildInvoice($invoiceInfo, $egs_unit, $certificate, $private_key, '02_383', $secretKey);
    }

    public function buildInvoice01_381(array $invoiceInfo, array $egs_unit, mixed $certificate, mixed $private_key, string $secretKey)
    {
        return $this->buildInvoice($invoiceInfo, $egs_unit, $certificate, $private_key, '01_381', $secretKey);
    }

    public function buildInvoice02_381(array $invoiceInfo, array $egs_unit, mixed $certificate, mixed $private_key, string $secretKey)
    {
        return $this->buildInvoice($invoiceInfo, $egs_unit, $certificate, $private_key, '02_381', $secretKey);
    }

    private function buildInvoice(array $invoiceInfo, array $egs_unit, string $certificate, string $private_key, string $invoiceCode, string $secretKey): array
    {
        $invoiceBuilder = new InvoiceBuilder();

        $invoice_xml = $invoiceBuilder->invoice($invoiceInfo, $egs_unit, $invoiceCode);

        $InvoicSigned = InvoiceSigner::signInvoice($invoice_xml->saveXML(), new Certificate($certificate, $private_key, $secretKey));
        //         $invoice_hash = $invoiceBuilder->getInvoiceHash($invoice_xml);

        //         list($hash, $issuer, $serialNumber, $public_key, $signature)
        //             = $invoiceBuilder->getCertificateInfo($certificate);

        //         $digital_signature = $invoiceBuilder->createInvoiceDigitalSignature($invoice_hash, $private_key);

        // //        $qr = $invoiceBuilder->generateQR(
        // //            $invoice_xml,
        // //            $digital_signature,
        // //            $public_key,
        // //            $signature,
        // //            $this->invoice_time,
        // //            $invoice_hash
        // //        );

        //         $signed_properties_props = [
        //             'sign_timestamp' => $this->invoice_time,
        //             'certificate_hash' => $hash, // SignedSignatureProperties/SigningCertificate/CertDigest/<ds:DigestValue>SET_CERTIFICATE_HASH</ds:DigestValue>
        //             'certificate_issuer' => $issuer,
        //             'certificate_serial_number' => $serialNumber
        //         ];
        //         $ubl_signature_signed_properties_xml_string_for_signing = $invoiceBuilder->defaultUBLExtensionsSignedPropertiesForSigning($signed_properties_props);
        //         $ubl_signature_signed_properties_xml_string = $invoiceBuilder->defaultUBLExtensionsSignedProperties($signed_properties_props);

        //         $signed_properties_hash = base64_encode(openssl_digest($ubl_signature_signed_properties_xml_string_for_signing, 'sha256'));

        //         // UBL Extensions
        //         $ubl_signature_xml_string = $invoiceBuilder->defaultUBLExtensions(
        //             $invoice_hash, // <ds:DigestValue>SET_INVOICE_HASH</ds:DigestValue>
        //             $signed_properties_hash, // SignatureInformation/Signature/SignedInfo/Reference/<ds:DigestValue>SET_SIGNED_PROPERTIES_HASH</ds:DigestValue>
        //             $digital_signature,
        //             $certificate,
        //             $ubl_signature_signed_properties_xml_string
        //         );

        //         // Set signing elements
        //         $unsigned_invoice_str = $invoice_xml->saveXML();

        //         $unsigned_invoice_str = str_replace('SET_UBL_EXTENSIONS_STRING', $ubl_signature_xml_string, $unsigned_invoice_str);
        //         // $unsigned_invoice_str = str_replace('SET_QR_CODE_DATA', $qr, $unsigned_invoice_str);

        //         $signed_invoice = new DOMDocument();
        //         $signed_invoice->loadXML($unsigned_invoice_str);

        //         $invoice_string = $signed_invoice->saveXML();
        //         //$signed_invoice_string = $zatca_simplified_tax_invoice->signedPropertiesIndentationFix($signed_invoice_string);

        //         $tmp_xml_path = ROOT_PATH . '/tmp/invoice.xml';
        //         $invoice = fopen($tmp_xml_path, "w");
        //         fwrite($invoice, $invoice_string);
        //         fclose($invoice);
        //         $qr = $invoiceBuilder->generateQR(
        //             $invoice_xml,
        //             $digital_signature,
        //             $public_key,
        //             $signature,
        //             $this->invoice_time,
        //             $invoice_hash
        //         );
        //         unlink($tmp_xml_path);

        //         $invoice_string = str_replace('SET_QR_CODE_DATA', $qr, $invoice_string);

        return [$InvoicSigned->getInvoice(), $InvoicSigned->getHash(), $InvoicSigned->getQRCode(), $invoice_xml->saveXML()];
    }

    public function checkInvoiceCompliance(string $signed_invoice_string, string $invoice_hash, string $certificate, string $secret, $invoice_uuid): string
    {
        if (!$certificate || !$secret)
            throw new Exception('EGS is missing a certificate/private key/api secret to check the invoice compliance.');

        list($issueCertificate, $checkInvoiceCompliance) = $this->api->compliance();
        $issued_data = $checkInvoiceCompliance($signed_invoice_string, $invoice_hash, $invoice_uuid,  $certificate,  $secret);

        return json_encode($issued_data);
    }

    public function issueProductionCertificate(string $certificate, string $secret, int $request_id): array
    {
        if (!$certificate || !$secret)
            throw new Exception('EGS is missing a certificate/private key/api secret to check the invoice compliance.');

        $response = $this->api->productionCSIDs($certificate, $secret, $request_id);

        return [$response->requestID, $response->binarySecurityToken, $response->secret];
    }

    public function productionCSIDRenewal(string $csr, string $otp)
    {
        if (!$csr)
            throw new Exception('EGS is missing a certificate/private key/api secret to check the invoice compliance.');

        return $this->api->productionCSIDsRenew($csr, $otp);
    }

    // public function reportInvoice(string $invoice, string $invoice_hash, string $pro_certificate, string $pro_secret)
    // {
    //     if (!$invoice_hash || !$invoice)
    //         throw new Exception('invoice hash or invoice not null');

    //     shell_exec("fatoora -sign -qr -invoice storage/invoice.xml -signedinvoice storage/invoice_signed.xml");
    //     shell_exec("fatoora -invoice storage/invoice_signed.xml -invoiceRequest -apiRequest storage/invoice_signed.json");

    //     $invoice_body = file_get_contents('storage/invoice_signed.json');

    //     return $this->api->reporting($invoice_body, $pro_certificate, $pro_secret, 1);
    // }

    public function reportInvoice(string $invoice_string, $invoice_hash, $invoice_uuid, string $pro_certificate, string $pro_secret)
    {
        if (!$invoice_string || !$invoice_hash || !$invoice_uuid)
            throw new Exception('invoice hash or invoice or invoice uuid should not be null');


        return $this->api->reporting($invoice_hash, $invoice_uuid, base64_encode($invoice_string),  $pro_certificate, $pro_secret, 1);
    }
}
