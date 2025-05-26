<?php

function getFormattedOrderId(
    int $orderIdentifier,
    $saleDate
) {
    if (is_string($saleDate)) {
        $saleDateTime = new DateTime($saleDate);
    } elseif ($saleDate instanceof DateTimeInterface) {
        $saleDateTime = $saleDate;
    } else {
        throw new InvalidArgumentException("saleDate must be a string or DateTimeInterface object.");
    }

    $invPrefix = "INV-";
    $formattedDate = $saleDateTime->format("Ymd"); // YYYYMMDD format
    return "{$invPrefix}{$formattedDate}{$orderIdentifier}";
}
