<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

namespace Vanilla\Sniffs\NamingConventions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Ensures curly brackets are on the same line as the Class declaration
 *
 */
class ValidClassBracketsSniff implements Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return [T_CLASS];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $found = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPtr);
        if ($tokens[$found - 1]['code'] != T_WHITESPACE) {
            $error = 'Expected 1 space after class declaration, found 0';
            $phpcsFile->addError($error, $found - 1, 'InvalidSpacing', []);

            return;
        } elseif ($tokens[$found - 1]['content'] != " ") {
            $error = 'Expected 1 space before curly opening bracket';
            $phpcsFile->addError($error, $found - 1, 'InvalidBracketPlacement', []);
        }

        if (strlen($tokens[$found - 1]['content']) > 1 || $tokens[$found - 2]['code'] == T_WHITESPACE) {
            $error = 'Expected 1 space after class declaration, found ' . strlen($tokens[$found - 1]['content']);
            $phpcsFile->addError($error, $found - 1, 'InvalidSpacing', []);
        }
    }

}

