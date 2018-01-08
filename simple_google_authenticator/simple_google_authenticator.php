<?php
/**
 * @package Simple_Google_Authenticator
 * @version 1.0
 */
/*
Plugin Name: Simple Google Authenticator
Plugin URI: https://github.com/blogscripthere/Simple_Google_Authenticator
Description: ScriptHere's a simple Google Authenticator
Author: Narendra Padala
Version: 1.0
Author URI: http://scripthere.com/
Text Domain: shtfa
Version: 1.0
Last Updated: 20/01/2018
*/

/**
 * Initialize Google Two-Factor Authentication library
 */
require_once "lib/GoogleAuthenticator.php";

/**
 * Adding Google Two-Factor Authentication QR Code widget at dashboard
 */
add_action( 'wp_dashboard_setup','sh_add_dashboard_tfa_qrcode_widget');

/**
 * Add a Google Two-Factor Authentication QR Code widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function sh_add_dashboard_tfa_qrcode_widget() {
    //get setting details
    $enable_tfa = get_option( 'sh_enable_tfa', '');
    //check
    if($enable_tfa) {
        wp_add_dashboard_widget(
            'qrcode_dashboard_widget',
            'Two-Factor Authentication QR Code',
            'sh_display_tfa_qrcode_widget_callback');
    }
}

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function sh_display_tfa_qrcode_widget_callback() {
    //title
    $tfa_title = str_replace(" ","",get_bloginfo( 'name' ));
    //default secret
    $tfa_secret = 'KAFDR6S2HE4ARLSF';
    //url
    $tfa_url =  home_url();
    //time
    $time = floor(time() / 30);
    //init object
    $tfa_obj = new GoogleAuthenticator();

    //check if secret already not exists create new one and update
    if(!$tfa_secret = get_option('_google_tfa_secret')){
        //get new secret
        $tfa_secret = $tfa_obj->createSecret();
        //update at options
        add_option('_google_tfa_secret',$tfa_secret);
    }else{
        //get secret
        $tfa_secret = get_option('_google_tfa_secret');
    }
    //get qr code url
    $tfa_qrurl = $tfa_obj->getQRCodeGoogleUrl($tfa_title,$tfa_secret,$tfa_url);

    $html = '';
    $html .= '<div>Scan your QR with Google Authenticator, download from <a target="_blank" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&amp;hl=en" > Google Play </a> or the <a target="_blank" href="https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8" >App Store </a> and use the same app codes when signing with Two-factor authentication</div>';
    $html .= '<div><img src="'.$tfa_qrurl.'" /></div>';

    // Display Google Two-Factor Authentication QR Code
    echo $html;
}

/**
 * Add a setting to enable Two-Factor Authentication at wp-login.php
 */
add_filter('admin_init', 'sh_general_settings_register_fields');

/**
 * Register a setting to enable Two-Factor Authentication setting at General settings
 */
function sh_general_settings_register_fields() {
    //register
    register_setting('general', 'sh_enable_tfa', 'esc_attr');
    //add setting field
    add_settings_field('sh_enable_tfa_id', '<label for="sh_enable_tfa_id">'.__('Enable Two-Factor Authentication' , 'shtfa' ).'</label>' , 'sh_general_settings_tfa_field_html', 'general');
}

/**
 * Register a setting to enable Two-Factor Authentication setting at General settings callback
 */
function sh_general_settings_tfa_field_html(){
    //get setting details
    $value = get_option( 'sh_enable_tfa', '');
    //display setting
    echo $html = '<input type="checkbox" id="sh_enable_tfa_id" name="sh_enable_tfa" value="1"' . checked( 1,$value, false ) . '/>';
}


/**
 * Add additional Two-Factor Authentication custom field at wp-login.php callback
 */
function sh_add_tfa_code_field(){
    //init
    $html = '<p>
        <label for="tfa_code">'.__("Two-Factor Authentication","shtfa").'<br>
        <input type="text" name="tfa_code" id="tfa_code" class="input" value="" size="20"></label>
    </p>';
    //get setting details
    $enable_tfa = get_option( 'sh_enable_tfa', '');
    //check
    if($enable_tfa) {
        //display
        echo $html;
    }
}

/**
 * display additional Two-Factor Authentication custom field at wp-login.php hook
 */
add_action('login_form','sh_add_tfa_code_field');

/**
 * First, check whether the user entered the appropriate Google Two-Factor code when the user attempted to sign in.
 * You can sign in by entering the correct code. Otherwise, an error message is displayed that indicates the invalid two-factor authentication code.
 * Used "authenticate" filter
 */
function sh_tfa_signon_callback( $user, $username, $password ){
    //get setting details
    $enable_tfa = get_option( 'sh_enable_tfa', '');

    //Check whether user name exists, then go forward otherwise return user as it is
    if (!empty($username) && !empty($enable_tfa)) {
        //init object
        $tfa_obj = new GoogleAuthenticator();
        //get secret
        $tfa_secret = get_option('_google_tfa_secret');
        //get user entered tfa code
        $tfa_code = $_POST['tfa_code'];
        //check
        if ($tfa_obj->verifyCode($tfa_secret,$tfa_code)) {
            //return
            return $user;
        }else{
            //message
            $message = __('Invalid two-factor authentication code', 'shtfa');
            //Set up a error message filter to allow users to customize error message when they need to use filters
            $message = apply_filters('sh_two_factor_authentication_error_message',$message);
            //return a error message
            return new WP_Error('_login_two_factor_authentication_error', $message);
        }
    }
    //return
    return $user;
}

/**
 * First, check whether the user entered the appropriate Google Two-Factor code when the user attempted to sign in.
 * You can sign in by entering the correct code. Otherwise, an error message is displayed that indicates the invalid two-factor authentication code.
 * Use "authenticate" filter hook to check this
 */
add_filter( 'authenticate', 'sh_tfa_signon_callback', 30, 3 );



