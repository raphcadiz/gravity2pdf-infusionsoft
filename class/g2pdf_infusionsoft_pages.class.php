<?php
class G2PDF_Infusionsoft_Pages {

    public function __construct() {
        add_action('admin_init', array( $this, 'settings_options_init' ));
        add_action('admin_menu', array( $this, 'admin_menus'), 12 );
    }

    public function settings_options_init() {
        register_setting( 'gmergeinfusionsoft_settings_options', 'gmergeinfusionsoft_settings_options', '' );
    }

    public function admin_menus() {
        add_submenu_page ( 'gravitymerge' , 'Infusionsoft' , 'Infusionsoft' , 'manage_options' , 'gravitymergeinfusionsoft' , array( $this , 'gravity2pdf_infusionsoft' ));
    }

    public function gravity2pdf_infusionsoft() {
        include_once(GMI_PATH_INCLUDES.'/gravity_merge_infusionsoft.php');
    }
}

new G2PDF_Infusionsoft_Pages();