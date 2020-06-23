<?php
/**
 * Plugin Name: TCT Specific Weather Day
 * Description: Return weather data for specific day within next 7 days
 * Version: 1.0
 * Author: Taylor Drayson
 * Author URI: https://www.thecreativetinker.com/
 **/

add_action( 'admin_menu', 'tct_spd_add_admin_menu' );
add_action( 'admin_init', 'tct_spd_settings_init' );

function tct_spd_add_admin_menu() {
	add_options_page( 'TCT Specific Weather Day', 'Specifc Weather Counter', 'manage_options', 'tct_spd', 'tct_spd_options_page' );
}

function tct_spd_settings_init() {

	register_setting( 'tct_spd', 'tct_spd_settings' );

	add_settings_section(
		'tct_spd_section',
		__( 'Plugin Settings', 'tct_spd' ),
		'tct_spd_settings_section_callback',
		'tct_spd'
	);

	add_settings_field(
		'tct_spd_api_key',
		__( 'API Key', 'tct_spd' ),
		'tct_spd_api_key_render',
		'tct_spd',
		'tct_spd_section'
	);
	add_settings_field(
		'tct_spd_lat',
		__( 'Latitude', 'tct_spd' ),
		'tct_spd_lat_render',
		'tct_spd',
		'tct_spd_section'
	);

	add_settings_field(
		'tct_spd_long',
		__( 'Longitude', 'tct_spd' ),
		'tct_spd_long_render',
		'tct_spd',
		'tct_spd_section'
	);

}

function tct_spd_api_key_render() {

	$options = get_option( 'tct_spd_settings' );
	?>
    <input type='text' name='tct_spd_settings[tct_spd_api_key]'
           value='<?php echo $options['tct_spd_api_key']; ?>'>
	<?php

}
function tct_spd_lat_render() {

	$options = get_option( 'tct_spd_settings' );
	?>
    <input type='text' name='tct_spd_settings[tct_spd_lat]'
           value='<?php echo $options['tct_spd_lat']; ?>'>
	<?php

}
function tct_spd_long_render() {

	$options = get_option( 'tct_spd_settings' );
	?>
    <input type='text' name='tct_spd_settings[tct_spd_long]'
           value='<?php echo $options['tct_spd_long']; ?>'>
	<?php

}

function tct_spd_settings_section_callback() {
	echo __( '', 'tct_spd' );
}

function tct_spd_options_page() {

	?>
    <form action='options.php' method='post'>

        <h2>TCT Specifc Weather Day</h2>

		<?php
		settings_fields( 'tct_spd' );
		do_settings_sections( 'tct_spd' );
		submit_button();
		?>
				<h3>How to create shortcode</h3>
				<h4>Units are in UK format</h4>
				<ol>
					<li>Create an account with <strong><a href="https://home.openweathermap.org/users/sign_in">openweathermap</a></strong> and add your API Key to the box above.
					<li>Add your latitude and longitude for the location. (If you dont know the co-ordinates, <a href="https://www.latlong.net/">click here</a>)
					<li>Choose your attribute to display. For example: <strong>temperature.</strong>
					<li>Choose your day to display. For example: <strong>tuesday.</strong> (Defaults to Monday)
					<li>Add it to the the shortcode: <strong>[showweather attribute="temperature" day="tuesday"]</strong>
					<li>Display the date for the next chosen day <strong>[selected_day day="tuesday"]
				</ol>
				<br>
				<h3>Attributes to choose from</h3>
				<ul>
					<li>sunrise</li>
					<li>sunset</li>
					<li>temperature</li>
					<li>pressure</li>
					<li>humidity</li>
					<li>dew point</li>
					<li>windspeed</li>
					<li>wind gust</li>
					<li>wind deg</li>
					<li>uvi</li>
					<li>visibility</li>
					<li>rain</li>
					<li>snow</li>
					<li>description</li>
					<li>icon</li>
				</ul>
    </form>
	<?php

}

// Selected day shortcode
function tct_spd_selected_day($atts) {
	$atts = shortcode_atts( array(
				'day' =>''
		), $atts, 'selected_day' );
$day=$atts['day'];
$timestamp=date('l, jS F Y', strtotime("next $day"));
return $timestamp;
}
add_shortcode('selected_day', 'tct_spd_selected_day');

function tct_spd_weather_value($weather_data){
	$options   = get_option( 'tct_spd_settings' );
	$API       = $options['tct_spd_api_key'];
	$api_key   = esc_attr( $API );
	$lat_value       = $options['tct_spd_lat'];
	$lat   = esc_attr( $lat_value );
	$long_value       = $options['tct_spd_long'];
	$long  = esc_attr( $long_value );

	if(empty($lat)){
		$lat = "51.5014";
	}
	if(empty($long)){
		$long = "0.1419";
	}


	if(!$data=get_transient('tct_spd_weather_data'.$weather_data)){
		$googleApiUrl = "http://api.openweathermap.org/data/2.5/onecall?lat=".$lat."&lon=".$long."&exclude=current,minutely,hourly&units=metric&appid=".$api_key;

		$response = wp_remote_get( esc_url_raw($googleApiUrl ) );

		$returned_data = json_decode( wp_remote_retrieve_body( $response ));

		set_transient('tct_spd_weather_data' .$weather_data, $returned_data,86400);
	}
	return $returned_data;
}


function tct_spd_weather_shortcode($atts){

	$atts = shortcode_atts( array(
        'attribute' => '',
				'day' =>'',
    ), $atts, 'showweather' );

	if ( isset( $atts['type'] ) ) {
		if ( $atts['type'] == 'day' ) {
			$day = $atts['day'];
		}

	}

	$data=tct_spd_weather_value($atts[$weather_data]);

	//Find # days until selected day
	$day= $atts['day'];
	$today = new DateTime("now");
	$next_day = new DateTime("next $day");
	$next_day->setTime(24,0,0);
	$interval = date_diff($today, $next_day);

	// sunrise
	$sunrise=$data->daily[$interval->d]->sunrise;
	$sunrise=gmdate("g:i a", $sunrise);

	//icon
	$icon=$data->daily[$interval->d]->weather[0]->icon;

	//Description
	$description=$data->daily[$interval->d]->weather[0]->description;


	//Temperature
	$temperature=round($data->daily[$interval->d]->temp->day);

	//Wind Speed
	$windspeed=$data->daily[$interval->d]->wind_speed;
	$windspeed=round($windspeed*2.23694);

	//Humidity
	$humidity=round($data->daily[$interval->d]->humidity);


		switch($atts['attribute']){
			case 'sunrise':
				return'<span>'.$sunrise.'</span>';
				break;
			case 'icon':
				return'<img src="http://openweathermap.org/img/wn/'.$icon.'@2x.png">';
				break;
			case 'description':
				return'<span style="text-transform: capitalize;">'.$description.'</span>';
				break;
			case 'temperature':
				return'<span>'.$temperature.'&deg;C</span>';
				break;
			case 'windspeed':
				return'<span>'.$windspeed.' mph </span>';
				break;
			case 'humidity':
				return'<span>'.$humidity.' % </span>';
				break;
		}

}
add_shortcode('showweather','tct_spd_weather_shortcode')
?>
