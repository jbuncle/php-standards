<?php declare(strict_types=1);
namespace JBuncle\Sniffs\CodeErrors;

use JBuncle\Helpers\Util;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * ConstructorCallsParentSniff - Ensure
 *
 * @author jbuncle
 */
class ConstructorCallsParentSniff implements Sniff {

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
        if (!$this->classExtendsClass($phpcsFile, $pos, $classToken)) {
            return;
        }

        $startPos = $classToken['scope_opener'];
        $endPos = $classToken['scope_closer'];

        // Check type is initiliased in constructor
        //  Find constructor
        $tokens = $phpcsFile->getTokens();
        $constructorPos = $this->findConstructor($phpcsFile, $startPos, $endPos);
        if ($constructorPos === null) {
            // No constructor - this is OK
            return;
        }

        $constructor = $tokens[$constructorPos];

        $hasCallToParentConstructor = $this->hasCallToParentConstructor(
                $phpcsFile,
                intval($constructor['scope_opener'] + 1),
                intval($constructor['scope_closer'] - 1)
        );

        if ($hasCallToParentConstructor) {
            return;
        }

        $this->handleMissingCallToParent($phpcsFile, $constructorPos);
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

    private function hasCallToParentConstructor(File $phpcsFile, int $startPos, int $endPos): bool {
        $tokens = $phpcsFile->getTokens();
        for ($position = $startPos; $position < $endPos; $position++) {
            $token = $tokens[$position];
            $tokenType = $token['type'];
            if ($tokenType !== 'T_PARENT') {
                continue;
            }

            // Found
            $memberNamePos = Util::skipForward($tokens, $position + 1, ['T_WHITESPACE', 'T_DOUBLE_COLON']);

            if ($memberNamePos === null) {
                // Treat as missing
                return false;
            }

            $memberNameToken = $tokens[$memberNamePos];
            if ($memberNameToken['content'] === '__construct') {
                // Not an assignment
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

    private function handleMissingCallToParent(File $phpcsFile, int $memberPos): void {

        $phpcsFile->addError("Class constructor does not call parent", $memberPos, 'Missing');
        $phpcsFile->recordMetric($memberPos, 'Class constructor calls parent', 'no');
    }

}
