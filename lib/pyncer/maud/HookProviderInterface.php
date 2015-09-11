<?php
namespace pyncer\maud;

/**
* Describes a hook provider instance.
*/
interface HookProviderInterface
{
    /**
    * Yields a hook alias code pair.
    *
    * @return Generator
    */
    function getHook();

    /**
    * Gets the priority of the specified hook from the specified hook set.
    *
    * @param string $alias The alias of the hook set.
    * @param string $hook A hook name.
    * @return integer
    */
    function getHookPriority($alias, $hook);
}
