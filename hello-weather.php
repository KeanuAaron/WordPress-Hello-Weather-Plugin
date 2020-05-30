<?php
/**
 * Plugin Name: Hello Weather
 * Plugin URI: https://github.com/KeanuAaron/WordPress-Hello-Weather-Plugin
 *
 * Description: This is the very first plugin I ever created. This plugin is similar to "Hello Dolly", except that it
 * just displays the local weather of the user signed in based on their IP Address. This info isn't used anywhere or
 * even saved. It's just a learning experience for developing wordpress plugins.
 *
 * Version: 1.0
 * Author: Keanu Aaron
 * Author URI: https://virbuntu.com/
 *
 * @package    WordPress
 * @subpackage Hello_Weather
 */

/**
 * @const OpenWeatherMap API Key.
 *
 * You can get a free API KEY at https://openweathermap.org.
 */
const VFPWP_OWM_API_KEY = '8ba3346d05de20755b9bd014fd6ebc0b';

/**
 * @const IpInfo API Key.
 *
 * You can get a free API KEY at https://ipinfo.io.
 */
const VFPWP_IPINFO_API_KEY = '93ec69647888d2';

/**
 * Init plugin.
 */
function vfpwp_init() {

	// Now we set that function up to execute when the admin_notices action is called.
	add_action( 'admin_notices', 'vfpwp_display_admin_notice' );

	// Finally we add the CSS rules inside of admin_head.
	add_action( 'admin_head', 'vfpwp_weather_css' );
}

add_action( 'admin_init', 'vfpwp_init' );

/**
 * CSS Styling Rules for Hello Weather
 */
function vfpwp_weather_css() {
	echo "
	<style type='text/css'>
	#vfpwp_weather {
		float: right;
		padding: 5px 10px;
		margin: 0;
		font-size: 12px;
		line-height: 1.6666;
		max-width: 50%;
	}
	.rtl #vfpwp_weather {
		float: left;
	}
	.block-editor-page #vfpwp_weather {
		display: none;
	}
	@media screen and (max-width: 782px) {
		#vfpwp_weather,
		.rtl #vfpwp_weather {
			float: none;
			padding-left: 0;
			padding-right: 0;
		}
	}
	</style>
	";
}

/**
 * Get IP/Geo information.
 *
 * @param string|null $ip_address Optional IP Address.
 *
 * @return array|WP_Error
 */
function vfpwp_get_ipinfo( string $ip_address = null ) {
	$response = vfpwp_remote_cached_get(
		vfpwp_get_ipinfo_url( $ip_address ),
		array(
			'transient_name'       => 'vfpwp_ip_info',
			'transient_expiration' => 6 * HOUR_IN_SECONDS,
			'required_fields'      => array(
				'loc' => 1,
			),
		)
	);

	// If there are any errors, return them.
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$latlong = explode( ',', $response['loc'] );

	$value              = $response;
	$value['latitude']  = $latlong[0];
	$value['longitude'] = $latlong[1];

	return $value;
}

/**
 * Get IPInfo URL string.
 *
 * @param string|null $ip_address IP Address string (optional).
 *
 * @return string URL string.
 */
function vfpwp_get_ipinfo_url( string $ip_address = null ) {
	if ( empty( VFPWP_IPINFO_API_KEY ) ) {
		if ( is_null( $ip_address ) ) {
			$url = 'http://ipinfo.io/geo';
		} else {
			$url = sprintf( 'http://ipinfo.io/%s/geo', $ip_address );
		}
	} else {
		if ( is_null( $ip_address ) ) {
			$url = sprintf( 'http://ipinfo.io/geo?token=%s', VFPWP_IPINFO_API_KEY );
		} else {
			$url = sprintf( 'http://ipinfo.io/%s/geo?token=%s', $ip_address, VFPWP_IPINFO_API_KEY );
		}
	}

	return $url;
}

/**
 * Here we are just making another CURL GET request to OpenWeather API. You can
 * pretty much copy and paste this from the early API call.
 *
 * Once it's all gathered, we just send it over to: vfpWP_display_weather
 *
 * @param string $latitude  Latitude string.
 * @param string $longitude Longitude string.
 * @param string $api_key   API Key string.
 *
 * @return string|WP_Error
 */
function vfpwp_get_local_weather( string $latitude, string $longitude, string $api_key ) {
	$response = vfpwp_remote_cached_get(
		sprintf( 'http://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&appid=%s', $latitude, $longitude, $api_key ),
		array(
			'transient_name'       => 'vfpwp_weather_info',
			'transient_expiration' => 6 * HOUR_IN_SECONDS,
			'required_fields'      => array(
				'weather' => 1,
			),
		)
	);

	return $response;
}

/**
 * Display Admin notice.
 */
function vfpwp_display_admin_notice() {
	$ip_info = vfpwp_get_ipinfo();

	if ( is_wp_error( $ip_info ) ) {
		$error_message = $ip_info->get_error_message();

		printf(
			'<h1 id="vfpwp_weather">
				<span class="screen-reader-text">%s </span>
				<span>%s</span>
				</h1>',
			esc_html__( 'There has been an error.' ),
			/* translators: %1$s: Error message from IP Address API. */
			esc_html( sprintf( __( 'IP Address API error: %1$s' ), wp_unslash( $error_message ) ) )
		);

		return;
	}

	$local_weather = vfpwp_get_local_weather( $ip_info['latitude'], $ip_info['longitude'], VFPWP_OWM_API_KEY );

	if ( is_wp_error( $local_weather ) ) {
		$error_message = $local_weather->get_error_message();

		printf(
			'<h1 id="vfpwp_weather">
				<span class="screen-reader-text">%s </span>
				<span>%s</span>
				</h1>',
			esc_html__( 'There has been an error.' ),
			esc_html( wp_unslash( $error_message ) )
		);

		return;
	}

	$description = $local_weather['weather'][0]['description'];
	$city        = $ip_info['city'];
	$region      = $ip_info['region'];

	printf(
		'<h1 id="vfpwp_weather">
				<span class="screen-reader-text">%s </span>
				<span>It looks like %s in %s, %s today.</span>
				</h1>',
		esc_html__( 'Local Weather for your location.' ),
		esc_html( $description ),
		esc_html( $city ),
		esc_html( $region )
	);
}

/**
 * We can get the clients IP address based on HTTP Headers.
 * Thought this was cool, as I didn't actually know this before.
 *
 * @return string|string
 */
function vfpwp_get_client_ip() {
	$ip_addresses = array_filter(
		filter_input_array(
			INPUT_SERVER,
			array(
				'HTTP_CLIENT_IP'       => FILTER_VALIDATE_IP,
				'HTTP_X_FORWARDED_FOR' => FILTER_VALIDATE_IP,
				'HTTP_X_FORWARDED'     => FILTER_VALIDATE_IP,
				'HTTP_FORWARDED_FOR'   => FILTER_VALIDATE_IP,
				'HTTP_FORWARDED'       => FILTER_VALIDATE_IP,
				'REMOTE_ADDR'          => FILTER_VALIDATE_IP,
			)
		)
	);

	if ( 0 === count( $ip_addresses ) ) {
		return new WP_Error(
			'ip-fetch-error',
			__( 'Unable to fetch IP error.' )
		);
	}

	return array_pop( $ip_addresses );
}

/**
 * Get Public IP.
 *
 * @return string IP Address.
 */
function vfpwp_get_public_ip() {
	$response = vfpwp_remote_cached_get(
		'http://checkip.dyndns.com/',
		array(
			'transient_name'       => 'vfpwp_public_ip',
			'transient_expiration' => 6 * HOUR_IN_SECONDS,
			'required_fields'      => array(),
		)
	);

	if ( is_wp_error( $response ) ) {
		return '';
	}

	preg_match( '/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $response, $matches );

	return filter_var( $matches[1], FILTER_VALIDATE_IP );
}

/**
 * Remote Cached get URL helper.
 *
 * @param string $url     Remote URL to fetch.
 * @param array  $options Array of options.
 *
 * @return mixed|WP_Error
 */
function vfpwp_remote_cached_get( string $url, array $options = array() ) {
	$args = wp_parse_args(
		$options,
		array(
			'transient_name'       => null,
			'transient_expiration' => 60,
			'remote_get'           => array(
				'headers'     => array(
					'Accept'        => 'application/json',
					'Cache-Control' => 'no-cache',
				),
				'httpversion' => '1.1',
				'timeout'     => 30,
			),
			'required_fields'      => array(),
		)
	);

	$value = get_transient( $args['transient_name'] );

	if ( false === ( $value ) ) {

		$remote_get_args = $args['remote_get'];

		$response = wp_remote_get(
			$url,
			$remote_get_args
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_error = null;

		if ( is_wp_error( $response ) ) {
			$response_error = $response;
		} elseif ( 200 !== $response_code ) {
			$response_error = new WP_Error(
				'api-error',
				/* translators: %1$d: Numeric HTTP status code, e.g. 400, 403, 500, 504, etc., %2$s Error message, if any. */
				sprintf( __( 'Invalid API response code (%1$d): %2$s' ), $response_code, json_encode( $response_body ) )
			);
		} elseif ( count( array_intersect_key( $response_body, $args['required_fields'] ) ) < count( $args['required_fields'] ) ) {
			$response_error = new WP_Error(
				'api-invalid-response',
				isset( $response_body['error'] ) ? $response_body['error'] : __( 'Unknown API error. The response was unexpected.' )
			);
		}

		// If there are any errors, return them.
		if ( is_wp_error( $response_error ) ) {
			return $response_error;
		}

		$value = $response_body;

		set_transient( $args['transient_name'], $value, $args['transient_expiration'] );
	}

	return $value;
}
