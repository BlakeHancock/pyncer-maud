<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Basic implementation of the hook provider interface based around php files.
*/
trait FileHookProviderTrait
{
    protected $hookFiles = [];

    /**
    * Yields a hook alias code pair.
    *
    * @return Generator
    */
    public function getHook()
    {
        foreach ($this->hookFiles as $key => $value) {
            if (!$value || !file_exists($value)) {
                continue;
            }

            yield $key => file_get_contents($value);
        }
    }

    /**
    * Gets the priority of the specified hook from the specified hook set.
    *
    * @param mixed $alias The alias of the hook set.
    * @param mixed $hook A hook name.
    * @return integer
    */
    public function getHookPriority($alias, $hook)
    {
        return -1;
    }
}
