<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cptui_edit_plugin_list_links( $links ) {

	if ( is_array( $links ) && isset( $links['edit'] ) ) {
		// We shouldn't encourage editing our plugin directly.
		unset( $links['edit'] );
	}

	// Add our custom links to the returned array value.
	return array_merge(
		array(
			'<a href="' . admin_url( 'admin.php?page=cptui_main_menu' ) . '">' . esc_html__( 'About', 'custom-post-type-ui' ) . '</a>',
			'<a href="' . admin_url( 'admin.php?page=cptui_support' ) . '">' . esc_html__( 'Help', 'custom-post-type-ui' ) . '</a>',
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) ) . '/custom-post-type-ui.php', 'cptui_edit_plugin_list_links' );

function cptui_menu_icon() {
	return 'dashicons-forms';
}


function get_disp_boolean( $bool_text ) {
	$bool_text = (string) $bool_text;
	if ( empty( $bool_text ) || '0' === $bool_text || 'false' === $bool_text ) {
		return false;
	}

	return true;
}

function disp_boolean( $bool_text ) {
	$bool_text = (string) $bool_text;
	if ( empty( $bool_text ) || '0' === $bool_text || 'false' === $bool_text ) {
		return 'false';
	}

	return 'true';
}


function cptui_get_current_action() {
	$current_action = '';
	if ( ! empty( $_GET ) && isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$current_action .= esc_textarea( wp_unslash( $_GET['action'] ) ); // phpcs:ignore
	}

	return $current_action;
}

function cptui_get_post_type_slugs() {
	$post_types = get_option( 'cptui_post_types' );
	if ( ! empty( $post_types ) ) {
		return array_keys( $post_types );
	}
	return [];
}

function cptui_get_taxonomy_slugs() {
	$taxonomies = get_option( 'cptui_taxonomies' );
	if ( ! empty( $taxonomies ) ) {
		return array_keys( $taxonomies );
	}
	return [];
}

function cptui_admin_url( $path ) {
	if ( is_multisite() && is_network_admin() ) {
		return network_admin_url( $path );
	}

	return admin_url( $path );
}

function cptui_get_post_form_action( $ui = '' ) {
	/**
	 * Filters the string to be used in an `action=""` attribute.
	 *
	 * @since 1.3.0
	 */
	return apply_filters( 'cptui_post_form_action', '', $ui );
}


function cptui_post_form_action( $ui ) {
	echo esc_attr( cptui_get_post_form_action( $ui ) );
}


function cptui_get_post_type_data() {
	return apply_filters( 'cptui_get_post_type_data', get_option( 'cptui_post_types', [] ), get_current_blog_id() );
}

function cptui_get_post_type_exists( $slug = '', $data = [] ) {

	/**
	 * Filters the boolean value for if a post type exists for 3rd parties.
	 *
	 * @since 1.3.0
	 *
	 * @param string       $slug Post type slug to check.
	 * @param array|string $data Post type data being utilized.
	 */
	return apply_filters( 'cptui_get_post_type_exists', post_type_exists( $slug ), $data );
}

function enqueue_email_octopus_assets() {

	$current_screen = get_current_screen();

	if ( ! is_object( $current_screen ) ) {
		return;
	}

	$screens = [
		'toplevel_page_cptui_main_menu',
		'cpt-ui_page_cptui_manage_post_types',
		'cpt-ui_page_cptui_manage_taxonomies',
	];

	if ( ! in_array( $current_screen->base, $screens, true ) ) {
		return;
	}

	if ( ! has_action( 'cptui_below_post_type_tab_menu' ) || ! has_action( 'cptui_below_taxonomy_tab_menu' ) ) {
		return;
	}

	wp_enqueue_style( 'cptui-emailoctopus', 'https://emailoctopus.com/bundles/emailoctopuslist/css/formEmbed.css' ); // phpcs:ignore

	wp_enqueue_script( 'cptui-emailoctopus-js', 'https://emailoctopus.com/bundles/emailoctopuslist/js/1.4/formEmbed.js', [ 'jquery' ], '', true ); // phpcs:ignore

}
add_action( 'admin_enqueue_scripts', 'enqueue_email_octopus_assets' );


function cptui_admin_notices_helper( $message = '', $success = true ) {

	$class   = [];
	$class[] = $success ? 'updated' : 'error';
	$class[] = 'notice is-dismissible';

	$messagewrapstart = '<div id="message" class="' . implode( ' ', $class ) . '"><p>';

	$messagewrapend = '</p></div>';

	$action = '';

	return apply_filters( 'cptui_admin_notice', $messagewrapstart . $message . $messagewrapend, $action, $message, $messagewrapstart, $messagewrapend );
}

function cptui_get_object_from_post_global() {
	if ( isset( $_POST['cpt_custom_post_type']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$type_item = filter_input( INPUT_POST, 'cpt_custom_post_type', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( $type_item ) {
			return sanitize_text_field( $type_item['name'] );
		}
	}

	if ( isset( $_POST['cpt_custom_tax']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$tax_item = filter_input( INPUT_POST, 'cpt_custom_tax', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		if ( $tax_item ) {
			return sanitize_text_field( $tax_item['name'] );
		}
	}

	return esc_html__( 'Object', 'custom-post-type-ui' );
}

function cptui_add_success_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			esc_html__( '%s has been successfully added', 'custom-post-type-ui' ),
			cptui_get_object_from_post_global()
		)
	);
}

function cptui_add_fail_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			esc_html__( '%s has failed to be added', 'custom-post-type-ui' ),
			cptui_get_object_from_post_global()
		),
		false
	);
}

function cptui_update_success_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			esc_html__( '%s has been successfully updated', 'custom-post-type-ui' ),
			cptui_get_object_from_post_global()
		)
	);
}

function cptui_update_fail_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			esc_html__( '%s has failed to be updated', 'custom-post-type-ui' ),
			cptui_get_object_from_post_global()
		),
		false
	);
}

function cptui_delete_success_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			esc_html__( '%s has been successfully deleted', 'custom-post-type-ui' ),
			cptui_get_object_from_post_global()
		)
	);
}

function cptui_delete_fail_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			esc_html__( '%s has failed to be deleted', 'custom-post-type-ui' ),
			cptui_get_object_from_post_global()
		),
		false
	);
}

function cptui_import_success_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'Successfully imported data.', 'custom-post-type-ui' )
	);
}

function cptui_import_fail_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'Invalid data provided', 'custom-post-type-ui' ),
		false
	);
}

function cptui_nonce_fail_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'Nonce failed verification', 'custom-post-type-ui' ),
		false
	);
}

function cptui_slug_matches_post_type() {
	return sprintf(
		/* translators: Placeholders are just for HTML markup that doesn't need translated */
		esc_html__( 'Please choose a different post type name. %s is already registered.', 'custom-post-type-ui' ),
		cptui_get_object_from_post_global()
	);
}

function cptui_slug_matches_page() {
	$slug         = cptui_get_object_from_post_global();
	$matched_slug = get_page_by_path(
		cptui_get_object_from_post_global()
	);
	if ( $matched_slug instanceof WP_Post ) {
		$slug = sprintf(
			/* translators: Placeholders are just for HTML markup that doesn't need translated */
			'<a href="%s">%s</a>',
			get_edit_post_link( $matched_slug->ID ),
			cptui_get_object_from_post_global()
		);
	}

	return sprintf(
		/* translators: Placeholders are just for HTML markup that doesn't need translated */
		esc_html__( 'Please choose a different post type name. %s matches an existing page slug, which can cause conflicts.', 'custom-post-type-ui' ),
		$slug
	);
}

function cptui_slug_has_quotes() {
	return sprintf(
		esc_html__( 'Please do not use quotes in post type/taxonomy names or rewrite slugs', 'custom-post-type-ui' ),
		cptui_get_object_from_post_global()
	);
}

function cptui_error_admin_notice() {
	echo cptui_admin_notices_helper( // phpcs:ignore WordPress.Security.EscapeOutput
		apply_filters( 'cptui_custom_error_message', '' ),
		false
	);
}

function cptui_not_new_install( $wp_upgrader, $extras ) {

	if ( $wp_upgrader instanceof \Plugin_Upgrader ) {
		return;
	}

	if ( ! array_key_exists( 'plugins', $extras ) || ! is_array( $extras['plugins'] ) ) {
		return;
	}


	if ( ! in_array( 'custom-post-type-ui/custom-post-type-ui.php', $extras['plugins'], true ) ) {
		return;
	}

	
	if ( cptui_is_new_install() ) {
		return;
	}

	
	cptui_set_not_new_install();
}
add_action( 'upgrader_process_complete', 'cptui_not_new_install', 10, 2 );

function cptui_is_new_install() {
	$new_or_not = true;
	$saved      = get_option( 'cptui_new_install', '' );

	if ( 'false' === $saved ) {
		$new_or_not = false;
	}

	return (bool) apply_filters( 'cptui_is_new_install', $new_or_not );
}

function cptui_set_not_new_install() {
	update_option( 'cptui_new_install', 'false' );
}

function cptui_get_cptui_post_type_object( $post_type = '' ) {
	$post_types = get_option( 'cptui_post_types', [] );

	if ( is_array( $post_types ) && array_key_exists( $post_type, $post_types ) ) {
		return $post_types[ $post_type ];
	}
	return [];
}

function cptui_post_type_supports( $post_type, $feature ) {

	$object = cptui_get_cptui_post_type_object( $post_type );

	if ( ! empty( $object ) ) {
		if ( array_key_exists( $feature, $object ) && ! empty( $object[ $feature ] ) ) {
			return true;
		}

		return false;
	}

	return false;
}

function cptui_published_post_format_fix( $post_types ) {
	if ( empty( $post_types ) || ! is_array( $post_types ) ) {
		return;
	}

	foreach ( $post_types as $type ) {
		if ( ! is_array( $type['supports'] ) ) {
			continue;
		}

		if ( in_array( 'post-formats', $type['supports'], true ) ) {
			add_post_type_support( $type['name'], 'post-formats' );
			register_taxonomy_for_object_type( 'post_format', $type['name'] );
		}
	}
}
add_action( 'cptui_post_register_post_types', 'cptui_published_post_format_fix' );

function cptui_get_add_new_link( $content_type = '' ) {
	if ( ! in_array( $content_type, [ 'post_types', 'taxonomies' ], true ) ) {
		return cptui_admin_url( 'admin.php?page=cptui_manage_post_types' );
	}

	return cptui_admin_url( 'admin.php?page=cptui_manage_' . $content_type );
}
