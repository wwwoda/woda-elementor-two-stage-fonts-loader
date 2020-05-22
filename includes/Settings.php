<?php

namespace Woda\WordPress\Elementor\TwoStageFontsLoader;

final class Settings
{
    public const FILTER = 'woda_elementor_two_stage_fonts_loader_settings';
    /**
     * @var array
     */
    private static $defaults;
    /**
     * @var array
     */
    private static $settings;

    /**
     * @param array $settings
     */
    public static function init(array $settings = []): void
    {
        self::$defaults = [
            'classStage1' => 'fonts-loaded-stage1',
            'classStage2' => 'fonts-loaded-stage2',
            'defaultGenericFonts' => 'sans-serif',
            'fontFamilies' => [],
        ];
        $settings = array_merge(self::$defaults, $settings);
        self::$settings = apply_filters(self::FILTER, $settings);
    }

    /**
     * @return string
     */
    public static function getClassStage1(): string
    {
        $class = self::$defaults['classStage1'];
        if (!empty(self::$settings['classStage1'])) {
            $class = self::$defaults['classStage1'];
        }

        return self::cleanDotFromClass($class);
    }

    /**
     * @return string
     */
    public static function getClassStage2(): string
    {
        $class = self::$defaults['classStage2'];
        if (!empty(self::$settings['classStage2'])) {
            $class = self::$defaults['classStage2'];
        }

        return self::cleanDotFromClass($class);
    }

    public static function getDefaultGenericFonts(): string {
        if (empty(self::$settings['defaultGenericFonts'])) {
            return self::$defaults['defaultGenericFonts'];
        }
        return self::$settings['defaultGenericFonts'];
    }

    public static function getFontFamilies(): array
    {
        return self::$settings['fontFamilies'] ?? [];
    }

    public static function getFontFamilyNames(): array
    {
        return array_keys(self::getFontFamilies());
    }

    private static function cleanDotFromClass($class): string {
        if (substr($class, 0, 1) == '.') {
            $class = substr($class, 1);
        }
        return $class;
    }

    public static function getInitialFontFamily(string $fontFamily): string
    {
        if (array_key_exists($fontFamily, Settings::getFontFamilies()) && is_array(Settings::getFontFamilies()[$fontFamily]) && ! empty(Settings::getFontFamilies()[$fontFamily][1])) {
            return Settings::getFontFamilies()[$fontFamily][1];
        }

        return Settings::getDefaultGenericFonts();
    }

    public static function getStage1FontFamily(string $fontFamily): string
    {
        if (!isset(Settings::getFontFamilies()[$fontFamily])) {
            return '';
        }
        return Settings::getFontFamilies()[$fontFamily];
    }

    public static function getInitialStageWrapper(): string {
        return 'html:not(.' . Settings::getClassStage1() . ')';
    }

    public static function getStage1Wrapper(): string {
        return 'html.' . Settings::getClassStage1() . ':not(.' . Settings::getClassStage2() . ')';
    }
}
