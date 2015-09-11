<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Cacheable 'include' method implementation of the hook handler interface.
*/
class IncludeHookHandler extends maud\CacheableHookHandler
{
    protected $hooksUsed = [];
    protected $isReady = false;

    /**
    * Gets the method this hook handler uses.
    *
    * @return string
    */
    public function getMethod()
    {
        if ($this->isReady) {
            return Hook::METHOD_INCLUDE;
        }

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
        if (!$this->isReady) {
            return (isset($this->hooks[$hook]) ? $this->hooks[$hook] : null);
        }

        $dir = $this->getCacheDirectory();

        if (!isset($dir)) {
            throw new maud\Exception('Cache directory not set.');
        }

        if (in_array($hook, $this->hooksUsed)) {
            return $dir . DIRECTORY_SEPARATOR . "maud_include_" . $hook . ".php";
        }

        return false;
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

        $file = $dir . DIRECTORY_SEPARATOR . 'maud_hooks_used.php';

        if (!file_exists($file)) {
            return false;
        }

        $this->hooksUsed = include($file);
        $this->isReady = true;

        return true;
    }

    /**
    * Saves the current hook array to the cache directory.
    *
    * @return this
    */
    public function save()
    {
        $dir = $this->getCacheDirectory();

        if (!isset($dir)) {
            throw new maud\Exception('Cache directory not set.');
        }
        $this->delete();

        $this->hooksUsed = [];

        foreach ($this->hooks as $hook => $code) {
            // Include some base use statements
            $code = "<?php\n" . $code;

            // Save each hook into its own file
            $file = $dir . DIRECTORY_SEPARATOR . 'maud_include_' . $hook . '.php';
            file_put_contents($file, $code);

            $this->hooksUsed[] = $hook;
        }

        $this->hooks = [];

        // Save hook list
        $file = $dir . DIRECTORY_SEPARATOR . 'maud_hooks_used.php';
        file_put_contents($file, "<?php\nreturn " . var_export($this->hooksUsed, true) . ";");

        $this->isReady = true;

        return $this;
    }

    /**
    * Parses and compiles hooks into an array.
    *
    * @param maud\HookProviderInterface $hookProvider A hook provider.
    * @return HookHandler
    */
    public function build(maud\HookProviderInterface $hookProvider)
    {
        $this->isReady = false;

        return parent::build($hookProvider);
    }
}