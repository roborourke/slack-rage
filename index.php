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
$app['webhooks'] = array(
	'hmn'           => 'https://hooks.slack.com/services/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',
	'screeningfilm' => 'https://hooks.slack.com/services/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'
);

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

$app->get( '/search', function ( \Silex\Application $app ) {
	$img = $app['fetch_rage'];
	return sprintf( '<img src="%s" alt="%s" />', $app->escape( $img->png ), $app->escape( $img->title ) );
} );

$app->post( '/search', function ( \Silex\Application $app ) {

	$img = $app['fetch_rage'];

	// check for slack data
	$team_domain = $app['request']->get( 'team_domain' );

	if ( $team_domain && isset( $app['webhooks'][ $team_domain ] ) ) {

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
		$promise = $client->postAsync( $app['webhooks'][ $team_domain ], array(
			'body' => $payload
		) );
		$promise->wait();

	}

	return sprintf( '<img src="%s" alt="%s" />', $app->escape( $img->png ), $app->escape( $img->title ) );;
} );

$app->run();