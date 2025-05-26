<?php

namespace  ZATCA;

use GuzzleHttp\Client;
use Exception;
use ZATCA\Mode;
use stdClass;

class API
{
    private ?string $sandbox_url;
    private string $version = 'V2';

    public function __construct(Mode $is_production)
    {
        $this->sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal';

        if ($is_production === Mode::Pro)
            $this->sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core';

        if ($is_production === Mode::Sim)
            $this->sandbox_url = 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation';

        Logger::debug($this->sandbox_url, [__CLASS__ . '::' . __FUNCTION__]);
    }

    private function getAuthHeaders($certificate, $secret): array
    {
        Logger::debug($certificate, [__CLASS__ . '::' . __FUNCTION__]);
        Logger::debug($secret, [__CLASS__ . '::' . __FUNCTION__]);

        if ($certificate && $secret) {

            $certificate_stripped = $this->cleanUpCertificateString($certificate);
            $certificate_stripped = base64_encode($certificate_stripped);
            $basic = base64_encode($certificate_stripped . ':' . $secret);
            Logger::debug("Authorization: Basic $basic", [__CLASS__ . '::' . __FUNCTION__]);

            return [
                "Authorization: Basic $basic",
            ];
        }

        Logger::debug("[]", [__CLASS__ . '::' . __FUNCTION__]);

        return [];
    }

    public function compliance($certificate = NULL, $secret = NULL)
    {
        $auth_headers = $this->getAuthHeaders($certificate, $secret);

        $issueCertificate = function (string $csr, string $otp): stdClass {
            $headers = [
                'Accept-Version: ' . $this->version,
                'OTP: ' . $otp,
                'Content-Type: application/json'
            ];

            $curl = curl_init($this->sandbox_url . '/compliance');

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(['csr' => base64_encode($csr)]),
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            Logger::debug($response, ["http_code: {$http_code} /compliance"]);

            $response = json_decode($response);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error decoding JSON response: ' . json_last_error_msg() . '. HTTP Code: ' . $http_code);
            }
            $decoded_response = $response;

            if ($http_code != 200) {
                $error_message_to_throw = 'Error issuing a compliance certificate. HTTP Code: ' . $http_code;
                $error_details_log = [];
                $error_objects = [];
                if (isset($decoded_response->validationResults->errorMessages) && is_array($decoded_response->validationResults->errorMessages)) {
                    $error_objects = $decoded_response->validationResults->errorMessages;
                } elseif (isset($decoded_response->errorMessages) && is_array($decoded_response->errorMessages)) { // Check direct errorMessages
                    $error_objects = $decoded_response->errorMessages;
                }

                if (!empty($error_objects)) {
                    $error_message_to_throw .= ". API Errors: ";
                    $parsed_errors = [];
                    foreach ($error_objects as $error_obj) {
                        $category = $error_obj->category ?? ($error_obj->type ?? 'N/A');
                        $code = $error_obj->code ?? 'N/A';
                        $message = $error_obj->message ?? 'No message provided';

                        $parsed_errors[] = "Category: {$category}, Code: {$code}, Message: {$message}";
                        $error_details_log[] = ['category' => $category, 'code' => $code, 'message' => $message];
                    }
                    $error_message_to_throw .= implode('; ', $parsed_errors);
                    Logger::debug("Parsed API Errors from /compliance", $error_details_log);
                } elseif (!empty($response_body_string)) {
                    $error_message_to_throw .= '. Raw Response Body: ' . $response_body_string;
                }

                throw new Exception($error_message_to_throw);
            }

            $issued_certificate = base64_decode($response->binarySecurityToken);
            $response->binarySecurityToken = $issued_certificate;

            return $response;
        };

        $checkInvoiceCompliance = function (string $signed_invoice_string, string $invoice_hash, string $uuid, string $certificate, string $secret): stdClass {

            $client = new Client();

            $response = $client->post($this->sandbox_url . '/compliance/invoices', [
                "http_errors" => false,
                'headers' => [
                    'accept'          => 'application/json',
                    'Accept-Language' => 'en',
                    'Accept-Version'  => $this->version,
                    'Authorization'   => 'Basic ' . base64_encode(base64_encode($certificate) . ":" . $secret),
                    'Content-Type'    => 'application/json'
                ],
                'json' => [
                    'invoiceHash' => $invoice_hash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode($signed_invoice_string)
                ]
            ]);

            $http_code = $response->getStatusCode() . '';
            $response_string = $response->getBody()->getContents();
            Logger::debug($response_string, ["http_code: {$http_code} /compliance/invoices"]);

            $response = json_decode($response_string);

            if ($http_code != 200 && $http_code != 202 && $http_code != 201) {
                echo "\n " . $response_string . "\n";
                throw new Exception('Error in compliance check.');
            }
            return $response;
        };

        return [$issueCertificate, $checkInvoiceCompliance];
    }

    public static function cleanUpCertificateString(string $certificate): string
    {
        Logger::debug($certificate, [__CLASS__ . '::' . __FUNCTION__]);

        $certificate = str_replace('-----BEGIN CERTIFICATE-----', '', $certificate);
        $certificate = str_replace('-----BEGIN CERTIFICATE REQUEST-----', '', $certificate);
        $certificate = str_replace('-----END CERTIFICATE-----', '', $certificate);
        $certificate = str_replace('-----END CERTIFICATE REQUEST-----', '', $certificate);

        Logger::debug($certificate, [__CLASS__ . '::' . __FUNCTION__]);

        return trim($certificate);
    }

    public function productionCSIDs(string $certificate, string $secret, int $compliance_request_id)
    {
        $headers = [
            'Accept-Version: ' . $this->version,
            'Content-Type: application/json',
        ];

        $auth_headers = $this->getAuthHeaders($certificate, $secret);

        $curl = curl_init($this->sandbox_url . '/production/csids');

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'compliance_request_id' => $compliance_request_id
            ]),
            CURLOPT_HTTPHEADER => [...$headers, ...$auth_headers],
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::debug($response, ["http_code: {$http_code} /production/csids"]);

        $response = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON response: ' . json_last_error_msg() . '. HTTP Code: ' . $http_code);
        }
        $decoded_response = $response;

        if ($http_code != 200) {
            $error_message_to_throw = 'Error issuing a CSIDs Production . HTTP Code: ' . $http_code;
            $error_details_log = [];
            $error_objects = [];
            if (isset($decoded_response->validationResults->errorMessages) && is_array($decoded_response->validationResults->errorMessages)) {
                $error_objects = $decoded_response->validationResults->errorMessages;
            } elseif (isset($decoded_response->errorMessages) && is_array($decoded_response->errorMessages)) {
                $error_objects = $decoded_response->errorMessages;
            }

            if (!empty($error_objects)) {
                $error_message_to_throw .= ". API Errors: ";
                $parsed_errors = [];
                foreach ($error_objects as $error_obj) {
                    //here 
                    $category = $error_obj->category ?? ($error_obj->type ?? 'N/A');
                    $code = $error_obj->code ?? 'N/A';
                    $message = $error_obj->message ?? 'No message provided';

                    $parsed_errors[] = "Category: {$category}, Code: {$code}, Message: {$message}";
                    $error_details_log[] = ['category' => $category, 'code' => $code, 'message' => $message];
                }
                $error_message_to_throw .= implode('; ', $parsed_errors);
                Logger::debug("Parsed API Errors from /production/csids ", $error_details_log);
            } elseif (!empty($response_body_string)) {
                $error_message_to_throw .= '. Raw Response Body: ' . $response_body_string;
            }

            throw new Exception($error_message_to_throw);
        }

        $issued_certificate = base64_decode($response->binarySecurityToken);
        $response->binarySecurityToken = "-----BEGIN CERTIFICATE-----\n{$issued_certificate}\n-----END CERTIFICATE-----";

        return $response;
    }

    public function productionCSIDsRenew(string $csr, string $otp)
    {
        $headers = [
            'OTP: ' . $otp,
            'Accept-Version: ' . $this->version,
            'accept-language: en',
            'Content-Type: application/json',
        ];

        $curl = curl_init($this->sandbox_url . '/production/csids');

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode([
                'csr' => $this->cleanUpCertificateString($csr),
            ]),
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::debug($response, ["http_code: {$http_code} /production/csids"]);

        $response = json_decode($response);

        if ($http_code != 200)
            throw new Exception('Error in /production/csids.');

        return $response;
    }

    public function reporting(string $invoiceHash, string $uuid, string $invoice, string $pro_certificate, string $pro_secret, int $clearance_status = 0)
    {

        $client = new Client();

        $response = $client->post($this->sandbox_url . '/invoices/reporting/single', [
            'http_errors' => false,
            'headers' => [
                'accept'           => 'application/json',
                'accept-language'  => 'en',
                'Clearance-Status' => $clearance_status,
                'Accept-Version'   => $this->version,
                'Authorization'    => 'Basic ' . base64_encode(base64_encode($pro_certificate) . ":" . $pro_secret),
                'Content-Type'     => 'application/json'
            ],
            'json' => [
                'invoiceHash' => $invoiceHash,
                'uuid' => $uuid,
                'invoice' => $invoice
            ]
        ]);

        $http_code = $response->getStatusCode();
        Logger::debug($response->getBody()->getContents(), ["http_code: {$http_code} /invoices/reporting/single"]);
        if ($http_code != 200 && $http_code != 202 && $http_code != 201) {
            echo $response->getBody()->getContents();
            throw new Exception('Error in /invoices/reporting/single.');
        }

        $response = json_decode($response->getBody());
        return $response;
    }
}
