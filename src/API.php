<?php

namespace  ZATCA;

use Exception;
use Mode;
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

            if ($http_code != 200)
                throw new Exception('Error issuing a compliance certificate.');

            $issued_certificate = base64_decode($response->binarySecurityToken);
            $response->binarySecurityToken = $issued_certificate;

            return $response;
        };

        $checkInvoiceCompliance = function (string $signed_invoice_string, string $invoice_hash, string $uuid) use ($auth_headers): stdClass {

            $headers = [
                'Accept-Version: ' . $this->version,
                'Accept-Language: en',
                'Content-Type: application/json',
            ];

            $curl = curl_init($this->sandbox_url . '/compliance/invoices');

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    'invoiceHash' => $invoice_hash,
                    'uuid' => $uuid,
                    'invoice' => base64_encode($signed_invoice_string),
                ]),
                CURLOPT_HTTPHEADER => [...$headers, ...$auth_headers],
            ));

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            Logger::debug($response, ["http_code: {$http_code} /compliance/invoices"]);

            $response = json_decode($response);

            if ($http_code != 200) {
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

        if ($http_code != 200)
            throw new Exception('Error in /production/csids.');

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

    public function reporting(string $invoice_body, string $pro_certificate, string $pro_secret, int $clearance_status = 0)
    {
        $headers = [
            'Accept-Version: ' . $this->version,
            'accept-language: en',
            "Clearance-Status: {$clearance_status}",
            'Content-Type: application/json',
        ];

        $auth_headers = $this->getAuthHeaders($pro_certificate, $pro_secret);

        $curl = curl_init($this->sandbox_url . '/invoices/reporting/single');

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $invoice_body,
            CURLOPT_HTTPHEADER => [...$headers, ...$auth_headers],
        ]);

        $response = curl_exec($curl);

        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::debug($response, ["http_code: {$http_code} /invoices/reporting/single"]);

        $response = json_decode($response);

        if ($http_code != 200)
            throw new Exception('Error in /invoices/reporting/single.');

        return $response;
    }
}
