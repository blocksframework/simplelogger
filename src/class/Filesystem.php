<?php

namespace Bausystem;

use Bausystem\Helper\Param;
use Assert\Assert;
use Assert\LazyAssertionException;
use RuntimeException;
use GlobIterator;
use SplFileInfo;
use Exception;
use Bausystem\Output;

class Filesystem {

    /**
     * Collects a list of files based on glob strings (like /home/user/Backups/*.tgz)
     *
     * @param string or array of strings
     *
     * @return array of strings
     */
    public static function collectFiles(array|string $param) {
        $paths = Param::getStringsParam($param);

        $results = [];

        foreach ($paths as $path) {
            $iterator = new GlobIterator($path);

            while ( $iterator->valid() ) {
                $results[] = $iterator->current();
                $iterator->next();
            }
        }

        // TODO: to return an array of SplFileInfo items

        return $results;
    }

    /**
     * Deletes files
     *
     * @param string or array of strings or array of SplFileInfo
     *
     * @return array of strings
     */
    public static function deleteFiles(array|string|SplFileInfo $files_param, array $settings = [ 'ignore_non_existing' => true ] ): bool {
        $files = Param::getFilesParam($files_param);

        foreach ($files as $file) {
            $absolute_path = $file->getPathname();

            try {
                Assert::lazy()->tryAll()
                    ->that($absolute_path)
                        ->string()
                        ->notEmpty()
                        ->notSame('/')
                        ->notSame('.')
                        ->notSame('..')
                        ->notContains('*')
                        ->notContains('?')
                        ->betweenLength(1, 4096)
                    ->verifyNow();

            } catch (LazyAssertionException $e) {
                throw new Exception($e->getMessage());

            } catch (\Throwable $e) {
                throw new RuntimeException( "Fatal error: Invalid absolute path" . $e->getMessage() );
            }

            if ( $file->isLink() ) {
                if ( @unlink($absolute_path) ) {
                    Output::success('Successfully deleted symlink: "' . $absolute_path . '"');
                    continue;

                } else {
                    throw new RuntimeException('Cannot delete symlink: "' . $absolute_path . '"');
                }
   
            } elseif ( is_dir($absolute_path) ) {
                if ( @rmdir($absolute_path) ) {
                    Output::success('Successfully deleted directory: "' . $absolute_path . '"');
                    continue;

                } else {
                    // TODO: to check access rights

                    // TODO: to recursively delete content

                    throw new RuntimeException('Cannot delete directory: "' . $absolute_path . '"');
                }

            } elseif ( is_file($absolute_path) ) {
                if ( @unlink($absolute_path) ) {
                    Output::success('Successfully deleted file: "' . $absolute_path . '"');
                    continue;

                } else {
                    throw new RuntimeException('Cannot delete file: "' . $absolute_path . '"');
                }

            } else {
                if ( isset( $settings['ignore_non_existing'] ) && $settings['ignore_non_existing'] === false ) {
                    throw new RuntimeException('Cannot delete file: "' . $absolute_path . '". The file does not exist');
                }
            }
        }

        return true;
    }

}
