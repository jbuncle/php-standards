<?php

declare(strict_types=1);

namespace JBuncle\Sniffs\CodeErrors;

use Iterator;
use JBuncle\Helpers\Util;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use function count;

/**
 * ClassNameMatchesFileNameSniff - Ensure class matches file.
 *
 * @author jbuncle
 */
class OneClassPerFileSniff implements Sniff {

    public function __construct() {
        
    }

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
        $found = false;
        foreach ($this->findClasses($tokens) as $classIndex) {
            if ($found) {
                // More than one
                $classNameTokenIndex = Util::searchForward($tokens, $classIndex, ['T_STRING']);
                $classNameToken = $tokens[$classNameTokenIndex];
                $className = $classNameToken['content'];
                $this->handleMultipleClass($phpcsFile, $classIndex, $className);
            } else {
                $found = true;
            }
        }

        return ($phpcsFile->numTokens + 1);
    }

    private function findClasses(array $tokens): Iterator {
        for ($index = 0; $index < count($tokens); $index++) {
            $token = $tokens[$index];
            if ($token['type'] === 'T_CLASS') {
                yield $index;
            }
        }
    }

    private function handleMultipleClass(File $phpcsFile, int $classOpener, string $className): void {
        $phpcsFile->addError("Found additional class '$className' in the same file", $classOpener, 'MultipleClassesInFile');
        $phpcsFile->recordMetric($classOpener, 'Multiple classes', 'no');
    }

}
