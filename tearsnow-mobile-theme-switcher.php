<?php defined( 'ABSPATH' ) or exit;
/**
 * TearSnow Mobile Theme Switcher for WordPress
 * 
 * Plugin Name:       TearSnow Mobile Theme Switcher
 * Description:       Automatically switch WordPress themes for mobile visitors.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            TearSnow
 * Author URI:        https://tearsnow.com
 * License:           GPL v2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tearsnow-mobile-theme-switcher
 */

final class TearSnow_Mobile_Theme_Switcher {
    private const OPTION = 'tearsnow_mobile_theme';

    public function __construct() {
        // Register plugin settings
        add_action('admin_init', [$this, 'register_setting']);
        // Switch theme
        add_filter('stylesheet', [$this, 'switch_theme']);
        add_filter('template', [$this, 'switch_template']);
        // settings link
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_settings_link']);
    }


    // Register plugin settings
    public function register_setting(): void {
        register_setting('general', self::OPTION,
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_key',
                'default'           => '',
            ]
        );
        add_settings_field(self::OPTION, __('Mobile Theme', 'tearsnow-mobile-theme-switcher'),
            [$this, 'render_field'],
            'general',
            'default',
            [
                'label_for' => self::OPTION,
            ]
        );
    }

    // Get configured mobile theme
    private function get_mobile_theme(): ?WP_Theme {
        static $theme = null;

        if ($theme instanceof WP_Theme) {
            return $theme;
        }

        $stylesheet = get_option(self::OPTION,'');
        if (!$stylesheet) {
            return null;
        }

        $theme = wp_get_theme($stylesheet);

        return $theme->exists() ? $theme : null;
    }


    // Render theme selection field
    public function render_field(): void {
        $selected = get_option(self::OPTION, '');
        $themes = wp_get_themes();

        echo '<select name="' . esc_attr(self::OPTION) . '" id="' . esc_attr(self::OPTION) . '">';
        echo '<option value="">' . esc_html__('Disabled','tearsnow-mobile-theme-switcher') . '</option>';
        foreach ($themes as $stylesheet => $theme) {
            if (!$theme->exists()) {
                continue;
            }
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($stylesheet),
                selected(
                    $selected,
                    $stylesheet,
                    false
                ),
                esc_html($theme->get('Name'))
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select the theme used for mobile visitors.','tearsnow-mobile-theme-switcher') . '</p>';
    }

    // Check mobile device
    private function is_mobile(): bool {
        if (
            is_admin()
            || is_customize_preview()
            || wp_doing_ajax()
            || wp_is_json_request()
        ) {
            return false;
        }
        return wp_is_mobile();
    }

    // Load mobile stylesheet
    public function switch_theme( string $stylesheet ): string {
        if ( ! $this->is_mobile() ) {
            return $stylesheet;
        }

        $theme = $this->get_mobile_theme();

        if (!$theme) {
            return $stylesheet;
        }

        return $theme->get_stylesheet();
    }

    // Load mobile template
    public function switch_template(string $template): string {
        if (!$this->is_mobile()) {
            return $template;
        }

        $theme = $this->get_mobile_theme();

        if (!$theme) {
            return $template;
        }

        return $theme->get_template();
    }

    //  Add settings link
    public function plugin_settings_link( array $links ): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(
                admin_url('options-general.php#' . self::OPTION)
            ),
            esc_html__('Settings', 'tearsnow-mobile-theme-switcher')
        );

        $links[] = $settings_link;
        return $links;
    }

}

new TearSnow_Mobile_Theme_Switcher();