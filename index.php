<?php
	function convertCurrencyXE($amount, $from, $to){
		$data = file_get_contents("https://www.xe.com/currencyconverter/convert/?Amount=$amount&From=$from&To=$to");
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


echo convertCurrencyXE("1", "USD", "IDR");

?>
