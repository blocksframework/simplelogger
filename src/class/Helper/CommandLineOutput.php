<?php

namespace Blocks\System\Helper;

/**
 * A class for printing text to the command line.
 */
class CommandLineOutput {
    public static function error( $text ) {
        echo "\033[01;31m{$text}\033[0m".PHP_EOL;
    }

    public static function success( $text ) {
        echo "\033[00;32m{$text}\033[0m".PHP_EOL;
    }

    public static function title( $text ) {
        $text_length = mb_strlen( $text );

        $terminal_width = self::getTerminalWidth();

        $equal_signs = str_repeat( '=', $terminal_width - $text_length - 5 );

        echo "=== {$text} {$equal_signs}".PHP_EOL;
    }

    private static function getTerminalWidth() {
        $terminal_width = intval( shell_exec( 'tput cols' ) );

        // If tput cols doesn't work, use a default width (80 characters)
        return ( $terminal_width > 0 ) ? $terminal_width : 80;
    }
}
