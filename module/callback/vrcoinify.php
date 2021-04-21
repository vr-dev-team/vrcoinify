<?php

# Required File Includes
if (file_exists('../../../dbconnect.php')) {
    include '../../../dbconnect.php';
} else if (file_exists('../../../init.php')) {
    include '../../../init.php';
} else {
    exit('error');
}
include('../../../includes/functions.php');
include('../../../includes/gatewayfunctions.php');
include('../../../includes/invoicefunctions.php');
include('../coinify/CoinifyCallback.php');

$gatewaymodule = 'vrcoinify';

$gateway = getGatewayVariables($gatewaymodule);
if (!$gateway['type']) {
    die('Module is not activated'); // Checks gateway module is active before accepting callback
}

$callback = new CoinifyCallback($gateway['ipn']);

// Get the signature from the HTTP or email headers
$signature = $_SERVER['HTTP_X_COINIFY_CALLBACK_SIGNATURE'];

// Get the raw HTTP POST body (JSON object encoded as a string)
// Note: Substitute getBody() with a function call to retrieve the raw HTTP body.
// In "plain" PHP this can be done with file_get_contents('php://input')
$body = file_get_contents('php://input');
$arr = json_decode($body, true);

// Always reply with a HTTP 200 OK status code and an empty body, regardless of the result of validating the callback
header('HTTP/1.1 200 OK');

if (!$callback->validateCallback($body, $signature)) {
    // Invalid signature, disregard this callback
    logTransaction($gateway['name'], $body, 'Unsuccessful'); # Save to Gateway Log: name, data array, status
    return;
}

// Find invoice id from provided Coinify POST
$invoiceid = checkCbInvoiceID($arr['data']["custom"]["invoiceid"], $gateway["name"]); # Checks invoice ID is a valid invoice number or ends processing

// Get bitcoin address used for payment, as to be used for transaction id
$txid = $arr['data']['bitcoin']['address'];

// Blank amount will be the full amount of the invoice.
// If 'Convert to for processing' is set, the callback will be in the processing currency and this might
// not be the same as the invoice currency and we can only add an amount and not currency in addInvoicePayment().
// So if the invoice is in state 'complete' we assume it is fully paid.
$amount = '';
checkCbTransID($txid);

switch ($arr['data']['state']) {
    case 'complete':
        addInvoicePayment($invoiceid, $txid, $amount, 0, $gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
        logTransaction($gateway["name"], $body, 'Successful'); # Save to Gateway Log: name, data array, status
        break;
    case 'paid':
        logTransaction($gateway["name"], $body, 'We have received payments, but they are not yet confirmed enough');
        break;
    case 'expired':
        logTransaction($gateway["name"], $body, 'The transaction is expired, do not process!');
        break;
}
