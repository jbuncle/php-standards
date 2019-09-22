<?php declare(strict_types=1);
namespace JBuncle\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use const T_DOC_COMMENT_WHITESPACE;
use function count;

/**
 * FileCommentSniff
 *
 * @author jbuncle
 */
class FileCommentSpacingSniff implements Sniff {

    public function register() {
        return [T_DOC_COMMENT_WHITESPACE];
    }

    private function skipOver(array $tokens, int $start, string $type): int {

        $count = count($tokens);
        for ($index = $start; $index < $count; $index++) {
            if ($tokens[$index]['type'] !== $type) {
                return $index;
            }
        }

        // Not found
        return $start;
    }

    public function process(File $phpcsFile, $stackPtr): void {
        $tokens = $phpcsFile->getTokens();
        $currentToken = $tokens[$stackPtr];

        $nextToken = $tokens[$this->skipOver($tokens, $stackPtr, 'T_DOC_COMMENT_WHITESPACE')];
        if (in_array($nextToken['type'], ['T_DOC_COMMENT_STAR', 'T_DOC_COMMENT_CLOSE_TAG'])) {
            // Skip whitespace before comment star
            return;
        }

        $length = strlen($currentToken['content']);

        if ($length <= 1) {
            return;
        }

        $test = str_repeat(' ', $length);

        if ($currentToken['content'] === $test) {
            // Report double whitespace
            $this->handleDoubleWhitespace($phpcsFile, $stackPtr);
        }

        return;
    }

    private function handleDoubleWhitespace(File $phpcsFile, int $pointer): void {
        $error = 'Double whitespace found';
        $fix = $phpcsFile->addFixableError($error, $pointer, 'DoubleSpace');
        if ($fix === true) {
            $expected = " ";
            $phpcsFile->fixer->replaceToken($pointer, $expected);
        }
    }

}
