<?php
require_once("rest.php");

function getDOMXPath($amount, $from, $to){
    $from = strtoupper($from);
    $to = strtoupper($to);
    try {
        $data = fileContents(
            "https://www.xe.com/en/currencyconverter/convert/?Amount=$amount&From=$from&To=$to".'&t='.mt_rand());
    } catch (Exception $e) {
        return -2;
    }

    //var_dump($data);
    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML($data);
    libxml_use_internal_errors(false);
    return $doc;
}

function getRateValue($doc) {
	$xpath = new DOMXPath($doc);
	$result = $xpath->query('//p[starts-with(@class,"result__BigRate")]');
	if($result->length > 0) {
	  $node = $result->item(0);
	  $value = $node->nodeValue;
	  return floatValue(explode(" ", $value)[0]);
	} else {
	  return -1;
	}
}

function getDateValue($doc){
    $result_arr = array(
        'date' => '',
        'date_time' => 0
    );
    $xpath = new DOMXPath($doc);
    $result = $xpath->query('//div[starts-with(@class,"result__LiveSubText")]');
    if($result->length > 0) {
        $node = $result->item(0);
        $value = $node->nodeValue;
        $value = explode("Last updated ", $value)[1];
        $result_arr['date'] = $value;
        $result_arr['date_time'] = strtotime($value);
    }
    return $result_arr;
}

// Returns the contents of a file
function fileContents($path) {
    $str = @file_get_contents($path);
    if ($str === FALSE) {
        throw new Exception("Cannot access '$path' to read contents.");
    } else {
        return $str;
    }
}


function floatValue($val){
    $val = str_replace(",",".",$val);
    $val = preg_replace('/\.(?=.*\.)/', '', $val);
    return floatval($val);
}

$rest = new REST();

if($rest->get_request_method() != "GET") $rest->response('',406);
if(!isset($rest->_request['from'])) $rest->responseInvalidParam('from');
if(!isset($rest->_request['to'])) $rest->responseInvalidParam('to');
$from = $rest->_request['from'];
$to = $rest->_request['to'];
$to_arr = array();
$multi = false;
$amount = 1;

if(isset($rest->_request['amount'])){
    $amount = floatValue($rest->_request['amount']);
}

if (strpos($to, ',') !== false) {
    $to_arr = explode(',', $to);
    $multi = true;
}

$result = array(
    'from' => $from,
    'to' => $to,
    'amount' => $amount
);

if(!$multi){
    $doc = getDOMXPath($amount, $from, $to);

    $rate = getRateValue($doc);
    $date = getDateValue($doc);
    $result['date'] = $date['date'];
    $result['date_time'] = $date['date_time'];
    $result['rate'] = $rate;

} else {
    $multi = array();
    foreach ($to_arr as $t) {
        $doc = getDOMXPath($amount, $from, $t);
        $rate = getRateValue($doc);
        $multi[] = array('to' => $t, 'rate' => $rate);
    }
    $result['multi'] = $multi;
}

$rest->show_response($result);

?>
