<?php

declare(strict_types=1);

namespace JBuncle\Sniffs\CodeErrors;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * ClassNameMatchesFileNameSniff - Ensure class matches file.
 *
 * @author jbuncle
 */
class ClassNameMatchesFileNameSniff implements Sniff {

    public function __construct() {
        
    }

    /**
     *
     * @return array<mixed>
     */
    public function register(): array {
        return [T_CLASS];
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

        if ($this->getClassesCount($tokens) > 1) {
            return;
        }
        $currentToken = $tokens[$stackPtr];
        $this->processClass($phpcsFile, $stackPtr, $currentToken);

        return ($phpcsFile->numTokens + 1);
    }

    
    /**
     * 
     * @param array $tokens
     * @return int
     */
    private function getClassesCount(array $tokens): int {
        $count = 0;
        for ($index = 0; $index < count($tokens); $index++) {
            $token = $tokens[$index];
            if ($token['type'] === 'T_CLASS') {
                $count++;
            }
        }
        return $count;
    }

    /**
     *
     * @param File $phpcsFile
     * @param array<string,mixed> $classToken
     * @return void
     */
    private function processClass(File $phpcsFile, int $pos, array $classToken): void {
        $tokens = $phpcsFile->getTokens();
        for ($index = $pos; $index < count($tokens); $index++) {
            $token = $tokens[$index];
            if ($token['type'] === 'T_STRING') {
                $classNameTokenIndex = $index;
                break;
            }
        }

        if ($classNameTokenIndex !== null) {
            // No constructor
            $classNameToken = $tokens[$index];
            $className = $classNameToken['content'];

            $fileName = $this->getFileName($phpcsFile);
            if (strcmp($className, $fileName) !== 0) {
                $this->handleBadClassName($phpcsFile, intval($index), $className, $fileName);
            }
        }
    }

    private function getFileName($phpcsFile): string {
        return basename($phpcsFile->getFilename(), '.php');
    }

    private function handleBadClassName(File $phpcsFile, int $classOpener, string $className, string $fileName): void {

        $phpcsFile->addError("Class '$className' doesn't match file name '$fileName'", $classOpener, 'ClassFileMisMatch');
//        $fix = $phpcsFile->addFixableError("Class doesn't match file name", $classOpener, 'ClassFileMisMatch');
//        if ($fix === true) {
//            $phpcsFile->fixer->addContent($classOpener, $fileName);
//        }

        $phpcsFile->recordMetric($classOpener, 'Class name correct', 'no');
    }

}
