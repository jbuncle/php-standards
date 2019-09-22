<?php declare(strict_types=1);
namespace JBuncle\Sniffs\Commenting;

use JBuncle\Helpers\Util;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use function count;

/**
 * FileCommentSniff
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

        $commentStart = Util::skipDeclaration($tokens, $stackPtr + 1);
        $commentStart = Util::skipWhitespace($tokens, $commentStart);

        if (!in_array($tokens[$commentStart]['type'], ['T_COMMENT', 'T_DOC_COMMENT_OPEN_TAG'])) {
            return ($phpcsFile->numTokens + 1);
        }

        if (isset($tokens[$commentStart]['comment_closer']) === false ||
                ($tokens[$tokens[$commentStart]['comment_closer']]['content'] === '' && $tokens[$commentStart]['comment_closer'] === ($phpcsFile->numTokens - 1))
        ) {
            // Don't process an unfinished file comment during live coding.
            return ($phpcsFile->numTokens + 1);
        }

        $commentStringPos = $this->findNext($tokens, $commentStart, ['T_DOC_COMMENT_STRING', 'T_DOC_COMMENT_CLOSE_TAG']);

        if ($tokens[$commentStringPos]['type'] !== 'T_DOC_COMMENT_STRING') {
            // No comment string
            $this->handleMissingFileCommentString($phpcsFile, $commentStart);
            return ($phpcsFile->numTokens + 1);
        }

        // Check comment string
        $content = $tokens[$commentStringPos]['content'];
        if ($content !== "Copyright (C) 2019 CyberPear (https://www.cyberpear.co.uk) - All Rights Reserved") {
            $this->handleBadFileCommentString($phpcsFile, $commentStart);
        }

        return ($phpcsFile->numTokens + 1);
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

    private function handleBadFileCommentString(File $phpcsFile, int $stackPtr): void {
        $phpcsFile->addError('Incorrect file comment', $stackPtr, 'Missing');
        $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'no');
    }

    private function handleMissingFileCommentString(File $phpcsFile, int $commentStart): void {
        $phpcsFile->addError('Missing file doc comment', $$commentStart, 'Missing');
        $phpcsFile->recordMetric($$commentStart, 'File has doc comment', 'no');
    }

}
