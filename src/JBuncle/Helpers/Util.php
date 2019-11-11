<?php declare(strict_types=1);
namespace JBuncle\Helpers;

/**
 * Util
 *
 * @author jbuncle
 */
class Util {

    public function __construct() {
    }

    /**
     * Go backward until we find a token that doesn't match the ones defined.
     *
     * @param array<int, array<string, mixed>> $tokens
     * @param int $position
     * @param array<string> $types
     *
     * @return ?int
     */
    public static function skipBack(array $tokens, int $position, array $types): ?int {
        for ($index = $position; $index > 0; $index--) {
            $currentToken = $tokens[$index];
            if (!in_array($currentToken['type'], $types)) {
                return $index;
            }
        }

        return null;
    }

    /**
     *
     * @param array<int, array<string, mixed>> $tokens
     * @param int $position
     * @param array<string> $types
     *
     * @return int|null
     */
    public static function skipForward(array $tokens, int $position, array $types): ?int {
        $tokenCount = count($tokens);
        for ($index = $position; $index < $tokenCount; $index++) {
            if (!in_array($tokens[$index]['type'], $types)) {
                return $index;
            }
        }

        return null;
    }

    /**
     *
     * @param array<int, array<string, mixed>>  $tokens
     * @param int $position
     * @param array<string> $types
     *
     * @return int|null
     */
    public static function searchBack(array $tokens, int $position, array $types): ?int {
        for ($index = $position; $index > 0; $index--) {
            $currentToken = $tokens[$index];
            if (in_array($currentToken['type'], $types)) {
                return $index;
            }
        }

        return null;
    }

    /**
     *
     * @param array<int, array<string, mixed>>  $tokens
     * @param int $position
     * @param array<string> $types
     *
     * @return int|null
     */
    public static function searchForward(array $tokens, int $position, array $types): ?int {
        $tokenCount = count($tokens);
        for ($index = $position; $index < $tokenCount; $index++) {
            if (in_array($tokens[$index]['type'], $types)) {
                return $index;
            }
        }

        return null;
    }

    /**
     *
     * @param array<int, array<string, mixed>>  $tokens
     * @param int $position
     *
     * @return int
     */
    public static function skipWhitespace(array $tokens, int $position): int {

        while (in_array($tokens[$position]['type'], ['T_WHITESPACE','T_DOC_COMMENT_WHITESPACE'])) {
            $position++;
        }

        return $position;
    }

    /**
     *
     * @param array<int, array<string, mixed>>  $tokens
     * @param array<string> $types
     * @param int $from
     * @param int $to
     *
     * @return int|null
     */
    public static function find(array $tokens, array $types, int $from, int $to): ?int {
        for ($index = $from; $index < $to; $index++) {
            if (\in_array($tokens[$index]['type'], $types)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Skip over declare strict call.
     *
     * @param array<int, array<string, mixed>> $tokens
     * @param int $position
     * @return int
     */
    public static function skipDeclaration(array $tokens, int $position): int {

        $declarationTokens = [
            'T_DECLARE', 'T_OPEN_PARENTHESIS', 'T_STRING',
            'T_EQUAL', 'T_LNUMBER', 'T_CLOSE_PARENTHESIS', 'T_SEMICOLON'
        ];

        $tokensCount = count($tokens);
        $declarationTokensCount = count($declarationTokens);

        $startPosition = $position;
        while ($position < $tokensCount && ($position - $startPosition) < $declarationTokensCount) {
            $currentTokenType = $tokens[$position]['type'];
            //Ignore whitespace
            if ($currentTokenType !== 'T_WHITESPACE') {
                if ($currentTokenType !== $declarationTokens[$position - $startPosition]) {
                    // Fail
                    return $startPosition;
                }
            }

            $position++;
        }

        return $position;
    }

}
