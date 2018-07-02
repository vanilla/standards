<?php
/**
 * Verifies method calls formatting.

 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Class Vanilla_Sniffs_Methods_MethodCallSniff
 */
class Vanilla_Sniffs_Methods_MethodCallFormattingSniff implements Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return [T_VARIABLE];
    }


    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     * @return null
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $pointer = $stackPtr + 1;
        $this->moveToNextNonWhitespace($tokens, $pointer);
        $objectCalls = $this->getObjectFunctionCalls($tokens, $pointer);

        // Check that the semicolon is right after the method call if they are no chaining
        if ($objectCalls['calls']
            && !$objectCalls['hasChaining']
            && $tokens[$pointer]['code'] === T_SEMICOLON
            && $tokens[$pointer-1]['code'] === T_WHITESPACE) {

            $error = 'Semicolon must be at the end of the function when not chaining calls.';
            $phpcsFile->addError($error, $pointer, 'SemicolonWhiteSpace');
        }
    }

    /**
     * Get the function calls (can be multiple if chained) on an object.
     *
     * @param array $tokens
     * @param $pointer
     * @return array
     */
    private function getObjectFunctionCalls(array $tokens, &$pointer) {
        $chainedCalls = [
            'hasChaining' => false,
            'calls' => [],
        ];
        while ($tokens[$pointer]['code'] === T_OBJECT_OPERATOR) {
            $tmpToken = $tokens[++$pointer];
            $pointer++; // Next token;
            $this->moveToNextNonWhitespace($tokens, $pointer);
            $tmpTokenType = $this->getTokenType($tokens[$pointer]);

            $chainedCalls['calls'] = [
                'token' => $tmpToken,
                'type' => $tmpTokenType,
            ];

            $lineFeedDetected = $this->movePointerToNextObject($tokens, $pointer);
            if ($tokens[$pointer]['code'] === T_SEMICOLON) {
                break;
            }

            if ($chainedCalls['hasChaining'] === false && $lineFeedDetected) {
                $chainedCalls['hasChaining'] = true;
            }
        }
        return $chainedCalls;
    }

    /**
     * Return the current token type.
     *
     * @param array $token
     *
     * @return string
     */
    private function getTokenType($token) {
        if ($token['code'] === T_OPEN_PARENTHESIS) {
            return 'method';
        }
        return 'property';
    }

    /**
     * Move the pointer to the next non whitespace token.
     *
     * @param array $tokens
     * @param int $pointer
     * @return bool true if a line feed was detected, false otherwise.
     */
    private function moveToNextNonWhitespace(array $tokens, &$pointer) {
        $hasLineFeed = false;
        while ($tokens[$pointer]['code'] === T_WHITESPACE) {
            if (!$hasLineFeed) {
                $hasLineFeed = strpos($tokens[$pointer]['content'], "\n") !== false;
            }
            ++$pointer;
        }
        return $hasLineFeed;
    }

    /**
     * Move the pointer to the next object.
     *
     * @param array $tokens
     * @param int $pointer
     *
     * @return bool true if a line feed was detected, false otherwise.
     */
    private function movePointerToNextObject(array $tokens, &$pointer) {
        $token = $tokens[$pointer];
        // Ignore "(" ... ")" in a method call by moving pointer after close parenthesis token
        if ($token['code'] === T_OPEN_PARENTHESIS) {
            $pointer = $token['parenthesis_closer'] + 1;
        }
        return $this->moveToNextNonWhitespace($tokens, $pointer);
    }

}
?>
