<?php declare(strict_types=1);
namespace JBuncle\Sniffs\CodeErrors;

use JBuncle\Helpers\Util;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * MemberInitialisation - Ensure that class members are initialised in the constructor.
 *
 * @author jbuncle
 */
class MemberInitialisationSniff implements Sniff {

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
        $this->processClass($phpcsFile, $currentToken);

        return ($phpcsFile->numTokens + 1);
    }

    /**
     *
     * @param File $phpcsFile
     * @param int $startPos
     * @param int $endPos
     *
     * @return array<int,mixed>
     */
    private function findMembers(File $phpcsFile, int $startPos, int $endPos): array {
        $tokens = $phpcsFile->getTokens();
        // Find class
        $members = [];
        for ($position = $startPos; $position < $endPos; $position++) {
            $token = $tokens[$position];
            if (strcasecmp($token['type'], 'T_FUNCTION') === 0) {
                if (isset($token['scope_closer'])) {
                    // Skip to end of function
                    $position = $token['scope_closer'];
                    continue;
                }

                if (isset($token['parenthesis_closer'])) {
                    // Skip to end of function
                    $position = $token['parenthesis_closer'];
                    continue;
                }

                continue;
            }

            if (strcasecmp($token['type'], 'T_VARIABLE') === 0) {
                // Variable outside of a function but in a class must be a class member
                $members[$position] = $token;
            }
        }

        return $members;
    }

    /**
     *
     * @param File $phpcsFile
     * @param array<string,mixed> $classToken
     * @return void
     */
    private function processClass(File $phpcsFile, array $classToken): void {

        $startPos = $classToken['scope_opener'];
        $endPos = $classToken['scope_closer'];

        // Find all members and positions
        $members = $this->findMembers($phpcsFile, $startPos, $endPos);
        // Check type is initiliased in constructor
        //  Find constructor
        $constructor = $this->findConstructor($phpcsFile, $startPos, $endPos);

        //  Fetch variable assignments in constructor
        if ($constructor === null) {
            // No constructor, so no assignments
            $initialisedMembers = [];
        } else {
            $initialisedMembers = $this->getInitilisedMembers(
                    $phpcsFile,
                    intval($constructor['scope_opener'] + 1),
                    intval($constructor['scope_closer'] - 1)
            );
        }

        //  Cross check assignments with non-nullable members
        foreach ($members as $pos => $member) {
            if (!isset($member['content']) || !is_string($member['content'])) {
                throw new \Exception("Bad member token");
            }

            $memberName = substr($member['content'], 1);
            if ($memberName === false) {
                throw new \Exception("Failed to cleanup member name '{$member['content']}'");
            }

            if ($this->isMemberStatic($phpcsFile, $pos)) {
                // Ignore static for now
                continue;
            }

            if (!in_array($memberName, $initialisedMembers)) {
                $this->handleUninitialisedMember($phpcsFile, $memberName, $pos);
            }
        }
    }

    /**
     *
     * @param File $phpcsFile
     * @param int $startPos
     * @param int $endPos
     * @return array<string>
     */
    private function getInitilisedMembers(File $phpcsFile, int $startPos, int $endPos): array {
        $tokens = $phpcsFile->getTokens();
        $arr = [];
        for ($position = $startPos; $position < $endPos; $position++) {
            $token = $tokens[$position];
            $tokenType = $token['type'];
            if ($tokenType !== 'T_VARIABLE') {
                continue;
            }

            if ($token['content'] !== '$this') {
                continue;
            }

            // Found
            $memberNamePos = Util::skipForward($tokens, $position + 1, ['T_WHITESPACE', 'T_OBJECT_OPERATOR']);
            if ($memberNamePos === null) {
                continue;
            }

            $memberNameToken = $tokens[$memberNamePos];
            if ($memberNameToken['type'] !== 'T_STRING') {
                // Not an assignment
                continue;
            }

            // Continue to check assignment actually occurs (i.e. not $this->thing;).
            $assignmentOperatorPos = Util::skipForward($tokens, intval($memberNamePos + 1), ['T_WHITESPACE']);
            if ($assignmentOperatorPos === null) {
                continue;
            }

            $assignmentOperatorToken = $tokens[$assignmentOperatorPos];
            if ($assignmentOperatorToken['type'] !== 'T_EQUAL') {
                continue;
            }

            $arr[] = $memberNameToken['content'];
        }

        return $arr;
    }

    private function isMemberStatic(File $phpcsFile, int $memberPos): bool {
        $tokens = $phpcsFile->getTokens();

        $memberProperty = Util::skipBack(
                        $tokens,
                        intval($memberPos - 1),
                        ['T_WHITESPACE', 'T_PRIVATE', 'T_PROTECTED', 'T_PUBLIC']
        );
        if ($memberProperty === null) {
            return false;
        }

        if ($tokens[$memberProperty]['type'] === 'T_STATIC') {
            return true;
        }

        return false;
    }

    private function getMemberTypeHint(File $phpcsFile, int $memberPos): ?string {
        $tokens = $phpcsFile->getTokens();

        $commentEnd = Util::skipBack($tokens, $memberPos - 1, ['T_WHITESPACE', 'T_PRIVATE', 'T_STATIC']);
        if ($commentEnd !== null && $tokens[$commentEnd]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            $commentStart = $tokens[$commentEnd]['comment_opener'];

            return $this->getTypeHintFromDoc($phpcsFile, $commentStart, $commentEnd);
        } else {
            // HANDLE MISSING TYPE HINT
            $this->handleMissingTypeComment($phpcsFile, $memberPos);
            return null;
        }
    }

    private function getTypeHintFromDoc(File $phpcsFile, int $startPos, int $endPos): ?string {
        $tokens = $phpcsFile->getTokens();

        for ($position = $startPos; $position < $endPos; $position++) {
            $token = $tokens[$position];
            if ($token['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }

            if ($token['content'] !== '@var') {
                continue;
            }

            // Found it.
            $typePos = Util::skipWhitespace($tokens, $position + 1);
            if ($tokens[$typePos]['type'] !== 'T_DOC_COMMENT_STRING') {
                $this->handleMissingTypeComment($phpcsFile, $startPos);
                return null;
            }

            return $tokens[$typePos]['content'];
        }

        return null;
    }

    /**
     *
     * @param File $phpcsFile
     * @param int $startPos
     * @param int $endPos
     *
     * @return array<string,mixed>|null
     */
    private function findConstructor(File $phpcsFile, int $startPos, int $endPos): ?array {
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
                return $token;
            }
        }

        return null;
    }

    private function handleMissingTypeComment(File $phpcsFile, int $memberPos): void {
        $phpcsFile->addError('Missing member doc', $memberPos, 'Missing');
        $phpcsFile->recordMetric($memberPos, 'Member has doc comment', 'no');
    }

    private function handleUninitialisedMember(File $phpcsFile, string $name, int $memberPos): void {

        $phpcsFile->addError("Class member '$name' not initialised in constructor", $memberPos, 'Missing');
        $phpcsFile->recordMetric($memberPos, 'Member initialised', 'no');
    }

}
