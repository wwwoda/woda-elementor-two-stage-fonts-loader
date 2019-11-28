<?php
/**
 * Plugin Name:     Woda Elementor Two Stage Fonts Loader
 * Plugin URI:      https://github.com/wwwoda/wp-plugin-elementor-two-stage-fonts-loader
 * Description:     ...
 * Author:          Woda
 * Author URI:      https://www.woda.at
 * Text Domain:     woda-elementor-two-stage-fonts-loader
 * Domain Path:     /languages
 * Version:         0.1.2
 *
 * @package         Woda_Elementor_Two_Stage_Fonts_Loader
 */

// Copyright (c) 2019 Woda Digital OG. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace Woda\WordPress\Elementor\TwoStageFontsLoader;

include_once 'vendor/autoload.php';

add_action('elementor_pro/init', static function (): void {
    $settings = apply_filters('woda_elementor_two_stage_fonts_loader_settings', []);
    Loader::register($settings);
});

$githubAccessToken = get_option('woda_admin_option_github_access_token');
if (!empty($githubAccessToken)) {
    $pluginUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/wwwoda/wp-plugin-elementor-two-stage-fonts-loader/',
        __FILE__,
        'woda-elementor-two-stage-fonts-loader'
    );
    $pluginUpdateChecker->setAuthentication($githubAccessToken);
}
