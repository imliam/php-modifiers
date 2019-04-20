<?php

namespace ImLiam\Modifiers;

/**
 * @see https://github.com/kint-php/kint/blob/master/src/CallFinder.php
 */
class CallFinder
{
    private static $ignore = [
        T_CLOSE_TAG => true,
        T_COMMENT => true,
        T_DOC_COMMENT => true,
        T_INLINE_HTML => true,
        T_OPEN_TAG => true,
        T_OPEN_TAG_WITH_ECHO => true,
        T_WHITESPACE => true,
    ];

    private static $up = [
        '(' => true,
        '[' => true,
        '{' => true,
        T_CURLY_OPEN => true,
        T_DOLLAR_OPEN_CURLY_BRACES => true,
    ];

    private static $down = [
        ')' => true,
        ']' => true,
        '}' => true,
    ];

    private static $modifiers = [
        '!' => true,
        '@' => true,
        '+' => true,
        '-' => true,
        '~' => true,
    ];

    private static $identifier = [
        T_DOUBLE_COLON => true,
        T_STRING => true,
        T_NS_SEPARATOR => true,
    ];

    public static function getModifiersUsed($source, $line, $function)
    {
        $tokens = token_get_all($source);
        $cursor = 1;
        $modifiersUsed = [];
        /** @var array<int, array|string|null> Performance optimization preventing backwards loops */
        $prevTokens = [null, null, null];

        if (is_array($function)) {
            $class = explode('\\', $function[0]);
            $class = strtolower(end($class));
            $function = strtolower($function[1]);
        } else {
            $class = null;
            $function = strtolower($function);
        }

        // Loop through tokens
        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            // Count newlines for line number instead of using $token[2]
            // since certain situations (String tokens after whitespace) may
            // not have the correct line number unless you do this manually
            $cursor += substr_count($token[1], "\n");
            if ($cursor > $line) {
                break;
            }

            // Store the last real tokens for later
            if (isset(self::$ignore[$token[0]])) {
                continue;
            }

            $prevTokens = [$prevTokens[1], $prevTokens[2], $token];

            // Check if it's the right type to be the function we're looking for
            if (T_STRING !== $token[0] || strtolower($token[1]) !== $function) {
                continue;
            }

            // Check if it's a function call
            $nextReal = self::realTokenIndex($tokens, $index);
            if (! isset($nextReal, $tokens[$nextReal]) || '(' !== $tokens[$nextReal]) {
                continue;
            }

            // Check if it matches the signature
            if (null === $class) {
                if ($prevTokens[1] && in_array($prevTokens[1][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR], true)) {
                    continue;
                }
            } else {
                if (! $prevTokens[1] || T_DOUBLE_COLON !== $prevTokens[1][0]) {
                    continue;
                }

                if (! $prevTokens[0] || T_STRING !== $prevTokens[0][0] || strtolower($prevTokens[0][1]) !== $class) {
                    continue;
                }
            }

            $innerCursor = $cursor;
            $depth = 1; // The depth respective to the function call
            $offset = $nextReal + 1; // The start of the function call
            $inString = false; // Whether we're in a string or not

            // Loop through the following tokens until the function call ends
            while (isset($tokens[$offset])) {
                $token = $tokens[$offset];

                // Ensure that the $innerCursor is correct and
                // that $token is either a T_ constant or a string
                if (is_array($token)) {
                    $innerCursor += substr_count($token[1], "\n");
                }

                // If it's a token that makes us to up a level, increase the depth
                if (isset(static::$up[$token[0]])) {
                    $depth++;
                } elseif (isset(static::$down[$token[0]])) {
                    $depth--;
                } elseif ('"' === $token[0]) {
                    // Strings use the same symbol for up and down, but we can
                    // only ever be inside one string, so just use a bool for that
                    if ($inString) {
                        $depth--;
                    } else {
                        $depth++;
                    }

                    $inString = ! $inString;
                }

                // Depth has dropped to 0 (So we've hit the closing paren)
                if ($depth <= 0) {
                    break;
                }

                $offset++;
            }

            // If we're not passed (or at) the line at the end
            // of the function call, we're too early so skip it
            if ($innerCursor < $line) {
                continue;
            }

            // Get the modifiers
            $index--;

            while (isset($tokens[$index])) {
                if (! isset(self::$ignore[$tokens[$index][0]]) && ! isset(static::$identifier[$tokens[$index][0]])) {
                    break;
                }

                $index--;
            }

            $mods = [];

            while (isset($tokens[$index])) {
                if (isset(self::$ignore[$tokens[$index][0]])) {
                    $index--;
                    continue;
                }

                if (isset(static::$modifiers[$tokens[$index][0]])) {
                    $mods[] = $tokens[$index];
                    $index--;
                    continue;
                }

                break;
            }

            $modifiersUsed[] = $mods;
        }

        return array_unique(array_merge(...$modifiersUsed));
    }

    private static function realTokenIndex(array $tokens, $index)
    {
        $index++;

        while (isset($tokens[$index])) {
            if (! isset(self::$ignore[$tokens[$index][0]])) {
                return $index;
            }

            $index++;
        }

        return null;
    }
}
