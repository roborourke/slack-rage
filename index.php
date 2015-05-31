<?php
/**
 * Simple Rage Face fetcher
 */

namespace RageApp;

// get autoloader
require_once __DIR__ . '/vendor/autoload.php';

if ( file_exists( __DIR__ . '/config.php' ) ) {
	require_once __DIR__ . '/config.php';
}

$app = new \Silex\Application();

// slack hooks
// 1. copy the sample config file to config.php
// 2. change the sample array so that your slash command token is the key and you incoming webhook is the value
// 3. add as many as you like
$app['webhooks'] = isset( $webhooks ) ? $webhooks : array();

// add fetch service
$app['fetch_rage'] = function ( \Silex\Application $app ) {

	$search = $app['request']->get( 'text' );
	$search = str_replace( ' ', ',', $search );

	// fetch from alltherage
	$client = new \GuzzleHttp\Client();
	$res    = $client->get( 'http://alltheragefaces.com/api/search/' . rawurlencode( $search ) );
	$json   = json_decode( $res->getBody() );

	if ( $json ) {
		$img = $json[ array_rand( $json ) ];
		return $img;
	}

	$app->abort( 200, 'Failed to fetch anything from the API' );
};

$app->get( '/', function ( \Silex\Application $app ) {
	$img = $app['fetch_rage'];
	return sprintf( '<img src="%s" alt="%s" />', $app->escape( $img->png ), $app->escape( $img->title ) );
} );

$app->post( '/', function ( \Silex\Application $app ) {

	$img = $app['fetch_rage'];

	// check for slack data
	$token = $app['request']->get( 'token' );

	if ( $token && isset( $app['webhooks'][ $token ] ) ) {

		// check if user chat or channel
		$prefix = strpos( $app['request']->get( 'channel_id' ), 'C' ) === 0 ? '#' : '@';

		$payload = json_encode( array(
			'channel'     => $prefix . $app['request']->get( 'channel_name' ),
			'username'    => 'RAGE',
			'icon_url'    => 'http://cdn.alltheragefaces.com/img/faces/png/troll-troll-face.png',
			'attachments' => array(
				array(
					'title'     => $img->title,
					'fallback'  => $img->title,
					'image_url' => $img->png,
				)
			)
		) );

		$client  = new \GuzzleHttp\Client();
		$promise = $client->postAsync( $app['webhooks'][ $token ], array(
			'body' => $payload
		) );
		$promise->wait();

	}

	return sprintf( '<img src="%s" alt="%s" />', $app->escape( $img->png ), $app->escape( $img->title ) );;
} );

$app->run();