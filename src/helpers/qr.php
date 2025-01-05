<?php

function qr_get_fatoora_invoice(string $invoice_path): ?string
{
    $output = shell_exec("fatoora -qr -invoice $invoice_path");

    $qrCodeStartPos = strpos($output, '*** QR code = ');
    if ($qrCodeStartPos !== false) {
        // Extract the QR code string
        $qrCodeStartPos += strlen('*** QR code = ');
        $qrCodeEndPos = strpos($output, "\n", $qrCodeStartPos); // Assuming the QR code is terminated by a newline character
        if ($qrCodeEndPos !== false)
            $qrCode = substr($output, $qrCodeStartPos, $qrCodeEndPos - $qrCodeStartPos);
    }

    return $qrCode ?? null;
}