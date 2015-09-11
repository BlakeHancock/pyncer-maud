<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Provides basic file caching options.
*/
trait HookCacheTrait
{
    protected $cacheDirectory;

    /**
    * Gets the current cache directory.
    *
    * @return string
    */
    public function getCacheDirectory()
    {
        return $this->cacheDirectory;
    }

    /**
    * Sets the current cache directory.
    *
    * @param mixed $value The cache directory path to use.
    * @return $this
    */
    public function setCacheDirectory($value)
    {
        $value = rtrim($value, DIRECTORY_SEPARATOR);

        if (!is_dir($value)) {
            throw new maud\InvalidArgumentException('Directory not found.');
        }

        $this->cacheDirectory = $value;

        return $this;
    }

    /**
    * Loads the cached hooks.
    *
    * @return bool
    */
    abstract public function load();

    /**
    * Saves the current hook array to the cache directory.
    *
    * @return $this
    */
    abstract public function save();

    /**
    * Deletes all 'maud_' prefixed files from the current cache directory.
    *
    * @return $this
    */
    public function delete()
    {
        $dir = $this->getCacheDirectory();

        if (!isset($dir)) {
            throw new maud\Exception('Cache directory not set.');
        }

        if (!file_exists($dir)) {
            throw new maud\Exception('Cache directory not found.');
        }

        if ($handle = opendir($dir)) {
            while (($filename = readdir($handle)) !== false) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }

                $file = $dir . DIRECTORY_SEPARATOR . $filename;
                if (is_file($file) && substr($filename, 0, 5) == 'maud_') {
                    unlink($file);
                }
            }
            closedir($handle);
        }

        return $this;
    }
}