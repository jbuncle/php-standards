<?php

namespace JBuncle\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use const T_CLOSURE;
use const T_DOC_COMMENT_OPEN_TAG;
use const T_DOC_COMMENT_STRING;
use const T_PROPERTY;

/**
 * Description of FileCommentSniff
 *
 * @author jbuncle
 */
class FileCommentSniff implements Sniff {

    public function register() {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        $commentStart = $this->skipDeclaration($tokens, $stackPtr + 1);
        $commentStart = $this->skipWhitespace($tokens, $commentStart);

        if ($tokens[$commentStart]['code'] === T_COMMENT) {
            $this->handleBadOpenComment($phpcsFile, $commentStart);
            return ($phpcsFile->numTokens + 1);
        }

        if ($commentStart === false || $tokens[$commentStart]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
            $this->handleMissingFileComment($phpcsFile, $stackPtr);
            return($phpcsFile->numTokens + 1);
        }

        if (isset($tokens[$commentStart]['comment_closer']) === false ||
                ($tokens[$tokens[$commentStart]['comment_closer']]['content'] === '' && $tokens[$commentStart]['comment_closer'] === ($phpcsFile->numTokens - 1))
        ) {
            // Don't process an unfinished file comment during live coding.
            return ($phpcsFile->numTokens + 1);
        }


        $commentEnd = $tokens[$commentStart]['comment_closer'];

        $commentStringPos = $this->findNext($tokens, $commentStart, ['T_DOC_COMMENT_STRING', 'T_DOC_COMMENT_CLOSE_TAG']);


        if ($tokens[$commentStringPos]['type'] !== 'T_DOC_COMMENT_STRING') {
            // No comment string
            $this->handleMissingFileCommentString($phpcsFile, $commentStart);
            return ($phpcsFile->numTokens + 1);
        } else {
            // Check comment string
            $content = $tokens[$commentStringPos]['content'];
            if ($content !== "Copyright (C) 2019 CyberPear (https://www.cyberpear.co.uk) - All Rights Reserved") {
                $this->handleBadFileCommentString($phpcsFile, $commentStart);
            }
            return ($phpcsFile->numTokens + 1);
        }

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);
    }

    /**
     * Skip over declare strict call.
     *
     * @param array $tokens
     * @param int $position
     * @return int
     */
    private function skipDeclaration(array $tokens, int $position): int {


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

    private function skipWhitespace(array $tokens, int $position): int {

        while ($tokens[$position]['type'] === 'T_WHITESPACE') {
            $position++;
        }
        return $position;
    }

    private function findNext(array $tokens, int $position, array $types): ?int {
        $tokenCount = count($tokens);
        while ($position < $tokenCount) {
            if (in_array($tokens[$position]['type'], $types)) {
                return $position;
            }
            $position++;
        }

        return null;
    }

    private function handleBadOpenComment(File $phpcsFile, int $pointer): void {
        $error = 'You must use "/**" style comments for a file comment';
        $fix = $phpcsFile->addFixableError($error, $pointer, 'InvalidFileCommentOpening');
        if ($fix === true) {
            $expected = "/**\n";
            $phpcsFile->fixer->replaceToken($pointer, $expected);
        }
    }

    private function handleMissingFileComment(File $phpcsFile, int $stackPtr): void {
        $phpcsFile->addError('Missing file doc comment', $stackPtr, 'Missing');
        $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'no');
    }

    private function handleBadFileCommentString(File $phpcsFile, int $stackPtr): void {
        $phpcsFile->addError('Incorrect file comment', $stackPtr, 'Missing');
        $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'no');
    }

    private function handleMissingFileCommentString(File $phpcsFile, int $commentStart): void {
        $phpcsFile->addError('Missing file doc comment', $$commentStart, 'Missing');
        $phpcsFile->recordMetric($$commentStart, 'File has doc comment', 'no');
    }

}
