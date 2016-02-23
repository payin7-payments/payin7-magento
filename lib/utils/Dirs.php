<?php
/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */

namespace Payin7Payments;

use DirectoryIterator;
use Exception;

class Dirs
{
    /**
     * Recursively move files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     * @return bool
     */
    public static function recursiveMove($src, $dest)
    {
        // If source is not a directory stop processing
        if (!is_dir($src)) return false;

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), $dest . DS . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                self::recursiveMove($f->getRealPath(), $dest . DS . $f);
            }
        }

        return rmdir($src);
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param String $src - Source of files being moved
     * @param String $dest - Destination of files being moved
     * @return bool
     */
    public static function recursiveCopy($src, $dest)
    {
        // If source is not a directory stop processing
        if (!is_dir($src)) return false;

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), $dest . DS . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                self::recursiveCopy($f->getRealPath(), $dest . DS . $f);
            }
        }

        return true;
    }

    public static function recursiveChmod($path, $filePerm = 0644, $dirPerm = 0755)
    {
        // Check if the path exists
        if (!file_exists($path)) {
            return (false);
        }

        // See whether this is a file
        if (is_file($path)) {
            // Chmod the file with our given filepermissions
            chmod($path, $filePerm);

            // If this is a directory...
        } elseif (is_dir($path)) {
            // Then get an array of the contents
            $foldersAndFiles = scandir($path);

            // Remove " . " and " .." from the list
            $entries = array_slice($foldersAndFiles, 2);

            // Parse every result...
            foreach ($entries as $entry) {
                // And call this function again recursively, with the same permissions
                self::recursiveChmod($path . " / " . $entry, $filePerm, $dirPerm);
            }

            // When we are done with the contents of the directory, we chmod the directory itself
            chmod($path, $dirPerm);
        }

        // Everything seemed to work out well, return true
        return (true);
    }

    public static function fixDirDelimiter($dirname)
    {
        if ($dirname{strlen($dirname) - 1} != DS) {
            $dirname .= DS;
        }

        return $dirname;
    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     * @param string $source Source path
     * @param string $dest Destination path
     * @param string $permissions New folder creation permissions
     *
     * @return bool Returns true on success, false on failure
     */
    public static function xcopy($source, $dest, $permissions = 0755)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }

        // Loop through the folder
        $dir = dir($source);

        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            self::xcopy("$source / $entry", "$dest / $entry");
        }

        // Clean up
        $dir->close();

        return true;
    }

    public static function mkdirRecursive($path, $mode = 0777)
    {
        if (is_dir($path)) {
            return true;
        }

        $old = umask(0);

        if (!@mkdir($path, $mode, true)) {
            umask($old);
            throw new Exception('Cannot create folder recursively: ' . $path);
        }

        umask($old);

        if ($old != umask()) {
            throw new Exception('Error setting umask');
        }

        return true;
    }

    public static function isDirEmpty($dir)
    {
        $iterator = new \FilesystemIterator($dir);
        $is_dir_empty = !$iterator->valid();
        return $is_dir_empty;
    }

    public static function checkCreateDir($dirname, $trycreate = false)
    {
        if (substr($dirname, strlen($dirname) - 1, strlen($dirname)) != DS) {
            $dirname .= DS;
        }

        if (((!is_dir($dirname)) || (!is_readable($dirname))) && (!$trycreate)) {
            return false;
        }

        if ($trycreate) {
            if (is_dir($dirname)) {
                if (!is_writable($dirname)) {
                    throw new Exception('Directory ' . $dirname . ' is not writeable');
                }
            } else {
                self::mkdirRecursive($dirname);
            }
        }
        return true;
    }

    public static function rmdirRecursive($directory, $empty = false, $skipHidden = false)
    {
        if (substr($directory, -1) == DS) {
            $directory = substr($directory, 0, -1);
        }

        if (!file_exists($directory) || !is_dir($directory)) {
            return true;
            //throw new lcIOException('Directory is not valid: '.$directory);
        } elseif (!is_readable($directory)) {
            throw new Exception('Directory is not readable: ' . $directory);
        } else {
            $handle = opendir($directory);

            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if ($empty && $skipHidden && (substr($item, 0, 1) == '.')) {
                        continue;
                    }

                    $path = $directory . DS . $item;

                    if (is_dir($path)) {
                        # makes a recursion to get subfolders
                        self::rmdirRecursive($path);
                    } else {
                        unlink($path);
                    }
                }

                unset($item, $path);
            }

            closedir($handle);

            if ($empty == false) {
                if (!rmdir($directory)) {
                    throw new Exception('Cannot remove folder recursively: ' . $directory);
                }
            }

            return true;
        }
    }

    public static function getFileCountInDir($dir)
    {
        return count(glob($dir . "*"));
    }

    public static function getRandomFileDirName()
    {
        return
            md5(
                md5(time()) .
                md5(microtime()) .
                md5(rand(1, 10000000)) .
                md5(rand(1, 10000000)) .
                md5(rand(1, 10000000)) .
                md5(rand(1, 10000000))
            );
    }

    public static function getSubDirsOfDir($dir, $skip = null)
    {
        if (!$d = @dir($dir)) {
            return false;
        }

        $dirs = array();

        while (false !== ($entry = $d->read())) {
            if (($entry == '.') || ($entry == '..') || !is_dir($dir . DS . $entry) || substr($entry, 0, 1) == '.' || ((isset($skip)) && ($entry == $skip))) {
                continue;
            }

            $dirs[] = $entry;
            unset($entry);
        }
        $d->close();

        return $dirs;
    }

    public static function exists($dirname)
    {
        return (file_exists($dirname) && is_dir($dirname));
    }

    public static function writable($dirname)
    {
        return is_writable($dirname);
    }

    public static function create($dirname, $recursive = false, $mode = 0777)
    {
        try {
            if ($recursive) {
                self::mkdirRecursive($dirname, $mode);
            } else {
                $old = umask(0);
                mkdir($dirname, $mode);
                umask($old);

                if ($old != umask()) {
                    throw new Exception('Error setting umask');
                }
            }
        } catch (Exception $e) {
            throw new Exception('Cannot create folder: ' . $dirname . ': ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}