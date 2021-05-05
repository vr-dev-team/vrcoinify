<?php

define('vrcoinify_plugin_name', 'VR Coinify WHMCS');
define('vrcoinify_plugin_version', '0.1');
define('vrcoinify_error_not_available', 'Coinify Gateway is not available right now. Please choose another payment gateway or contact support.');
define('vrcoinify_error_amount_too_low', 'The amount to pay is too small for Coinify. Please choose another payment gateway.');

/** @return array */
function vrcoinify_config()
{
    return [
        'FriendlyName' => ['Type' => 'System', 'Value' => 'Coinify Gateway'],
        'api' => ['FriendlyName' => 'API Key', 'Type' => 'text', 'Size' => '40'],
        'secret' => ['FriendlyName' => 'API Secret', 'Type' => 'text', 'Size' => '40'],
        'ipn' => ['FriendlyName' => 'IPN Secret', 'Type' => 'text', 'Size' => '40'],
    ];
}

/** @return string */
function renderError($message)
{
    return "<p style='color: #ea316f;'>$message</p>";
}

/**
 * @param $params
 * @return string
 */
function vrcoinify_link($params)
{
    if (class_exists('CoinifyAPI') === false) {
        require_once('vrcoinify/CoinifyAPI.php');
    }

    $api = new CoinifyAPI($params['api'], $params['secret']);

    $systemUrl = rtrim($params['systemurl'], '/');

    $result = $api->invoiceCreate(
        $params['amount'],
        $params['currency'],
        vrcoinify_plugin_name,
        vrcoinify_plugin_version,
        $params['description'],
        ['invoiceid' => $params['invoiceid']],
        $systemUrl . '/modules/gateways/callback/vrcoinify.php',
        null,
        $systemUrl . '/viewinvoice.php?id=' . $params['invoiceid']
    );

    if (empty($result)) {
        logModuleCall('vrcoinify', 'invoiceCreation', $params, $result, null, null);
        return renderError(vrcoinify_error_not_available);
    }

    $error = $result['error'] ?? null;

    if ($error) {
        $code = $error['code'] ?? null;
        logModuleCall('vrcoinify', 'invoiceCreation', $params, $result, null, null);

        if ($code && 'amount_too_low' === $code) {
            return renderError(vrcoinify_error_amount_too_low);
        }

        return renderError(vrcoinify_error_not_available);
    }

    return <<<EOT
<form id="myForm" method="GET" action="{$result['data']['payment_url']}">
    <input type="submit" value="Pay Now" />
</form>
EOT;
}
