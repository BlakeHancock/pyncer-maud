<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* This is a simple hook handler interface that other hook handlers can inherit
* from.
*/
abstract class CacheableHookHandler implements maud\HookHandlerInterface
{
    use maud\HookHandlerTrait;
    use maud\HookCacheTrait;
}