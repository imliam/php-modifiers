<?php

namespace ImLiam\Modifiers;

trait HasModifiers
{
    /**
     * @return array
     */
    // public static $modifierAliases = [];

    /**
     * @return array
     */
    protected static function getModifierAliases(): array
    {
        if (isset(static::$modifierAliases)) {
            return static::$modifierAliases;
        }

        return [];
    }

    protected static function hasModifier(string $modifier): bool
    {
        return in_array($modifier, static::getModifiers());
    }

    protected static function hasModifiers(array $modifiers): bool
    {
        $usedModifiers = static::getModifiers();

        foreach ($modifiers as $modifier) {
            if (! in_array($modifier, $usedModifiers)) {
                return false;
            }
        }

        return true;
    }

    protected static function getModifiers(): array
    {
        $modifiers = static::getCallInfo(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

        if (! is_array($modifiers)) {
            return [];
        }

        return $modifiers;
    }

    /**
     * Gets call info from the backtrace, alias, and argument count.
     *
     * @param array[] $trace Backtrace
     *
     * @return array{params:null|array, modifiers:array, callee:null|array, caller:null|array, trace:array[]} Call info
     */
    protected static function getCallInfo(array $trace)
    {
        $found = false;
        $callee = null;
        $miniTrace = [];

        $aliases = Utils::normalizeAliases(static::getModifierAliases());

        foreach ($trace as $frame) {
            if (Utils::traceFrameIsListed($frame, $aliases)) {
                $found = true;
                $miniTrace = [];
            }

            if (! Utils::traceFrameIsListed($frame, ['spl_autoload_call'])) {
                $miniTrace[] = $frame;
            }
        }

        if ($found) {
            $callee = reset($miniTrace) ?: null;
        }

        return static::getSingleCall($callee ?: []);
    }

    /**
     * Returns specific function call info from a stack trace frame, or null if no match could be found.
     *
     * @param array $frame The stack trace frame in question
     *
     * @return array|null modifiers, or null if a specific call could not be determined
     */
    protected static function getSingleCall(array $frame)
    {
        if (! isset($frame['file'], $frame['line'], $frame['function']) || ! is_readable($frame['file'])) {
            return null;
        }

        if (empty($frame['class'])) {
            $callfunc = $frame['function'];
        } else {
            $callfunc = [$frame['class'], $frame['function']];
        }

        $calls = CallFinder::getModifiersUsed(
            file_get_contents($frame['file']),
            $frame['line'],
            $callfunc
        );

        return $calls;
    }
}
