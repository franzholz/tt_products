<?php
if (!defined ("TYPO3_MODE"))    die ("Access denied.");

if (!defined ('PATH_BE_ttproducts')) {
	define('PATH_ttproducts', t3lib_extMgm::extPath('tt_products'));
}

if (!defined ('PATH_BE_ttproducts_rel')) {
	define('PATH_ttproducts_rel', t3lib_extMgm::extRelPath('tt_products'));
}

if (!defined ('PATH_FE_ttproducts_rel')) {
	define('PATH_ttproducts_rel', t3lib_extMgm::siteRelPath('tt_products'));
}

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products=1
');

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products_cat=1
');

$TYPO3_CONF_VARS['EXTCONF']['tt_products']['pageAsCategory'] = 0; //for page as categories = 1

?>
