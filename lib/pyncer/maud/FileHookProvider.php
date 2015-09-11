<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* A basic implementation of the hook provider interface that gets hook code from
* php files.
*/
class FileHookProvider implements maud\HookProviderInterface
{
    use maud\FileHookProviderTrait;

    /**
    * Adds a php containing file to be used to get hook code from.
    *
    * @param mixed $alias The alias of the hook set.
    * @param mixed $file The path to the file to add.
    */
    public function addHookFile($alias, $file)
    {
        $alias = strval($alias);
        $file = strval($file);

        if (!file_exists($file)) {
            throw new maud\Exception('Hook file not found.');
        }

        $this->hookFiles[$alias] = $file;
    }
}