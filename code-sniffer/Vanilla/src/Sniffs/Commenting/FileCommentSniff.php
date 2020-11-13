<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

namespace Vanilla\Sniffs\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Parses and verifies the doc comments for files.
 *
 * Verifies that :
 * <ul>
 *  <li>A file doc comment exists.</li>
 *  <li>Check that license and copyright tags are presents.</li>
 * </ul>
 */
class FileCommentSniff implements Sniff {

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    /**
     * The current PHP_CodeSniffer_File object we are processing.
     *
     * @var File
     */
    protected $currentFile = null;

    /**
     * Tags in correct order and related info.
     *
     * @var array
     */
    protected $tags = [
        '@copyright' => [
            'required' => true,
            'allow_multiple' => true,
        ],
        '@license' => [
            'required' => false,
            'allow_multiple' => false,
        ],
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return [T_OPEN_TAG];

    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        // Find the next non whitespace token.
        $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        $errorToken = ($stackPtr + 1);
        if (isset($tokens[$errorToken]) === false) {
            $errorToken--;
        }

        if ($tokens[$commentStart]['code'] === T_CLOSE_TAG) {
            // We are only interested if this is the first open tag.
            return;
        } elseif ($tokens[$commentStart]['code'] === T_COMMENT) {
            $error = 'You must use "/**" style comments for a file comment';
            $phpcsFile->addError($error, $errorToken, 'WrongStyle');

            return;
        } elseif ($commentStart === false || $tokens[$commentStart]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
            $phpcsFile->addError('Missing file doc comment', $errorToken, 'Missing');

            return;
        }

        $commentEnd = $tokens[$commentStart]['comment_closer'];

        // No blank line between the open tag and the file comment.
        if ($tokens[$commentStart]['line'] - 1 !== $tokens[$stackPtr]['line']) {
            $error = 'File comment must be right below the open tag.';
            $phpcsFile->addError($error, $errorToken, 'WrongStyle');
        }

        // Exactly one blank line after the file comment.
        $nextNonWhitespace = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), null, true);
        if ($tokens[$nextNonWhitespace]['line'] - 2 !== $tokens[$commentEnd]['line']) {
            $error = 'There must be exactly one blank line after the file comment';
            $phpcsFile->addError($error, ($commentEnd + 1), 'SpacingAfterComment');
        }
    }
}
