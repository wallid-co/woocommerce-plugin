<?php


namespace WallidCommerceGateway;


class AdminPortalUI
{
    /**
     * Allowed HTML for WooCommerce gateway settings markup.
     *
     * @return array
     */
    private static function getAllowedSettingsHtml()
    {
        $allowed_html = wp_kses_allowed_html('post');

        $allowed_html['table'] = [
            'class' => true,
            'role' => true,
        ];
        $allowed_html['tbody'] = [];
        $allowed_html['thead'] = [];
        $allowed_html['tfoot'] = [];
        $allowed_html['tr'] = [
            'valign' => true,
            'class' => true,
        ];
        $allowed_html['th'] = [
            'scope' => true,
            'class' => true,
        ];
        $allowed_html['td'] = [
            'class' => true,
            'style' => true,
            'colspan' => true,
        ];
        $allowed_html['fieldset'] = [
            'class' => true,
        ];
        $allowed_html['legend'] = [
            'class' => true,
        ];
        $allowed_html['label'] = [
            'for' => true,
            'class' => true,
        ];
        $allowed_html['input'] = [
            'id' => true,
            'type' => true,
            'name' => true,
            'value' => true,
            'class' => true,
            'placeholder' => true,
            'checked' => true,
            'readonly' => true,
            'disabled' => true,
            'autocomplete' => true,
            'step' => true,
            'min' => true,
            'max' => true,
            'maxlength' => true,
            'size' => true,
            'style' => true,
            'data-*' => true,
        ];
        $allowed_html['select'] = [
            'id' => true,
            'name' => true,
            'class' => true,
            'disabled' => true,
            'multiple' => true,
            'style' => true,
            'data-*' => true,
        ];
        $allowed_html['option'] = [
            'value' => true,
            'selected' => true,
            'disabled' => true,
        ];
        $allowed_html['optgroup'] = [
            'label' => true,
        ];
        $allowed_html['textarea'] = [
            'id' => true,
            'name' => true,
            'class' => true,
            'rows' => true,
            'cols' => true,
            'placeholder' => true,
            'readonly' => true,
            'disabled' => true,
            'style' => true,
            'data-*' => true,
        ];

        return $allowed_html;
    }

    public static function get($settings)
    {
        ?>
        <h2><?php echo esc_html__('Wallid Payment Gateway', 'wallid'); ?></h2>
        <table class="form-table">
            <?php echo wp_kses($settings, self::getAllowedSettingsHtml()); ?>
        </table>
        <?php
    }
}
