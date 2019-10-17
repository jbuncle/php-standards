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

    /**
     *
     * @return array<mixed>
     */
    public function register(): array {
        return [T_OPEN_TAG];
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
        /** @var array<int, array<string, mixed>> $tokens */
        $tokens = $phpcsFile->getTokens();
        /* @phan-suppress-next-line PhanTypeMismatchArgument */
        $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        $commentStart = Util::skipDeclaration($tokens, $stackPtr + 1);
        $commentStart = Util::skipWhitespace($tokens, $commentStart);

        if (!in_array($tokens[$commentStart]['type'], ['T_COMMENT', 'T_DOC_COMMENT_OPEN_TAG'])) {
            return ($phpcsFile->numTokens + 1);
        }

        if (isset($tokens[$commentStart]['comment_closer']) === false ||
                ($tokens[$tokens[$commentStart]['comment_closer']]['content'] === '' &&
                $tokens[$commentStart]['comment_closer'] === ($phpcsFile->numTokens - 1))
        ) {
            // Don't process an unfinished file comment during live coding.
            return ($phpcsFile->numTokens + 1);
        }

        $commentStringPos = $this->findNext($tokens, $commentStart, ['T_DOC_COMMENT_STRING', 'T_DOC_COMMENT_CLOSE_TAG']);

        if ($commentStringPos === null) {
            // No comment found
            $this->handleMissingFileCommentString($phpcsFile, $commentStart);
            return ($phpcsFile->numTokens + 1);
        }

        if ($tokens[$commentStringPos]['type'] !== 'T_DOC_COMMENT_STRING') {
            // No comment string
            $this->handleMissingFileCommentString($phpcsFile, $commentStart);
            return ($phpcsFile->numTokens + 1);
        }

        // Check comment string
        $content = $tokens[$commentStringPos]['content'];
        $allowedCopyrights = [
            "Copyright (C) 2019 CyberPear (https://www.cyberpear.co.uk) - All Rights Reserved",
            "Copyright (C) 2019 James Buncle (https://www.jbuncle.co.uk) - All Rights Reserved",
        ];

        if (!in_array($content, $allowedCopyrights)) {
            $this->handleBadFileCommentString($phpcsFile, $commentStart);
        }

        return ($phpcsFile->numTokens + 1);
    }

    /**
     *
     * @param array<int, array<string, mixed>> $tokens
     * @param int $position
     * @param array<string> $types
     *
     * @return int|null
     */
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
        $phpcsFile->addError('Missing file doc comment', $commentStart, 'Missing');
        $phpcsFile->recordMetric($commentStart, 'File has doc comment', 'no');
    }

}
