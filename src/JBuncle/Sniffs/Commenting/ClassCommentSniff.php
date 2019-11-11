<?php declare(strict_types=1);
namespace JBuncle\Sniffs\Commenting;

use JBuncle\Helpers\Util;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * FileCommentSniff
 *
 * @author jbuncle
 */
class ClassCommentSniff implements Sniff {

    public function __construct() {
    }

    /**
     *
     * @return array<mixed>
     */
    public function register(): array {
        return [T_CLASS, T_INTERFACE];
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

        $prevTokenIndex = Util::skipBack($tokens, $stackPtr - 1, [
            'T_WHITESPACE',
            'T_ABSTRACT',
            'T_FINAL',
        ]);
        if ($prevTokenIndex === null) {
            // No class comment found
            $this->handleMissingClassComment($phpcsFile, $stackPtr);
            return $stackPtr;
        }

        $prevToken = $tokens[$prevTokenIndex];

        if ($prevToken['type'] !== "T_DOC_COMMENT_CLOSE_TAG") {
            // No class comment found
            $this->handleMissingClassComment($phpcsFile, $stackPtr);
            return $stackPtr;
        }

        $commentStart = Util::searchBack($tokens, $stackPtr, ['T_COMMENT', 'T_DOC_COMMENT_OPEN_TAG']);

        if ($commentStart === null) {
            // No comment string
            $this->handleMissingClassCommentString($phpcsFile, $stackPtr);
            return ($phpcsFile->numTokens + 1);
        }

        $commentStringPos = Util::searchForward($tokens, $commentStart, [
                    'T_DOC_COMMENT_STRING',
                    'T_DOC_COMMENT_CLOSE_TAG',
        ]);

        if ($commentStringPos === null) {
            // No comment string
            $this->handleMissingClassCommentString($phpcsFile, $stackPtr);
            return ($phpcsFile->numTokens + 1);
        }

        if ($tokens[$commentStringPos]['type'] !== 'T_DOC_COMMENT_STRING') {
            // No comment string
            $this->handleMissingClassCommentString($phpcsFile, $stackPtr);
            return ($phpcsFile->numTokens + 1);
        }

        // Check comment string
        $this->processCommentString($phpcsFile, $commentStringPos);

        return ($phpcsFile->numTokens + 1);
    }

    private function processCommentString(File $phpcsFile, int $stackPtr): void {
        $token = $phpcsFile->getTokens()[$stackPtr];
        $content = $token['content'];

        $matches = null;
        if (preg_match('/^Description of (.*)$/', $content, $matches)) {
            $content = $token['content'];

            $expected = $matches[1];

            $fix = $phpcsFile->addFixableError("Incorrect class comment '$content', expected '$expected'", $stackPtr, 'InvalidClassDoc');
            $phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'yes');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($stackPtr, $expected);
            }
        }
    }

    private function handleMissingClassCommentString(File $phpcsFile, int $commentStart): void {
        $phpcsFile->addError('Missing class doc description', $commentStart, 'Missing');
        $phpcsFile->recordMetric($commentStart, 'Class has doc comment', 'no');
    }

    private function handleMissingClassComment(File $phpcsFile, int $commentStart): void {
        $phpcsFile->addError('Missing class doc', $commentStart, 'Missing');
        $phpcsFile->recordMetric($commentStart, 'Class has doc comment', 'no');
    }

}
