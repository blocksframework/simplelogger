<?php

namespace Blocks\System;

use Assert\Assert;
use Assert\LazyAssertionException;
use Blocks\System\Collection\FilesCollection;
use Blocks\System\Helper\CommandLineOutput;
use Blocks\System\Helper\StringsArrayParam;
use SplFileInfo;

class Filesystem {
    /**
     * Collects a list of files based on glob strings (like /home/user/Backups/*.tgz).
     *
     * @param string or array of strings
     *
     * @return array of strings
     */
    public static function collectFiles( array|string $param ) {
        $paths = StringsArrayParam::get( $param );

        $results = [];

        foreach ( $paths as $path ) {
            $iterator = new \GlobIterator( $path );

            while ( $iterator->valid() ) {
                $results[] = $iterator->current();
                $iterator->next();
            }
        }

        // TODO: to return an array of SplFileInfo items

        return $results;
    }

    /**
     * Deletes files.
     *
     * @param string or array of strings or array of SplFileInfo
     *
     * @return array of strings
     */
    public static function deleteFiles( array|FilesCollection|\SplFileInfo|string $files_param, array $settings = ['ignore_non_existing' => true, 'verbose' => false] ): bool {
        if ( $files_param instanceof FilesCollection ) {
            $files = $files_param;
        }
        else {
            $files = new FilesCollection( $files_param );
        }

        foreach ( $files as $file ) {
            $absolute_path = $file->getPathname();

            try {
                Assert::lazy()->tryAll()
                    ->that( $absolute_path )
                    ->string()
                    ->notEmpty()
                    ->notSame( '/' )
                    ->notSame( '.' )
                    ->notSame( '..' )
                    ->notContains( '*' )
                    ->notContains( '?' )
                    ->betweenLength( 1, 4096 )
                    ->verifyNow()
                ;
            }
            catch ( LazyAssertionException $e ) {
                throw new \Exception( $e->getMessage() );
            }
            catch ( \Throwable $e ) {
                throw new \RuntimeException( 'Fatal error: Invalid absolute path'.$e->getMessage() );
            }

            if ( $file->isLink() ) {
                if ( @unlink( $absolute_path ) ) {
                    if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                        CommandLineOutput::success( 'Successfully deleted symlink: "'.$absolute_path.'"' );
                    }

                    continue;
                }

                throw new \RuntimeException( 'Cannot delete symlink: "'.$absolute_path.'"' );
            }
            elseif ( is_dir( $absolute_path ) ) {
                if ( @rmdir( $absolute_path ) ) {
                    if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                        CommandLineOutput::success( 'Successfully deleted directory: "'.$absolute_path.'"' );
                    }

                    continue;
                }
                // TODO: to check access rights

                // TODO: to recursively delete content

                throw new \RuntimeException( 'Cannot delete directory: "'.$absolute_path.'"' );
            }
            elseif ( is_file( $absolute_path ) ) {
                if ( @unlink( $absolute_path ) ) {
                    if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                        CommandLineOutput::success( 'Successfully deleted file: "'.$absolute_path.'"' );
                    }

                    continue;
                }

                throw new \RuntimeException( 'Cannot delete file: "'.$absolute_path.'"' );
            }
            else {
                if ( isset( $settings, $settings['ignore_non_existing'] ) && false === $settings['ignore_non_existing'] ) {
                    throw new \RuntimeException( 'Cannot delete file: "'.$absolute_path.'". The file does not exist' );
                }
            }
        }

        return true;
    }
}
