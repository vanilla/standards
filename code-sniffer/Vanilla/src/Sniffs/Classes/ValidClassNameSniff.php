<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

namespace Vanilla\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Common;

/**
 * Ensures classes are in camel caps, and the first letter is capitalised or begin with "Gdn_".
 */
class ValidClassNameSniff implements Sniff {


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return [
            T_CLASS,
            T_INTERFACE,
        ];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The current file being processed.
     * @param int $stackPtr The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            $error = 'Possible parse error: %s missing opening or closing brace';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $data);

            return;
        }

        // Determine the name of the class or interface. Note that we cannot
        // simply look for the first T_STRING because a class name
        // starting with the number will be multiple tokens.
        $opener = $tokens[$stackPtr]['scope_opener'];
        $nameStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), $opener, true);
        $nameEnd = $phpcsFile->findNext(T_WHITESPACE, $nameStart, $opener);
        $name = trim($phpcsFile->getTokensAsString($nameStart, ($nameEnd - $nameStart)));

        // Names that begin with "Gdn_" are okay.
        if (strpos($name, 'Gdn_') === 0) {
            $name = substr($name, 4);
        }

        // Check for camel caps format.
        $valid = Common::isCamelCaps($name, true, true, false);
        if ($valid === false) {
            $type = ucfirst($tokens[$stackPtr]['content']);
            $error = '%s name "%s" is not in camel caps format';
            $data = [
                $type,
                $name,
            ];
            $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $data);
        }

    }//end process()


}//end class
