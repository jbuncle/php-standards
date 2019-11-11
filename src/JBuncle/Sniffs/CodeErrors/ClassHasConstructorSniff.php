<?php declare(strict_types=1);
namespace JBuncle\Sniffs\CodeErrors;

use JBuncle\Helpers\Util;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * ClassHasConstructorSniff - Ensure all classes define constructor.
 *
 * @author jbuncle
 */
class ClassHasConstructorSniff implements Sniff {

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

        $currentToken = $tokens[$stackPtr];
        $this->processClass($phpcsFile, $stackPtr, $currentToken);

        return ($phpcsFile->numTokens + 1);
    }

    /**
     *
     * @param File $phpcsFile
     * @param array<string,mixed> $classToken
     * @return void
     */
    private function processClass(File $phpcsFile, int $pos, array $classToken): void {
        if ($this->classExtendsClass($phpcsFile, $pos, $classToken)) {
            // Only check that top level classes have constructors
            return;
        }

        $classOpener = $classToken['scope_opener'];
        $classCloser = $classToken['scope_closer'];

        // Check type is initiliased in constructor
        //  Find constructor
        $constructorPos = $this->findConstructor($phpcsFile, $classOpener, $classCloser);
        if ($constructorPos === null) {
            // No constructor

            $this->handleMissingConstructor($phpcsFile, $classOpener + 1);
        }
    }

    /**
     *
     * @param File $phpcsFile
     * @param int $pos
     * @param array<string,mixed> $classToken
     * @return bool
     */
    private function classExtendsClass(File $phpcsFile, int $pos, array $classToken): bool {
        $tokens = $phpcsFile->getTokens();
        for ($index = $pos; $index < $classToken['scope_opener']; $index++) {
            $token = $tokens[$index];
            $tokenType = $token['type'];
            if ($tokenType === 'T_EXTENDS') {
                return true;
            }
        }

        return false;
    }

    private function findConstructor(File $phpcsFile, int $startPos, int $endPos): ?int {
        $tokens = $phpcsFile->getTokens();

        for ($position = $startPos; $position < $endPos; $position++) {
            $token = $tokens[$position];
            if (strcasecmp($token['type'], 'T_FUNCTION') !== 0) {
                continue;
            }

            $fnPos = Util::skipWhitespace($tokens, $position + 1);
            $functionNameToken = $tokens[$fnPos];
            if (strcasecmp($functionNameToken['type'], 'T_STRING') !== 0) {
                // Probably an anonymous function
                continue;
            }

            if (strcasecmp($functionNameToken['content'], '__construct') === 0) {
                return $position;
            }
        }

        return null;
    }

    private function handleMissingConstructor(File $phpcsFile, int $classOpener): void {

        $fix = $phpcsFile->addFixableError("Missing constructor", $classOpener, 'MissingConstructor');
        if ($fix === true) {
            $str = "";
            $str .= "\n";
            $str .= "\n    public function __construct() {";
            $str .= "\n    }";
            $str .= "\n";

            $phpcsFile->fixer->addContent($classOpener, $str);
        }

        $phpcsFile->recordMetric($classOpener, 'Class has constructor', 'no');
    }

}
