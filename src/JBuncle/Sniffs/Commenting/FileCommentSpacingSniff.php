<?php declare(strict_types=1);
namespace JBuncle\Sniffs\Commenting;

use JBuncle\Helpers\Util;
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

    /**
     *
     * @return array<mixed>
     */
    public function register(): array {
        return [T_DOC_COMMENT_WHITESPACE];
    }

    /**
     * Process.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     *
     * @param File $phpcsFile
     * @param int  $stackPtr
     *
     * @return void|int
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        $currentToken = $tokens[$stackPtr];

        $nextToken = $tokens[Util::skipForward($tokens, $stackPtr, ['T_DOC_COMMENT_WHITESPACE'])];
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
