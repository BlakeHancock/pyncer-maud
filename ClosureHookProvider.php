<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Closure implementation of the hook provider interface.
*
* It allows you to assign code to a hook using closures.
*
* Note: Since this implementation relies on reflection, it is only able to
* determine the start and end line of the closure. Because of this you must
* ensure your opening 'function() {' and closing '}' are the first and last
* of those strings on a line.
*/
class ClosureHookProvider implements maud\HookProviderInterface
{
    protected $hooks = [];

    /**
    * Get's the next string of hook code to process.
    *
    * @return string
    */
    public function getHook()
    {
        foreach ($this->hooks as $value) {
            $hook = '<?php function ' . $value[1] . '(){';

            $reflection = new \ReflectionFunction($value[2]);

            $file = new \SplFileObject($reflection->getFileName());
            $file->seek($reflection->getStartLine() -1);

            $code = '';

            while ($file->key() < $reflection->getEndLine()) {
                $code .= $file->current();
                $file->next();
            }

            $begin = strpos($code, 'function') + 8;
            $begin = strrpos($code, '{', $begin) + 1;
            $end = strrpos($code, '}');

            $hook .= substr($code, $begin, $end - $begin);
            $hook .= '}' . PHP_EOL;

            yield $value[0] => $hook;
        }
    }

    /**
    * Gets the specified aliased hook's priority.
    *
    * If you have two or more modifications using the same hook, you can specify
    * to the hook handler what order they should be called.
    *
    * @param string $alias The alias of the hook set.
    * @param string $hook A hook name.
    * @return integer
    */
    public function getHookPriority($alias, $hook)
    {
        return -1;
    }

    /**
    * Add a closure to this hook provider.
    *
    * @param string $alias The alias of the hook set.
    * @param string $hook A hook name.
    * @param Closure $closure A closure containing the hook's code.
    * @return null
    */
    public function addHook($alias, $hook, $closure)
    {
        if (!($closure instanceof \Closure)) {
            throw new maud\InvalidArgumentException();
        }

        $alias = strval($alias);
        $hook = strval($hook);

        $this->hooks[] = [$alias, $hook, $closure];
    }
}
