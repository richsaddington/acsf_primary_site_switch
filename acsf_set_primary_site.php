#!/usr/bin/env php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Example script for performing a backup and immediate restore of a site through the ACSF REST API.
// Two things are left up to the script user:
// - Including Guzzle, which is used by request();
//   e.g. by doing: 'composer init; composer require guzzlehttp/guzzle'
require 'vendor/autoload.php';

// - Populating $config:
$config_acqprod = [
  // URL of a subsection inside the SF REST API; must end with sites/.
  'url' => 'https://www.acqsupport.acsitefactory.com/api/v1/',
  'api_user' => '',
  'api_key' => '',
];

// - Populating $config:
$config_acqdev = [
  // URL of a subsection inside the SF REST API; must end with sites/.
  'url' => 'https://www.dev-acqsupport.acsitefactory.com/api/v1/',
  'api_user' => '',
  'api_key' => '',
];

// set the env config...
$config = $config_acqdev;

// Prod data
$collection_id = 2311; //6686

// All the sites we want in the collection...
$site_ids = array(2281,2316,2286);

// Populate with the site ID to switch to
$site_id = 2281; // stack 1 school1.acqsupport.acsitefactory.com
$site_id = 2316; // stack 2 school3.acqsupport.acsitefactory.com
//$site_id = 2286; // stack 1 school2.acqsupport.acsitefactory.com

// Dev data...
$collection_id = 6686; 

// All the sites we want in the collection...
$site_ids = array(1506,6666);

$site_id = 1506; // Stack 1
$site_id = 6666; // Stack 2

// Check current switch status...
$primary_site_id = check_current_primary_site($collection_id,$config);

if($primary_site_id == $site_id) {
  echo 'Site '. $site_id . ' is already the primary site. No switch required.', PHP_EOL;
  exit();
}

// Setup the site collection if we need to...
$body = add_sites_to_collection($site_ids,$collection_id,$config);

// Switch the primary site...
$body = perform_switch($site_id,$collection_id,$config);

if($body->message != "") {
  print_r($body);
  exit();
}

// Check if switch has been completed...
check_switch_status($site_id,$collection_id,$config);

function add_sites_to_collection($site_ids,$collection_id,$config) {

  echo 'Adding sites : '.implode(",",$site_ids).' to collection: ' . $collection_id, PHP_EOL;
  $url = $config["url"] . "collections/" . $collection_id . "/set-primary";
  $method = "POST";

  // Set the site to switch to...
  $form_params = [
    'site_ids' => implode(",",$site_ids)
  ];

  $res = request($url, $method, $config, $form_params);
  $body = json_decode($res->getBody()->getContents());

  return $body;

}

// Set primary site in the collection
function perform_switch($site_id,$collection_id,$config) {

  echo 'Switching to site: '. $site_id .' in collection: ' . $collection_id, PHP_EOL;
  $url = $config["url"] . "collections/" . $collection_id . "/set-primary";
  $method = "POST";

  // Set the site to switch to...
  $form_params = [
    'site_id' => 2316
  ];

  $res = request($url, $method, $config, $form_params);
  $body = json_decode($res->getBody()->getContents());

  return $body;
}

function check_current_primary_site($collection_id, $config) {

    // Check primary site status....
    $url = $config["url"]."collections/".$collection_id;
    $method = "GET";
    $res = request($url, $method, $config);
    $body = json_decode($res->getBody()->getContents());

    return $body->primary_site;
}

// Check the switch status...
function check_switch_status($site_id, $collection_id,$config) {

  // try 1000 times before failing....
  $retryCount = 1000;
  $time_pre = microtime(true);

  echo 'Checking status of switch to site: '. $site_id, PHP_EOL;
  do {
      sleep(10);

      // Check primary site status....
      $url = $config["url"]."collections/".$collection_id;
      $method = "GET";
      $res = request($url, $method, $config);
      $body = json_decode($res->getBody()->getContents());

      if ($body->primary_site == $site_id) {
          $time_post = microtime(true);
          $exec_time = $time_post - $time_pre;
          echo "\033[32mSuccessfully switched primary site to ".$site_id." in " . number_format($exec_time,2) . "ms.\033[0m", PHP_EOL;
          $retry = FALSE;
      } else {
          echo 'Site switch in progress...', PHP_EOL;
          $retryCount--;
          $retry = $retryCount > 0;
      }
  } while ($retry);

  return NULL;
}

// Helper function to return API user and key.
function get_request_auth($config) {
  return [
    'auth' => [$config['api_user'], $config['api_key']],
  ];
}

// Sends a request using the guzzle HTTP library; prints out any errors.
function request($url, $method, $config, $form_params = []) {
  // We are setting http_errors => FALSE so that we can handle them ourselves.
  // Otherwise, we cannot differentiate between different HTTP status codes
  // since all 40X codes will just throw a ClientError exception.
  $client = new Client(['http_errors' => FALSE]);

  $parameters = get_request_auth($config);
  if ($form_params) {
    $parameters['json'] = $form_params;
  }

  try {
    $res = $client->request($method, $url, $parameters);
    return $res;
  }
  catch (RequestException $e) {
    printf("Request exception!\nError message %s\n", $e->getMessage());
  }

  return NULL;
}
