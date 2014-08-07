WP Addthis API Connect
======================

Connect to the [Addthis API](http://support.addthis.com/customer/portal/articles/381262-api-and-sdk-overview) using WordPress APIs

To get started, create a new WP_Addthis_API_Connect object by passing your username, password, pubid, and url (registered with Addthis).
```php
// Consumer credentials
$credentials = array(
	'username' => 'justin@webdevstudios.com',
	'password' => 'XXXXXXXXXXX',
	'pubid'    => 'ra-XXXXXXXXXXX',
	'url'      => 'webdevstudios.com',
);
$api = new WP_Addthis_API_Connect( $credentials );
```

You can then use this object to retrieve the authentication request URL, or if you have been authenticated, make requests.

```php
<?php

if ( ! class_exists( 'WP_Addthis_API_Connect' ) ) {
	require_once( 'WP_Addthis_API_Connect.php' );
}

/**
 * Example WP_Addthis_API_Connect usage
 */
function wp_addthis_api_connect_example_test() {

	// Addthis credentials
	$credentials = array(
		'username' => 'justin@webdevstudios.com',
		'password' => 'XXXXXXXXXXX',
		'pubid'    => 'ra-XXXXXXXXXXX',
		'url'      => 'webdevstudios.com',
	);

	$api = new WP_Addthis_API_Connect( $credentials );

	$args = array(
		'service' => 'twitter',
		'domain'  => 'webdevstudios.com',
		'period'  => 'last24',
		'url'     => 'http://webdevstudios.com/post-to-check',
	);
	// Get all twitter shares split into counts for every 10 minute increment
	// in the last 24 hours.
	$shares = $api->get_shares( 'url.json', $args );

	if ( is_wp_error( $shares ) ) {

		echo '<div id="message" class="error">';
		echo wpautop( $shares->get_error_message() );
		echo '</div>';

	} else {

		$shares_count = 0;
		foreach ( $shares as $share ) {
			$shares_count = $shares_count + absint( $share->shares );
		}

		echo '<div id="message" class="updated"><p>';
		echo '<xmp>$shares_count: '. print_r( $shares_count, true ) .'</xmp>';
		echo '<xmp>$shares: '. print_r( $shares, true ) .'</xmp>';
		echo '</p></div>';

	}

}
add_action( 'all_admin_notices', 'wp_addthis_api_connect_example_test' );
```
