<?php

namespace Blocks\System;

use Assert\Assert;
use Assert\LazyAssertionException;
use Blocks\System\Collection\FilesCollection;
use Blocks\System\Helper\CommandLineOutput;
use Blocks\System\Helper\StringsArrayParam;

class Filesystem {
    /**
     * Collects a list of files based on glob strings (like /home/user/Backups/*.tgz).
     *
     * @param string or array of strings
     */
    public static function collectFiles( array|string $param ): FilesCollection {
        $paths = StringsArrayParam::get( $param );

        $results = [];

        foreach ( $paths as $path ) {
            $iterator = new \GlobIterator( $path );

            while ( $iterator->valid() ) {
                $results[] = $iterator->current();
                $iterator->next();
            }
        }

        return new FilesCollection( $results );
    }

    /**
     * Deletes files and folders recursively.
     *
     * @param string or array of strings or array of SplFileInfo
     *
     * @return array of strings
     */
    public static function delete( array|FilesCollection|\SplFileInfo|string $files_param, array $settings = ['ignore_non_existing' => true, 'verbose' => false] ): bool {
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

    /**
     * Copy a file, or recursively copy a folder and its contents.
     *
     * @param string $source      Source path
     * @param string $dest        Destination path
     * @param int    $permissions New folder creation permissions
     *
     * @return bool Returns true on success, false on failure
     */
    public static function copy( $source, $dest, $permissions = 0755 ) {
        $sourceHash = self::hashDirectory( $source );

        // Check for symlinks
        if ( is_link( $source ) ) {
            return symlink( readlink( $source ), $dest );
        }

        // Simple copy for a file
        if ( is_file( $source ) ) {
            return copy( $source, $dest );
        }

        // Create destination directory
        if ( !is_dir( $dest ) ) {
            mkdir( $dest, $permissions, true );
        }

        // Loop through the folder
        $dir = dir( $source );

        while ( false !== $entry = $dir->read() ) {
            // Skip dots
            if ( '.' == $entry || '..' == $entry ) {
                continue;
            }

            // Deep copy directories
            if ( $sourceHash != self::hashDirectory( $source.'/'.$entry ) ) {
                self::copy( "{$source}/{$entry}", "{$dest}/{$entry}", $permissions );
            }
        }

        $dir->close();

        return true;
    }

    /**
     * In case of coping a directory inside itself, there is a need to hash check the directory otherwise and infinite loop of coping is generated.
     *
     * @param mixed $directory
     */
    public static function hashDirectory( $directory ) {
        if ( !is_dir( $directory ) ) {
            return false;
        }

        $files = [];
        $dir = dir( $directory );

        while ( false !== ( $file = $dir->read() ) ) {
            if ( '.' != $file and '..' != $file ) {
                if ( is_dir( $directory.'/'.$file ) ) {
                    $files[] = hashDirectory( $directory.'/'.$file );
                }
                else {
                    $files[] = md5_file( $directory.'/'.$file );
                }
            }
        }

        $dir->close();

        return md5( implode( '', $files ) );
    }
}
