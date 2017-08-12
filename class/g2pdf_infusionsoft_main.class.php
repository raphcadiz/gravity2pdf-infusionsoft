<?php
class G2PDF_Infusionsoft {
    private static $instance;

    // plugin version
    private $version = '1.0';

    public static function get_instance()
    {
        if( null == self::$instance ) {
            self::$instance = new G2PDF_Infusionsoft();
            self::$instance->licensing();
        }

        return self::$instance;
    }

    private function licensing() {
        if ( class_exists( 'Gravity_Merge_License' ) ) {
            $license = new Gravity_Merge_License( __FILE__, 'Gravity 2 PDF - Infusionsoft', $this->version, 'Raph Cadiz' );
        }
    }

    public function __construct() {
        add_filter('gmerge_integrations_ajax_paths', array( $this, 'register_integrations_path' ), 1);
        add_action( 'admin_init', array($this, 'getInfusionsoftAccessToken') );
        add_action( 'admin_init', array($this, 'setInfusionsoftToken') );
        add_action( 'admin_init', array($this, 'syncInfusionsoftTags') );
        add_action('g2pdf_after_merge', array($this, 'process_integration'), 10, 4);
        add_action('wp_ajax_infusionsoftIntegrationTemplate' , array( $this , 'ajax_integration_template' ));
        add_action('infusionsoft_integration_template' , array( $this , 'integration_template' ), 10, 3);
    }

    public function register_integrations_path($paths) {
        $dropbox_path = array(
            'infusionsoft' => 'infusionsoftIntegrationTemplate'
        );

        return array_merge( $paths, $dropbox_path );
    }

    public function getInfusionsoftAccessToken() {
        if (isset($_REQUEST['integration']) && $_REQUEST['integration'] == 'infusionsoft' ):
            $gmergeinfusionsoft_settings_options    = get_option('gmergeinfusionsoft_settings_options');
            $infusionsoft_client_id         = isset($gmergeinfusionsoft_settings_options['client_id']) ? $gmergeinfusionsoft_settings_options['client_id'] : '';
            $infusionsoft_client_secret     = isset($gmergeinfusionsoft_settings_options['client_secret']) ? $gmergeinfusionsoft_settings_options['client_secret'] : '';

            if( !empty($infusionsoft_client_id) && !empty($infusionsoft_client_secret) ){
                $infusionsoft = new \Infusionsoft\Infusionsoft(array(
                    'clientId'     => $infusionsoft_client_id,
                    'clientSecret' => $infusionsoft_client_secret,
                    'redirectUri'  => admin_url()
                ));
                
                if (!isset($_GET['code'])) {
                    update_option( 'infusionsoft_request_token', 1 );
                    $authorizationUrl = $infusionsoft->getAuthorizationUrl();
                    header('Location: ' . $authorizationUrl);
                }
            }
        endif;
    }

    public function setInfusionsoftToken() {
        $infusionsoft_request_token     = get_option('infusionsoft_request_token');
        if( isset($infusionsoft_request_token) && $infusionsoft_request_token == 1 ) {
            $gmergeinfusionsoft_settings_options    = get_option('gmergeinfusionsoft_settings_options');
            $infusionsoft_client_id             = isset($gmergeinfusionsoft_settings_options['client_id']) ? $gmergeinfusionsoft_settings_options['client_id'] : '';
            $infusionsoft_client_secret     = isset($gmergeinfusionsoft_settings_options['client_secret']) ? $gmergeinfusionsoft_settings_options['client_secret'] : '';

            if( !empty($infusionsoft_client_id) && !empty($infusionsoft_client_secret) ){
                $infusionsoft = new \Infusionsoft\Infusionsoft(array(
                    'clientId'     => $infusionsoft_client_id,
                    'clientSecret' => $infusionsoft_client_secret,
                    'redirectUri'  => admin_url()
                ));

                if (isset($_GET['code'])) {
                    update_option( 'infusionsoft_token_key', 0 );
                    $gmergeinfusionsoft_settings_options['token'] = serialize($infusionsoft->requestAccessToken($_GET['code']));
                    // self::getInfusionTags();

                    update_option( 'gmergeinfusionsoft_settings_options', $gmergeinfusionsoft_settings_options );
                    header('Location: ' . admin_url( 'admin.php?page=gravitymergeinfusionsoft' ));
                }
            }
        }
    }

    private function getInfusionTags() {
        $gmergeinfusionsoft_settings_options    = get_option('gmergeinfusionsoft_settings_options');
        $infusionsoft_client_id             = isset($gmergeinfusionsoft_settings_options['client_id']) ? $gmergeinfusionsoft_settings_options['client_id'] : '';
        $infusionsoft_client_secret     = isset($gmergeinfusionsoft_settings_options['client_secret']) ? $gmergeinfusionsoft_settings_options['client_secret'] : '';
        $infusionsoft_token_key     = isset($gmergeinfusionsoft_settings_options['token']) ? $gmergeinfusionsoft_settings_options['token'] : '';

        if( !empty($infusionsoft_client_id) && !empty($infusionsoft_client_secret) ){
            $infusionsoft = new \Infusionsoft\Infusionsoft(array(
                'clientId'     => $infusionsoft_client_id,
                'clientSecret' => $infusionsoft_client_secret,
                'redirectUri'  => admin_url()
            ));

            $infusionsoft->setToken(unserialize($infusionsoft_token_key));

            $tags = [];
            $page = 0;

            do {
                $result = $infusionsoft
                    ->data
                    ->query('ContactGroup', 1000, $page, ['id' => '%'], ['id', 'GroupName', 'GroupCategoryId'], 'GroupName', true);

                $tags = array_merge($tags, $result);

            } while (count($result) === 1000);

            $gmergeinfusionsoft_settings_options['infusionsoft_tags'] = serialize($tags);
            
            update_option( 'gmergeinfusionsoft_settings_options', $gmergeinfusionsoft_settings_options );
        }
    }

    public function syncInfusionsoftTags() {
        if (isset($_REQUEST['integration']) && $_REQUEST['integration'] == 'infusionsoftsynctags' ):
            self::getInfusionTags();
            header('Location: ' . admin_url( 'admin.php?page=gravitymergeinfusionsoft' ));
        endif;
    }

    public function ajax_integration_template() {
        $gmergeinfusionsoft_settings_options    = get_option('gmergeinfusionsoft_settings_options');
        $infusionsoft_tags              = isset($gmergeinfusionsoft_settings_options['infusionsoft_tags']) ? $gmergeinfusionsoft_settings_options['infusionsoft_tags'] : '';
        $tags_options = '';
        foreach (unserialize($infusionsoft_tags) as $key => $tag) {
            $tags_options .=  '<option value="'.$tag['id'].'">'. $tag['GroupName'] .'</option>';
        }

        $email_options = array();
        if( isset( $_POST['data'] ) ):
            $form_id = isset($_POST['data']['form_id']) ? $_POST['data']['form_id'] : '';
            $form = GFAPI::get_form( $form_id );
            foreach ( $form['fields'] as $key => $field) {
                if( $field['type'] == 'email' ){
                    $email_options[] = array(
                        'field_id'  => $field['id'],
                        'type'      => $field['type'],
                        'label'     => $field['label']
                    );
                }
            }
        endif;
        $email_select  = '<select name="integrations[%key%][infusionsoft][email]" class="select-2 email-other">';
            $email_select .= '<option value="current_user">Current User</option>';
            foreach($email_options as $email_option){
                $email_select .= '<option value="'.$email_option['field_id'].'">'.$email_option['label'].'</option>';
            }
        $email_select .= '</select>';

        ob_start();
        ?>
        <div class="integration-wrapper">
            <a href="javascript:;" class="integration-remove"><span class="dashicons dashicons-minus"></span></a>
            <label><strong>Infusionsoft</strong></label><br /><br />
            <input type="hidden" name="integrations[%key%][infusionsoft][integration_infusionsoft]" value="1" />
            <label>Contact</label><br />
                <?= $email_select ?>
            <br /><br />
            <label>Apply Tags</label><br />
            <select multiple name="integrations[%key%][infusionsoft][apply_tags][]" class="select-2 multiselect"><?= $tags_options ?></select>
            <br /><br />
            <label>Remove Tags</label><br />
            <select multiple name="integrations[%key%][infusionsoft][remove_tags][]" class="select-2 multiselect"><?= $tags_options ?></select>
            <br /><br />
            <label>
                <input type="checkbox" value="1" name="integrations[%key%][infusionsoft][create_contact]" />
                Create new user if email doesn\'t exist
            </label>
        </div>
        <?php
        $template = ob_get_contents();
        ob_end_clean();

        echo $template;
        die();
    } 

    public function integration_template($index = 0, $value = array(), $form_id) {
        $gmergeinfusionsoft_settings_options    = get_option('gmergeinfusionsoft_settings_options');
        $infusionsoft_tags              = isset($gmergeinfusionsoft_settings_options['infusionsoft_tags']) ? $gmergeinfusionsoft_settings_options['infusionsoft_tags'] : '';
        $apply_tags_options = '';
        foreach (unserialize($infusionsoft_tags) as $key => $tag) {
            $selected = in_array($tag['id'], $value->apply_tags) ? 'selected' : '';
            $apply_tags_options .=  '<option value="'.$tag['id'].'" '.$selected.'>'. $tag['GroupName'] .'</option>';
        }

        $remove_tags_option = '';
        foreach (unserialize($infusionsoft_tags) as $key => $tag) {
            $selected = in_array($tag['id'], $value->remove_tags) ? 'selected' : '';
            $remove_tags_option .=  '<option value="'.$tag['id'].'" '.$selected.'>'. $tag['GroupName'] .'</option>';
        }

        $email_options = array();
        $form = GFAPI::get_form( $form_id );
        foreach ( $form['fields'] as $key => $field) {
            if( $field['type'] == 'email' ){
                $email_options[] = array(
                    'field_id'  => $field['id'],
                    'type'      => $field['type'],
                    'label'     => $field['label']
                );
            }
        }
        $email_select  = '<select name="integrations['. $index .'][infusionsoft][email]" class="select-2 email-other">';
            $email_select .= '<option value="current_user">Current User</option>';
            foreach($email_options as $email_option){
                $selected = ($value->email == $email_option['field_id']) ? 'selected' : '';
                $email_select .= '<option value="'.$email_option['field_id'].'" '.$selected.'>'.$email_option['label'].'</option>';
            }
        $email_select .= '</select>';

        ob_start();
        ?>
        <div class="integration-wrapper">
            <a href="javascript:;" class="integration-remove"><span class="dashicons dashicons-minus"></span></a>
            <label><strong>Infusionsoft</strong></label><br /><br />
            <input type="hidden" name="integrations[<?= $index ?>][infusionsoft][integration_infusionsoft]" value="1" />
            <label>Contact</label><br />
                <?= $email_select ?>
            <br /><br />
            <label>Apply Tags</label><br />
            <select multiple name="integrations[<?= $index ?>][infusionsoft][apply_tags][]"><?= $apply_tags_options ?></select>
            <br /><br />
            <label>Remove Tags</label><br />
            <select multiple name="integrations[<?= $index ?>][infusionsoft][remove_tags][]"><?= $remove_tags_option ?></select>
            <br /><br />
            <label>
                <input type="checkbox" value="1" name="integrations[<?= $index ?>][infusionsoft][create_contact]" <?= ($value->create_contact) ? 'checked' : '' ?> />
                Create new user if email doesn\'t exist
            </label>
        </div>
        <?php
        $template = ob_get_contents();
        ob_end_clean();

        echo $template;
    }

    public function process_integration($final_file, $file_name, $entry, $integrations){
        if(!property_exists($integrations, 'infusionsoft'))
            return;

        $integration = $integrations->infusionsoft;
        $user_email = '';
        if( $integration->email === 'current_user' ) {
            if( !is_user_logged_in() )
            return;

            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email;
        } else {
            $user_email = rgar( $entry, $integration->email );
        }
        

        if( empty($user_email) )
            return;

        $gmergeinfusionsoft_settings_options    = get_option('gmergeinfusionsoft_settings_options');
        $infusionsoft_client_id         = isset($gmergeinfusionsoft_settings_options['client_id']) ? $gmergeinfusionsoft_settings_options['client_id'] : '';
        $infusionsoft_client_secret     = isset($gmergeinfusionsoft_settings_options['client_secret']) ? $gmergeinfusionsoft_settings_options['client_secret'] : '';
        $infusionsoft_token_key     = isset($gmergeinfusionsoft_settings_options['token']) ? $gmergeinfusionsoft_settings_options['token'] : '';

        if( !empty($infusionsoft_client_id) && !empty($infusionsoft_client_secret) ){
            try {
                $infusionsoft = new \Infusionsoft\Infusionsoft(array(
                    'clientId'     => $infusionsoft_client_id,
                    'clientSecret' => $infusionsoft_client_secret,
                    'redirectUri'  => admin_url()
                ));
                
                $infusionsoft->setToken(unserialize($infusionsoft_token_key));
                $infusionsoft->refreshAccessToken();
                $gmergeinfusionsoft_settings_options['token'] = serialize($infusionsoft->getToken());
                update_option( 'gmergeinfusionsoft_settings_options', $gmergeinfusionsoft_settings_options );
                $contact_details = $infusionsoft->contacts('xml')->findByEmail($user_email, array());

                $contact_id = 0;
                if ( empty($contact_details) ) {
                    if( $integration->create_contact ) {
                        $contact_details = $infusionsoft->contacts('xml')->add(['email' => $user_email]);
                        $contact_id = $contact_details;
                    }
                } else {
                    $contact_id = $contact_details[0]['Id'];
                }
            
                if ( !empty($contact_id) ){
                    $fileOpen = fopen($final_file, 'r');
                    $data = fread($fileOpen, filesize($final_file));
                    fclose($fileOpen);
                    $dataEncoded = base64_encode($data);
                    $uploadFile = $infusionsoft->files->uploadFile($contact_id, $file_name.'.pdf', $dataEncoded);
                }

                if( !empty($integration->remove_tags) ) {
                    foreach($integration->remove_tags as $tag_id) {
                        $infusionsoft->contacts('xml')->removeFromGroup($contact_id, $tag_id);
                    }
                }

                if( !empty($integration->apply_tags) ) {
                    foreach($integration->apply_tags as $tag_id) {
                        $infusionsoft->contacts('xml')->addToGroup($contact_id, $tag_id);
                    }
                }

                // done
            } catch (Exception $e) {
                error_log($e);
            }
        }
    }
}