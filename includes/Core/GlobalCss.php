<?php

namespace Woda\WordPress\Elementor\TwoStageFontsLoader\Core;

use Elementor\Core\DynamicTags\Dynamic_CSS;
use Elementor\Plugin;
use Woda\WordPress\Elementor\TwoStageFontsLoader\Settings;


final class GlobalCss
{
    public static function init(): void
    {
        add_action('elementor/css-file/global/parse', [self::class, 'handleGlobalCSS']);
    }

    /**
     * @param Dynamic_CSS $css
     */
    public static function handleGlobalCSS(Dynamic_CSS $css): void
    {
        self::addRulesToGlobalCSS($css, Settings::getInitialStageWrapper(), static function ($scheme_value) {
            return Settings::getInitialFontFamily($scheme_value);
        });
        self::addRulesToGlobalCSS($css, Settings::getStage1Wrapper(), static function ($scheme_value) {
            return Settings::getStage1FontFamily($scheme_value);
        });
    }

    /**
     * @param Dynamic_CSS $css
     * @param string $selectorPrefix
     * @param callable $fontFamilyGetter
     */
    private static function addRulesToGlobalCSS(Dynamic_CSS $css, string $selectorPrefix, callable $fontFamilyGetter): void
    {
        $elementor = Plugin::$instance;
        foreach ($elementor->widgets_manager->get_widget_types() as $widget) {
            foreach ($widget->get_scheme_controls() as $control) {
                $css->add_control_rules(
                    $control,
                    $widget->get_controls(),
                    static function ($control) use ($elementor, $fontFamilyGetter) {
                        if ($control['scheme']['key'] !== 'font_family') {
                            return null;
                        }
                        $scheme_value = $elementor->schemes_manager->get_scheme_value($control['scheme']['type'], $control['scheme']['value']);

                        if (empty($scheme_value)) {
                            return null;
                        }

                        if (!empty($control['scheme']['key'])) {
                            $scheme_value = $scheme_value[ $control['scheme']['key'] ];
                        }

                        $scheme_value = $fontFamilyGetter($scheme_value);

                        if (empty($scheme_value)) {
                            return null;
                        }

                        return $scheme_value;
                    },
                    [ '{{WRAPPER}}' ],
                    [ $selectorPrefix . ' .elementor-widget-' . $widget->get_name() ]
                );
            }
        }
    }
}
