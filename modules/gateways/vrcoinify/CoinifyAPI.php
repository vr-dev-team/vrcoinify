<?php
/**
 * Created by PhpStorm.
 * User: jesperborgstrup
 * Date: 16/03/15
 * Time: 10:53
 */

class CoinifyAPI {

    /**
     * Coinify API key. Get yours at https://www.coinify.com/merchant/api
     *
     * @var string
     */
    private $api_key;
    /**
     * Coinify API secret. Get yours at https://www.coinify.com/merchant/api
     *
     * @var string
     */
    private $api_secret;

    /**
     * Base URL to the Coinify API.
     *
     * @var string
     */
    private $api_base_url;

    /**
     * A human-readable error message for the last error that happened during a cURL call to the API.
     * This property is set whenever an API call returns false.
     *
     * @var string|null
     */
    public $last_curl_error = null;
    /**
     * A cURL error code for the last error that happened during a cURL call to the API.
     * This property is set whenever an API call returns false.
     *
     * @var string|null
     */
    public $last_curl_errno = null;

    /**
     * The base URL for the API without a trailing slash
     */
    const API_DEFAULT_BASE_URL = "https://api.coinify.com";

    /**
     * @param string $api_key Your Coinify API key
     * @param string $api_secret Your Coinify API secret
     * @param string|null $api_base_url Custom API base URL for testing. Set to null for default URL
     */
    public function __construct( $api_key, $api_secret, $api_base_url=null ) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->api_base_url = $api_base_url !== null ? $api_base_url : self::API_DEFAULT_BASE_URL;
    }

    /**
     * Returns an array of your Coinify invoices.
     *
     * @link https://www.coinify.com/docs/api/#list-all-invoices
     *
     * @param int $limit Maximum number of invoices to retrieve. Maximum is 200.
     * @param int $offset How many invoices to skip.
     * @param bool $include_expired If set to true, expired invoices are included in the result. Default is not to include expired invoices.
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains a list of your invoices.
     */
    public function invoicesList( $limit=null, $offset=null, $include_expired=null ) {
        $query_params = [];

        if ( $limit !== null ) $query_params['limit'] = $limit;
        if ( $offset !== null ) $query_params['offset'] = $offset;
        if ( $include_expired !== null ) $query_params['include_expired'] = $include_expired;

        return $this->callApiAuthenticated( '/v3/invoices', 'GET', [], $query_params );
    }

    /**
     * Create a new invoice.
     *
     * @link https://www.coinify.com/docs/api/#create-an-invoice
     *
     * @param float $amount Fiat price of the invoice
     * @param string $currency 3 letter ISO 4217 currency code denominating amount
     * @param string $plugin_name The name of the plugin used to call this API
     * @param string $plugin_version The version of the above plugin
     * @param string $description Your custom text for this invoice.
     * @param array $custom Your custom data for this invoice
     * @param string $callback_url A URL that Coinify calls when the invoice state changes.
     * @param string $callback_email An email address to send a mail to when the invoice state changes
     * @param string $return_url We redirect your customer to this URL to when the invoice has been paid
     * @param string $cancel_url We redirect your customer to this URL if they cancel the invoice (not yet in use)
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains the new invoice.
     */
    public function invoiceCreate( $amount, $currency, $plugin_name, $plugin_version,
                                   $description=null, $custom=null, $callback_url=null, $callback_email=null,
                                   $return_url=null, $cancel_url=null ) {
        $params = [
            'amount' => $amount,
            'currency' => $currency,
            'plugin_name' => $plugin_name,
            'plugin_version' => $plugin_version,
        ];

        if ( $description !== null ) $params['description'] = $description;
        if ( $custom !== null ) $params['custom'] = $custom;
        if ( $callback_url !== null ) $params['callback_url'] = $callback_url;
        if ( $callback_email !== null ) $params['callback_email'] = $callback_email;
        if ( $return_url !== null ) $params['return_url'] = $return_url;
        if ( $cancel_url !== null ) $params['cancel_url'] = $cancel_url;

        return $this->callApiAuthenticated( '/v3/invoices', 'POST', $params );
    }

    /**
     * Get a specific invoice
     *
     * @link https://www.coinify.com/docs/api/#get-a-specific-invoice
     *
     * @param $invoice_id
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains the requested invoice.
     */
    public function invoiceGet( $invoice_id ) {
        return $this->callApiAuthenticated( "/v3/invoices/{$invoice_id}" );
    }

    /**
     * Update the description and custom data of an invoice
     *
     * @link https://www.coinify.com/docs/api/#update-an-invoice
     *
     * @param int $invoice_id The ID of the invoice you want to update
     * @param string $description Your custom text for this invoice.
     * @param array $custom Your custom data for this invoice
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains the updated invoice.
     */
    public function invoiceUpdate( $invoice_id, $description=null, $custom=null ) {
        $params = [];

        if ( $description !== null ) $params['description'] = $description;
        if ( $custom !== null ) $params['custom'] = $custom;

        return $this->callApiAuthenticated( "/v3/invoices/{$invoice_id}", "PUT", $params );
    }

    /**
     * Returns an array of your Coinify buy orders
     *
     * @link https://www.coinify.com/docs/api/#list-all-buy-orders
     *
     * @param int $limit Maximum number of buy orders to retrieve. Maximum is 200.
     * @param int $offset How many buy orders to skip.
     * @param bool $include_cancelled If set to true, cancelled buy orders are included in the result. Default is not to include cancelled buy orders.
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains a list of your buy orders.
     */
    public function buyOrdersList( $limit = null, $offset = null, $include_cancelled = null ) {
        $query_params = [];

        if ( $limit !== null ) $query_params['limit'] = $limit;
        if ( $offset !== null ) $query_params['offset'] = $offset;
        if ( $include_cancelled !== null ) $query_params['include_cancelled'] = $include_cancelled;

        return $this->callApiAuthenticated( '/v3/buys', 'GET', [], $query_params );
    }

    /**
     * Create a new buy order
     *
     * @param float $amount Amount that you want to buy BTC for - denominated in currency
     * @param string $currency 3 letter ISO 4217 currency code denominating amount. Must be either BTC or your merchant account currency.
     * @param string $btc_address The bitcoin address to send the bitcoins to.
     * @param bool $instant_order Should this be an instant order or not?
     * @param string $callback_url A URL that Coinify calls when the buy order state changes.
     * @param string $callback_email An email address to send a mail to when the buy order state changes
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains the new buy order.
     */
    public function buyOrderCreate( $amount, $currency, $btc_address, $instant_order=null, $callback_url=null, $callback_email=null ) {
        $params = [
            'amount' => $amount,
            'currency' => $currency,
            'btc_address' => $btc_address,
        ];

        if ( $instant_order !== null ) $params['instant_order'] = boolval( $instant_order );
        if ( $callback_url !== null ) $params['callback_url'] = $callback_url;
        if ( $callback_email !== null ) $params['callback_email'] = $callback_email;

        return $this->callApiAuthenticated( "/v3/buys", "POST", $params );
    }

    /**
     * Confirm a buy order
     *
     * @link https://www.coinify.com/docs/api/#buy-order-confirm
     *
     * @param int $buy_order_id
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains the requested buy order.
     */
    public function buyOrderConfirm( $buy_order_id ) {
        return $this->callApiAuthenticated( "/v3/buys/{$buy_order_id}/actions/confirm", "PUT" );
    }

    /**
     * Get a specific buy order
     *
     * @link https://www.coinify.com/docs/api/#get-a-specific-buy-order
     *
     * @param int $buy_order_id
     * @return array A PHP array as described in https://www.coinify.com/docs/api/#response-format. If success,
     * then the 'data' value contains the requested buy order.
     */
    public function buyOrderGet( $buy_order_id ) {
        return $this->callApiAuthenticated( "/v3/buys/{$buy_order_id}" );
    }

    /**
     * Perform an authenticated API call, using the
     * API key and secret provided in the constructor.
     *
     * @param string $path The API path, WITH leading slash, e.g. '/v3/invoices'
     * @param string $method. The HTTP method to use.
     * @param array $params Associative array of parameters to the body of the API call. Ignored for GET calls.
     * @param array $query_params Associative array of parameters to put in the query string of the URL
     * @return array|false A PHP array as described in https://www.coinify.com/docs/api/#response-format,
     * or false if the HTTP call couldn't be performed correctly.
     * If false, use the $last_curl_error and $last_curl_errno properties to
     * get the error.
     */
    private function callApiAuthenticated( $path, $method='GET', $params=[], $query_params=[] ) {
        $url = $this->api_base_url . $path;

        if ( count( $query_params ) > 0 ) {
            $url .= '?' . http_build_query( $query_params );
        }

        $ch = curl_init( $url );
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ $this->generateAuthorizationHeader() ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $params ) );
        }

        $json_response = curl_exec( $ch );

        if ( $json_response === false ) {
            /*
             * If an error occurred, remember the error
             * and return false.
             */
            $this->last_curl_error = curl_error( $ch );
            $this->last_curl_errno = curl_errno( $ch );

            // Remember to close the cURL object
            curl_close( $ch );
            return false;
        }

        /*
         * No error, just decode the JSON response, and return it.
         */
        $response = json_decode( $json_response, true );

        // Remember to close the cURL object
        curl_close( $ch );

        return $response;
    }

    /**
     * Generate a nonce and a signature for an API call and wrap those in a HTTP header
     *
     * @return string A string with a full HTTP header like the following:
     * 'Authorization: Coinify apikey="<api_key>", nonce="<nonce>", signature="<signature>"'
     */
    private function generateAuthorizationHeader() {
        // Generate a nonce, based on the current time
        $mt = explode( ' ', microtime() );
        $nonce = $mt[1] . substr($mt[0], 2, 6 );

        $apikey = $this->api_key;

        // Concatenate the nonce and the API key
        $message = $nonce . $apikey;
        // Compute the signature and convert it to lowercase
        $signature = strtolower( hash_hmac('sha256', $message, $this->api_secret, false ) );

        // Construct the HTTP Authorization header.
        $auth_header = "Authorization: Coinify apikey=\"$apikey\", nonce=\"$nonce\", signature=\"$signature\"";

        return $auth_header;
    }

    /**
     * @return string|null
     */
    public function getLastCurlError()
    {
        return $this->last_curl_error;
    }

    /**
     * @return string|null
     */
    public function getLastCurlErrno()
    {
        return $this->last_curl_errno;
    }
}
