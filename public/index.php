<?php
include __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

if (empty($_POST['text'])) {
	echo base();
} else {
	$input_array 	= explode("*", strtoupper($_POST['text']));
	$pair 			= end($input_array);

	echo rate($pair);
}

function base($reply = "")
{
	if (empty($reply))
		$reply .= 'CON ';

	$reply .= 'Enter two currencies separated by a space to get the rates between them. e.g. "USD NGN"';

	return $reply;	
}

function error($message = '')
{
	if (empty($message)) 
		$message = 'We could not find rates for that pair';

	return base("CON $message \n\n");
}

function rate($pair_string)
{
	$response_body = json_decode(getResponse(), true);

	if (!array_key_exists('rates', $response_body) || !is_array($response_body['rates'])) {
		return error();
	}

	$pair 	= explode(" ", $pair_string);
	$rates 	= $response_body['rates'];

	if (count($pair) !== 2) {
		return error('That is not a valid pair');
	}

	foreach ($pair as $currency) {
		if (!array_key_exists($currency, $rates)) {
			return error("There are no rates for $currency");
		}
	}

	$reply 	 = 'END ';
	$reply 	.= '1 ' . $pair[0] . " = " . number_format($rates[$pair[1]] / $rates[$pair[0]], 5) . " " . $pair[1] . "\n";
	$reply 	.= '1 ' . $pair[1] . " = " . number_format($rates[$pair[0]] / $rates[$pair[1]], 5) . " " . $pair[0] . "\n";

	return $reply;	
}

function getResponse() 
{
	$current_timestamp 	= file_get_contents("../last_timestamp");

	if (ceil(time() / 3600) > ceil($current_timestamp / 3600)) {

		$client 	= new Client([
			'base_uri' 	=> 'https://openexchangerates.org/api/',
			'headers' 	=> [
				'Content-Type' 	=> 'application/x-www-form-urlencoded',
				'Accept' 		=> 'application/json'
			]
		]);

		$query 		= [
			'app_id'			=> $_ENV['API_KEY'],
			'prettyprint'		=> 0,
			'show_alternative'	=> 1
		];

		$response 	= $client->get('latest.json', ['query' => $query])->getBody()->getContents();

		$current_rates = fopen("../current_rates.json","w");
		echo fwrite($current_rates, $response);
		fclose($current_rates);

		$last_timestamp = fopen("../last_timestamp","w");
		echo fwrite($last_timestamp, time());
		fclose($last_timestamp);
	}

	return file_get_contents("../current_rates.json");
}