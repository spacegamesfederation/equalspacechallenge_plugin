<?php
/*
Plugin name: Equal Space Challenge Contact Form 7 Database
Description: A Modified version of CFDB7 to store the Equal Space Challenge Data.
Text Domain: equalspace-challenge
Version: 0.0.1
*/

function cfdb7_create_table(){

    global $wpdb;
    $cfdb       = apply_filters( 'cfdb7_database', $wpdb );
    $table_name = $cfdb->prefix.'db7_forms';

    if( $cfdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {

        $charset_collate = $cfdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            form_id bigint(20) NOT NULL AUTO_INCREMENT,
            form_post_id bigint(20) NOT NULL,
            form_value longtext NOT NULL,
            form_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (form_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    $upload_dir    = wp_upload_dir();
    $cfdb7_dirname = $upload_dir['basedir'].'/equalspace_uploads';
    if ( ! file_exists( $cfdb7_dirname ) ) {
        wp_mkdir_p( $cfdb7_dirname );
    }
    add_option( 'cfdb7_view_install_date', date('Y-m-d G:i:s'), '', 'yes');

}

function cfdb7_on_activate( $network_wide ){

    global $wpdb;
    if ( is_multisite() && $network_wide ) {
        // Get all blogs in the network and activate plugin on each one
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            cfdb7_create_table();
            restore_current_blog();
        }
    } else {
        cfdb7_create_table();
    }
}

register_activation_hook( __FILE__, 'cfdb7_on_activate' );

function cfdb7_before_send_mail( $form_tag ) {

    global $wpdb;
    $cfdb          = apply_filters( 'cfdb7_database', $wpdb );
    $table_name    = $cfdb->prefix.'db7_forms';
    $upload_dir    = wp_upload_dir();
    $cfdb7_dirname = $upload_dir['basedir'].'/equalspace_uploads';
    $time_now      = time();

    $form = WPCF7_Submission::get_instance();

    if ( $form ) {

        $black_list   = array('_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag',
        '_wpcf7_is_ajax_call','cfdb7_name', '_wpcf7_container_post','_wpcf7cf_hidden_group_fields',
        '_wpcf7cf_hidden_groups', '_wpcf7cf_visible_groups', '_wpcf7cf_options');

        $data           = $form->get_posted_data();
        $files          = $form->uploaded_files();
        $uploaded_files = array();

        foreach ($files as $file_key => $file) {
            array_push($uploaded_files, $file_key);
            copy($file, $cfdb7_dirname.'/'.$time_now.'-'.basename($file));
        }

        $form_data   = array();

        $form_data['cfdb7_status'] = 'unread';

        if(@$form_data['your_email']){
          $email = $form_data['your_email'];//hack to test for unique email sets value from form data;
            $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE form_post_id = $form_post_id AND email = '$email'");//checks data

            if($rowcount>0){//email already there for this record;
 
                    //HACK needs to safeguard against duplicate entry, does not work yet.
                return;
            }

        }

        foreach ($data as $key => $d) {
            if ( !in_array($key, $black_list ) && !in_array($key, $uploaded_files ) ) {

                $tmpD = $d;

                if ( ! is_array($d) ){

                    $bl   = array('\"',"\'",'/','\\');
                    $wl   = array('&quot;','&#039;','&#047;', '&#092;');

                    $tmpD = str_replace($bl, $wl, $tmpD );
                }

                $form_data[$key] = $tmpD;
            }
            if ( in_array($key, $uploaded_files ) ) {
                $form_data[$key.'cfdb7_file'] = $time_now.'-'.$d;
            }
        }

        /* cfdb7 before save data. */
        $form_data = apply_filters('cfdb7_before_save_data', $form_data);

        do_action( 'cfdb7_before_save_data', $form_data );

        $form_post_id = $form_tag->id();
        $form_value   = serialize( $form_data );
        $form_date    = current_time('Y-m-d H:i:s');

        $cfdb->insert( $table_name, array(
            'form_post_id' => $form_post_id,
            'form_value'   => $form_value,
            'form_date'    => $form_date,
            'email'    => trim($email)
            
        ) );

        /* cfdb7 after save data */
        $insert_id = $cfdb->insert_id;
        do_action( 'cfdb7_after_save_data', $insert_id );
    }

}

add_action( 'wpcf7_before_send_mail', 'cfdb7_before_send_mail' );


add_action( 'init', 'cfdb7_init');

/**
 * CFDB7 cfdb7_init and cfdb7_admin_init
 * Admin setting
 */
function cfdb7_init(){

    do_action( 'cfdb7_init' );

    if( is_admin() ){

        require_once 'inc/admin-mainpage.php';
        require_once 'inc/admin-subpage.php';
        require_once 'inc/admin-form-details.php';
        require_once 'inc/export-csv.php';

        do_action( 'cfdb7_admin_init' );

        $csv = new Expoert_CSV();
        if( isset($_REQUEST['csv']) && ( $_REQUEST['csv'] == true ) && isset( $_REQUEST['nonce'] ) ) {

            $nonce  = filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_STRING );

            if ( ! wp_verify_nonce( $nonce, 'dnonce' ) ) wp_die('Invalid nonce..!!');

            $csv->download_csv_file();
        }
        new Cfdb7_Wp_Main_Page();
    }
}


add_action( 'admin_notices', 'cfdb7_admin_notice' );
add_action('admin_init', 'cfdb7_view_ignore_notice' );

function cfdb7_admin_notice() {

    $install_date = get_option( 'cfdb7_view_install_date', '');
    $install_date = date_create( $install_date );
    $date_now     = date_create( date('Y-m-d G:i:s') );
    $date_diff    = date_diff( $install_date, $date_now );

    if ( $date_diff->format("%d") < 7 ) {

        return false;
    }

    global $current_user ;
    $user_id = $current_user->ID;

    if ( ! get_user_meta($user_id, 'cfdb7_view_ignore_notice' ) ) {

        echo '<div class="updated"><p>';

      //  printf(__('Awesome, you\'ve been using <a href="admin.php?page=cfdb7-list.php">Contact Form CFDB7</a> for more than 1 week. May we ask you to give it a 5-star rating on WordPress? | <a href="%2$s" target="_blank">Ok, you deserved it</a> | <a href="%1$s">I already did</a> | <a href="%1$s">No, not good enough</a>'), '?cfdb7-ignore-notice=0',
        //'https://wordpress.org/plugins/contact-form-cfdb7/');
        echo "</p></div>";
    }
}

function cfdb7_view_ignore_notice() {
    global $current_user;
    $user_id = $current_user->ID;

    if ( isset($_GET['cfdb7-ignore-notice']) && '0' == $_GET['cfdb7-ignore-notice'] ) {

        add_user_meta($user_id, 'cfdb7_view_ignore_notice', 'true', true);
    }
}

/**
 * Plugin settings link
 * @param  array $links list of links
 * @return array of links
 */
function cfdb7_settings_link( $links ) {
  $forms_link = '<a href="admin.php?page=cfdb7-list.php">Contact Forms</a>';
  array_unshift($links, $forms_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'cfdb7_settings_link' );


/*EQUAL SPACE CHALLENGE MOD*/


add_action( 'admin_menu', 'register_menu_page' );
function register_menu_page() {
  // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page( '#EqualSpace Challenge', '#EqualSpace', 'manage_options', __FILE__, '', '',1,plugins_url( 'equal-space-challenge/sgf.png' ) );
    
/*
    add_menu_page( 
        __( '#EqualSpace Challenge', 'equal-space-challenge' ),
        '#EqualSpace',
        'manage_options',
        '__FILE__',
        'equalspace_challenge',
        plugins_url( 'equal-space-challenge/equalspace.svg' ),
        6
    ); */
    //this is the direct path to the inbound entries, the fid must be pointed to the correct form
    add_submenu_page(__FILE__, 'Entries', 'New Entries', 'manage_options', 'cfdb7-list.php&fid=5', 'equalspace_entries');
     //this is the direct path to the approved entries, directed to the entry content type
    add_submenu_page(__FILE__, 'Entries', 'Approved Entries', 'manage_options', '/edit.php&post_type=entry', 'equalspace_entries');
    //this is the direct path to the voting list, the fid must be pointed to the correct form
   add_submenu_page(__FILE__, 'Voting', 'Voting', 'manage_options','cfdb7-list.php&fid=4763', 'equalspace_voting');

 
}
/* THIS CREATES THE METABOX IN ENTRIES TO EDIT CUSTOM FIELDS */

function challenge_meta_box( $meta_boxes ) {
    $prefix = 'challenge';

    $meta_boxes[] = array(
        'id' => 'challenge',
        'title' => esc_html__( '#EqualSpace Challenge', 'challenge-info' ),
        'post_types' => array( 'entry' ),
        'context' => 'advanced',
        'priority' => 'default',
        'autosave' => false,
        'fields' => array(
            
            
            array(
                'id' => $prefix . '_video',
                'type' => 'text',
                'name' => esc_html__( 'VideoURL', 'challenge_video' ),
                'desc' => esc_html__( 'USE EMBED VERSION
                    youtube.com/embed/~video id~
                    player.vimeo.com/video/~video_id~
                    Test Before submitting', 'challenge_video' ),
                'placeholder' => esc_html__( '', 'embed video url' ),
                'size' => 25,
            ), array(
                'id' => $prefix . 'rs',
                'type' => 'text',
                'name' => esc_html__( 'Challengers', 'Challengers' ),
                'desc' => esc_html__( 'Names', 'challengers' ),
                'placeholder' => esc_html__( '', '' ),
                'size' => 25,
            )



        ),
    );

    return $meta_boxes;
}
