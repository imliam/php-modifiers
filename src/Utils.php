<?php

namespace ImLiam\Modifiers;

/**
 * @see https://github.com/kint-php/kint/blob/master/src/Utils.php
 */
class Utils
{
    public static function traceFrameIsListed(array $frame, array $matches)
    {
        if (isset($frame['class'])) {
            $called = [strtolower($frame['class']), strtolower($frame['function'])];
        } else {
            $called = strtolower($frame['function']);
        }

        return in_array($called, $matches, true);
    }

    public static function normalizeAliases(array $aliases)
    {
        foreach ($aliases as $index => &$alias) {
            if (is_array($alias) && 2 === count($alias)) {
                $alias = array_values(array_filter($alias, 'is_string'));

                if (2 === count($alias) &&
                    preg_match('/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/', $alias[1]) &&
                    preg_match('/^[a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*$/', $alias[0])
                ) {
                    $alias = [
                        strtolower(ltrim($alias[0], '\\')),
                        strtolower($alias[1]),
                    ];
                } else {
                    unset($aliases[$index]);
                    continue;
                }
            } elseif (is_string($alias)) {
                if (preg_match('/^[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/', $alias)) {
                    $alias = strtolower($alias);
                } else {
                    unset($aliases[$index]);
                    continue;
                }
            } else {
                unset($aliases[$index]);
            }
        }

        return array_values($aliases);
    }
}
