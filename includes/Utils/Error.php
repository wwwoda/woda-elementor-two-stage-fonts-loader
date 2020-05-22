<?php

namespace Woda\WordPress\Elementor\TwoStageFontsLoader\Utils;

final class Error {
    /**
     * @param string $message
     */
    public static function notice(string $message): void
    {
        trigger_error($message, E_USER_NOTICE);
    }
}
