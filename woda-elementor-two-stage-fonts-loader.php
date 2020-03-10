<?php
/**
 * Plugin Name:       Woda Elementor Two Stage Fonts Loader
 * Plugin URI:        https://github.com/wwwoda/woda-elementor-two-stage-fonts-loader
 * Description:       ...
 * Version:           0.3.1
 * Author:            Woda
 * Author URI:        https://www.woda.at
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:       /languages
 * Text Domain:       woda-scripts-styles-loader
 * GitHub Plugin URI: https://github.com/wwwoda/woda-elementor-two-stage-fonts-loader
 * Release Asset:     true
 *
 * @package           Woda_Elementor_Two_Stage_Fonts_Loader
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
