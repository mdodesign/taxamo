<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DEBUG_MODE', true);

require_once('taxamo_functions.php');

$orderId = '555-'.rand(1000, 9999);
$shipping = '5.00';

$delivery = [
  'name' => 'Eric Wimp',
  'street_address' => '29 Acacia Road',
  'city' => 'Nuttytown',
  'country' => 'BE',
];

$products = [
  [
    'qty' => 1,
    'name' => 'Card 1',
    'price' => 2.50,
    'model' => 'DMA 01',
  ], [
    'qty' => 3,
    'name' => 'Card 2',
    'price' => 2.50,
    'model' => 'DMZ 254',
  ], [
    'qty' => 1,
    'name' => 'Mug 1',
    'price' => 8.75,
    'model' => 'DMM 06',
  ], [
    'qty' => 2,
    'name' => 'Pen',
    'price' => 2.75,
    'model' => 'PEN 23',
  ],
];


echo '<h2>Transaction</h2>';
$iossData = taxamoTransaction($orderId, $delivery, $products, $shipping);
echoDebug($iossData);


echo '<h2>Tax Calculation</h2>';
$iossTax = taxamoCalculateTax($iossData, DEBUG_MODE);
echoDebug($iossTax);

echo '<h2>Save Transaction</h2>';
$iossResult = taxamoSaveTransaction($iossData, DEBUG_MODE);
echoDebug($iossResult);
