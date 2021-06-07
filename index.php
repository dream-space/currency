<?php
require_once("rest.php");

// Returns the contents of a file
function file_contents($path) {
    $str = @file_get_contents($path);
    if ($str === FALSE) {
        throw new Exception("Cannot access '$path' to read contents.");
    } else {
        return $str;
    }
}

function convertCurrencyXE($amount, $from, $to){
    $from = strtoupper($from);
    $to = strtoupper($to);
    try {
        $data = file_contents("https://www.xe.com/currencyconverter/convert/?Amount=$amount&From=$from&To=$to");
    } catch (Exception $e) {
        return -2;
    }

	//var_dump($data);
	$doc = new DOMDocument;
	libxml_use_internal_errors(true);
	$doc->loadHTML($data);
	libxml_use_internal_errors(false);

	$xpath = new DOMXPath($doc);
	$result = $xpath->query('//p[starts-with(@class,"result__BigRate")]');
	if($result->length > 0) {
	  $node = $result->item(0);
	  $value = $node->nodeValue;
	  return explode(" ", $value)[0];
	} else {
	  return -1;
	}
}

$rest = new REST();

if($rest->get_request_method() != "GET") $rest->response('',406);
if(!isset($rest->_request['from'])) $rest->responseInvalidParam('from');
if(!isset($rest->_request['to'])) $rest->responseInvalidParam('to');
$from = $rest->_request['from'];
$to = $rest->_request['to'];
$amount = 1;
if(isset($rest->_request['amount'])){
    $amount = (int)$rest->_request['amount'];
}

$result = convertCurrencyXE($amount, $from, $to);

$rest->show_response(array('result' => $result));

?>
