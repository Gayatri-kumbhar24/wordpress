<?php
/**
 * Custom Post Type UI Post Type Settings.
 *
 * @package CPTUI
 * @subpackage PostTypes
 * @author WebDevStudios
 * @since 1.0.0
 * @license GPL-2.0+
 */

// phpcs:disable WebDevStudios.All.RequireAuthor

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add our cptui.js file, with dependencies on jQuery and jQuery UI.
 *
 * @since 1.0.0
 *
 * @internal
 */
function cptui_post_type_enqueue_scripts() {

	$current_screen = get_current_screen();

	if ( ! is_object( $current_screen ) || 'cpt-ui_page_cptui_manage_post_types' !== $current_screen->base ) {
		return;
	}

	if ( wp_doing_ajax() ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'cptui' );
	wp_enqueue_script( 'dashicons-picker' );
	wp_enqueue_style( 'cptui-css' );

	$core                  = get_post_types( [ '_builtin' => true ] );
	$public                = get_post_types(
		[
			'_builtin' => false,
			'public'   => true,
		]
	);
	$private               = get_post_types(
		[
			'_builtin' => false,
			'public'   => false,
		]
	);
	$registered_post_types = array_merge( $core, $public, $private );

	wp_localize_script(
		'cptui',
		'cptui_type_data',
		[
			'confirm'             => esc_html__( 'Are you sure you want to delete this? Deleting will NOT remove created content.', 'custom-post-type-ui' ),
			'existing_post_types' => $registered_post_types,
		]
	);
}
add_action( 'admin_enqueue_scripts', 'cptui_post_type_enqueue_scripts' );

/**
 * Register our tabs for the Post Type screen.
 *
 * @since 1.3.0
 *
 * @internal
 *
 * @param array  $tabs         Array of tabs to display. Optional.
 * @param string $current_page Current page being shown. Optional. Default empty string.
 * @return array Amended array of tabs to show.
 */
function cptui_post_type_tabs( $tabs = [], $current_page = '' ) {

	if ( 'post_types' === $current_page ) {
		$post_types = cptui_get_post_type_data();
		$classes    = [ 'nav-tab' ];

		$tabs['page_title'] = get_admin_page_title();
		$tabs['tabs']       = [];
		// Start out with our basic "Add new" tab.
		$tabs['tabs']['add'] = [
			'text'          => __( 'Add New Post Type', 'custom-post-type-ui' ),
			'classes'       => $classes,
			'url'           => cptui_admin_url( 'admin.php?page=cptui_manage_' . $current_page ),
			'aria-selected' => 'false',
		];

		$action = cptui_get_current_action();
		if ( empty( $action ) ) {
			$tabs['tabs']['add']['classes'][]     = 'nav-tab-active';
			$tabs['tabs']['add']['aria-selected'] = 'true';
		}

		if ( ! empty( $post_types ) ) {

			if ( ! empty( $action ) ) {
				$classes[] = 'nav-tab-active';
			}
			$tabs['tabs']['edit'] = [
				'text'          => __( 'Edit Post Types', 'custom-post-type-ui' ),
				'classes'       => $classes,
				'url'           => esc_url( add_query_arg( [ 'action' => 'edit' ], cptui_admin_url( 'admin.php?page=cptui_manage_' . $current_page ) ) ),
				'aria-selected' => ! empty( $action ) ? 'true' : 'false',
			];

			$tabs['tabs']['view'] = [
				'text'          => __( 'View Post Types', 'custom-post-type-ui' ),
				'classes'       => [ 'nav-tab' ], // Prevent notices.
				'url'           => esc_url( cptui_admin_url( 'admin.php?page=cptui_listings#post-types' ) ),
				'aria-selected' => 'false',
			];

			
		}
	}

	return $tabs;
}
add_filter( 'cptui_get_tabs', 'cptui_post_type_tabs', 10, 2 );

/**
 * Create our settings page output.
 *
 * @since 1.0.0
 *
 * @internal
 */
function cptui_manage_post_types() {

	$tab       = ( ! empty( $_GET ) && ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) ? 'edit' : 'new'; // phpcs:ignore.
	$tab_class = 'cptui-' . $tab;
	$current   = null;
	?>

	<div class="wrap <?php echo esc_attr( $tab_class ); ?>">

	<?php
	/**
	 * Fires right inside the wrap div for the post type editor screen.
	 *
	 * @since 1.3.0
	 */
	do_action( 'cptui_inside_post_type_wrap' );

	/**
	 * Filters whether or not a post type was deleted.
	 *
	 * @since 1.4.0
	 *
	 * @param bool $value Whether or not post type deleted. Default false.
	 */
	$post_type_deleted = apply_filters( 'cptui_post_type_deleted', false );

	cptui_settings_tab_menu();

	/**
	 * Fires below the output for the tab menu on the post type add/edit screen.
	 *
	 * @since 1.3.0
	 */
	do_action( 'cptui_below_post_type_tab_menu' );

	if ( 'edit' === $tab ) {

		$post_types = cptui_get_post_type_data();

		$selected_post_type = cptui_get_current_post_type( $post_type_deleted );

		if ( $selected_post_type && array_key_exists( $selected_post_type, $post_types ) ) {
			$current = $post_types[ $selected_post_type ];
		}
	}

	$ui = new my_custom_post_type();

	// Will only be set if we're already on the edit screen.
	if ( ! empty( $post_types ) ) {
		?>
		<form id="cptui_select_post_type" method="post" action="<?php echo esc_url( cptui_get_post_form_action( $ui ) ); ?>" style="padding-top:20px;">
			<label for="post_type" ><?php esc_html_e( 'Select: ', 'custom-post-type-ui' ); ?></label>
			<?php
			cptui_post_types_dropdown( $post_types );

			wp_nonce_field( 'cptui_select_post_type_nonce_action', 'cptui_select_post_type_nonce_field' );

			/**
			 * Filters the text value to use on the select post type button.
			 *
			 * @since 1.0.0
			 *
			 * @param string $value Text to use for the button.
			 */
			?>
			<input type="submit" class="button-secondary" id="cptui_select_post_type_submit" name="cptui_select_post_type_submit" value="<?php echo esc_attr( apply_filters( 'cptui_post_type_submit_select', __( 'Select', 'custom-post-type-ui' ) ) ); ?>" />
		</form>
		<?php

		/**
		 * Fires below the post type select input.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Current post type selected.
		 */
		do_action( 'cptui_below_post_type_select', $current['name'] );
	}
	?>

	<form class="posttypesui" method="post" action="<?php echo esc_url( cptui_get_post_form_action( $ui ) ); ?>">
		<div class="postbox-container">
		<div id="poststuff">
			<div class="cptui-section postbox">
				
				<div class="inside">
					<div class="main">
						<table class="form-table cptui-table">
						<?php
						echo $ui->get_text_input( // phpcs:ignore.
							[
								'namearray' => 'cpt_custom_post_type',
								'name'      => 'label',
								'textvalue' => isset( $current['label'] ) ? esc_attr( $current['label'] ) : '', // phpcs:ignore.
								'labeltext' => esc_html__( 'Name', 'custom-post-type-ui' ),
								'aftertext' => esc_html__( '(e.g. Movies)', 'custom-post-type-ui' ),
								
								'required'  => true,
							]
						);

						echo $ui->get_tr_start() . $ui->get_th_start(); // phpcs:ignore.
						echo $ui->get_label( 'name', esc_html__( 'Slug', 'custom-post-type-ui' ) ); // phpcs:ignore.
						echo $ui->get_required_span(); // phpcs:ignore.

						if ( 'edit' === $tab ) {
							
						}
						

						echo $ui->get_th_end() . $ui->get_td_start(); // phpcs:ignore.

						echo $ui->get_text_input( // phpcs:ignore.
							[
								'namearray'   => 'cpt_custom_post_type',
								'name'        => 'name',
								'textvalue'   => isset( $current['name'] ) ? esc_attr( $current['name'] ) : '', // phpcs:ignore.
								'maxlength'   => '20',
								'helptext'    => esc_html__( 'The post type name/slug.', 'custom-post-type-ui' ),
								'required'    => true,
								'placeholder' => false,
								'wrap'        => false,
							]
						);
					
						if ( 'edit' === $tab ) {
							

							echo '<div class="cptui-spacer">';
							
							echo '</div>';
						}

						echo $ui->get_td_end(); echo $ui->get_tr_end(); // phpcs:ignore.

						
						

						?>
							<a href="#" id="auto-populate"><?php echo esc_html( $link_text ); ?></a> 
							
								
						</table>
						<p class="submit">
						<?php
						wp_nonce_field( 'cptui_addedit_post_type_nonce_action', 'cptui_addedit_post_type_nonce_field' );
						if ( ! empty( $_GET ) && ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) { // phpcs:ignore.
							?>
							<?php

							/**
							 * Filters the text value to use on the button when editing.
							 *
							 * @since 1.0.0
							 *
							 * @param string $value Text to use for the button.
							 */
							?>
						
						<?php } else { ?>
							<?php

							/**
							 * Filters the text value to use on the button when adding.
							 *
							 * @since 1.0.0
							 *
							 * @param string $value Text to use for the button.
							 */
							?>
						
							<?php
						}

						if ( ! empty( $current ) ) {
							?>
							<input type="hidden" name="cpt_original" id="cpt_original" value="<?php echo esc_attr( $current['name'] ); ?>" />
							<?php
						}

						// Used to check and see if we should prevent duplicate slugs.
						?>
						<input type="hidden" name="cpt_type_status" id="cpt_type_status" value="<?php echo esc_attr( $tab ); ?>" />
						</p>
					</div>
				</div>
			</div>
			
				
			<div class="cptui-section cptui-settings postbox">
				
				<div class="inside">
					<div class="main">
					
						<table style="display:none;">	<?php
							
							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];

							$selected           = isset( $current ) ? disp_boolean( $current['public'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['public'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'public',
									'labeltext'  => esc_html__( 'Public', 'custom-post-type-ui' ),
									'aftertext'  => esc_html__( '(Custom Post Type UI default: true) Whether or not posts of this type should be shown in the admin UI and is publicly queryable.', 'custom-post-type-ui' ),
									'selections' => $select, // phpcs:ignore.
								]
							);

							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];

							$selected           = isset( $current ) && ! empty( $current['publicly_queryable'] ) ? disp_boolean( $current['publicly_queryable'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['publicly_queryable'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'publicly_queryable',
									'labeltext'  => esc_html__( 'Publicly Queryable', 'custom-post-type-ui' ),
									'aftertext'  => esc_html__( '(default: true) Whether or not queries can be performed on the front end as part of parse_request()', 'custom-post-type-ui' ),
									'selections' => $select, // phpcs:ignore.
								]
							);

							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];

							$selected           = isset( $current ) ? disp_boolean( $current['show_ui'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['show_ui'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'show_ui',
									'labeltext'  => esc_html__( 'Show UI', 'custom-post-type-ui' ),
									'aftertext'  => esc_html__( '(default: true) Whether or not to generate a default UI for managing this post type.', 'custom-post-type-ui' ),
									'selections' => $select, // phpcs:ignore.
								]
							);
							

							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];

							$selected           = isset( $current ) && ! empty( $current['show_in_nav_menus'] ) ? disp_boolean( $current['show_in_nav_menus'] ) : '';
							$select['selected'] = ( ! empty( $selected ) && ! empty( $current['show_in_nav_menus'] ) ) ? $current['show_in_nav_menus'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'show_in_nav_menus',
									'labeltext'  => esc_html__( 'Show in Nav Menus', 'custom-post-type-ui' ),
								
									'selections' => $select, // phpcs:ignore.
								]
							);

							$select = [
								'options' => [
									[
										'attr'    => '0',
										'text'    => esc_attr__( 'False', 'custom-post-type-ui' ),
										'default' => 'false',
									],
									[
										'attr' => '1',
										'text' => esc_attr__( 'True', 'custom-post-type-ui' ),
									],
								],
							];

							$selected           = ( isset( $current ) && ! empty( $current['delete_with_user'] ) ) ? disp_boolean( $current['delete_with_user'] ) : '';
							$select['selected'] = ( ! empty( $selected ) && ! empty( $current['delete_with_user'] ) ) ? $current['delete_with_user'] : '';
						

							
							echo $ui->get_th_end() . $ui->get_td_start(); // phpcs:ignore.
							
							echo $ui->get_td_end() . $ui->get_tr_end(); // phpcs:ignore.

							echo $ui->get_tr_start() . $ui->get_th_start(); // phpcs:ignore.
							echo $ui->get_label( 'show_in_menu', esc_html__( 'Show in Menu', 'custom-post-type-ui' ) ); // phpcs:ignore.
							
							echo $ui->get_th_end() . $ui->get_td_start(); // phpcs:ignore.

							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];

							$selected           = isset( $current ) ? disp_boolean( $current['show_in_menu'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['show_in_menu'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'show_in_menu',
									
									'selections' => $select, // phpcs:ignore.
									'wrap'       => false,
								]
							);

							echo '<br/>';

							
							echo $ui->get_td_end() . $ui->get_tr_end(); // phpcs:ignore.

							echo $ui->get_tr_start() . $ui->get_th_start(); // phpcs:ignore.

					
?></table>	<table class="form-table cptui-table">
<?php
							

							echo $ui->get_tr_start() . $ui->get_th_start() . esc_html__( 'Supports', 'custom-post-type-ui' ); // phpcs:ignore.

							

							echo $ui->get_th_end() . $ui->get_td_start() . $ui->get_fieldset_start(); // phpcs:ignore.

							echo $ui->get_legend_start() . esc_html__( 'Post type options', 'custom-post-type-ui' ) . $ui->get_legend_end(); // phpcs:ignore.

							$title_checked = ( ! empty( $current['supports'] ) && is_array( $current['supports'] ) && in_array( 'title', $current['supports'] ) ) ? 'true' : 'false'; // phpcs:ignore.
							if ( 'new' === $tab ) {
								$title_checked = 'true';
							}
							echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'title',
									'checked'    => $title_checked, // phpcs:ignore.
									'name'       => 'title',
									'namearray'  => 'cpt_supports',
									'textvalue'  => 'title',
									'labeltext'  => esc_html__( 'Title', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);

							$editor_checked = ( ! empty( $current['supports'] ) && is_array( $current['supports'] ) && in_array( 'editor', $current['supports'] ) ) ? 'true' : 'false'; // phpcs:ignore.
							if ( 'new' === $tab ) {
								$editor_checked = 'true';
							}
							echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'editor',
									'checked'    => $editor_checked, // phpcs:ignore.
									'name'       => 'editor',
									'namearray'  => 'cpt_supports',
									'textvalue'  => 'editor',
									'labeltext'  => esc_html__( 'Editor', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);

							$thumb_checked = ( ! empty( $current['supports'] ) && is_array( $current['supports'] ) && in_array( 'thumbnail', $current['supports'] ) ) ? 'true' : 'false'; // phpcs:ignore.
							if ( 'new' === $tab ) {
								$thumb_checked = 'true';
							}
							echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'thumbnail',
									'checked'    => $thumb_checked, // phpcs:ignore.
									'name'       => 'thumbnail',
									'namearray'  => 'cpt_supports',
									'textvalue'  => 'thumbnail',
									'labeltext'  => esc_html__( 'Featured Image', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);

							echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'custom-fields',
									'checked'    => ( ! empty( $current['supports'] ) && is_array( $current['supports'] ) && in_array( 'custom-fields', $current['supports'] ) ) ? 'true' : 'false', // phpcs:ignore.
									'name'       => 'custom-fields',
									'namearray'  => 'cpt_supports',
									'textvalue'  => 'custom-fields',
									'labeltext'  => esc_html__( 'Custom Fields', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);
							
								global $wpdb;
  $dr="SELECT * FROM `wp_post_type` where post_type='".$current['name']."'  order by id DESC limit 1";
	   $result441=$wpdb->get_results($dr);

  $mm= $result441[0]->meta_key;
	$myArray = explode(',', $mm);
					
				
					if(in_array('g5blog_format_audio_embed',$myArray))
					{
						
						$bb1='true';
					}
					else
					{
						
					$bb1='false';	
						
					}
					if(in_array('g5blog_single_post_layout',$myArray))
					{
						
						$bb2='true';
					}
					else
					{
						
					$bb2='false';	
						
					}
						
						if(in_array('g5blog_format_gallery_images',$myArray))
					{
						
						$bb3='true';
					}
					else
					{
						
					$bb3='false';	
						
					}
						
						if(in_array('g5core_footer_enable',$myArray))
					{
						
						$bb4='true';
					}
					else
					{
						
					$bb4='false';	
						
					}
						
						if(in_array('g5core_page_menu',$myArray))
					{
						
						$bb5='true';
					}
					else
					{
						
					$bb5='false';	
						
					}
						
						
							echo $ui->get_tr_start() . $ui->get_th_start() . esc_html__('Custom Fields', 'custom-post-type-ui' ); // phpcs:ignore.
							echo $ui->get_th_end() . $ui->get_td_start() . $ui->get_fieldset_start(); // phpcs:ignore.
                          	echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'g5blog_format_audio_embed',
									'checked'    => $bb1 , // phpcs:ignore.
									'name'       => 'g5blog_format_audio_embed',
									'namearray'  => 'cpt_field',
									'textvalue'  => 'custom-fields',
									'labeltext'  => esc_html__( 'g5blog_format_audio_embed', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);
							echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'g5blog_single_post_layout',
									'checked'    => $bb2,
									'name'       => 'g5blog_single_post_layout',
									'namearray'  => 'cpt_field',
									'textvalue'  => 'custom-fields',
									'labeltext'  => esc_html__( 'g5blog_single_post_layout', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);
								echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'g5blog_format_gallery_images',
									'checked'    => $bb3,
									'name'       => 'g5blog_format_gallery_images',
									'namearray'  => 'cpt_field',
									'textvalue'  => 'custom-fields',
									'labeltext'  => esc_html__( 'g5blog_format_gallery_images', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);
								
                          	echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'g5core_footer_enable',
									'checked'    => $bb4,
									'name'       => 'g5core_footer_enable',
									'namearray'  => 'cpt_field',
									'textvalue'  => 'custom-fields',
									'labeltext'  => esc_html__( 'g5core_footer_enable', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);
								
                           	echo $ui->get_check_input( // phpcs:ignore.
								[
									'checkvalue' => 'g5core_page_menu',
									'checked'    => $bb5,
									'name'       => 'g5core_page_menu',
									'namearray'  => 'cpt_field',
									'textvalue'  => 'custom-fields',
									'labeltext'  => esc_html__( 'g5core_page_menu', 'custom-post-type-ui' ),
									'default'    => true,
									'wrap'       => false,
								]
							);
																


							
							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];
?></table>	<table class="form-table cptui-tabl1e" style="display:none;">
<?php
							$selected           = isset( $current ) ? disp_boolean( $current['rewrite'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['rewrite'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'rewrite',
									'labeltext'  => esc_html__( 'Rewrite', 'custom-post-type-ui' ),
									'aftertext'  => esc_html__( '(default: true) Whether or not WordPress should use rewrites for this post type.', 'custom-post-type-ui' ),
									'selections' => $select, // phpcs:ignore.
								]
							);

							echo $ui->get_text_input( // phpcs:ignore.
								[
									'namearray' => 'cpt_custom_post_type',
									'name'      => 'rewrite_slug',
									'textvalue' => isset( $current['rewrite_slug'] ) ? esc_attr( $current['rewrite_slug'] ) : '', // phpcs:ignore.
									'labeltext' => esc_html__( 'Custom Rewrite Slug', 'custom-post-type-ui' ),
									'aftertext' => esc_attr__( '(default: post type slug)', 'custom-post-type-ui' ),
									'helptext'  => esc_html__( 'Custom post type slug to use instead of the default.', 'custom-post-type-ui' ),
								]
							);

							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];
$selected           = isset( $current ) ? disp_boolean( $current['rewrite_withfront'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['rewrite_withfront'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'rewrite_withfront',
									'labeltext'  => esc_html__( 'With Front', 'custom-post-type-ui' ),
									'aftertext'  => esc_html__( '(default: true) Should the permalink structure be prepended with the front base. (example: if your permalink structure is /blog/, then your links will be: false->/news/, true->/blog/news/).', 'custom-post-type-ui' ),
									'selections' => $select, // phpcs:ignore.
								]
							);

							$select = [
								'options' => [
									[
										'attr' => '0',
										'text' => esc_attr__( 'False', 'custom-post-type-ui' ),
									],
									[
										'attr'    => '1',
										'text'    => esc_attr__( 'True', 'custom-post-type-ui' ),
										'default' => 'true',
									],
								],
							];

							$selected           = isset( $current ) ? disp_boolean( $current['query_var'] ) : '';
							$select['selected'] = ! empty( $selected ) ? $current['query_var'] : '';
							echo $ui->get_select_input( // phpcs:ignore.
								[
									'namearray'  => 'cpt_custom_post_type',
									'name'       => 'query_var',
									'labeltext'  => esc_html__( 'Query Var', 'custom-post-type-ui' ),
									'aftertext'  => esc_html__( '(default: true) Sets the query_var key for this post type.', 'custom-post-type-ui' ),
									'selections' => $select, // phpcs:ignore.
								]
							);

							echo $ui->get_text_input( // phpcs:ignore.
								[
									'namearray' => 'cpt_custom_post_type',
									'name'      => 'query_var_slug',
									'textvalue' => isset( $current['query_var_slug'] ) ? esc_attr( $current['query_var_slug'] ) : '', // phpcs:ignore.
									'labeltext' => esc_html__( 'Custom Query Var Slug', 'custom-post-type-ui' ),
									'aftertext' => esc_attr__( '(default: post type slug) Query var needs to be true to use.', 'custom-post-type-ui' ),
									'helptext'  => esc_html__( 'Custom query var slug to use instead of the default.', 'custom-post-type-ui' ),
								]
							);

							echo $ui->get_tr_start() . $ui->get_th_start(); // phpcs:ignore.
							echo $ui->get_label( 'menu_position', esc_html__( 'Menu Position', 'custom-post-type-ui' ) ); // phpcs:ignore.
							echo $ui->get_p( // phpcs:ignore.
								sprintf(
									// phpcs:ignore.
									esc_html__(
										'See %s in the "menu_position" section. Range of 5-100',
										'custom-post-type-ui'
									),
									sprintf(
										'<a href="https://developer.wordpress.org/reference/functions/register_post_type/#menu_position" target="_blank" rel="noopener">%s</a>',
										esc_html__( 'Available options', 'custom-post-type-ui' )
									)
								)
							);


?></table>
<?php
							echo $ui->get_fieldset_end() . $ui->get_td_end() . $ui->get_tr_end(); // phpcs:ignore.

							
							/**
							 * Filters the arguments for taxonomies to list for post type association.
							 *
							 * @since 1.0.0
							 *
							 * @param array $value Array of default arguments.
							 */
							$args = apply_filters( 'cptui_attach_taxonomies_to_post_type', [ 'public' => true ] );

							// If they don't return an array, fall back to the original default. Don't need to check for empty, because empty array is default for $args param in get_post_types anyway.
							if ( ! is_array( $args ) ) {
								$args = [ 'public' => true ];
							}

							/**
							 * Filters the results returned to display for available taxonomies for post type.
							 *
							 * @since 1.6.0
							 *
							 * @param array  $value  Array of taxonomy objects.
							 * @param array  $args   Array of arguments for the taxonomies query.
							 */
							$add_taxes = apply_filters( 'cptui_get_taxonomies_for_post_types', get_taxonomies( $args, 'objects' ), $args );
							unset( $add_taxes['nav_menu'], $add_taxes['post_format'] );
							foreach ( $add_taxes as $add_tax ) {

								$core_label = in_array( $add_tax->name, [ 'category', 'post_tag' ], true ) ? __( '(WP Core)', 'custom-post-type-ui' ) : '';
								
							}
							echo $ui->get_fieldset_end() . $ui->get_td_end() . $ui->get_tr_end(); // phpcs:ignore.
							?>
						</table>
					</div>
				</div>
			</div>

			<?php
			/**
			 * Fires after the default fieldsets on the post editor screen.
			 *
			 * @since 1.3.0
			 *
			 * @param cptui_admin_ui $ui Admin UI instance.
			 */
			do_action( 'cptui_post_type_after_fieldsets', $ui );
			?>

			<p>
			<?php
				if ( ! empty( $_GET ) && ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) { // phpcs:ignore.
					/**
					 * Filters the text value to use on the button when editing.
					 *
					 * @since 1.0.0
					 *
					 * @param string $value Text to use for the button.
					 */
				?>
					<input type="submit" class="button-primary" name="cpt_submit" value="<?php echo esc_attr( apply_filters( 'cptui_post_type_submit_edit', __( 'Save Post Type', 'custom-post-type-ui' ) ) ); ?>" />
					<?php

					/**
					 * Filters the text value to use on the button when deleting.
					 *
					 * @since 1.0.0
					 *
					 * @param string $value Text to use for the button.
					 */
				?>
					<input type="submit" class="button-secondary cptui-delete-bottom" name="cpt_delete" id="cpt_submit_delete" value="<?php echo esc_attr( apply_filters( 'cptui_post_type_submit_delete', __( 'Delete Post Type', 'custom-post-type-ui' ) ) ); ?>" />
				<?php
			} else {

					/**
					 * Filters the text value to use on the button when adding.
					 *
					 * @since 1.0.0
					 *
					 * @param string $value Text to use for the button.
					 */
				?>
					<input type="submit" class="button-primary" name="cpt_submit" value="<?php echo esc_attr( apply_filters( 'cptui_post_type_submit_add', __( 'Add Post Type', 'custom-post-type-ui' ) ) ); ?>" />
			<?php } ?>
			</p>
		</div>
	</form>
	</div><!-- End .wrap -->
	<?php
}

/**
 * Construct a dropdown of our post types so users can select which to edit.
 *
 * @since 1.0.0
 *
 * @param array $post_types Array of post types that are registered. Optional.
 */
function cptui_post_types_dropdown( $post_types = [] ) {

	$ui = new my_custom_post_type();

	if ( ! empty( $post_types ) ) {
		$select            = [];
		$select['options'] = [];

		foreach ( $post_types as $type ) {
			$text                = ! empty( $type['label'] ) ? esc_html( $type['label'] ) : esc_html( $type['name'] );
			$select['options'][] = [
				'attr' => esc_html( $type['name'] ),
				'text' => $text,
			];
		}

		$current = cptui_get_current_post_type();

		$select['selected'] = $current;

		/**
		 * Filters the post type dropdown options before rendering.
		 *
		 * @since 1.6.0
		 * @param array $select     Array of options for the dropdown.
		 * @param array $post_types Array of original passed in post types.
		 */
		$select = apply_filters( 'cptui_post_types_dropdown_options', $select, $post_types );

		echo $ui->get_select_input( // phpcs:ignore.
			[
				'namearray'  => 'cptui_selected_post_type',
				'name'       => 'post_type',
				'selections' => $select, // phpcs:ignore.
				'wrap'       => false,
			]
		);
	}
}

/**
 * Get the selected post type from the $_POST global.
 *
 * @since 1.0.0
 *
 * @internal
 *
 * @param bool $post_type_deleted Whether or not a post type was recently deleted. Optional. Default false.
 * @return bool|string $value False on no result, sanitized post type if set.
 */
function cptui_get_current_post_type( $post_type_deleted = false ) {

	$type = false;

	if ( ! empty( $_POST ) ) {
		if ( ! empty( $_POST['cptui_select_post_type_nonce_field'] ) ) {
			check_admin_referer( 'cptui_select_post_type_nonce_action', 'cptui_select_post_type_nonce_field' );
		}
		if ( isset( $_POST['cptui_selected_post_type']['post_type'] ) ) {
			$type = sanitize_text_field( wp_unslash( $_POST['cptui_selected_post_type']['post_type'] ) );
		} elseif ( $post_type_deleted ) {
			$post_types = cptui_get_post_type_data();
			$type       = key( $post_types );
		} elseif ( isset( $_POST['cpt_custom_post_type']['name'] ) ) {
			// Return the submitted value.
			if ( ! in_array( $_POST['cpt_custom_post_type']['name'], cptui_reserved_post_types(), true ) ) {
				$type = sanitize_text_field( wp_unslash( $_POST['cpt_custom_post_type']['name'] ) );
			} else {
				// Return the original value since user tried to submit a reserved term.
				$type = sanitize_text_field( wp_unslash( $_POST['cpt_original'] ) ); // phpcs:ignore.
			}
		}
	} elseif ( ! empty( $_GET ) && isset( $_GET['cptui_post_type'] ) ) {
		$type = sanitize_text_field( wp_unslash( $_GET['cptui_post_type'] ) );
	} else {
		$post_types = cptui_get_post_type_data();
		if ( ! empty( $post_types ) ) {
			// Will return the first array key.
			$type = key( $post_types );
		}
	}

	/**
	 * Filters the current post type to edit.
	 *
	 * @since 1.3.0
	 *
	 * @param string $type Post type slug.
	 */
	return apply_filters( 'cptui_current_post_type', $type );
}

/**
 * Delete our custom post type from the array of post types.
 *
 * @since 1.0.0
 *
 * @internal
 *
 * @param array $data $_POST values. Optional.
 * @return bool|string False on failure, string on success.
 */
function cptui_delete_post_type( $data = [] ) {

	// Pass double data into last function despite matching values.
	if ( is_string( $data ) && cptui_get_post_type_exists( $data, $data ) ) {
		$slug         = $data;
		$data         = [];
		$data['name'] = $slug;
	}

	if ( empty( $data['name'] ) ) {
		return cptui_admin_notices( 'error', '', false, __( 'Please provide a post type to delete', 'custom-post-type-ui' ) );
	}

	/**
	 * Fires before a post type is deleted from our saved options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Array of post type data we are deleting.
	 */
	do_action( 'cptui_before_delete_post_type', $data );

	$post_types = cptui_get_post_type_data();

	if ( array_key_exists( strtolower( $data['name'] ), $post_types ) ) {

		unset( $post_types[ $data['name'] ] );

		/**
		 * Filters whether or not 3rd party options were saved successfully within post type deletion.
		 *
		 * @since 1.3.0
		 *
		 * @param bool  $value      Whether or not someone else saved successfully. Default false.
		 * @param array $post_types Array of our updated post types data.
		 * @param array $data       Array of submitted post type to update.
		 */
		if ( false === ( $success = apply_filters( 'cptui_post_type_delete_type', false, $post_types, $data ) ) ) { // phpcs:ignore.
			$success = update_option( 'cptui_post_types', $post_types );
		}
	}

	/**
	 * Fires after a post type is deleted from our saved options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Array of post type data that was deleted.
	 */
	do_action( 'cptui_after_delete_post_type', $data );

	// Used to help flush rewrite rules on init.
	set_transient( 'cptui_flush_rewrite_rules', 'true', 5 * 60 );

	if ( isset( $success ) ) {
		return 'delete_success';
	}
	return 'delete_fail';
}

/**
 * Add to or update our CPTUI option with new data.
 *
 * @since 1.0.0
 *
 * @internal
 *
 * @param array $data Array of post type data to update. Optional.
 * @return bool|string False on failure, string on success.
 */
function cptui_update_post_type( $data = [] ) {

	/**
	 * Fires before a post_type is updated to our saved options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Array of post_type data we are updating.
	 */
	do_action( 'cptui_before_update_post_type', $data );

	// They need to provide a name.
	if ( empty( $data['cpt_custom_post_type']['name'] ) ) {
		return cptui_admin_notices( 'error', '', false, __( 'Please provide a post type name', 'custom-post-type-ui' ) );
	}

	if ( ! empty( $data['cpt_original'] ) && $data['cpt_original'] != $data['cpt_custom_post_type']['name'] ) { // phpcs:ignore.
		if ( ! empty( $data['update_post_types'] ) ) {
			add_filter( 'cptui_convert_post_type_posts', '__return_true' );
		}
	}

	// Clean up $_POST data.
	foreach ( $data as $key => $value ) {
		if ( is_string( $value ) ) {
			$data[ $key ] = sanitize_text_field( $value );
		} else {
			array_map( 'sanitize_text_field', $data[ $key ] );
		}
	}

	// Check if they didn't put quotes in the name or rewrite slug.
	if ( false !== strpos( $data['cpt_custom_post_type']['name'], '\'' ) ||
		false !== strpos( $data['cpt_custom_post_type']['name'], '\"' ) ||
		false !== strpos( $data['cpt_custom_post_type']['rewrite_slug'], '\'' ) ||
		false !== strpos( $data['cpt_custom_post_type']['rewrite_slug'], '\"' ) ) {

		add_filter( 'cptui_custom_error_message', 'cptui_slug_has_quotes' );
		return 'error';
	}

	$post_types = cptui_get_post_type_data();

	/**
	 * Check if we already have a post type of that name.
	 *
	 * @since 1.3.0
	 *
	 * @param bool   $value Assume we have no conflict by default.
	 * @param string $value Post type slug being saved.
	 * @param array  $post_types Array of existing post types from CPTUI.
	 */
	$slug_exists = apply_filters( 'cptui_post_type_slug_exists', false, $data['cpt_custom_post_type']['name'], $post_types );
	if ( true === $slug_exists ) {
		add_filter( 'cptui_custom_error_message', 'cptui_slug_matches_post_type' );
		return 'error';
	}
	if ( 'new' === $data['cpt_type_status'] ) {
		$slug_as_page = cptui_check_page_slugs( $data['cpt_custom_post_type']['name'] );
		if ( true === $slug_as_page ) {
			add_filter( 'cptui_custom_error_message', 'cptui_slug_matches_page' );
			return 'error';
		}
	}

	if ( empty( $data['cpt_addon_taxes'] ) || ! is_array( $data['cpt_addon_taxes'] ) ) {
		$data['cpt_addon_taxes'] = [];
	}

	if ( empty( $data['cpt_supports'] ) || ! is_array( $data['cpt_supports'] ) ) {
		$data['cpt_supports'] = [];
	}
	if ( empty( $data['cpt_field'] ) || ! is_array( $data['cpt_field'] ) ) {
		$data['cpt_field'] = [];
	}

 $ghj=implode(',',$_POST['cpt_field']);
        
	foreach ( $data['cpt_labels'] as $key => $label ) {
		if ( empty( $label ) ) {
			unset( $data['cpt_labels'][ $key ] );
		}

		$label = str_replace( '"', '', htmlspecialchars_decode( $label ) );
		$label = htmlspecialchars( $label, ENT_QUOTES );
		$label = trim( $label );
		if ( 'parent' === $key ) {
			$data['cpt_labels']['parent_item_colon'] = stripslashes_deep( $label );
		} else {
			$data['cpt_labels'][ $key ] = stripslashes_deep( $label );
		}
	}

	if ( empty( $data['cpt_custom_post_type']['menu_icon'] ) ) {
		$data['cpt_custom_post_type']['menu_icon'] = null;
	}

	$register_meta_box_cb = trim( $data['cpt_custom_post_type']['register_meta_box_cb'] );
	if ( empty( $register_meta_box_cb ) ) {
		$register_meta_box_cb = null;
	}

	 $label12 = ucwords( str_replace( '_', ' ', $data['cpt_custom_post_type']['name'] ) );
	
	global $wpdb;

$result=$wpdb->get_results("SELECT * FROM `wp_post_type`");

 $result['meta_key'];

 $myString = $rr;
$myArray = explode(',', $myString);


$result44=$wpdb->get_results("SELECT * FROM `wp_post_type` order by ID DESC");


 $rr44= $result44[0]->ID;

        
	foreach($myArray  as $key1 => $value1) {
	if($result44[0]->post_type==$label12)
	{		
 $gg="UPDATE `wp_post_type`  SET meta_key='$ghj' WHERE post_type='$label12'";;
	}
	else
	{
	$gg="INSERT INTO `wp_post_type` (`post_type`, `meta_key`) values ( '$label12','$ghj')";	
	}
	}

$wpdb->query($gg);



$gg="INSERT INTO `wp_post_type` (`post_type`, `meta_key`) values ( '$label12','$ghj')";




	if ( ! empty( $data['cpt_custom_post_type']['label'] ) ) {
		$label = str_replace( '"', '', htmlspecialchars_decode( $data['cpt_custom_post_type']['label'] ) );
		$label = htmlspecialchars( stripslashes( $label ), ENT_QUOTES );
	}

	$singular_label = ucwords( str_replace( '_', ' ', $data['cpt_custom_post_type']['name'] ) );
	if ( ! empty( $data['cpt_custom_post_type']['singular_label'] ) ) {
		$singular_label = str_replace( '"', '', htmlspecialchars_decode( $data['cpt_custom_post_type']['singular_label'] ) );
		$singular_label = htmlspecialchars( stripslashes( $singular_label ), ENT_QUOTES );
	}

	// We are handling this special because we can't accurately get to exclude the description index
	// in the cptui_filtered_post_type_post_global() function. So we clean this up from the $_POST
	// global afterwards here.
	$description = wp_kses_post( stripslashes_deep( $_POST['cpt_custom_post_type']['description'] ) );

	$name                  = trim( $data['cpt_custom_post_type']['name'] );
	$rest_base             = trim( $data['cpt_custom_post_type']['rest_base'] );
	$rest_controller_class = trim( $data['cpt_custom_post_type']['rest_controller_class'] );
	$rest_namespace        = trim( $data['cpt_custom_post_type']['rest_namespace'] );
	$has_archive_string    = trim( $data['cpt_custom_post_type']['has_archive_string'] );
	$capability_type       = trim( $data['cpt_custom_post_type']['capability_type'] );
	$rewrite_slug          = trim( $data['cpt_custom_post_type']['rewrite_slug'] );
	$query_var_slug        = trim( $data['cpt_custom_post_type']['query_var_slug'] );
	$menu_position         = trim( $data['cpt_custom_post_type']['menu_position'] );
	$show_in_menu_string   = trim( $data['cpt_custom_post_type']['show_in_menu_string'] );
	$menu_icon             = trim( $data['cpt_custom_post_type']['menu_icon'] );
	$custom_supports       = trim( $data['cpt_custom_post_type']['custom_supports'] );
	$enter_title_here      = trim( $data['cpt_custom_post_type']['enter_title_here'] );

	$post_types[ $data['cpt_custom_post_type']['name'] ] = [
		'name'                  => $name,
		'label'                 => $label,
		
		'show_ui'               => disp_boolean( $data['cpt_custom_post_type']['show_ui'] ),
		'show_in_nav_menus'     => disp_boolean( $data['cpt_custom_post_type']['show_in_nav_menus'] ),
		
		
		'show_in_menu'          => disp_boolean( $data['cpt_custom_post_type']['show_in_menu'] ),
		'singular_label'        => $singular_label,
		'description'           => $description,
		'public'                => disp_boolean( $data['cpt_custom_post_type']['public'] ),
		'publicly_queryable'    => disp_boolean( $data['cpt_custom_post_type']['publicly_queryable'] ),
		'show_ui'               => disp_boolean( $data['cpt_custom_post_type']['show_ui'] ),
		'show_in_nav_menus'     => disp_boolean( $data['cpt_custom_post_type']['show_in_nav_menus'] ),
		'delete_with_user'      => disp_boolean( $data['cpt_custom_post_type']['delete_with_user'] ),
		'show_in_rest'          => disp_boolean( $data['cpt_custom_post_type']['show_in_rest'] ),
		'rest_base'             => $rest_base,
		'rest_controller_class' => $rest_controller_class,
		'rest_namespace'        => $rest_namespace,
		'has_archive'           => disp_boolean( $data['cpt_custom_post_type']['has_archive'] ),
		'has_archive_string'    => $has_archive_string,
		'exclude_from_search'   => disp_boolean( $data['cpt_custom_post_type']['exclude_from_search'] ),
		'capability_type'       => $capability_type,
		'hierarchical'          => disp_boolean( $data['cpt_custom_post_type']['hierarchical'] ),
		'can_export'            => disp_boolean( $data['cpt_custom_post_type']['can_export'] ),
		'rewrite'               => disp_boolean( $data['cpt_custom_post_type']['rewrite'] ),
		'rewrite_slug'          => $rewrite_slug,
		'rewrite_withfront'     => disp_boolean( $data['cpt_custom_post_type']['rewrite_withfront'] ),
		'query_var'             => disp_boolean( $data['cpt_custom_post_type']['query_var'] ),
		'query_var_slug'        => $query_var_slug,
		'menu_position'         => $menu_position,
		'show_in_menu'          => disp_boolean( $data['cpt_custom_post_type']['show_in_menu'] ),
		'show_in_menu_string'   => $show_in_menu_string,
		'menu_icon'             => $menu_icon,
		'register_meta_box_cb'  => $register_meta_box_cb,
		'supports'              => $data['cpt_supports'],
		'taxonomies'            => $data['cpt_addon_taxes'],
		'labels'                => $data['cpt_labels'],
		'custom_supports'       => $custom_supports,
		'enter_title_here'      => $enter_title_here,
		
	];

	/**
	 * Filters final data to be saved right before saving post type data.
	 *
	 * @since 1.6.0
	 *
	 * @param array  $post_types Array of final post type data to save.
	 * @param string $name       Post type slug for post type being saved.
	 */
	$post_types = apply_filters( 'cptui_pre_save_post_type', $post_types, $name );

	/**
	 * Filters whether or not 3rd party options were saved successfully within post type add/update.
	 *
	 * @since 1.3.0
	 *
	 * @param bool  $value      Whether or not someone else saved successfully. Default false.
	 * @param array $post_types Array of our updated post types data.
	 * @param array $data       Array of submitted post type to update.
	 */
	if ( false === ( $success = apply_filters( 'cptui_post_type_update_save', false, $post_types, $data ) ) ) { // phpcs:ignore.
		$success = update_option( 'cptui_post_types', $post_types );
	}

	/**
	 * Fires after a post type is updated to our saved options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Array of post type data that was updated.
	 */
	do_action( 'cptui_after_update_post_type', $data );

	// Used to help flush rewrite rules on init.
	set_transient( 'cptui_flush_rewrite_rules', 'true', 5 * 60 );

	if ( isset( $success ) && 'new' === $data['cpt_type_status'] ) {
		return 'add_success';
	}
	return 'update_success';
}

/**
 * Return an array of names that users should not or can not use for post type names.
 *
 * @since 1.0.0
 *
 * @return array $value Array of names that are recommended against.
 */
function cptui_reserved_post_types() {

	$reserved = [
		'post',
		'page',
		'attachment',
		'revision',
		'nav_menu_item',
		'action',
		'order',
		'theme',
		'themes',
		'fields',
		'custom_css',
		'customize_changeset',
		'author',
		'post_type',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
	];

	/**
	 * Filters the list of reserved post types to check against.
	 *
	 * 3rd party plugin authors could use this to prevent duplicate post types.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Array of post type slugs to forbid.
	 */
	$custom_reserved = apply_filters( 'cptui_reserved_post_types', [] );

	if ( is_string( $custom_reserved ) && ! empty( $custom_reserved ) ) {
		$reserved[] = $custom_reserved;
	} elseif ( is_array( $custom_reserved ) && ! empty( $custom_reserved ) ) {
		foreach ( $custom_reserved as $slug ) {
			$reserved[] = $slug;
		}
	}

	return $reserved;
}

/**
 * Converts post type between original and newly renamed.
 *
 * @since 1.1.0
 *
 * @internal
 *
 * @param string $original_slug Original post type slug. Optional. Default empty string.
 * @param string $new_slug      New post type slug. Optional. Default empty string.
 */
function cptui_convert_post_type_posts( $original_slug = '', $new_slug = '' ) {
	$args    = [
		'posts_per_page' => -1,
		'post_type'      => $original_slug,
	];
	$convert = new WP_Query( $args );

	if ( $convert->have_posts() ) :
		while ( $convert->have_posts() ) :
			$convert->the_post();
			set_post_type( get_the_ID(), $new_slug );
		endwhile;
	endif;

	cptui_delete_post_type( $original_slug );
}

/**
 * Checks if we are trying to register an already registered post type slug.
 *
 * @since 1.3.0
 *
 * @param bool   $slug_exists    Whether or not the post type slug exists. Optional. Default false.
 * @param string $post_type_slug The post type slug being saved. Optional. Default empty string.
 * @param array  $post_types     Array of CPTUI-registered post types. Optional.
 * @return bool
 */
function cptui_check_existing_post_type_slugs( $slug_exists = false, $post_type_slug = '', $post_types = [] ) {

	// If true, then we'll already have a conflict, let's not re-process.
	if ( true === $slug_exists ) {
		return $slug_exists;
	}

	// Check if CPTUI has already registered this slug.
	if ( array_key_exists( strtolower( $post_type_slug ), $post_types ) ) {
		return true;
	}

	// Check if we're registering a reserved post type slug.
	if ( in_array( $post_type_slug, cptui_reserved_post_types() ) ) { // phpcs:ignore.
		return true;
	}

	// Check if other plugins have registered non-public this same slug.
	$public = get_post_types(
		[
			'_builtin' => false,
			'public'   => true,
		]
	);

	$private = get_post_types(
		[
			'_builtin' => false,
			'public'   => false,
		]
	);

	$registered_post_types = array_merge( $public, $private );
	if ( in_array( $post_type_slug, $registered_post_types ) ) { // phpcs:ignore.
		return true;
	}

	// If we're this far, it's false.
	return $slug_exists;
}
add_filter( 'cptui_post_type_slug_exists', 'cptui_check_existing_post_type_slugs', 10, 3 );

/**
 * Checks if the slug matches any existing page slug.
 *
 * @since 1.3.0
 *
 * @param string $post_type_slug The post type slug being saved. Optional. Default empty string.
 * @return bool Whether or not the slug exists.
 */
function cptui_check_page_slugs( $post_type_slug = '' ) {
	$page = get_page_by_path( $post_type_slug );

	if ( null === $page ) {
		return false;
	}

	if ( is_object( $page ) && ( true === $page instanceof WP_Post ) ) {
		return true;
	}

	return false;
}

/**
 * Handle the save and deletion of post type data.
 *
 * @since 1.4.0
 */
function cptui_process_post_type() {

	if ( wp_doing_ajax() ) {
		return;
	}

	if ( ! is_admin() ) {
		return;
	}

	if ( ! empty( $_GET ) && isset( $_GET['page'] ) && 'cptui_manage_post_types' !== $_GET['page'] ) {
		return;
	}

	if ( ! empty( $_POST ) ) {
		$result = '';
		if ( isset( $_POST['cpt_submit'] ) ) {
			check_admin_referer( 'cptui_addedit_post_type_nonce_action', 'cptui_addedit_post_type_nonce_field' );
			$data   = cptui_filtered_post_type_post_global();
			$result = cptui_update_post_type( $data );
		} elseif ( isset( $_POST['cpt_delete'] ) ) {
			check_admin_referer( 'cptui_addedit_post_type_nonce_action', 'cptui_addedit_post_type_nonce_field' );

			$filtered_data = filter_input( INPUT_POST, 'cpt_custom_post_type', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
			$result        = cptui_delete_post_type( $filtered_data );
			add_filter( 'cptui_post_type_deleted', '__return_true' );
		}

		if ( $result ) {
			if ( is_callable( "cptui_{$result}_admin_notice" ) ) {
				add_action( 'admin_notices', "cptui_{$result}_admin_notice" );
			}
		}
		if ( isset( $_POST['cpt_delete'] ) && empty( cptui_get_post_type_slugs() ) ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'cptui_manage_post_types',
					],
					cptui_admin_url( 'admin.php?page=cptui_manage_post_types' )
				)
			);
		}
	}
}
add_action( 'init', 'cptui_process_post_type', 8 );

/**
 * Handle the conversion of post type posts.
 *
 * This function came to be because we needed to convert AFTER registration.
 *
 * @since 1.4.3
 */
function cptui_do_convert_post_type_posts() {

	/**
	 * Whether or not to convert post type posts.
	 *
	 * @since 1.4.3
	 *
	 * @param bool $value Whether or not to convert.
	 */
	if ( apply_filters( 'cptui_convert_post_type_posts', false ) ) {
		check_admin_referer( 'cptui_addedit_post_type_nonce_action', 'cptui_addedit_post_type_nonce_field' );

		$original = filter_input( INPUT_POST, 'cpt_original', FILTER_SANITIZE_STRING );
		$new      = filter_input( INPUT_POST, 'cpt_custom_post_type', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );

		// Return early if either fails to successfully validate.
		if ( ! $original || ! $new ) {
			return;
		}

		cptui_convert_post_type_posts( sanitize_text_field( $original ), sanitize_text_field( $new['name'] ) );
	}
}
add_action( 'init', 'cptui_do_convert_post_type_posts' );

/**
 * Handles slug_exist checks for cases of editing an existing post type.
 *
 * @since 1.5.3
 *
 * @param bool   $slug_exists    Current status for exist checks.
 * @param string $post_type_slug Post type slug being processed.
 * @param array  $post_types     CPTUI post types.
 * @return bool
 */
function cptui_updated_post_type_slug_exists( $slug_exists, $post_type_slug = '', $post_types = [] ) {
	if (
		( ! empty( $_POST['cpt_type_status'] ) && 'edit' === $_POST['cpt_type_status'] ) &&// phpcs:ignore.
		! in_array( $post_type_slug, cptui_reserved_post_types() ) &&// phpcs:ignore.
		( ! empty( $_POST['cpt_original'] ) && $post_type_slug === $_POST['cpt_original'] ) // phpcs:ignore.
	) {
		$slug_exists = false;
	}
	return $slug_exists;
}
add_filter( 'cptui_post_type_slug_exists', 'cptui_updated_post_type_slug_exists', 11, 3 );

/**
 * Sanitize and filter the $_POST global and return a reconstructed array of the parts we need.
 *
 * Used for when managing post types.
 *
 * @since 1.10.0
 * @return array
 */
function cptui_filtered_post_type_post_global() {
	$filtered_data = [];

	$default_arrays = [
		'cpt_custom_post_type',
		'cpt_labels',
		'cpt_supports',
		'cpt_addon_taxes',
		'update_post_types',
	];

	$third_party_items_arrays = apply_filters(
		'cptui_filtered_post_type_post_global_arrays',
		(array) [] // phpcs:ignore.
	);

	$items_arrays = array_merge( $default_arrays, $third_party_items_arrays );
	foreach ( $items_arrays as $item ) {
		$first_result = filter_input( INPUT_POST, $item, FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );

		if ( $first_result ) {
			$filtered_data[ $item ] = $first_result;
		}
	}

	$default_strings = [
		'cpt_original',
		'cpt_type_status',
	];

	$third_party_items_strings = apply_filters(
		'cptui_filtered_post_type_post_global_strings',
		(array) [] // phpcs:ignore.
	);

	$items_string = array_merge( $default_strings, $third_party_items_strings );

	foreach ( $items_string as $item ) {
		$second_result = filter_input( INPUT_POST, $item, FILTER_SANITIZE_STRING );
		if ( $second_result ) {
			$filtered_data[ $item ] = $second_result;
		}
	}

	return $filtered_data;
}

// phpcs:ignore.
function cptui_custom_enter_title_here( $text, $post ) {
	$cptui_obj = cptui_get_cptui_post_type_object( $post->post_type );
	if ( empty( $cptui_obj ) ) {
		return $text;
	}

	if ( empty( $cptui_obj['enter_title_here'] ) ) {
		return $text;
	}

	return $cptui_obj['enter_title_here'];
}
add_filter( 'enter_title_here', 'cptui_custom_enter_title_here', 10, 2 );
