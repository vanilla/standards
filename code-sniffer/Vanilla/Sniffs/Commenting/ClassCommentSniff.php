<?php
/**
 * Parses and verifies the doc comments for classes.
 */

/**
 */
class Vanilla_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return array(
            T_CLASS,
            T_INTERFACE
        );
    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $this->currentFile = $phpcsFile;

        $tokens    = $phpcsFile->getTokens();
        $type      = strtolower($tokens[$stackPtr]['content']);
        $errorData = array($type);

        $find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $empty = array(
            T_DOC_COMMENT_WHITESPACE,
            T_DOC_COMMENT_STAR,
        );

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $phpcsFile->addError('Missing class doc comment', $stackPtr, 'Missing');
            return;
        }

        // Try and determine if this is a file comment instead of a class comment.
        // We assume that if this is the first comment after the open PHP tag, then
        // it is most likely a file comment instead of a class comment.
        if ($tokens[$commentEnd]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            $start = ($tokens[$commentEnd]['comment_opener'] - 1);
        } else {
            $start = $phpcsFile->findPrevious(T_COMMENT, ($commentEnd - 1), null, true);
        }

        $prev = $phpcsFile->findPrevious(T_WHITESPACE, $start, null, true);
        if ($tokens[$prev]['code'] === T_OPEN_TAG) {
            $prevOpen = $phpcsFile->findPrevious(T_OPEN_TAG, ($prev - 1));
            if ($prevOpen === false) {
                // This is a comment directly after the first open tag,
                // so probably a file comment.
                $phpcsFile->addError('Missing class doc comment', $stackPtr, 'Missing');
                return;
            }
        }

        // The first line of the comment should just be the /** code.
        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $phpcsFile->addError('You must use "/**" style comments for a class comment', $stackPtr, 'WrongStyle');
            return;
        }
        $commentStart = $tokens[$commentEnd]['comment_opener'];

        // The last line of the comment should just be the */ code.
        $prev = $phpcsFile->findPrevious($empty, ($commentEnd - 1), $commentStart, true);
        if ($tokens[$prev]['line'] === $tokens[$commentEnd]['line']) {
            $phpcsFile->addError('The close comment tag must be the only content on the line', $commentStart, 'WrongStyle');
            return;
        }

        $short = $phpcsFile->findNext($empty, ($commentStart + 1), $commentEnd, true);

        // Check that a short description exists
        if ($short === false) {
            $error = 'Missing short description in class doc comment';
            $phpcsFile->addError($error, $commentStart, 'MissingShort');
            return;
        }

        // No extra newline before short description.
        if ($tokens[$short]['line'] !== $tokens[$commentStart]['line'] + 1) {
            $error = 'Doc comment short description must be on the first line';
            $phpcsFile->addError($error, ($commentStart + 1), 'SpacingBeforeShort');
        }

        // Short desc must be single line.
        $shortEnd = $short;
        $isShortSingleLine = true;
        for ($i = ($short + 1); $i < $commentEnd; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                if ($tokens[$i]['line'] === ($tokens[$shortEnd]['line'] + 1)) {
                    $error = 'Class comment short description must be on a single line';
                    $phpcsFile->addError($error, ($commentStart + 1), 'ShortSingleLine');
                    $isShortSingleLine = false;
                }
                break;
            }
        }

        $shortContent = $tokens[$short]['content'];
        // Short desc start with capital letter
        if (preg_match('#^(\p{Lu}|\P{L})#u', $shortContent) === 0) {
            $error = 'Doc comment short description must start with a capital letter';
            $phpcsFile->addError($error, $short, 'ShortNotCapital');
        }

        // Short desc must end with a full stop
        if ($isShortSingleLine && substr($shortContent, -1) !== '.') {
            $error = 'Description must end with a full stop';
            $phpcsFile->addError($error, $commentStart, 'MissingShort');
        }

        // Detect long description
        $long = $phpcsFile->findNext($empty, ($shortEnd + 1), ($commentEnd - 1), true);
        if ($long !== false) {
            if ($tokens[$long]['code'] === T_DOC_COMMENT_STRING) {

                // There must be a blank line before long desc and short desc
                if ($tokens[$long]['line'] !== ($tokens[$shortEnd]['line'] + 2)) {
                    $error = 'There must be exactly one blank line between descriptions in a doc comment';
                }

                // Long desc must start with a capital letter
                if (preg_match('/^(\p{Lu}|\P{L})/u', $tokens[$long]['content'][0]) === 0) {
                    $error = 'Doc comment long description must start with a capital letter';
                    $phpcsFile->addError($error, $long, 'LongNotCapital');
                }
            }//end if
        }

        // No tags are allowed in the class comment.
        if (empty($tokens[$commentStart]['comment_tags']) === false) {
            $firstTag = $tokens[$tokens[$commentStart]['comment_tags'][0]]['content'];
            $error = '%s tag is not allowed in class comment';
            $phpcsFile->addWarning($error, $tokens[$commentStart]['comment_tags'][0], 'TagNotAllowed', $firstTag);
        }

        // There should be no blank line before the comment ending
        $lastCommentString = $phpcsFile->findPrevious($empty, $commentEnd - 1, $commentStart, true);
        if ($tokens[$commentEnd]['line'] - 1 !== $tokens[$lastCommentString]['line']) {
            $error = 'Additional blank lines found at end of class comment';
            $this->currentFile->addError($error, $commentEnd, 'SpacingAfter');
        }


    }//end process()

}
