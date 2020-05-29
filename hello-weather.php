<?php
/**
 * Plugin Name: Hello Weather
 * Plugin URI: https://github.com/KeanuAaron/WordPress-Hello-Weather-Plugin
 * Description: This is the very first plugin I ever created. This plugin is similar to "Hello Dolly", except that it just displays the local weather of the user signed in based on their IP Address. This info isn't used anywhere or even saved. It's just a learning experience for developing wordpress plugins.
 * Version: 1.0
 * Author: Keanu Aaron
 * Author URI: https://virbuntu.com/
**/  

/* ===================================
 * CSS Styling Rules for Hello Weather
 * ===================================
 */
function vfpWP_weather_css() {
	echo "
	<style type='text/css'>
	#vfpWP_weather {
		float: right;
		padding: 5px 10px;
		margin: 0;
		font-size: 12px;
		line-height: 1.6666;
	}
	.rtl #vfpWP_weather {
		float: left;
	}
	.block-editor-page #vfpWP_weather {
		display: none;
	}
	@media screen and (max-width: 782px) {
		#vfpWP_weather,
		.rtl #vfpWP_weather {
			float: none;
			padding-left: 0;
			padding-right: 0;
		}
	}
	</style>
	";
}

/* ===================================================================================
 * Here we are just making another CURL GET request to OpenWeather API. You can pretty
 * much copy and paste this from the early API call.
 * Once it's all gathered, we just send it over to: vfpWP_display_weather
 * ===================================================================================
 */
function vfpWP_get_local_weather($vfpWP_lat, $vfpWP_long, $vfpWP_API_KEY) {
	$vfpWP_curl = curl_init();
	// weather?q={city name},{state code},{country code} [ alternative way to get the weather info
	curl_setopt_array($vfpWP_curl, array(
	  CURLOPT_URL => "api.openweathermap.org/data/2.5/weather?lat=$vfpWP_lat&lon=$vfpWP_long&appid=$vfpWP_API_KEY",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
		"cache-control: no-cache"
	  ),
	));
	$vfpWP_json = curl_exec($vfpWP_curl);
	$vfpWP_err = curl_error($vfpWP_curl);
	curl_close($vfpWP_curl);

	/*
	 * It's crucial that you use both `json_decode` then `json_encode` here.
	 * 1. We are DECODING so that we can parse the JSON data from the string it returns.
	 * 2. We are ENCODING it before sending it to the next function because we have
	 * to later place it inside of a string and unless it's reverted back to string
	 * form, the plugin will crash the website.
	 */
	$vfpWP_weather_response = json_decode($vfpWP_json, true);
	$vfpWP_descrip_response = $vfpWP_weather_response['weather'][0]['description'];
	$vfpWP_city_response    = $vfpWP_weather_response['sys'][1]['name'];
	vfpWP_display_weather( json_encode($vfpWP_descrip_response) );
}

/* ==================================================================================
 * You can edit this to show whatever info you've so far collected from the API calls
 * ==================================================================================
 */
function vfpWP_display_weather($vfpWP_description_output) {
    global $vfpWP_city;
    global $vfpWP_region;
	printf(
		'<h1 id="vfpWP_weather"><span class="screen-reader-text">%s </span><span>It looks like %s in %s, %s today.</span></h1>',
		__( 'Local Weather for you location.' ),
		$vfpWP_description_output,
		$vfpWP_city,
		$vfpWP_region
	);
}

/* =============================================================
 * We can get the clients IP address based on HTTP Headers.
 * Thought this was cool, as I didn't actually know this before.
 *  =============================================================
 */
function vfpWP_get_client_ip()
{
    $vfpWP_ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $vfpWP_ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $vfpWP_ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $vfpWP_ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $vfpWP_ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $vfpWP_ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $vfpWP_ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $vfpWP_ipaddress = 'UNKNOWN';
    }

    return $vfpWP_ipaddress;
}


$vfpWP_PublicIP = vfpWP_get_client_ip();

// Initializing curl to make a GET Request to our API
$vfpWP_curl = curl_init();
curl_setopt_array($vfpWP_curl, array(
  CURLOPT_URL => "http://ipinfo.io/$vfpWP_PublicIP/geo",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "cache-control: no-cache"
  ),
));
$vfpWP_json = curl_exec($vfpWP_curl);
$vfpWP_err = curl_error($vfpWP_curl);

// It's important to close curl to save and spare resources
curl_close($vfpWP_curl);

$vfpWP_json      = json_decode($vfpWP_json, true);
$vfpWP_region    = $vfpWP_json['region'];
$vfpWP_city      = $vfpWP_json['city'];
$vfpWP_latlong   = explode(",", $vfpWP_json['loc']);
$vfpWP_lat       = $vfpWP_latlong[0];
$vfpWP_long      = $vfpWP_latlong[1];
$vfpWP_longitude = $vfpWP_json['longitude'];
$vfpWP_API_KEY   = 'YOUR_API_KEY'; // You can get an API KEY here [ https://openweathermap.org ]

vfpWP_get_local_weather($vfpWP_lat, $vfpWP_long, $vfpWP_API_KEY);

// Now we set that function up to execute when the admin_notices action is called.
add_action( 'admin_notices', 'vfpWP_display_weather' );
// Finally we add the CSS rules inside of admin_head
add_action( 'admin_head', 'vfpWP_weather_css' );
?>
