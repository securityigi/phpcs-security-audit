<?php


class Security_Sniffs_Drupal7_SQLiSniff implements PHP_CodeSniffer_Sniff {

	/**
	* Returns the token types that this sniff is interested in.
	*
	* @return array(int)
	*/
	public function register() {
		return array(T_STRING);
	}

	/**
	* Paranoya mode. Will generate more alerts but will miss less vulnerabilites.
	*
	* @var bool
	*/
	public $ParanoiaMode = 1;

	/**
	* Processes the tokens that this sniff is interested in.
	*
	* @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
	* @param int                  $stackPtr  The position in the stack where
	*                                        the token was found.
	*
	* @return void
	*/
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$utils = new Security_Sniffs_Drupal7_Utils();

		$tokens = $phpcsFile->getTokens();
		if ($tokens[$stackPtr]['content'] == 'db_query') {
			$closer = $tokens[$stackPtr + 1]['parenthesis_closer'];
			$s = $stackPtr + 1; // to skip the parenthesis opener '('
			$warn = FALSE;
			while ($s < $closer) {
				$s = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $s + 1, $closer, true);
				if (!$s
				// Paranoia : Sometimes the COMMA is not used for db_query
				|| ($tokens[$s]['code'] == T_COMMA && !$this->ParanoiaMode)) {
					break;
				}
				if ($tokens[$s]['code'] != T_CONSTANT_ENCAPSED_STRING) {
					$warn = TRUE;
				}
				if ($tokens[$s]['code'] == T_DOUBLE_QUOTED_STRING) {
					$phpcsFile->addError('Direct variable usage in db_query()', $s, 'D7DbQueryDirectVar');
				} elseif ($utils::is_token_user_input($tokens[$s])) {
					$phpcsFile->addError('Potential SQL injection found in db_query()', $s, 'D7DbQuerySQLi');
				}
			}
			if ($warn) {
				$phpcsFile->addWarning('db_query() is deprecated except when doing a static query', $stackPtr, 'D7NoDbQuery');
			}
		}
	}

}


?>