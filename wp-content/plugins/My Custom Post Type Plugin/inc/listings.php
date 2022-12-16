<?php

function cptui_listings() {
	?>
		<div class="wrap cptui-listings">
			<?php
			
			do_action( 'cptui_inside_listings_wrap' );
			?>

			<h1 class="wp-heading-inline"><?php esc_html_e( 'Registered Post Types.', 'custom-post-type-ui' ); ?></h1>
			<a href="<?php echo esc_url( cptui_get_add_new_link( 'post_types' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Post Type', 'custom-post-type-ui' ); ?></a>
			
			<?php
			$post_types = cptui_get_post_type_data();
			echo '<h2 id="post-types">' . esc_html__( 'Post Types', 'custom-post-type-ui' ) . '</h2>';
			if ( ! empty( $post_types ) ) {
				?>
			<p>
				<?php
				printf(
				
					esc_html__( ' Registered post types count total: %d', 'custom-post-type-ui' ),
					count( $post_types )
				);
				?>
			</p>

				<?php

				$post_type_table_heads = [
					esc_html__( 'Post Type', 'custom-post-type-ui' ),
					
					
					
				];

				do_action( 'cptui_before_post_type_listing' );
				?>
			<table class="wp-list-table widefat post-type-listing">
				<thead>
				<tr>
					<?php
					foreach ( $post_type_table_heads as $head ) {
						echo '<th>' . esc_html( $head ) . '</th>';
					}
					?>
				</tr>
				</thead>
				<tbody>
				<?php
				$counter = 1;
				foreach ( $post_types as $post_type => $post_type_settings ) {

					$rowclass = ( 0 === $counter % 2 ) ? '' : 'alternate';

					$strings    = [];
					$supports   = [];
					$taxonomies = [];
					$archive    = '';
					foreach ( $post_type_settings as $settings_key => $settings_value ) {
						if ( 'labels' === $settings_key ) {
							continue;
						}

						if ( is_string( $settings_value ) ) {
							$strings[ $settings_key ] = $settings_value;
						} else {
							if ( 'supports' === $settings_key ) {
								$supports[ $settings_key ] = $settings_value;
							}

							if ( 'taxonomies' === $settings_key ) {
								$taxonomies[ $settings_key ] = $settings_value;

								
								if ( empty( $taxonomies['taxonomies'] ) ) {
									$taxonomies['taxonomies'] = get_object_taxonomies( $post_type );
								}
							}
						}
						$archive = get_post_type_archive_link( $post_type );
					}
					?>
						<tr class="<?php echo esc_attr( $rowclass ); ?>">
							<?php
							$edit_path          = 'admin.php?page=cptui_manage_post_types&action=edit&cptui_post_type=' . $post_type;
							$post_type_link_url = is_network_admin() ? network_admin_url( $edit_path ) : admin_url( $edit_path );
							?>
							<td>
								<?php
								printf(
									'<a href="%s">%s</a><br/>
									<a href="%s">%s</a><br/>',
									esc_attr( $post_type_link_url ),
									sprintf(
									
										esc_html__( 'Edit %1$s (%2$s)', 'custom-post-type-ui' ),
										esc_html( $post_type_settings['label'] ),
										esc_html( $post_type )
									),
									esc_attr( admin_url( 'admin.php?page=cptui_tools&action=get_code#' . $post_type ) ),
									esc_html__( '', 'custom-post-type-ui' )
								);

								if ( $archive ) {
									?>
								<a href="<?php echo esc_attr( get_post_type_archive_link( $post_type ) ); ?>"><?php esc_html_e( 'View frontend archive', 'custom-post-type-ui' ); ?></a>
								<?php } ?>
							</td>
							
							
							<td>
								<?php
								$maybe_empty = array_filter( $post_type_settings['labels'] );
								if ( ! empty( $maybe_empty ) ) {
									foreach ( $post_type_settings['labels'] as $key => $value ) {
										if ( 'parent' === $key && array_key_exists( 'parent_item_colon', $post_type_settings['labels'] ) ) {
											continue;
										}
										printf(
											'<strong>%s</strong>: %s<br/>',
											esc_html( $key ),
											esc_html( $value )
										);
									}
								} else {
									
								}
								?>
							</td>
							
						</tr>

					<?php
					$counter++;
				}
				?>
				</tbody>
				<tfoot>
				
				</tfoot>
			</table>
				<?php
				
				do_action( 'cptui_after_post_type_listing' );
			} else {

				
				do_action( 'cptui_no_post_types_listing' );
			}

			$taxonomies = cptui_get_taxonomy_data();
			
			if ( ! empty( $taxonomies ) ) {
				?>
				<p>
				<?php
				printf(
					
					esc_html__( 'Custom Post Type UI registered taxonomies count total: %d', 'custom-post-type-ui' ),
					count( $taxonomies )
				);
				?>
				</p>

				<?php

				$taxonomy_table_heads = [
					esc_html__( 'Taxonomy', 'custom-post-type-ui' ),
					esc_html__( 'Settings', 'custom-post-type-ui' ),
					esc_html__( 'Post Types', 'custom-post-type-ui' ),
					esc_html__( 'Labels', 'custom-post-type-ui' ),
					esc_html__( 'Template Hierarchy', 'custom-post-type-ui' ),
				];

				
				do_action( 'cptui_before_taxonomy_listing' );
				?>
				<table class="wp-list-table widefat taxonomy-listing">
					<thead>
					
					</thead>
					<tbody>
					<?php
					$counter = 1;
					foreach ( $taxonomies as $taxonomy => $taxonomy_settings ) {

						$rowclass = ( 0 === $counter % 2 ) ? '' : 'alternate';

						$strings      = [];
						$object_types = [];
						foreach ( $taxonomy_settings as $settings_key => $settings_value ) {
							if ( 'labels' === $settings_key ) {
								continue;
							}

							if ( is_string( $settings_value ) ) {
								$strings[ $settings_key ] = $settings_value;
							} else {
								if ( 'object_types' === $settings_key ) {
									$object_types[ $settings_key ] = $settings_value;

									// In case they are not associated from the post type settings.
									if ( empty( $object_types['object_types'] ) ) {
										$types                        = get_taxonomy( $taxonomy );
										$object_types['object_types'] = $types->object_type;
									}
								}
							}
						}
						?>
							<tr class="<?php echo esc_attr( $rowclass ); ?>">
								<?php
								$edit_path         = 'admin.php?page=cptui_manage_taxonomies&action=edit&cptui_taxonomy=' . $taxonomy;
								$taxonomy_link_url = is_network_admin() ? network_admin_url( $edit_path ) : admin_url( $edit_path );
								?>
								<td>
									<?php
									printf(
										'<a href="%s">%s</a><br/>
										<a href="%s">%s</a>',
										esc_attr( $taxonomy_link_url ),
										sprintf(
											/* translators: %s: Taxonomy slug */
											esc_html__( 'Edit %1$s (%2$s)', 'custom-post-type-ui' ),
											esc_html( $taxonomy_settings['label'] ),
											esc_html( $taxonomy )
										),
										esc_attr( admin_url( 'admin.php?page=cptui_tools&action=get_code#' . $taxonomy ) ),
										
									);
									?>
								</td>
								<td>
									<?php
									foreach ( $strings as $key => $value ) {
										printf( '<strong>%s:</strong> ', esc_html( $key ) );
										if ( in_array( $value, [ '1', '0' ], true ) ) {
											echo esc_html( disp_boolean( $value ) );
										} else {
											echo ! empty( $value ) ? esc_html( $value ) : '""';
										}
										echo '<br/>';
									}
									?>
								</td>
								<td>
									<?php
									if ( ! empty( $object_types['object_types'] ) ) {
										foreach ( $object_types['object_types'] as $type ) {
											echo esc_html( $type ) . '<br/>';
										}
									}
									?>
								</td>
								<td>
									<?php
									$maybe_empty = array_filter( $taxonomy_settings['labels'] );
									if ( ! empty( $maybe_empty ) ) {
										foreach ( $taxonomy_settings['labels'] as $key => $value ) {
											printf(
												'<strong>%s</strong>: %s<br/>',
												esc_html( $key ),
												esc_html( $value )
											);
										}
									} else {
										printf(
											'<span aria-hidden="true">—</span><span class="screen-reader-text">%s</span>',
											esc_html__( 'No custom labels to display', 'custom-post-type-ui' )
										);
									}
									?>
								</td>
								
							</tr>

						<?php
						$counter++;
					}
					?>
					</tbody>
					<tfoot>
					
					</tfoot>
				</table>
				<?php
				
				do_action( 'cptui_after_taxonomy_listing' );

			} else {

				do_action( 'cptui_no_taxonomies_listing' );
			}
			?>

		</div>
	<?php
}

function cptui_no_post_types_to_list() {
	echo '<p>' . sprintf(
		/* translators: 1st %s: Link to manage post types section 2nd %s Link text */
		esc_html__( 'No post types registered for display. Visit %s to get started.', 'custom-post-type-ui' ),
		sprintf(
			'<a href="%s">%s</a>',
			esc_attr( admin_url( 'admin.php?page=cptui_manage_post_types' ) ),
			esc_html__( 'Add/Edit Post Types', 'custom-post-type-ui' )
		)
	) . '</p>';
}
add_action( 'cptui_no_post_types_listing', 'cptui_no_post_types_to_list' );

