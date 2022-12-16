<?php
/**
 * My Custom Post Type Plugin


/**
 * Plugin Name: My Custom Post Type Plugin


 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}
error_reporting(0);

function cptui_load_ui_class() {
require_once plugin_dir_path( __FILE__ ) . 'classes/class.my_custom_post_type.php';
}
add_action( 'init', 'cptui_load_ui_class' );
function cptui_activation_redirect() {
if ( is_network_admin() ) {
return;
}
set_transient( 'cptui_activation_redirect', true, 30 );
}
add_action( 'activate_' . plugin_basename( __FILE__ ), 'cptui_activation_redirect' );
function cptui_make_activation_redirect() {
if ( ! get_transient( 'cptui_activation_redirect' ) ) {
return;
}
delete_transient( 'cptui_activation_redirect' );
if ( is_network_admin() ) {
return;
}

wp_safe_redirect(
add_query_arg(
[ 'page' => 'cptui_main_menu' ],
cptui_admin_url( 'admin.php?page=cptui_main_menu' )
)
);
}

add_action( 'plugins_loaded', 'cptui_load_textdomain' );
function cptui_plugin_menu() {
$capability  = apply_filters( 'cptui_required_capabilities', 'manage_options' );
$parent_slug = 'cptui_main_menu';
add_menu_page( __( 'Custom Post Types', 'custom-post-type-ui' ), __( 'My Custom Post Type ', 'custom-post-type-ui' ), $capability, $parent_slug, 'cptui_settings', cptui_menu_icon() );
add_submenu_page( $parent_slug, __( 'Add/Edit Post Types', 'custom-post-type-ui' ), __( 'Add/Edit Post Types', 'custom-post-type-ui' ), $capability, 'cptui_manage_post_types', 'cptui_manage_post_types' );
add_submenu_page( $parent_slug, __( 'Registered Types and Taxes', 'custom-post-type-ui' ), __( 'Registered Post Types', 'custom-post-type-ui' ), $capability, 'cptui_listings', 'cptui_listings' );
do_action( 'cptui_extra_menu_items', $parent_slug, $capability );
remove_submenu_page( $parent_slug, 'cptui_main_menu' );
}
add_action( 'admin_menu', 'cptui_plugin_menu' );
function cptui_loaded() {
if ( class_exists( 'WPGraphQL' ) ) {
require_once plugin_dir_path( __FILE__ ) . 'external/wpgraphql.php';
}
do_action( 'cptui_loaded' );
}
add_action( 'plugins_loaded', 'cptui_loaded' );
function cptui_create_submenus() {
require_once plugin_dir_path( __FILE__ ) . 'inc/utility.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/post-types.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/listings.php';
}
add_action( 'cptui_loaded', 'cptui_create_submenus' );
function cptui_init() {
do_action( 'cptui_init' );
}
add_action( 'init', 'cptui_init' );
function cptui_add_styles() {
if ( wp_doing_ajax() ) {
return;
}
$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
wp_register_script( 'cptui', plugins_url( "build/cptui-scripts{$min}.js", __FILE__ ), [ 'jquery', 'jquery-ui-dialog', 'postbox' ], CPTUI_VERSION, true );
wp_register_script( 'dashicons-picker', plugins_url( "build/dashicons-picker{$min}.js", __FILE__ ), [ 'jquery'], '1.0.0', true );
wp_register_style( 'cptui-css', plugins_url( "build/cptui-styles{$min}.css", __FILE__ ), [ 'wp-jquery-ui-dialog' ], CPTUI_VERSION );
}
add_action( 'admin_enqueue_scripts', 'cptui_add_styles' );
function cptui_create_custom_post_types() {
$cpts = get_option( 'cptui_post_types', [] );
$cpts_override = apply_filters( 'cptui_post_types_override', [] );
if ( empty( $cpts ) && empty( $cpts_override ) ) {
return;
}
if ( is_array( $cpts_override ) && ! empty( $cpts_override ) ) {
$cpts = $cpts_override;
}
do_action( 'cptui_pre_register_post_types', $cpts );
if ( is_array( $cpts ) ) {
foreach ( $cpts as $post_type ) {
if ( (bool) apply_filters( "cptui_disable_{$post_type['name']}_cpt", false, $post_type ) ) {
continue;
}
if ( (bool) apply_filters( 'cptui_disable_cpt', false, $post_type ) ) {
continue;
}
cptui_register_single_post_type( $post_type );
}
}
do_action( 'cptui_post_register_post_types', $cpts );
}
add_action( 'init', 'cptui_create_custom_post_types', 10 ); // Leave on standard init for legacy purposes.
function cptui_register_single_post_type( $post_type = [] ) {
$post_type['map_meta_cap'] = apply_filters( 'cptui_map_meta_cap', true, $post_type['name'], $post_type );
if ( empty( $post_type['supports'] ) ) {
$post_type['supports'] = [];
}
$user_supports_params = apply_filters( 'cptui_user_supports_params', [], $post_type['name'], $post_type );
if ( is_array( $user_supports_params ) && ! empty( $user_supports_params ) ) {
if ( is_array( $post_type['supports'] ) ) {
$post_type['supports'] = array_merge( $post_type['supports'], $user_supports_params );
} else {
$post_type['supports'] = [ $user_supports_params ];
}
}
$yarpp = false; // Prevent notices.
if ( ! empty( $post_type['custom_supports'] ) ) {
$custom = explode( ',', $post_type['custom_supports'] );
foreach ( $custom as $part ) {
// We'll handle YARPP separately.
if ( in_array( $part, [ 'YARPP', 'yarpp' ], true ) ) {
$yarpp = true;
continue;
}
$post_type['supports'][] = trim( $part );
}
}
if ( isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) && in_array( 'none', $post_type['supports'], true ) ) {
$post_type['supports'] = false;
}
$labels = [
'name'          => $post_type['label'],
'singular_name' => $post_type['singular_label'],
];
$preserved        = cptui_get_preserved_keys( 'post_types' );
$preserved_labels = cptui_get_preserved_labels();
foreach ( $post_type['labels'] as $key => $label ) {
if ( ! empty( $label ) ) {
if ( 'parent' === $key ) {
$labels['parent_item_colon'] = $label;
} else {
$labels[ $key ] = $label;
}
} elseif ( empty( $label ) && in_array( $key, $preserved, true ) ) {
$singular_or_plural = ( in_array( $key, array_keys( $preserved_labels['post_types']['plural'] ) ) ) ? 'plural' : 'singular'; // phpcs:ignore.
$label_plurality    = ( 'plural' === $singular_or_plural ) ? $post_type['label'] : $post_type['singular_label'];
$labels[ $key ]     = sprintf( $preserved_labels['post_types'][ $singular_or_plural ][ $key ], $label_plurality );
}
}
$has_archive = isset( $post_type['has_archive'] ) ? get_disp_boolean( $post_type['has_archive'] ) : false;
if ( $has_archive && ! empty( $post_type['has_archive_string'] ) ) {
$has_archive = $post_type['has_archive_string'];
}
$show_in_menu = get_disp_boolean( $post_type['show_in_menu'] );
if ( ! empty( $post_type['show_in_menu_string'] ) ) {
$show_in_menu = $post_type['show_in_menu_string'];
}
$rewrite = get_disp_boolean( $post_type['rewrite'] );
if ( false !== $rewrite ) {
// Core converts to an empty array anyway, so safe to leave this instead of passing in boolean true.
$rewrite         = [];
$rewrite['slug'] = ! empty( $post_type['rewrite_slug'] ) ? $post_type['rewrite_slug'] : $post_type['name'];
$rewrite['with_front'] = true; // Default value.
if ( isset( $post_type['rewrite_withfront'] ) ) {
$rewrite['with_front'] = 'false' === disp_boolean( $post_type['rewrite_withfront'] ) ? false : true;
}
}
$menu_icon            = ! empty( $post_type['menu_icon'] ) ? $post_type['menu_icon'] : null;
$register_meta_box_cb = ! empty( $post_type['register_meta_box_cb'] ) ? $post_type['register_meta_box_cb'] : null;
if ( in_array( $post_type['query_var'], [ 'true', 'false', '0', '1' ], true ) ) {
$post_type['query_var'] = get_disp_boolean( $post_type['query_var'] );
}
if ( ! empty( $post_type['query_var_slug'] ) ) {
$post_type['query_var'] = $post_type['query_var_slug'];
}
$menu_position = null;
if ( ! empty( $post_type['menu_position'] ) ) {
$menu_position = (int) $post_type['menu_position'];
}
$delete_with_user = null;
if ( ! empty( $post_type['delete_with_user'] ) ) {
$delete_with_user = get_disp_boolean( $post_type['delete_with_user'] );
}
$capability_type = 'post';
if ( ! empty( $post_type['capability_type'] ) ) {
$capability_type = $post_type['capability_type'];
if ( false !== strpos( $post_type['capability_type'], ',' ) ) {
$caps = array_map( 'trim', explode( ',', $post_type['capability_type'] ) );
if ( count( $caps ) > 2 ) {
$caps = array_slice( $caps, 0, 2 );
}
$capability_type = $caps;
}
}
$public = get_disp_boolean( $post_type['public'] );
if ( ! empty( $post_type['exclude_from_search'] ) ) {
$exclude_from_search = get_disp_boolean( $post_type['exclude_from_search'] );
} else {
$exclude_from_search = false === $public;
}
$queryable = ( ! empty( $post_type['publicly_queryable'] ) && isset( $post_type['publicly_queryable'] ) ) ? get_disp_boolean( $post_type['publicly_queryable'] ) : $public;
if ( empty( $post_type['show_in_nav_menus'] ) ) {
// Defaults to value of public.
$post_type['show_in_nav_menus'] = $public;
}
if ( empty( $post_type['show_in_rest'] ) ) {
$post_type['show_in_rest'] = false;
}
$rest_base = null;
if ( ! empty( $post_type['rest_base'] ) ) {
$rest_base = $post_type['rest_base'];
}
$rest_controller_class = null;
if ( ! empty( $post_type['rest_controller_class'] ) ) {
$rest_controller_class = $post_type['rest_controller_class'];
}
$rest_namespace = null;
if ( ! empty( $post_type['rest_namespace'] ) ) {
$rest_namespace = $post_type['rest_namespace'];
}
$can_export = null;
if ( ! empty( $post_type['can_export'] ) ) {
$can_export = get_disp_boolean( $post_type['can_export'] );
}
$args = [
'labels'                => $labels,
'description'           => $post_type['description'],
'public'                => get_disp_boolean( $post_type['public'] ),
'publicly_queryable'    => $queryable,
'show_ui'               => get_disp_boolean( $post_type['show_ui'] ),
'show_in_nav_menus'     => get_disp_boolean( $post_type['show_in_nav_menus'] ),
'has_archive'           => $has_archive,
'show_in_menu'          => $show_in_menu,
'delete_with_user'      => $delete_with_user,
'show_in_rest'          => get_disp_boolean( $post_type['show_in_rest'] ),
'rest_base'             => $rest_base,
'rest_controller_class' => $rest_controller_class,
'rest_namespace'        => $rest_namespace,
'exclude_from_search'   => $exclude_from_search,
'capability_type'       => $capability_type,
'map_meta_cap'          => $post_type['map_meta_cap'],
'hierarchical'          => get_disp_boolean( $post_type['hierarchical'] ),
'can_export'            => $can_export,
'rewrite'               => $rewrite,
'menu_position'         => $menu_position,
'menu_icon'             => $menu_icon,
'register_meta_box_cb'  => $register_meta_box_cb,
'query_var'             => $post_type['query_var'],
'supports'              => $post_type['supports'],
'taxonomies'            => $post_type['taxonomies'],
];
if ( true === $yarpp ) {
$args['yarpp_support'] = $yarpp;
}
$args = apply_filters( 'cptui_pre_register_post_type', $args, $post_type['name'], $post_type );
return register_post_type( $post_type['name'], $args );
}

function cptui_register_single_taxonomy( $taxonomy = [] ) {
$labels = [
'name'          => $taxonomy['label'],
'singular_name' => $taxonomy['singular_label'],
];
$description = '';
if ( ! empty( $taxonomy['description'] ) ) {
$description = $taxonomy['description'];
}
$preserved        = cptui_get_preserved_keys( 'taxonomies' );
$preserved_labels = cptui_get_preserved_labels();
foreach ( $taxonomy['labels'] as $key => $label ) {
if ( ! empty( $label ) ) {
$labels[ $key ] = $label;
} elseif ( empty( $label ) && in_array( $key, $preserved, true ) ) {
$singular_or_plural = ( in_array( $key, array_keys( $preserved_labels['taxonomies']['plural'] ) ) ) ? 'plural' : 'singular'; // phpcs:ignore.
$label_plurality    = ( 'plural' === $singular_or_plural ) ? $taxonomy['label'] : $taxonomy['singular_label'];
$labels[ $key ]     = sprintf( $preserved_labels['taxonomies'][ $singular_or_plural ][ $key ], $label_plurality );
}
}

$show_admin_column = ( ! empty( $taxonomy['show_admin_column'] ) && false !== get_disp_boolean( $taxonomy['show_admin_column'] ) ) ? true : false;
$show_in_menu = ( ! empty( $taxonomy['show_in_menu'] ) && false !== get_disp_boolean( $taxonomy['show_in_menu'] ) ) ? true : false;
if ( empty( $taxonomy['show_in_menu'] ) ) {
$show_in_menu = get_disp_boolean( $taxonomy['show_ui'] );
}
$show_in_nav_menus = ( ! empty( $taxonomy['show_in_nav_menus'] ) && false !== get_disp_boolean( $taxonomy['show_in_nav_menus'] ) ) ? true : false;
if ( empty( $taxonomy['show_in_nav_menus'] ) ) {
$show_in_nav_menus = $public;
}



$args = [
'labels'                => $labels,
'label'                 => $taxonomy['label'],
'description'           => $description,
'public'                => $public,
'publicly_queryable'    => $publicly_queryable,
'hierarchical'          => get_disp_boolean( $taxonomy['hierarchical'] ),
'show_ui'               => get_disp_boolean( $taxonomy['show_ui'] ),
'show_in_menu'          => $show_in_menu,
'show_in_nav_menus'     => $show_in_nav_menus,
'show_tagcloud'         => $show_tagcloud,
'query_var'             => $taxonomy['query_var'],
'rewrite'               => $rewrite,
'show_admin_column'     => $show_admin_column,
'show_in_rest'          => $show_in_rest,
'rest_base'             => $rest_base,
'rest_controller_class' => $rest_controller_class,
'rest_namespace'        => $rest_namespace,
'show_in_quick_edit'    => $show_in_quick_edit,
'sort'                  => $sort,
'meta_box_cb'           => $meta_box_cb,
'default_term'          => $default_term,
];
$object_type = ! empty( $taxonomy['object_types'] ) ? $taxonomy['object_types'] : '';
$args = apply_filters( 'cptui_pre_register_taxonomy', $args, $taxonomy['name'], $taxonomy, $object_type );
return register_taxonomy( $taxonomy['name'], $object_type, $args );
}
function cptui_settings_tab_menu( $page = 'post_types' ) {
$tabs = (array) apply_filters( 'cptui_get_tabs', [], $page );
if ( empty( $tabs['page_title'] ) ) {
return '';
}
$tmpl = '
<h1>%s</h1>
<nav class="nav-tab-wrapper wp-clearfix" aria-label="Secondary menu">%s</nav>
';
$tab_output = '';
foreach ( $tabs['tabs'] as $tab ) {
$tab_output .= sprintf(
'<a class="%s" href="%s" aria-selected="%s">%s</a>',
implode( ' ', $tab['classes'] ),
$tab['url'],
$tab['aria-selected'],
$tab['text']
);
}
printf(
$tmpl, // phpcs:ignore.
$tabs['page_title'], // phpcs:ignore.
$tab_output // phpcs:ignore.
);
}
function cptui_convert_settings() {
if ( wp_doing_ajax() ) {
return;
}
$retval = '';
if ( false === get_option( 'cptui_post_types' ) && ( $post_types = get_option( 'cpt_custom_post_types' ) ) ) { // phpcs:ignore.
$new_post_types = [];
foreach ( $post_types as $type ) {
$new_post_types[ $type['name'] ]               = $type; // This one assigns the # indexes. Named arrays are our friend.
$new_post_types[ $type['name'] ]['supports']   = ! empty( $type[0] ) ? $type[0] : []; // Especially for multidimensional arrays.
$new_post_types[ $type['name'] ]['taxonomies'] = ! empty( $type[1] ) ? $type[1] : [];
$new_post_types[ $type['name'] ]['labels']     = ! empty( $type[2] ) ? $type[2] : [];
unset(
$new_post_types[ $type['name'] ][0],
$new_post_types[ $type['name'] ][1],
$new_post_types[ $type['name'] ][2]
); 
}
$retval = update_option( 'cptui_post_types', $new_post_types );
}

$retval = update_option( 'cptui_taxonomies', $new_taxonomies );
}
if ( ! empty( $retval ) ) {
flush_rewrite_rules();
}
return $retval;
add_action( 'admin_init', 'cptui_convert_settings' );
function cptui_admin_notices( $action = '', $object_type = '', $success = true, $custom = '' ) {
$class       = [];
$class[]     = $success ? 'updated' : 'error';
$class[]     = 'notice is-dismissible';
$object_type = esc_attr( $object_type );
$messagewrapstart = '
<div id="message" class="' . implode( ' ', $class ) . '">
   <p>';
      $message          = '';
      $messagewrapend = '
   </p>
</div>
';
if ( 'add' === $action ) {
if ( $success ) {
$message .= sprintf( __( '%s has been successfully added', 'custom-post-type-ui' ), $object_type );
} else {
$message .= sprintf( __( '%s has failed to be added', 'custom-post-type-ui' ), $object_type );
}
} elseif ( 'update' === $action ) {
if ( $success ) {
$message .= sprintf( __( '%s has been successfully updated', 'custom-post-type-ui' ), $object_type );
} else {
$message .= sprintf( __( '%s has failed to be updated', 'custom-post-type-ui' ), $object_type );
}
} elseif ( 'delete' === $action ) {
if ( $success ) {
$message .= sprintf( __( '%s has been successfully deleted', 'custom-post-type-ui' ), $object_type );
} else {
$message .= sprintf( __( '%s has failed to be deleted', 'custom-post-type-ui' ), $object_type );
}
} elseif ( 'error' === $action ) {
if ( ! empty( $custom ) ) {
$message = $custom;
}
}
if ( $message ) {
return apply_filters( 'cptui_admin_notice', $messagewrapstart . $message . $messagewrapend, $action, $message, $messagewrapstart, $messagewrapend );
}
return false;
}
function cptui_get_preserved_keys( $type = '' ) {
$preserved_labels = [
'post_types' => [
'add_new_item',
'edit_item',
'new_item',
'view_item',
'view_items',
'all_items',
'search_items',
'not_found',
'not_found_in_trash',
],
'taxonomies' => [
'search_items',
'popular_items',
'all_items',
'parent_item',
'parent_item_colon',
'edit_item',
'update_item',
'add_new_item',
'new_item_name',
'separate_items_with_commas',
'add_or_remove_items',
'choose_from_most_used',
],
];
return ! empty( $type ) ? $preserved_labels[ $type ] : [];
}
function cptui_get_preserved_label( $type = '', $key = '', $plural = '', $singular = '' ) {
$preserved_labels = [
'post_types' => [
'add_new_item'       => sprintf( __( 'Add new %s', 'custom-post-type-ui' ), $singular ),
'edit_item'          => sprintf( __( 'Edit %s', 'custom-post-type-ui' ), $singular ),
'new_item'           => sprintf( __( 'New %s', 'custom-post-type-ui' ), $singular ),
'view_item'          => sprintf( __( 'View %s', 'custom-post-type-ui' ), $singular ),
'view_items'         => sprintf( __( 'View %s', 'custom-post-type-ui' ), $plural ),
'all_items'          => sprintf( __( 'All %s', 'custom-post-type-ui' ), $plural ),
'search_items'       => sprintf( __( 'Search %s', 'custom-post-type-ui' ), $plural ),
'not_found'          => sprintf( __( 'No %s found.', 'custom-post-type-ui' ), $plural ),
'not_found_in_trash' => sprintf( __( 'No %s found in trash.', 'custom-post-type-ui' ), $plural ),
],
'taxonomies' => [
'search_items'               => sprintf( __( 'Search %s', 'custom-post-type-ui' ), $plural ),
'popular_items'              => sprintf( __( 'Popular %s', 'custom-post-type-ui' ), $plural ),
'all_items'                  => sprintf( __( 'All %s', 'custom-post-type-ui' ), $plural ),
'parent_item'                => sprintf( __( 'Parent %s', 'custom-post-type-ui' ), $singular ),
'parent_item_colon'          => sprintf( __( 'Parent %s:', 'custom-post-type-ui' ), $singular ),
'edit_item'                  => sprintf( __( 'Edit %s', 'custom-post-type-ui' ), $singular ),
'update_item'                => sprintf( __( 'Update %s', 'custom-post-type-ui' ), $singular ),
'add_new_item'               => sprintf( __( 'Add new %s', 'custom-post-type-ui' ), $singular ),
'new_item_name'              => sprintf( __( 'New %s name', 'custom-post-type-ui' ), $singular ),
'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'custom-post-type-ui' ), $plural ),
'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'custom-post-type-ui' ), $plural ),
'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'custom-post-type-ui' ), $plural ),
],
];
return $preserved_labels[ $type ][ $key ];
}
function cptui_get_preserved_labels() {
return [
'post_types' => [
'singular' => [
'add_new_item' => __( 'Add new %s', 'custom-post-type-ui' ),
'edit_item'    => __( 'Edit %s', 'custom-post-type-ui' ),
'new_item'     => __( 'New %s', 'custom-post-type-ui' ),
'view_item'    => __( 'View %s', 'custom-post-type-ui' ),
],
'plural'   => [
'view_items'         => __( 'View %s', 'custom-post-type-ui' ),
'all_items'          => __( 'All %s', 'custom-post-type-ui' ),
'search_items'       => __( 'Search %s', 'custom-post-type-ui' ),
'not_found'          => __( 'No %s found.', 'custom-post-type-ui' ),
'not_found_in_trash' => __( 'No %s found in trash.', 'custom-post-type-ui' ),
],
],
'taxonomies' => [
'singular' => [
'parent_item'       => __( 'Parent %s', 'custom-post-type-ui' ),
'parent_item_colon' => __( 'Parent %s:', 'custom-post-type-ui' ),
'edit_item'         => __( 'Edit %s', 'custom-post-type-ui' ),
'update_item'       => __( 'Update %s', 'custom-post-type-ui' ),
'add_new_item'      => __( 'Add new %s', 'custom-post-type-ui' ),
'new_item_name'     => __( 'New %s name', 'custom-post-type-ui' ),
],
'plural'   => [
'search_items'               => __( 'Search %s', 'custom-post-type-ui' ),
'popular_items'              => __( 'Popular %s', 'custom-post-type-ui' ),
'all_items'                  => __( 'All %s', 'custom-post-type-ui' ),
'separate_items_with_commas' => __( 'Separate %s with commas', 'custom-post-type-ui' ),
'add_or_remove_items'        => __( 'Add or remove %s', 'custom-post-type-ui' ),
'choose_from_most_used'      => __( 'Choose from the most used %s', 'custom-post-type-ui' ),
],
],
];
}