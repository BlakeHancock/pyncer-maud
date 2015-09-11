<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Describes a hook handler instance.
*/
interface HookHandlerInterface
{
    /**
    * Gets the method this hook handler uses.
    *
    * @return string
    */
    public function getMethod();

    /**
    * Gets the code for the specified hook.
    *
    * @param string $hook A hook name.
    * @return mixed
    */
    public function get($hook);

    /**
    * Parses and compiles hooks into an array.
    *
    * @param maud\HookProviderInterface $hookProvider A hook provider.
    * @return $this
    */
    public function build(maud\HookProviderInterface $hookProvider);
}