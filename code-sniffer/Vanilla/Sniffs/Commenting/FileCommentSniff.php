<?php
/**
 * Parses and verifies the doc comments for files.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

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
class Vanilla_Sniffs_Commenting_FileCommentSniff implements Sniff {

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
        'PHP',
        'JS',
    );

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
    protected $tags = array(
        '@copyright'  => array(
            'required'       => true,
            'allow_multiple' => true,
        ),
        '@license'    => array(
            'required'       => false,
            'allow_multiple' => false,
        ),
    );

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return array(T_OPEN_TAG);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $empty = array(
            T_DOC_COMMENT_WHITESPACE,
            T_DOC_COMMENT_STAR,
        );

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
        if ($tokens[$commentStart]['line'] -1 !== $tokens[$stackPtr]['line']) {
            $error = 'File comment must be right below the open tag.';
            $phpcsFile->addError($error, $errorToken, 'WrongStyle');
        }

        // Exactly one blank line after the file comment.
        $nextNonWhitespace = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), null, true);
        if ($tokens[$nextNonWhitespace]['line'] - 2 !== $tokens[$commentEnd]['line']) {
            $error = 'There must be exactly one blank line after the file comment';
            $phpcsFile->addError($error, ($commentEnd + 1), 'SpacingAfterComment');
        }

        // Check that a description exists
        $description = $phpcsFile->findNext($empty, ($commentStart + 1), $commentEnd, true);
        if ($description === false) {
            $error = 'Missing description in file doc comment';
            $phpcsFile->addError($error, $commentStart, 'MissingShort');
            return;
        }

        // Check each tag.
        $this->processTags($phpcsFile, $stackPtr, $commentStart);

        // Ignore the rest of the file.
        return;

    }//end process()


    /**
     * Processes each required or optional tag.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     * @param int $commentStart Position in the stack where the comment started.
     *
     * @return void
     */
    protected function processTags(File $phpcsFile, $stackPtr, $commentStart) {
        $tokens = $phpcsFile->getTokens();

        if (get_class($this) === 'PEAR_Sniffs_Commenting_FileCommentSniff') {
            $docBlock = 'file';
        } else {
            $docBlock = 'class';
        }

        $commentEnd = $tokens[$commentStart]['comment_closer'];

        $foundTags = array();
        $tagTokens = array();
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            $name = $tokens[$tag]['content'];
            if (isset($this->tags[$name]) === false) {
                continue;
            }

            if ($this->tags[$name]['allow_multiple'] === false && isset($tagTokens[$name]) === true) {
                $error = 'Only one %s tag is allowed in a %s comment';
                $data  = array(
                    $name,
                    $docBlock,
                );
                $phpcsFile->addError($error, $tag, 'Duplicate'.ucfirst(substr($name, 1)).'Tag', $data);
            }

            $foundTags[]        = $name;
            $tagTokens[$name][] = $tag;

            $string = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tag, $commentEnd);
            if ($string === false || $tokens[$string]['line'] !== $tokens[$tag]['line']) {
                $error = 'Content missing for %s tag in %s comment';
                $data  = array(
                    $name,
                    $docBlock,
                );
                $phpcsFile->addError($error, $tag, 'Empty'.ucfirst(substr($name, 1)).'Tag', $data);
                continue;
            }
        }//end foreach

        // Check if the tags are in the correct position.
        $pos = 0;
        foreach ($this->tags as $tag => $tagData) {
            if (isset($tagTokens[$tag]) === false) {
                if ($tagData['required'] === true) {
                    $error = 'Missing %s tag in %s comment';
                    $data  = array(
                        $tag,
                        $docBlock,
                    );
                    $phpcsFile->addError($error, $commentEnd, 'Missing'.ucfirst(substr($tag, 1)).'Tag', $data);
                }

                continue;
            } else {
                $method = 'process'.substr($tag, 1);
                if (method_exists($this, $method) === true) {
                    // Process each tag if a method is defined.
                    call_user_func(array($this, $method), $phpcsFile, $tagTokens[$tag]);
                }
            }

            if (isset($foundTags[$pos]) === false) {
                break;
            }

            if ($foundTags[$pos] !== $tag) {
                $error = 'The tag in position %s should be the %s tag';
                $data  = array(
                    ($pos + 1),
                    $tag,
                );
                $phpcsFile->addError($error, $tokens[$commentStart]['comment_tags'][$pos], ucfirst(substr($tag, 1)).'TagOrder', $data);
            }

            // Account for multiple tags.
            $pos++;
            while (isset($foundTags[$pos]) === true && $foundTags[$pos] === $tag) {
                $pos++;
            }
        }//end foreach

    }//end processTags()

    /**
     * Process the copyright tags.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array $tags The tokens for these tags.
     *
     * @return void
     */
    protected function processCopyright(File $phpcsFile, array $tags) {
        $vanillaFound = false;
        $tokens = $phpcsFile->getTokens();
        foreach ($tags as $tag) {
            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                // No content.
                continue;
            }

            $content = $tokens[($tag + 2)]['content'];
            $matches = array();

            if (empty($content) === true) {
                $error = 'Content missing for @copyright tag in file comment';
                $phpcsFile->addError($error, $tag, 'MissingCopyright');
            }

            date_default_timezone_set('UTC');
            preg_match('/^2009\-(\d{4}) Vanilla Forums Inc./', $content, $matches);
            if (!empty($matches) && $matches[1] == date('Y', time())) {
                $vanillaFound = true;
            }
        }//end foreach

        if (!$vanillaFound) {
            $error = 'Expected "2009-' . date('Y') . ' Vanilla Forums Inc." for copyright declaration';
            $phpcsFile->addError($error, $tag, 'IncorrectCopyright');
        }

    }//end processCopyright()

    /**
     * Process the license tag.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array $tags The tokens for these tags.
     *
     * @return void
     */
    protected function processLicense(File $phpcsFile, array $tags) {
        $tokens = $phpcsFile->getTokens();
        foreach ($tags as $tag) {
            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                // No content.
                continue;
            }

            $content = $tokens[($tag + 2)]['content'];
            $matches = array();
            preg_match('/^([^\s]+)\s+(.*)/', $content, $matches);
            if (count($matches) !== 3) {
                $error = '@license tag must contain a URL and a license name';
                $phpcsFile->addError($error, $tag, 'IncompleteLicense');
            }
        }
    }
}
