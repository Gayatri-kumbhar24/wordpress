<?php
/**
 * Travel Agency Template Hooks.
 *
 * @package Travel_Agency 
 */

/** Import content data*/
if ( ! function_exists( 'travel_agency_import_files' ) ) :
function travel_agency_import_files() {
  $upload_dir = wp_upload_dir();
    return array(
        array(
            'import_file_name'             => 'Travel Agency Demo',
            'local_import_file'            => $upload_dir['basedir'] . '/rara-demo-pack/travel-agency-demo-content/content/travelagency.xml',
            'local_import_widget_file'     => $upload_dir['basedir'] . '/rara-demo-pack/travel-agency-demo-content/content/travelagency.wie',
            'local_import_customizer_file' => $upload_dir['basedir'] . '/rara-demo-pack/travel-agency-demo-content/content/travelagency.dat',
            'import_preview_image_url'     => get_template_directory() .'/screenshot.png',
            'import_notice'                => __( 'Please waiting for a few minutes, do not close the window or refresh the page until the data is imported.', 'wedding-band' ),
        ),
    );       
}
add_filter( 'rrdi/import_files', 'travel_agency_import_files' );
endif;

/** Programmatically set the front page and menu */
if ( ! function_exists( 'travel_agency_after_import' ) ) :

function travel_agency_after_import( $selected_import ) {
      
      //Set Menu
      $primary   = get_term_by('name', 'Primary', 'nav_menu');
      set_theme_mod( 'nav_menu_locations' , array( 
            'primary'   => $primary->term_id
           ) 
      );
   
      /** Set Front page */
      $page = get_page_by_path('home'); /** This need to be slug of the page that is assigned as Front page */
      if ( isset( $page->ID ) ) {
        update_option( 'page_on_front', $page->ID );
        update_option( 'show_on_front', 'page' );
      }
           
      /** Blog Page */
      $postpage = get_page_by_path('blog'); /** This need to be slug of the page that is assigned as Posts page */
      if( $postpage ){
        $post_pgid = $postpage->ID;
        update_option( 'page_for_posts', $post_pgid );
      }
      
      $array = array(
        '5'  =>	'173',
        '6'  =>	'194',
        '7'  =>	'195',   
        '8'  =>	'196',
        '9'	 => '196',
        '10' =>	'197',
        '11' =>	'185',
        '12' =>	'198',
        '13' =>	'172',
        '14' =>	'176',
        '15' =>	'191',
        '16' =>	'70',
        '17' =>	'180',        
        '18' =>	'175',
        '19' =>	'364',
        '20' =>	'199',
        '21' =>	'163',
        '22' =>	'184',
        '23' =>	'182',
        '24' =>	'189',
        '25' =>	'177',
        '26' =>	'161',
        '27' =>	'186',
        '28' =>	'162',
        '29' =>	'183',
        '30' =>	'174',
        '31' =>	'179',
        '32' =>	'200',
        '33' =>	'201',        
    );

    foreach( $array as $k => $v ){
        add_term_meta( $k, 'category-image-id', $v );        
    }    
      
}
add_action( 'rrdi/after_import', 'travel_agency_after_import' );
endif;

function travel_agency_import_msg(){
    return __( 'Before you begin, make sure all recommended plugins are activated.', 'wedding-band' );
}
add_filter( 'rrdi_before_import_msg', 'travel_agency_import_msg' );