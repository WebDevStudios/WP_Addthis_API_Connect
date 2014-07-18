<?php

/**
 * Connect to the Addthis API using WordPress APIs
 *
 * AddThis Web Service Framework
 * http://support.addthis.com/customer/portal/articles/381267-addthis-web-service-framework
 *
 * AddThis Analytics API
 * http://support.addthis.com/customer/portal/articles/381264-addthis-analytics-api
 *
 * @author  Justin Sternberg <justin@webdevstudios.com>
 * @package WP_Addthis_API_Connect
 * @version 0.1.0
 */
class WP_Addthis_API_Connect {

	protected $addthis_base_url = 'https://api.addthis.com/';
	protected $api_version      = '1.0';
	protected $shares_endpoint  = 'pub/shares/';

	/**
	 * Initate our connect object
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Arguments
	 */
	public function __construct( $args ) {
		$args = wp_parse_args( $args, array(
			'username' => '',
			'password' => '',
			'pubid'    => '',
		) );
		$args['authorization'] = base64_encode( $args['username'] . ':' . $args['password'] );
		unset( $args['username'], $args['password'] );
		$this->args = $args;
	}

	public function get_shares( $endpoint, $args = array() ) {
		$transient_id = md5( serialize( array(
			'class'  => __CLASS__,
			'method' => __FUNCTION__,
			'args'   => $args,
		) ) );

		$cache_time_in_seconds = 3600;
		if ( isset( $args['cache_time_in_seconds'] ) ) {
			$cache_time_in_seconds = $args['cache_time_in_seconds'];
			unset( $args['cache_time_in_seconds'] );
		}

		$args['endpoint'] = $endpoint;

		$shares = $this->get_transient( array(
			'cache_time_in_seconds' => $cache_time_in_seconds,
			'transient_id'          => $transient_id,
			'cb'                    => array( $this, '_get_shares' ),
			'args'                  => $args,
		) );

		return $shares;
	}

	public function _get_shares( $query_args = array() ) {

		$query_args = wp_parse_args( $query_args, array(
			'pubid'    => $this->args['pubid'],
			'endpoint' => 'url.json',
		) );

		$url = $this->analytics_url( $this->shares_endpoint . $query_args['endpoint'] );
		unset( $query_args['endpoint'] );

		// normalize parameter key/values
		array_walk_recursive( $query_args, array( $this, 'normalize_parameters' ) );

		$url      = add_query_arg( $query_args, $url );
		$response = wp_remote_get( $url, array( 'headers' => $this->authorized_headers() ) );
		$body     = wp_remote_retrieve_body( $response );

		if ( $json = $this->is_json( $body ) ) {
			$json = $this->append_data( $json, array( 'url' => $url ) );
		}
		return $body && $json ? $json : $body;
	}

	public function analytics_url( $path = '' ) {
		$base_url = $this->addthis_base_url . 'analytics/' . $this->api_version . '/';
		return $path ? $base_url . $path : $base_url;
	}

	public function get_transient( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'cache_time_in_seconds' => 3600,
			'transient_id'          => '',
			'cb'                    => '',
			'args'                  => array(),
		) );

		if ( !$args['cache_time_in_seconds'] && is_callable( $args['cb'] ) ) {
			return call_user_func( $args['cb'], $args['args'] );
		}

		$transient = get_transient( $args['transient_id'] );

		if ( ! $transient && is_callable( $args['cb'] ) ) {
			$transient = call_user_func( $args['cb'], $args['args'] );
			if ( $this->is_json( $transient ) ) {
				set_transient( $args['transient_id'], $transient, $args['cache_time_in_seconds'] );
			}
		}

		$transient = $this->append_data( $transient, array( 'expires' => $this->expires_time( $args['transient_id'] ) ) );

		return $transient;
	}

	public function expires_time( $transient_id ) {
		if ( $expires = get_option( '_transient_timeout_' . $transient_id ) ) {
			return date( 'F j, Y, g:i a', $expires );
		}
		return 'unknown';
	}

	public function authorized_headers() {
		return array(
			'Authorization' => 'Basic ' . $this->args['authorization'],
		);
	}

 	protected function normalize_parameters( &$key, &$value ) {
		$key = rawurlencode( rawurldecode( $key ) );
		$value = rawurlencode( rawurldecode( $value ) );
	}

	public function append_data( $data, array $to_append ) {
		if ( is_object( $data ) ) {
			if ( isset( $data->api_request ) ) {
				$data->api_request = array_merge( $data->api_request, $to_append );
			} else {
				$data->api_request = $to_append;
			}
		} else {
			if ( isset( $data['api_request'] ) ) {
				$data['api_request'] = array_merge( $data['api_request'], $to_append );
			} else {
				$data['api_request'] = $to_append;
			}
		}

		return $data;
	}

	/**
	 * Determines if a string is JSON, and if so, decodes it.
	 *
	 * @since  0.1.0
	 *
	 * @param  string $string String to check if is JSON
	 *
	 * @return boolean|array  Decoded JSON object or false
	 */
	function is_json( $string ) {
		return is_string( $string ) && ( $json = json_decode( $string ) ) && ( is_object( $json ) || is_array( $json ) )
			? $json
			: false;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $field
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		switch( $field ) {
			case 'addthis_base_url':
			case 'api_version':
			case 'shares_endpoint':
				return $this->{$field};
			default:
				throw new Exception( 'Invalid property: ' . $field );
		}
	}

}
