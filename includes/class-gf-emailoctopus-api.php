<?php
// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Gravity Forms EmailOctopus Add-On.
 *
 * @since     1.0.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2019, Rocketgenius
 */

/**
 * Helper class for retrieving the EmailOctopus API validation.
 */
class GF_EmailOctopus_API {

	/**
	 * EmailOctopus API URL.
	 *
	 * @since  1.0
	 * @since  1.3 Changed API version from 1.5 to 1.6.
	 * @var    string $api_url EmailOctopus API URL.
	 */
	protected $api_url = 'https://emailoctopus.com/api/1.6/';

	/**
	 * EmailOctopus API key.
	 *
	 * @since  1.0
	 * @var    string $api_key EmailOctopus API Key.
	 */
	protected $api_key = null;

	/**
	 * Initialize API library.
	 *
	 * @since  1.0
	 *
	 * @param  string $api_key EmailOctopus API key.
	 */
	public function __construct( $api_key = null ) {
		$this->api_key = $api_key;
	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 *
	 * @param string    $path          Request path.
	 * @param array     $options       Request options.
	 * @param string    $method        Request method. Defaults to GET.
	 * @param string    $return_key    Array key from response to return. Defaults to null (return full response).
	 * @param int|array $response_code Expected HTTP response code.
	 *
	 * @return array|WP_Error
	 */
	private function make_request( $path = '', $options = array(), $method = 'GET', $return_key = null, $response_code = 200 ) {

		$request_url = $this->api_url . $path;

		// Log API call succeed.
		gf_emailoctopus()->log_debug( __METHOD__ . '(): Making request to: ' . $request_url );

		// Get API Key.
		$api_key = $this->api_key;

		$args = array( 'method' => $method );

		if ( 'GET' === $method ) {
			$options['api_key'] = $api_key;
			$request_url        = add_query_arg( $options, $request_url );
		}

		if ( 'POST' === $method ) {
			$args['body']            = $options['body'];
			$args['body']['api_key'] = $api_key;
		}

		/**
		 * Filters if SSL verification should occur.
		 *
		 * @since 1.0
		 * @since 1.3 Added the $request_url param.
		 *
		 * @param bool   $local_ssl_verify If the SSL certificate should be verified. Defaults to false.
		 * @param string $request_url      The request URL.
		 *
		 * @return bool
		 */
		$args['sslverify'] = apply_filters( 'https_local_ssl_verify', false, $request_url );

		/**
		 * Sets the HTTP timeout, in seconds, for the request.
		 *
		 * @since 1.0
		 * @since 1.3 Added the $request_url param.
		 *
		 * @param int    $request_timeout The timeout limit, in seconds. Defaults to 30.
		 * @param string $request_url     The request URL.
		 *
		 * @return int
		 */
		$args['timeout'] = apply_filters( 'http_request_timeout', 30, $request_url );

		// Execute request.
		$response = wp_remote_request(
			$request_url,
			$args
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response ) ) {
			return new WP_Error( 'emailoctopus_invalid_call', esc_html__( 'The API path supplied is not supported by the EmailOctopus API.', 'gravityformsemailoctopus' ), array() );
		}
		// If an incorrect response code was returned, return WP_Error.
		$response_body = gf_emailoctopus()->maybe_decode_json( wp_remote_retrieve_body( $response ) );
		if ( isset( $response_body['0'] ) ) {
			$retrieved_response_code = $response_body['0'];
		} else {
			$retrieved_response_code = $response['response']['code'];
		}

		if ( $retrieved_response_code !== $response_code ) {
			$error_message = "Expected response code: {$response_code}. Returned response code: {$retrieved_response_code}.";

			$error_data = array( 'status' => $retrieved_response_code );
			if ( ! rgempty( 'code', $response_body ) ) {
				$error_message = $response_body['code'];
			}
			$error_data['data'] = '';
			if ( rgars( $response_body, 'error/message' ) ) {
				$error_data['data'] = rgars( $response_body, 'error/message' );
			}
			gf_emailoctopus()->log_error( __METHOD__ . '(): Unable to validate with the EmailOctopus API: ' . $error_message . ' ' . $error_data['data'] );

			return new WP_Error( 'emailoctopus_api_error', $error_data['data'] );
		}

		return $response_body;
	}

	/**
	 * Gets a list from the EmailOctopus API
	 *
	 * @since  1.0.0
	 *
	 * @param string $list_id The EmailOctopus list ID to check.
	 *
	 * @return array|WP_Error List content if successful
	 */
	public function get_list( $list_id ) {
		return $this->make_request( 'lists/' . $list_id );
	}


	/**
	 * Gets lists from the EmailOctopus API
	 *
	 * @since  1.0.0
	 * @since  1.3.0 Updated to use get_items, to get all lists instead of a single page.
	 *
	 * @return array|WP_Error List content if successful
	 */
	public function get_lists() {
		return $this->get_items( 'lists' );
	}

	/**
	 * Tests the API Key by getting 1 list.
	 *
	 * @since 1.3.0
	 *
	 * @return WP_Error|true
	 */
	public function is_api_key_valid() {
		$response = $this->get_items( 'lists', array( 'limit' => 1, 'page' => 1 ), true );

		return is_wp_error( $response ) ? $response : true;
	}

	/**
	 * Performs one or more requests to GET items from the specified endpoint.
	 *
	 * @since 1.3.0
	 *
	 * @param string $path                 The request path.
	 * @param array  $options              The options to be added to the request query string.
	 * @param bool   $return_page_response Indicates if the full response for the page specific request should be immediately returned.
	 *
	 * @return array|WP_Error
	 */
	private function get_items( $path, $options = array(), $return_page_response = false ) {
		$response = $this->make_request( $path, $options );
		if ( $return_page_response || is_wp_error( $response ) ) {
			return $response;
		}

		$page  = 1;
		$items = rgar( $response, 'data', array() );

		if ( empty( $items ) || ! empty( $options['page'] ) || empty( $response['paging']['next'] ) ) {
			return $items;
		}

		while ( ! empty( $response['paging']['next'] ) ) {
			$options['page'] = ++$page;

			$response = $this->get_items( $path, $options, true );
			if ( is_wp_error( $response ) ) {
				gf_emailoctopus()->log_error( sprintf( '%s(): Unable to get page %d; %s', __METHOD__, $page, $response->get_error_message() ) );

				return $items;
			}

			$page_items = rgar( $response, 'data' );
			if ( empty( $page_items ) || ! is_array( $page_items ) ) {
				gf_emailoctopus()->log_error( sprintf( '%s(): Page %d is invalid.', __METHOD__, $page ) );

				return $items;
			}

			$items = array_merge( $items, $page_items );
		}

		gf_emailoctopus()->log_debug( sprintf( '%s(): Retrieved %d pages.', __METHOD__, $page ) );

		return $items;
	}

	/**
	 * Sends subscription data to EmailOctopus.
	 *
	 * @since 1.0.0
	 *
	 * @param string $list_id        EmailOctopus List ID.
	 * @param string $email_address The Email address to add as a contact.
	 * @param array  $merge_vars     EmailOctopus merge variables.
	 *
	 * @return array|WP_Error EmailOctopus Subscription Response.
	 */
	public function create_contact( $list_id, $email_address, $merge_vars ) {
		return $this->make_request(
			sprintf( 'lists/%s/contacts', $list_id ),
			array(
				'body' => array(
					'fields'        => $merge_vars,
					'email_address' => $email_address,
				),
			),
			'POST'
		);
	}
}
