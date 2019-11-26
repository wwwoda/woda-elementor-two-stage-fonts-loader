<?php

namespace Woda\WordPress\Elementor\TwoStageFontsLoader;

use Elementor\Core\DynamicTags\Dynamic_CSS;
use Elementor\Plugin;
use Elementor\Element_Base;

/**
 * Class Loader
 * @package Woda\WordPress\Elementor\TwoStageFontsLoader
 */
final class Loader
{
    /**
     * @var string
     *
     * Set in WordPress Dashboard > Elementor > Settings > Style
     */
    public static $defaultGenericFonts = 'sans-serif';
    /**
     * @var array
     *
     * Set in WordPress Dashboard > Elementor > Settings > Custom Fonts
     */
    public static $customElementorFonts;
    /** @var array */
    public static $fontFamilies;

    /**
     * Main registration function for plugin
     *
     * @param array|null $settings
     */
    public static function register(?array $settings = []): void
    {
        if (!isset($settings['fontFamilies'])) {
            return;
        }
        if (! self::checkFontFamiliesConfig($settings['fontFamilies'])) {
            return;
        }
        self::$fontFamilies = $settings['fontFamilies'];
        $elementorFontTypes = get_option('elementor_fonts_manager_font_types');
        self::$customElementorFonts = is_array($elementorFontTypes) ? $elementorFontTypes : [];
        self::$defaultGenericFonts = $settings['fallBackFontFamily'] ?? get_option('elementor_default_generic_fonts') ?? self::$defaultGenericFonts;
        add_filter('elementor/frontend/print_google_fonts', '__return_false');
        add_action('elementor/css-file/global/parse', [self::class, 'generateGlobalCSS']);
        add_action('elementor/css-file/dynamic/parse', [self::class, 'generateDynamicCSS']);
    }

    /**
     * Calls the global css updater to add stage 1 and stage 2 specific rules
     *
     * @param Dynamic_CSS $css
     */
    public static function generateGlobalCSS($css): void
    {
        self::addRulesToGlobalCSS($css, 'html:not(.fonts-1-loaded)', static function ($scheme_value) {
            return self::getFallBackFontFamily($scheme_value);
        });
        self::addRulesToGlobalCSS($css, 'html.fonts-1-loaded:not(.fonts-2-loaded)', static function ($scheme_value) {
            return self::getInitialFontFamily($scheme_value);
        });
    }

    /**
     * Function to be executed on Elementor's elementor/css-file/dynamic/parse action hook
     *
     * Loops through all Elementor elements in a specific post and passes their instance to the element parser
     *
     * @param Dynamic_CSS $css
     */
    public static function generateDynamicCSS($css): void
    {
        $elementor = Plugin::$instance;
        $data = $elementor->documents->get($css->get_post_id())->get_elements_data();
        foreach ($data as $element_data) {
            /** @var Element_Base $element */
            $element = Plugin::$instance->elements_manager->create_element_instance($element_data);

            if (! $element) {
                continue;
            }

            self::parseElement($css, $element);
        }
    }

    /**
     * Passes an element to the css updater and invokes itself with all child elements
     *
     * @param Dynamic_CSS $css
     * @param Element_Base $element
     */
    private static function parseElement($css, $element): void
    {
        self::updateCss($css, $element);
        foreach ($element->get_children() as $childElement) {
            /** @var Element_Base $childElement */
            self::parseElement($css, $childElement);
        }
    }

    /**
     * Loops through all Elementor widget types and creates stage specific CSS rules for each
     *
     * @param Dynamic_CSS $css
     * @param string $selectorPrefix
     * @param callable $fontFamilyGetter
     */
    private static function addRulesToGlobalCSS($css, string $selectorPrefix, $fontFamilyGetter): void
    {
        $elementor = Plugin::$instance;
        foreach ($elementor->widgets_manager->get_widget_types() as $widget) {
            $scheme_controls = $widget->get_scheme_controls();
            foreach ($scheme_controls as $control) {
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

                        if (! empty($control['scheme']['key'])) {
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

    /**
     * Checks an element's controls for custom font family usage and creates font-family rules for each
     *
     * @param Dynamic_CSS $css
     * @param Element_Base $element
     */
    public static function updateCss($css, $element): void
    {
        $fontFamilySettings = array_filter($element->get_settings(), static function ($value, $key) {
            return strpos($key, 'font_family') !== false && ! empty($value);
        }, ARRAY_FILTER_USE_BOTH);


        if (count($fontFamilySettings) > 0) {
             self::checkUsageOfUnregisteredCustomFonts($fontFamilySettings, $element);

            $stylesheet = $css->get_stylesheet();
            $placeholders = [ '{{ID}}', '{{WRAPPER}}' ];
            $replacements = [ $element->get_id(), $css->get_element_unique_selector($element) ];

            foreach ($fontFamilySettings as $controlId => $fontFamily) {
                $control = $element->get_controls($controlId);
                if (is_array($control) && array_key_exists('selectors', $control) && is_array($control['selectors'])) {
                    foreach (array_keys($control['selectors']) as $selector) {
                        $parsed_selector = str_replace($placeholders, $replacements, $selector);
                        $stageZero = 'html:not(.fonts-1-loaded) ' . $parsed_selector;
                        $stylesheet->add_rules($stageZero, [
                            'font-family' => self::getFallBackFontFamily($fontFamily),
                        ]);
                        $stageOneFontFamily = self::getInitialFontFamily($fontFamily);
                        if (! empty($stageOneFontFamily)) {
                            $stageOne = 'html.fonts-1-loaded:not(.fonts-2-loaded) ' . $parsed_selector;
                            $stylesheet->add_rules($stageOne, [
                                'font-family' => $stageOneFontFamily,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks if an element is using custom fonts that are not registered with the two stage font loader
     *
     * @param array $fontFamilySettings
     * @param Element_Base $element
     */
    private static function checkUsageOfUnregisteredCustomFonts(array $fontFamilySettings, $element): void
    {
        $fontFamilies = array_unique(array_values($fontFamilySettings));
        foreach ($fontFamilies as $fontFamily) {
            if (! array_key_exists($fontFamily, self::$customElementorFonts) && $element->get_settings('typography_typography') === 'custom') {
                $notice = sprintf('Font family "%s" used on Widget (type: %s, id: %d) without being registered with Elementor Fonts Manager', $fontFamily, $element->get_data('widgetType'), $element->get_data('id'));
                trigger_error($notice, E_USER_NOTICE);
            }
        }
    }

    /**
     * Checks the settings passed to the plugin and gives feedback when incorrectly set up
     *
     * @param array $fontFamilies
     * @return bool
     */
    private static function checkFontFamiliesConfig(array $fontFamilies): bool
    {
        $check = true;

        if (! is_array($fontFamilies) || count($fontFamilies) < 1) {
            trigger_error('The font family config needs to be an array with at least one entry', E_USER_NOTICE);
            return false;
        }

        foreach ($fontFamilies as $key => $config) {
            if (! is_string($config) && ! is_array($config)) {
                trigger_error('The config for the font family "' . $key . '" must be a string or an array', E_USER_NOTICE);
                $check = false;
            }
            if (is_string($config) && empty($config)) {
                trigger_error('The (string)config for the font family "' . $key . '" is empty', E_USER_NOTICE);
                $check = false;
            }

            if (is_array($config)) {
                if (count($config) !== 2) {
                    trigger_error('The (array)config for the font family "' . $key . '" contains less/more than 2 values', E_USER_NOTICE);
                    $check = false;
                }

                foreach ($config as $str) {
                    if (! is_string($str) || empty($str)) {
                        trigger_error('The (array)config for the font family "' . $key . '" contains something other than two non-empty strings', E_USER_NOTICE);
                        $check = false;
                    }
                }
            }
        }

        return $check;
    }

    /**
     * Retrieves the font family to be used at stage 0 for a specific font family registered with the two stage
     * font loader.
     *
     * @param string $fontFamily
     * @return string
     */
    private static function getFallBackFontFamily(string $fontFamily): string
    {
        if (array_key_exists($fontFamily, self::$fontFamilies) && is_array(self::$fontFamilies[$fontFamily]) && ! empty(self::$fontFamilies[$fontFamily][1])) {
            return self::$fontFamilies[$fontFamily][1];
        }

        return self::$defaultGenericFonts;
    }

    /**
     * Retrieves the font family to be used at stage 1 for a specific font family registered with the two stage
     * font loader.
     *
     * @param string $fontFamily
     * @return string
     */
    private static function getInitialFontFamily(string $fontFamily): string
    {
        if (array_key_exists($fontFamily, self::$fontFamilies)) {
            if (is_array(self::$fontFamilies[$fontFamily]) && ! empty(self::$fontFamilies[$fontFamily][0])) {
                return self::$fontFamilies[$fontFamily][0];
            }
            if (is_string(self::$fontFamilies[$fontFamily])) {
                return self::$fontFamilies[$fontFamily];
            }
        }
        return $fontFamily . ' Initial';
    }
}
