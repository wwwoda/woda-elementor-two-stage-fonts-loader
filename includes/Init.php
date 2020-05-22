<?php

namespace Woda\WordPress\Elementor\TwoStageFontsLoader;

use Woda\WordPress\Elementor\TwoStageFontsLoader\Core\GlobalCss;
use Woda\WordPress\Elementor\TwoStageFontsLoader\Core\PostCss;

final class Init
{
    public static function init(array $settings = []): void
    {
        add_action('init', static function() use ($settings): void {
            Settings::init($settings);
        });

        add_action('elementor_pro/init', static function(): void {
            GlobalCss::init();
            PostCss::init();
        });

        add_filter( 'elementor/fonts/groups', static function($font_groups): array {
            return array_merge(['woda' => 'Woda Fonts'], $font_groups);
        }, 999 );

        add_filter( 'elementor/fonts/additional_fonts', static function($additional_fonts): array {
            foreach (Settings::getFontFamilyNames() as $name) {
                $additional_fonts[$name] = 'woda';
            }
            return $additional_fonts;
        } );
    }
}
