<?php

namespace Blocks\System;

use RuntimeException;

/**
 * A class used for writing logs (no severity level).
 */
class SimpleLogger {
    private ?string $filepath;
    private $handle;

    public function __construct( ?string $filepath ) {
        $this->filepath = $filepath;

        if ( $filepath ) {
            if ( !file_exists( $this->filepath ) ) {
                if ( !touch( $this->filepath ) ) {
                    new RuntimeException( 'Can\'t create log file: '.$this->filepath );
                }

                chmod( $this->filepath, 0660 );
            }

            if ( !is_writable( $this->filepath ) ) {
                new RuntimeException( 'Can\'t write to a file: '.$this->filepath );
            }

            $this->handle = fopen( $this->filepath, 'a+' );
        }
    }

    public function __destruct() {
        if ( $this->handle ) {
            fclose( $this->handle );
        }
    }

    public function add( string $record ) {
        if ( $this->handle ) {
            fwrite( $this->handle, date( 'Y-m-d G:i:s' ).' - '.$record.PHP_EOL );
        }
    }
}
