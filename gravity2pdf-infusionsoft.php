<?php
/**
 * Plugin Name: Gravity 2 PDF - Infusionsoft
 * Plugin URI:  https://www.gravity2pdf.com
 * Description: Deiliver completed merge to Infusionsoft
 * Version:     1.0
 * Author:      gravity2pdf
 * Author URI:  https://github.com/raphcadiz
 * Text Domain: g2pdf-infusionsoft
 */

define( 'GMI_PATH', dirname( __FILE__ ) );
define( 'GMI_PATH_CLASS', dirname( __FILE__ ) . '/class' );
define( 'GMI_PATH_INCLUDES', dirname( __FILE__ ) . '/includes' );
define( 'GMI_FOLDER', basename( GMI_PATH ) );
define( 'GMI_URL', plugins_url() . '/' . GMI_FOLDER );
define( 'GMI_URL_INCLUDES', GMI_URL . '/includes' );

if(!class_exists('G2PDF_Infusionsoft')):

    register_activation_hook( __FILE__, 'g2pdf_infusionsoft_activation' );
    function g2pdf_infusionsoft_activation(){
        if ( ! class_exists('Gravity_Merge') ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die('Sorry, but this plugin requires the Gravity2PDF to be installed and active.');
        }
    }

    register_deactivation_hook( __FILE__, 'g2pdf_infusionsoft_deactivation' );
    function g2pdf_infusionsoft_deactivation(){
        // deactivation block
    }

    add_action( 'admin_init', 'g2pdf_infusionsoft_plugin_activate' );
    function g2pdf_infusionsoft_plugin_activate(){
        if ( ! class_exists('Gravity_Merge') ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
        }
    }

    require_once(GMI_PATH.'/vendor/autoload.php');
    
    // include classes
    include_once(GMI_PATH_CLASS.'/g2pdf_infusionsoft_main.class.php');
    include_once(GMI_PATH_CLASS.'/g2pdf_infusionsoft_pages.class.php');

    add_action( 'plugins_loaded', array( 'G2PDF_Infusionsoft', 'get_instance' ) );
endif;