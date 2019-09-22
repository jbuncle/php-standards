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
class FileCommentStartSniff implements Sniff {

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
        $tokens = $phpcsFile->getTokens();

        $commentStart = Util::skipDeclaration($tokens, $stackPtr + 1);
        $commentStart = Util::skipWhitespace($tokens, $commentStart);

        if ($tokens[$commentStart]['code'] === T_COMMENT) {
            $this->handleBadOpenComment($phpcsFile, $commentStart);
        }

        return ($phpcsFile->numTokens + 1);
    }

    private function handleBadOpenComment(File $phpcsFile, int $pointer): void {
        $error = 'You must use "/**" style comments for a file comment';
        $fix = $phpcsFile->addFixableError($error, $pointer, 'InvalidFileCommentOpening');
        if ($fix === true) {
            $expected = "/**\n";
            $phpcsFile->fixer->replaceToken($pointer, $expected);
        }
    }

}
