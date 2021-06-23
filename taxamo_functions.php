<?php

define('TAXAMO_URL', 'https://seller-transaction-api.sandbox.marketplace.taxamo.com');
define('TAXAMO_SELLER_TOKEN', '--- Your Seller Token ---');
define('TAXAMO_SHIP_FROM_COUNTRY', 'GB');
define('TAXAMO_COMM_CODES', [
  'card' => '49090000',
  'postcard' => '49090000',
  'pen' => '96081010',
  'coaster' => '39241000',
  'magnet' => '85051910',
  'wallet' => '39262000',
  'keyring' => '39069090',
  'wrap' => '48119000',
  'badge' => '83089000',
  'mug' => '69120023',
]);


//-- Output formatted text
function echoDebug($data) {
  echo "\n\n<!-- Begin: echoDebug -->\n<pre class=\"echodebug\">\n";
  print_r($data);
  echo "\n</pre>\n<!-- End: echoDebug -->\n\n";
} // echoDebug


// Return a v4 universally unique identifier
function taxamoUUID() {
  $data = random_bytes(16);
  assert(strlen($data) == 16);

  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
} // taxamoUUID()


// Return commodity code - update to suit your own application
function taxamoCommCode($model) {
  switch (strtoupper(substr($model, 0, 3))) {
    case 'PEN':
      return TAXAMO_COMM_CODES['pen'];
    case 'DMT':
      return TAXAMO_COMM_CODES['coaster'];
    case 'DMG':
      return TAXAMO_COMM_CODES['magnet'];
    case 'DMM':
      return TAXAMO_COMM_CODES['mug'];
    case 'DMD':
      return TAXAMO_COMM_CODES['badge'];
    case 'DMK':
      return TAXAMO_COMM_CODES['keyring'];
    case 'DMO':
      return TAXAMO_COMM_CODES['wallet'];
    case 'DMW':
      return TAXAMO_COMM_CODES['wrap'];
    case 'DPO':
      return TAXAMO_COMM_CODES['postcard'];
    default:
      return TAXAMO_COMM_CODES['card'];
  }
} // taxamoCommCode()


// Create a Taxmo Transaction object
function taxamoTransaction($orderId = false, $delivery = array(), $products = array(), $shipping = false) {
  $iossData = new stdClass;
  $transaction = new stdClass();

  // Set order ID
  if ($orderId) $transaction->custom_id = strval($orderId);

  // Set the store currency
  $transaction->currency_code = 'GBP';

  // Set the buyers name if specified
  if (isset($delivery['name'])) {
    $transaction->buyer_name = $delivery['name'];
  } else if (isset($delivery['firstname']) && isset($delivery['lastname'])) {
    $transaction->buyer_name = $delivery['firstname'].' '.$delivery['lastname'];
  }

  // Set the stores address
  $shipFromAddress = new stdClass();
  $shipFromAddress->country_code = TAXAMO_SHIP_FROM_COUNTRY;

  // Set the customers address (country code needs to be 2 letter code)
  $shipToAddress = new stdClass();
  $shipToAddress->street_name = (isset($delivery['street_address']) ? $delivery['street_address'] : '');
  $shipToAddress->address_detail = (isset($delivery['suburb']) ? $delivery['suburb'] : '');
  $shipToAddress->city = (isset($delivery['city']) ? $delivery['city'] : '');
  $shipToAddress->postal_code = strval((isset($delivery['postcode']) ? $delivery['postcode'] : ''));
  $shipToAddress->country_code = $delivery['country'];

  // Product list
  //   Use 'amount' for VAT exlcusive price
  $lines = array();
  foreach ($products as $line) {
    $lines[] = [
      'product_class' => 'P',
      'quantity' => $line['qty'],
      'description' => $line['name'],
      'total_amount' => ($line['price'] * $line['qty']),
      // 'amount' => ($line['price'] * $line['qty']),
      'custom_id' => taxamoUUID(),
      'product_cn_code' => taxamoCommCode($line['model']),
      'ship_from_address' => $shipFromAddress,
    ];
  }

  // Add shipping cost
  if ($shipping) {
    $lines[] = [
      'product_class' => 'S',
      'quantity' => 1,
      'description' => 'Shipping',
      'total_amount' => $shipping,
      // 'amount' => $shipping,
      'custom_id' => taxamoUUID(),
      'ship_from_address' => $shipFromAddress,
    ];
  }

  $transaction->transaction_lines = $lines;
  $transaction->ship_to_address = $shipToAddress;
  $iossData->transaction = $transaction;

  return $iossData;
} // tamaxmoTransaction()


// Calculate tax liabilty
function taxamoCalculateTax($iossData, $debug = false) {
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => TAXAMO_URL."/api/v3/seller/tax/calculate",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($iossData),
    CURLOPT_HTTPHEADER => [
      "Accept: application/json",
      "Content-Type: application/json",
      "x-marketplace-seller-token: ".TAXAMO_SELLER_TOKEN,
    ],
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);

  $iossResult = new stdClass();
  $iossResult->tax = false;
  $iossResult->eu = false;
  $iossResult->deliver = true;

  curl_close($curl);
  if (!$err) {
    $json = json_decode($response);
    if (isset($json->errors) && count($json->errors)) {
      $iossResult->response = $json;
      return $iossResult;
    }
    if (isset($json->transaction->tax_liability_owner_codes)) {
      switch($json->transaction->tax_liability_owner_codes) {
        case 'Taxamo':
          $iossResult->eu = true;
          $iossResult->tax = $json->transaction->tax_amount;
          break;
        case 'buyer':
          $iossResult->eu = true;
          $iossResult->deliver = false;
          break;
      }
    }
  }

  if ($debug) $iossResult->response = $json;
  return $iossResult;
} // taxamoCalculateTax()


//-- Confirm the taxamo transaction (will result in payment charge on production system)
function taxamoConfirmTransaction($txnKey, $debug = false) {
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => TAXAMO_URL."/api/v3/seller/transactions/".$txnKey."/confirm",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => [
      "Accept: application/json",
      "Content-Type: application/json",
      "x-marketplace-seller-token: ".TAXAMO_SELLER_TOKEN,
    ],
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);
  if (!$err) {
    $json = json_decode($response);

    if (isset($json->errors) && count($json->errors)) return ($debug ? $json : false);
    return true;
  }

  return false;
} // taxamoConfirmTransaction()


// Store the transaction in Taxamo's system
function taxamoStoreTransaction($iossData, $debug = false) {
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => TAXAMO_URL."/api/v3/seller/transactions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($iossData),
    CURLOPT_HTTPHEADER => [
      "Accept: application/json",
      "Content-Type: application/json",
      "x-marketplace-seller-token: ".TAXAMO_SELLER_TOKEN,
    ],
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);
  if (!$err) {
    $json = json_decode($response);

    if (isset($json->errors) && count($json->errors)) return ($debug ? $json : false);
    if (isset($json->transaction->key)) return ($debug ? $json : $json->transaction->key);
  }

  return false;
} // taxamoStoreTransaction


//-- Save the taxano transaction data / number to your database
function taxamoSaveTransaction($iossData, $debug = false) {
  $iossResult = taxamoStoreTransaction($iossData, $debug);

  $txn = ($debug ? $iossResult->transaction->key : $iossResult);
  if (taxamoConfirmTransaction($txn, $debug)) {
    // Add database update here
    return ($debug ? $txn : $iossResult);
  }

  return false;
} // taxamoSaveTransaction()

