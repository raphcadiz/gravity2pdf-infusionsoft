<div class="wrap" id="gravity-merge-wrap">
    <h1>Gravity 2 PDF - Infusionsoft</h1>
    <br />
    <?php settings_errors() ?>
    <div class="content-wrap">
        <?php
            $gmergeinfusionsoft_settings_options = get_option('gmergeinfusionsoft_settings_options');
            $client_id            = isset($gmergeinfusionsoft_settings_options['client_id']) ? $gmergeinfusionsoft_settings_options['client_id'] : '';
            $client_secret        = isset($gmergeinfusionsoft_settings_options['client_secret']) ? $gmergeinfusionsoft_settings_options['client_secret'] : '';
            $token                = isset($gmergeinfusionsoft_settings_options['token']) ? $gmergeinfusionsoft_settings_options['token'] : '';
            $infusionsoft_tags                = isset($gmergeinfusionsoft_settings_options['infusionsoft_tags']) ? $gmergeinfusionsoft_settings_options['infusionsoft_tags'] : '';
            
        ?>
        <br />
        <form method="post" action="options.php">
            <?php settings_fields( 'gmergeinfusionsoft_settings_options' ); ?>
            <?php do_settings_sections( 'gmergeinfusionsoft_settings_options' ); ?> 
            <table class="form-table">
                <tbody>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row">
                            <label>Client ID</label>
                        </th>
                        <td>
                            <input type="text" name="gmergeinfusionsoft_settings_options[client_id]" size="40" width="40" value="<?= $client_id ?>">
                        </td>
                    </tr>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row">
                            <label>Client Secret</label>
                        </th>
                        <td>
                            <input type="text" name="gmergeinfusionsoft_settings_options[client_secret]" size="40" width="40" value="<?= $client_secret ?>">
                        </td>
                    </tr>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row">
                            <label>Access Token</label>
                        </th>
                        <td>
                            <textarea rows="5" readonly="" name="gmergeinfusionsoft_settings_options[token]"><?= $token ?></textarea>
                        </td>
                    </tr>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row">
                            <label>Tags</label>
                        </th>
                        <td>
                            <?php
                                $tags = [];
                                if( !empty($infusionsoft_tags) ){
                                    foreach (unserialize($infusionsoft_tags) as $key => $tag) {
                                        $tags[] = $tag['GroupName'];
                                    }
                                }
                            ?>
                            <textarea rows="10" disabled="" name=""><?= implode(", ", $tags) ?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>
                <input type="submit" name="save_settings" class="button button-primary" value="Save">
                <?php if (!empty($client_id) && !empty($client_secret)): ?>
                <a href="<?= admin_url( 'admin.php?page=gravitymergeinfusionsoft&integration=infusionsoft' ); ?>" class="button button-primary">Get Access Token</a>
                    <?php if (!empty($token)): ?>
                        <a href="<?= admin_url( 'admin.php?page=gravitymergeinfusionsoft&integration=infusionsoftsynctags' ); ?>" class="button">Sync Tags</a>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </form>
    </div>
</div>