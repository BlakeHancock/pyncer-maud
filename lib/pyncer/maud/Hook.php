<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Static hook class used to place hooks in your code.
*/
class Hook
{
    private static $method;
    private static $isReady = false;
    private static $hookHandler;

    const METHOD_EVAL = 'eval';
    const METHOD_INCLUDE = 'include';

    /**
    * Private constructor to prevent instanciation.
    */
    private function __construct()
    {}

    /**
    * Initialized the Hook class with a hook handler.
    *
    * @param maud\HookHandlerInterface $hookHandler A hook handler.
    */
    public static function init(maud\HookHandlerInterface $hookHandler)
    {
        self::$hookHandler = $hookHandler;
        self::$isReady = true;
    }

    /**
    * Gets whether the hook class is initialized with a hook handler.
    *
    * @return bool
    */
    public static function isReady()
    {
        return self::$isReady;
    }

    /**
    * Gets the current hook process method. This will return either 'eval' or
    * 'include.'
    *
    * @return string
    */
    public static function getMethod()
    {
        return self::$hookHandler->getMethod();
    }

    /**
    * Gets a hook code value for the provided hook name.
    *
    * @param string $hook A hook name.
    * @return mixed The hook code value; otherwise false.
    */
    public static function get($hook)
    {
        if (!self::$isReady) {
            return false;
        }

        return self::$hookHandler->get($hook);
    }
}