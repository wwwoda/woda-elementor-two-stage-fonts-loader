<?php

namespace Woda\WordPress\Elementor\TwoStageFontsLoader\Core;

use Elementor\Controls_Stack;
use Elementor\Core\DynamicTags\Dynamic_CSS;
use Elementor\Fonts;
use Elementor\Plugin;
use Elementor\Element_Base;
use Woda\WordPress\Elementor\TwoStageFontsLoader\Settings;
use Woda\WordPress\Elementor\TwoStageFontsLoader\Utils\Error;


final class PostCss
{
    private static $elementorFonts;

    public static function init(): void
    {
        self::$elementorFonts = Fonts::get_fonts();
        // CSS rules will be created on a post save inside the Elementor editor.
        // If you change "post" to "dynamic" the css rules will be created dynamically when loading a post
        // and added to the DOM as inline styles, which makes it easier to debug this plugin.
        add_action('elementor/css-file/post/parse', [self::class, 'handlePostCss']);
    }

    /**
     * @param Dynamic_CSS $css
     */
    public static function handlePostCss(object $css): void
    {
        $elementor = Plugin::$instance;
        $document = $elementor->documents->get($css->get_post_id());
        switch ($document->get_name()) {
            case 'kit':
                self::updateCss($css, $document, $document->get_css_wrapper_selector());
                break;
            default:
                self::processDocumentElements($css, $document);
        }
    }


    /**
     * @param Dynamic_CSS $css
     * @param Element_Base $element
     */
    private static function processDocumentElements(object $css, object $document): void
    {
        $data = $document->get_elements_data();
        foreach ($data as $element_data) {
            /** @var Element_Base $element */
            $element = Plugin::$instance->elements_manager->create_element_instance($element_data);
            if (!$element) {
                continue;
            }
            self::processElement($css, $element);
        }
    }

    /**
     * @param Dynamic_CSS $css
     * @param Element_Base $element
     */
    private static function processElement(object $css, object $element): void
    {
        self::updateCss($css, $element);
        foreach ($element->get_children() as $childElement) {
            /** @var Element_Base $childElement */
            self::processElement($css, $childElement);
        }
    }

    /**
     * @param Dynamic_CSS $css
     * @param Controls_Stack $element
     */
    public static function updateCss(object $css, object $element, string $cssSelector = ''): void
    {
        $fontFamilySettings = self::getFontFamilySettings($element);

        if (count($fontFamilySettings) > 0) {
            self::checkFontFamilySettings($fontFamilySettings, $element);

            $stylesheet = $css->get_stylesheet();
            $replacements = [
                '{{ID}}' => $element->get_id(),
                '{{WRAPPER}}' => $cssSelector ?: $css->get_element_unique_selector($element),
            ];

            foreach ($fontFamilySettings as $controlId => $fontFamily) {
                $control = $element->get_controls($controlId);
                if (isset($control['selectors']) && is_array($control['selectors'])) {
                    foreach (array_keys($control['selectors']) as $selector) {
                        $cssSelector = '';
                        foreach ($replacements as $key => $value) {
                            $cssSelector = str_replace($key, $value, $selector);
                        }
                        $initialFontFamily = Settings::getInitialFontFamily($fontFamily);
                        if (!empty($initialFontFamily)) {
                            $preStage1CssSelector = Settings::getInitialStageWrapper() . ' ' . $cssSelector;
                            $stylesheet->add_rules($preStage1CssSelector, [
                                'font-family' => $initialFontFamily,
                            ]);
                        }
                        $stage1FontFamily = Settings::getStage1FontFamily($fontFamily);
                        if (!empty($stage1FontFamily)) {
                            $preStage2CssSelector = Settings::getStage1Wrapper() . ' ' . $cssSelector;
                            $stylesheet->add_rules($preStage2CssSelector, [
                                'font-family' => $stage1FontFamily,
                            ]);
                        }
                    }
                }
            }
        }
    }

    private static function getFontFamilySettings(object $element): array
    {
        /** @var Controls_Stack $element */
        return array_filter($element->get_settings(), static function ($value, $key) {
            return strpos($key, 'font_family') !== false && ! empty($value);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Checks if any font families are used that weren't configured
     *
     * @param array $fontFamilySettings
     * @param Controls_Stack $element
     */
    private static function checkFontFamilySettings(array $fontFamilySettings, object $element): void
    {
        $fontFamilies = array_unique(array_values($fontFamilySettings));
        foreach ($fontFamilies as $fontFamily) {
            if (array_key_exists($fontFamily, Settings::getFontFamilies())) {
                continue;
            }
            if (isset(self::$elementorFonts[$fontFamily])
                && !in_array(self::$elementorFonts[$fontFamily], [Fonts::GOOGLE, Fonts::EARLYACCESS])) {
                continue;
            }
            $notice = sprintf('%s font "%s" used on Widget (type: %s, id: %d)',
                self::$elementorFonts[$fontFamily],
                $fontFamily,
                $element->get_data('widgetType'),
                $element->get_data('id'));
            Error::notice($notice);
        }
    }
}
