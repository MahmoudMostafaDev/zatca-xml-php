ZATCA XML
==

ZATCA stands for the **Zakat, Tax and Customs Authority**, which is a government agency in Saudi Arabia responsible for overseeing zakat, taxes, and customs duties. It plays a crucial role in the country's financial regulations and digital transformation initiatives, including the implementation of e-invoicing.

### Overview of ZATCA

- **Full Name**: Zakat, Tax and Customs Authority
- **Established**: June 14, 1951
- **Jurisdiction**: Government of Saudi Arabia
- **Headquarters**: Riyadh
- **Governor**: Suhail Abnami
- **Parent Department**: Ministry of Finance

### Key Functions
- **Tax Collection**: Responsible for the assessment and collection of various taxes, including Value Added Tax (VAT), corporate income tax, and excise tax.
- **Zakat Management**: Oversees the collection and distribution of zakat, an obligatory form of almsgiving in Islam.
- **Customs Duties**: Manages customs regulations and duties for imports and exports.

### Digital Initiatives
- **Fatoora Platform**: An electronic invoicing system launched on August 24, 2021, aimed at streamlining the invoicing process and enhancing compliance with tax regulations.
- **E-Services**: Provides a range of online services for tax registration, filing returns, and managing customs declarations.

### Strategic Goals
- **Support Vision 2030**: Aims to enhance compliance, streamline processes, and promote trade and economic growth in alignment with Saudi Arabia's Vision 2030.
- **Transparency and Efficiency**: Focuses on simplifying tax regulations and fostering transparency in tax obligations.

### Services Offered
- **Zakat Services**: Registration, payment, and certification of zakat.
- **Customs Services**: Customs declarations, duties inquiries, and import/export registrations.
- **Tax Services**: VAT registration, corporate income tax management, and excise tax services.

### Benefits of Using ZATCA Portal
- **Centralized Access**: A single platform for all tax-related services.
- **User-Friendly Interface**: Designed for ease of use for both individuals and businesses.
- **International Accessibility**: Available in multiple languages and accessible from anywhere.

# Installation

```
php sample.php
```

# Usage

View full example at <a href="/sample.php">examples</a>

```php

$egs = new EGS($egs_unit, Mode::Dev);

// New Keys & CSR for the EGS
list($private_key, $csr) = $egs->generateNewKeysAndCSR('Jabbar Nasser Al-Bishi Co. W.L.L');

// Issue a new compliance cert for the EGS
list($request_id, $binary_security_token, $secret) = $egs->issueComplianceCertificate('272826', $csr);

// build invoice
list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice01_388($invoice, $egs_unit, $binary_security_token, $private_key);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_388($invoice, $egs_unit, $binary_security_token, $private_key);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;
invoice_write($invoice_string, 'invoice');

list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice01_381($invoice, $egs_unit, $binary_security_token, $private_key);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_381($invoice, $egs_unit, $binary_security_token, $private_key);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice01_383($invoice, $egs_unit, $binary_security_token, $private_key);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

list($invoice_string, $invoice_hash, $qr) = $egs->buildInvoice02_383($invoice, $egs_unit, $binary_security_token, $private_key);
$egs->checkInvoiceCompliance($invoice_string, $invoice_hash, $binary_security_token, $secret) . PHP_EOL;

// Issue production certificate
list($pro_request_id, $pro_binary_security_token, $pro_secret) = $egs->issueProductionCertificate($binary_security_token, $secret, $request_id);
// var_dump($egs->productionCSIDRenewal($csr, '123456'));

print_r($egs->reportInvoice($invoice_string, $invoice_hash, $pro_binary_security_token, $pro_secret));
```

# Implementation

- General implementation (<a href="/docs/20220624_ZATCA_Electronic_Invoice_XML_Implementation_Standard_vF.pdf">More
  details</a>)
  - KSA Rules & Business
  - UBL 2.1 Spec
  - ISO EN16931
  - UN/CEFACT Code List 1001
  - ISO 3166
  - ISO 4217:2015
  - UN/CEFACT Code List 5305, D.16B
- Security standards (<a href="/docs/20220624_ZATCA_Electronic_Invoice_Security_Features_Implementation_Standards.pdf">
  More details</a>)
  - NCA National Cryptographic Standards (NCS - 1 : 2020)
  - NCDC Digital Signing Policy (Version 1.1: 2020)
  - ETSI EN 319 102-1
  - ETSI EN 319 132-1
  - ETSI EN 319 142-1
  - W3C XML-Signature Syntax and Processing
  - ETSI EN 319 122-1
  - IETF RFC 5035 (2007)
  - RFC 5280
  - ISO 32000-1
  - IETF RFC 5652 (2009)
  - RFP6749
  - NIST SP 56A

# Notice of Non-Affiliation and Disclaimer

`zatca-xml-php` is not affiliated, associated, authorized, endorsed by, or in any way officially connected with ZATCA (
Zakat, Tax and Customs Authority), or any of its subsidiaries or its affiliates. The official ZATCA website can be found
at https://zatca.gov.sa.

# Contribution

All contributions are appreciated.

## Roadmap

- CSIDs renewal, revoking.
- Populating templates using a template engine instead of `replace`
- Getting ZATCA to hopefully minify the XMLs before hashing ?

I'm not planning on supporting `Tax Invoices` (Not simplified ones). If any one wants to tackle that part.
