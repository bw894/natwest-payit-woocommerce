<?php
if (!defined('ABSPATH')) exit;

$options = NatWest_PayIt_Admin::get();
?>

<div class="wrap">
    <h1>NatWest PayIt Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields(NatWest_PayIt_Admin::OPTION_KEY); ?>

        <table class="form-table">

            <tr>
                <th>Environment</th>
                <td>
                    <select name="natwest_payit_settings[environment]">
                        <option value="sandbox" <?= selected($options['environment'] ?? '', 'sandbox') ?>>Sandbox</option>
                        <option value="live" <?= selected($options['environment'] ?? '', 'live') ?>>Live</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>Client ID</th>
                <td>
                    <input type="text" name="natwest_payit_settings[client_id]"
                           value="<?= esc_attr($options['client_id'] ?? '') ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th>Client Secret</th>
                <td>
                    <input type="password" name="natwest_payit_settings[client_secret]"
                           value="<?= esc_attr($options['client_secret'] ?? '') ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th>Company ID</th>
                <td>
                    <input type="text" name="natwest_payit_settings[company_id]"
                           value="<?= esc_attr($options['company_id'] ?? '') ?>">
                </td>
            </tr>

            <tr>
                <th>Brand ID</th>
                <td>
                    <input type="text" name="natwest_payit_settings[brand_id]"
                           value="<?= esc_attr($options['brand_id'] ?? '') ?>">
                </td>
            </tr>

            <tr>
                <th>Enable Logging</th>
                <td>
                    <label>
                        <input type="checkbox" name="natwest_payit_settings[logging]" value="1"
                            <?= checked($options['logging'] ?? '', '1') ?>>
                        Enable debug logging
                    </label>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>
