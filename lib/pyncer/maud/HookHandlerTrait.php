<?php
namespace pyncer\maud;

use pyncer\maud;

/**
* Provides an implementation of the hook handler interfaces build function.
*/
trait HookHandlerTrait
{
    protected $hooks = [];

    private $hookProvider = null;
    private $labelCount = 0;
    private $hookCount = 0;

    /**
    * Parses and compiles hooks into an array.
    *
    * @param maud\HookProviderInterface $hookProvider A hook provider.
    * @return HookHandlerInterface
    */
    public function build(maud\HookProviderInterface $hookProvider)
    {
        $this->hookProvider = $hookProvider;
        $this->labelCount = 0;
        $this->hookCount = 0;

        $cache = [];

        foreach ($this->hookProvider->getHook() as $alias => $value) {
            $cache = $this->buildCacheArrayValue(
                $cache,
                $alias,
                $value
            );
        }

        $hooks = [];
        foreach ($cache as $hook => $codeBlocks) {
            $uses = '';

            usort($codeBlocks, function($a, $b) {
                if ($a[2] == $b[2]) {
                    // We compare hook count to ensure same order
                    if ($a[4] > $b[4]) {
                        return 1;
                    }
                    return -1;
                }

                if ($a[2] == -1) {
                    return 1;
                }

                if ($b[2] == -1) {
                    return -1;
                }

                if ($a[2] > $b[2]) {
                    return 1;
                }

                return -1;
            });

            $hooks[$hook] = '';

            foreach ($codeBlocks as $codeBlock) {
                $uses .= $codeBlock[1];

                $first = ($hooks[$hook] === '');

                // Only execute returnable codeblocks if the return value has not been set yet.
                if (!$first && $codeBlock[3]) {
                    $hooks[$hook] .= "\nif (\$maud__return === null) {\n";
                }

                $hooks[$hook] .= ' ' . $codeBlock[0];

                if (!$first && $codeBlock[3]) {
                    $hooks[$hook] .= "\n}\n";
                }
            }

            $uses = $this->makeUsesUnique($uses);

            $hooks[$hook] = $uses . "\n\$maud__return = null;\n" . $hooks[$hook] . "\nreturn \$maud__return;\n";
        }

        unset(
            $this->hookProvider,
            $this->labelCount
        ); // These object is no logner needed

        $this->hooks = $hooks;

        return $this;
    }

    /**
    * Builds an array of hook code pair arrays parsed from code string.
    *
    * @param array $cache The existing cache array.
    * @param string $alias The alias of the hook set.
    * @param string $value The hook code string.
    */
    private function buildCacheArrayValue(array $cache, $alias, $value)
    {
        // Tokenize php file and parse out functions
        // http://www.php.net/manual/en/tokens.php
        $tokens = token_get_all($value);

        $inFunc = false;
        $inClosure = 0;
        $hasReturnValue = false; // Does this extension return a non falsey value
        $label = false;
        $uses = [];
        $bracketDepth = 0;
        $funcName = '';
        $funcCode = '';

        $len = count($tokens);
        for ($i = 0; $i < $len; ++$i) {
            $token = $tokens[$i];

            if (!$inFunc) {
                // Anything outside of a function can be ignored
                if (is_array($token) && $token[0] == T_FUNCTION) {
                    $inFunc = true;

                    // Skip to function name (whitespace and possibly comments)
                    while (isset($tokens[$i])) {
                        ++$i;
                        $token = $tokens[$i];
                        if (is_array($token) && $token[0] == T_STRING) {
                            break;
                        }
                    }

                    $funcName = $token[1];

                    // Skip to {
                    while (isset($tokens[$i])) {
                        ++$i;
                        $token = $tokens[$i];
                        if (!is_array($token) && $token == '{') {
                            break;
                        }
                    }

                    // Reset values
                    $funcCode = '';
                    $bracketDepth = 1;
                } else if (is_array($token) && ($token[0] == T_INCLUDE || $token[0] == T_REQUIRE)) {
                    $path = '';

                    // Skip to ; while building path
                    while (isset($tokens[$i])) {
                        ++$i;
                        $token = $tokens[$i];
                        if (is_array($token)) {
                            if ($token[0] && $this->isIgnorableToken($token[0])) {
                                continue;
                            }

                            $path .= $token[1];
                        } else if ($token == ';') {
                            break;
                        } else if ($token != '(' && $token != ')') {
                            $path .= $token;
                        }
                    }

                    $path = eval("return " . $path . ";");

                    $new_tokens = token_get_all(file_get_contents($path));
                    $tokens = array_merge($tokens, $new_tokens);
                    $len = count($tokens);
                } else if (is_array($token) && $token[0] == T_USE) {
                    $use = $token[1] . ' ' ;

                    while (isset($tokens[$i])) {
                        ++$i;
                        $token = $tokens[$i];
                        if (is_array($token)) {
                            if ($token[0] && $this->isIgnorableToken($token[0])) {
                                continue;
                            }

                            $use .= $token[1];
                        } else if ($token == ';') {
                            break;
                        } else {
                            $use .= $token;
                        }
                    }

                    $uses[] = $use;
                }
            } else if (is_array($token)) {
                if ($token[0] == T_FUNCTION && !$inClosure) {
                    $inClosure = ($bracketDepth + 1);
                    $funcCode .= $token[1];
                    continue;
                }

                if ($inClosure || $token[0] != T_RETURN) {
                    $funcCode .= $token[1];
                    continue;
                }

                // We want to override returns with GOTOs so that multiple extensions can use the same hook
                if ($label === false) {
                    $label = ++$this->labelCount;
                }

                $return_value = null;
                while (isset($tokens[$i])) {
                    ++$i;
                    $token = $tokens[$i];

                    if (!is_array($token)) {
                        $token = [false, $token];
                    }

                    // Skip over whitespace and comments
                    if ($token[0] && $this->isIgnorableToken($token[0])) {
                        continue;
                    }

                    if ($token[1] == ';') {
                        break;
                    }

                    $return_value .= $token[1];
                }

                // Set extension return variable and goto ext label return spot if they return value
                if ($return_value !== null && strcasecmp($return_value, 'null') != 0) {
                    $hasReturnValue = true;
                    $funcCode .= '$maud__return = ' . $return_value . ';';
                }

                $funcCode .= 'goto maud_label_' . $label . ';';
            } else if ($token == '{') {
                ++$bracketDepth;
                $funcCode .= '{';
            } else if ($token == '}') {
                if ($inClosure && $bracketDepth == $inClosure) {
                    $inClosure = 0;
                }

                --$bracketDepth;

                if ($bracketDepth) {
                    $funcCode .= '}';
                }
                else { // End of function
                    $inFunc = false;

                    if ($label !== false) {
                        $funcCode .= "\nmaud_label_" . $label . ":\n";
                    }

                    ++$this->hookCount;

                    $cache[$funcName][] = [
                        $funcCode,
                        ($uses ? implode(';', $uses) . ';' : ''),
                        $this->hookProvider->getHookPriority($alias, $funcName),
                        $hasReturnValue,
                        $this->hookCount
                    ];

                    $label = false;
                    $hasReturnValue = false;
                }
            } else {
                $funcCode .= $token;
            }
        }

        return $cache;
    }

    /**
    * Gets whether a token can be ignored. An ignorable tokens include
    * whitespace and comments.
    *
    * @param string $token
    */
    private function isIgnorableToken($token)
    {
        return ($token == T_WHITESPACE || $token == T_COMMENT || $token == T_DOC_COMMENT);
    }

    /**
    * Removes duplicate use statements from a string.
    *
    * @param string $uses
    */
    private function makeUsesUnique($uses)
    {
        $newUseLines = [];

        $useLines = explode(';', $uses);
        foreach ($useLines as $useLine) {
            $useLine = trim($useLine);

            if (!$useLine) {
                continue;
            }

            $useLine = explode(',', $useLine);

            foreach ($useLine as $key => $value) {
                $value = trim($value);

                if ($key > 0) {
                    $value = 'use ' . $value;
                }

                $newUseLines[] = $value;
            }
        }

        return ($newUseLines ? implode(';', $newUseLines) . ';' : '');
    }
}
