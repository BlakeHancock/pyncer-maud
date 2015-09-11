<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Cacheable 'eval' method implementation of the hook handler interface.
*/
class EvalHookHandler extends maud\CacheableHookHandler
{
    /**
    * Gets the method this hook handler uses.
    *
    * @return string
    */
    public function getMethod()
    {
        return Hook::METHOD_EVAL;
    }

    /**
    * Gets the code for the specified hook.
    *
    * @param string $hook A hook name.
    * @return string
    */
    public function get($hook)
    {
        return (isset($this->hooks[$hook]) ? $this->hooks[$hook] : null);
    }

    /**
    * Loads the cached hooks.
    *
    * @return bool
    */
    public function load()
    {
        $dir = $this->getCacheDirectory();

        if (!isset($dir)) {
            throw new maud\Exception('Cache directory not set.');
        }

        $file = $dir . DIRECTORY_SEPARATOR . 'maud_eval.php';

        if (!file_exists($file)) {
            return false;
        }

        $this->hooks = include($file);

        return true;
    }

    /**
    * Saves the current hook array to the cache directory.
    *
    * @return $this
    */
    public function save()
    {
        $dir = $this->getCacheDirectory();

        if (!isset($dir)) {
            throw new maud\Exception('Cache directory not set.');
        }

        $this->delete();

        $file = $dir . DIRECTORY_SEPARATOR . 'maud_eval.php';
        file_put_contents($file, "<?php\nreturn " . var_export($this->hooks, true) . ";");

        return $this;
    }
}