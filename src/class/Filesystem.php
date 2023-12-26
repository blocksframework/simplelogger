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
     * @param $param string or array of strings
     *
     * @return FilesCollection instance
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
     * @return returns true on success, generates an exception on failure
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

            if ( is_dir( $absolute_path ) ) {
                $directory_iterator = new \RecursiveDirectoryIterator( $absolute_path, \RecursiveDirectoryIterator::SKIP_DOTS );
                $directory_files = new \RecursiveIteratorIterator( $directory_iterator, \RecursiveIteratorIterator::CHILD_FIRST );

                foreach ( $directory_files as $directory_file ) {
                    $directory_file_absolute_path = $directory_file->getPathname();

                    self::deleteSingleFileOrEmptyDirectory( $directory_file_absolute_path, $settings );
                }
            }

            self::deleteSingleFileOrEmptyDirectory( $absolute_path, $settings );
        }

        return true;
    }

    /**
     * Copy a file, copy a symlink, or recursively copy a folder and its contents.
     *
     * @param       $source      Source path
     * @param       $permissions New folder creation permissions
     * @param       $settings    An array of flags on how to copy data
     * @param mixed $destination
     *
     * @return returns true on success, generates an exception on failure
     */
    public static function copy( $source, $destination, $permissions = 0755, array $settings = ['write_into_existing_dirs' => true, 'overwrite_existing_files' => true, 'verbose' => false] ): bool {
        $top_parent_absolute_path = $source;

        $destination_trimmed = rtrim( $destination, '/' );

        if ( is_file( $source ) ) {
            self::copyLowLevelSingleFile( $source, $destination, $settings );
        }
        elseif ( is_dir( $source ) ) {
            $it = new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS );
            $files = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::SELF_FIRST );

            foreach ( $files as $file ) {
                $source_absolute_path = $file->getPathname();
                $source_relative_path = self::getRelativePathByRemovingTopParentAbsolutePath( $source_absolute_path, $top_parent_absolute_path );

                $destination_absolute_path = $destination_trimmed.'/'.$source_relative_path;

                // This is used for directories that are symbolic links to other directories
                if ( is_link( $source_absolute_path ) ) {
                    self::copyLowLevelSingleFile( $source_absolute_path, $destination_absolute_path, $settings );
                }
                elseif ( is_dir( $source_absolute_path ) ) {
                    if ( !file_exists( $destination_absolute_path ) ) {
                        $mkdir_result = @mkdir( $destination_absolute_path, $permissions, true );

                        if ( false === $mkdir_result ) {
                            throw new \Exception( 'Cannot create new directory "'.$destination_absolute_path.'"' );
                        }
                    }
                    else {
                        if ( !is_dir( $destination_absolute_path ) ) {
                            throw new \Exception( 'Destination was supposed to be a diretory, but happened to be a file instead: "'.$destination_absolute_path.'". This is an error' );
                        }

                        if ( isset( $settings, $settings['write_into_existing_dirs'] ) && false === $settings['write_into_existing_dirs'] ) {
                            throw new \Exception( 'A directory "'.$destination_absolute_path.'" already exists already under the same path' );
                        }
                    }
                }
                else {
                    self::copyLowLevelSingleFile( $source_absolute_path, $destination_absolute_path, $settings );
                }
            }
        }

        return true;
    }

    private static function deleteSingleFileOrEmptyDirectory( string $absolute_path, array $settings ) {
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

        if ( is_link( $absolute_path ) ) {
            if ( @unlink( $absolute_path ) ) {
                if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                    CommandLineOutput::success( 'Successfully deleted symlink: "'.$absolute_path.'"' );
                }
            }
            else {
                throw new \RuntimeException( 'Cannot delete symlink: "'.$absolute_path.'"' );
            }
        }
        elseif ( is_dir( $absolute_path ) ) {
            if ( @rmdir( $absolute_path ) ) {
                if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                    CommandLineOutput::success( 'Successfully deleted directory: "'.$absolute_path.'"' );
                }
            }
            else {
                throw new \RuntimeException( 'Cannot delete directory: "'.$absolute_path.'"' );
            }
        }
        elseif ( is_file( $absolute_path ) ) {
            if ( @unlink( $absolute_path ) ) {
                if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                    CommandLineOutput::success( 'Successfully deleted file: "'.$absolute_path.'"' );
                }
            }
            else {
                throw new \RuntimeException( 'Cannot delete file: "'.$absolute_path.'"' );
            }
        }
        else {
            if ( isset( $settings, $settings['ignore_non_existing'] ) && false === $settings['ignore_non_existing'] ) {
                throw new \RuntimeException( 'Cannot delete file: "'.$absolute_path.'". The file does not exist' );
            }
        }
    }

    /**
     * Copy a single file or a symlink only. Internal usage only, do not use.
     *
     * @param $source_absolute_path      Source absolute path
     * @param $destination_absolute_path Destination absolute path
     * @param $settings                  An array of flags on how to copy data
     *
     * @return returns true on success, generates an exception on failure
     */
    private static function copyLowLevelSingleFile( string $source_absolute_path, string $destination_absolute_path, array $settings ): bool {
        if ( is_link( $source_absolute_path ) ) {
            if ( isset( $settings, $settings['overwrite_existing_files'] ) && true === $settings['overwrite_existing_files'] ) {
                try {
                    self::deleteSingleFileOrEmptyDirectory( $destination_absolute_path, ['ignore_non_existing' => true, 'verbose' => false] );
                }
                catch ( \RuntimeException $e ) {
                    throw new \RuntimeException( 'Cannot overwrite symlink: "'.$destination_absolute_path.'". An error occured while trying to delete an already existing file: "'.$e->getMessage().'"' );
                }

                if ( @symlink( readlink( $source_absolute_path ), $destination_absolute_path ) ) {
                    if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                        CommandLineOutput::success( 'Successfully created symlink: "'.$destination_absolute_path.'"' );
                    }
                }
                else {
                    throw new \RuntimeException( 'Cannot create symlink: "'.$destination_absolute_path.'"' );
                }
            }
            else {
                throw new \RuntimeException( 'Cannot create symlink: "'.$destination_absolute_path.'". The file already exists' );
            }

            return true;
        }

        if ( is_file( $source_absolute_path ) ) {
            if ( isset( $settings, $settings['overwrite_existing_files'] ) && true === $settings['overwrite_existing_files'] ) {
                try {
                    self::deleteSingleFileOrEmptyDirectory( $destination_absolute_path, ['ignore_non_existing' => true, 'verbose' => false] );
                }
                catch ( \RuntimeException $e ) {
                    throw new \RuntimeException( 'Cannot overwrite file: "'.$destination_absolute_path.'". An error occured while trying to delete an already existing file: "'.$e->getMessage().'"' );
                }

                if ( @copy( $source_absolute_path, $destination_absolute_path ) ) {
                    if ( isset( $settings, $settings['verbose'] ) && true === $settings['verbose'] ) {
                        CommandLineOutput::success( 'Successfully copied file: "'.$destination_absolute_path.'"' );
                    }
                }
                else {
                    throw new \RuntimeException( 'Cannot copy file: "'.$destination_absolute_path.'"' );
                }
            }
            else {
                throw new \RuntimeException( 'Cannot copy file: "'.$destination_absolute_path.'". The file already exists' );
            }

            return true;
        }

        throw new \Exception( 'Fatal error: "'.$source_absolute_path.'" is neither a file nor a symlink' );
    }

    /**
     * Gets relative path from an absolute path excluding parent path. Internal usage only, do not use.
     *
     * @param $child_absolute_path      Child absolute path
     * @param $top_parent_absolute_path Top parent absolute path
     *
     * @return returns relative path on success, generates an exception on failure
     */
    private static function getRelativePathByRemovingTopParentAbsolutePath( string $child_absolute_path, string $top_parent_absolute_path ) {
        $pos = strpos( $child_absolute_path, $top_parent_absolute_path );

        if ( 0 === $pos ) {
            $top_parent_absolute_path_length = mb_strlen( $top_parent_absolute_path );

            return substr( $child_absolute_path, $top_parent_absolute_path_length + 1 );
        }

        throw new \RuntimeException( 'Fatal error occured in the function getRelativePathByRemovingTopParentAbsolutePath()' );
    }

    public static function write($filepath, $data) {
        if ( ($file = fopen($filepath, 'w') ) !== false ) {
            fwrite($file, $data);
            fclose($file);
        } else {
            throw new \RuntimeException( 'Could not write data to a file: ' );
        }
    }        

    public static function append($filepath, $data) {
        if ( ( $file = fopen($filepath, 'a+') ) !== false ) {
            fwrite($file, $data);
            fclose($file);
        } else {
            throw new \RuntimeException( 'Could not append data to a file: ' );
        }
    }

    /*
    public static function getFileExtensionSimpleWay($filename) {
        return substr($filename, strrpos($filename, '.') + 1);
    }
    */

    // From Zend, to re-write later
    static function getMimeType($filepath, $encoding = true) {
        self::assertFile($path);

        $mime = false;

        if ( function_exists('finfo_file') ) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mime = finfo_file($finfo, $filepath);
            finfo_close($finfo);

        } else if (substr(PHP_OS, 0, 3) == 'WIN') {
            $mime = mime_content_type($filepath);

        } else {
            $filepath = escapeshellarg($filepath);
            $cmd = "file -iL {$filepath}";

            exec($cmd, $output, $r);

            if ($r == 0) {
                $mime = substr( $output[0], strpos($output[0], ': ') + 2 );
            }
        }

        if ( !$mime ) {
            return false;
        }

        if ($encoding) {
            return $mime;
        }

        return substr( $mime, 0, strpos($mime, '; ') );
    }

}
