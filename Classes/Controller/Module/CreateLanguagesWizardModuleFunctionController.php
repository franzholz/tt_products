<?php

namespace JambageCom\TtProducts\Controller\Module;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Creates the "Import tables" wizard.
 */
class CreateLanguagesWizardModuleFunctionController
{
    /**
     * Contains a reference to the parent (calling) object (which is probably an instance of
     * an extension class to \TYPO3\CMS\Backend\Module\BaseScriptClass.
     *
     * @var BaseScriptClass
     *
     * @see init()
     */
    public $pObj;

    /**
     * @var BaseScriptClass
     */
    public $extObj;

    /**
     * Can be hardcoded to the name of a locallang.xlf file (from the same directory as the class file) to use/load
     * and is included / added to $GLOBALS['LOCAL_LANG'].
     *
     * @see init()
     *
     * @var string
     */
    public $localLangFile = '';

    /**
     * Contains module configuration parts from TBE_MODULES_EXT if found.
     *
     * @see handleExternalFunctionValue()
     *
     * @var array
     */
    public $extClassConf;

    /**
     * If this value is set it points to a key in the TBE_MODULES_EXT array (not on the top level..) where another classname/filepath/title can be defined for sub-subfunctions.
     * This is a little hard to explain, so see it in action; it used in the extension 'func_wizards' in order to provide yet a layer of interfacing with the backend module.
     * The extension 'func_wizards' has this description: 'Adds the 'Wizards' item to the function menu in Web>Func. This is just a framework for wizard extensions.' - so as you can see it is designed to allow further connectivity - 'level 2'.
     *
     * @see handleExternalFunctionValue(), \TYPO3\CMS\FuncWizards\Controller\WebFunctionWizardsBaseController
     *
     * @var string
     */
    public $function_key = '';

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * Initialize the object.
     *
     * @param \object $pObj A reference to the parent (calling) object
     *
     * @throws \RuntimeException
     *
     * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
     */
    public function init($pObj)
    {
        $this->pObj = $pObj;
        // Local lang:
        if (!empty($this->localLangFile)) {
            $this->getLanguageService()->includeLLFile($this->localLangFile);
        }
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Main function creating the content for the module.
     *
     * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
     */
    public function main()
    {
        $assigns = [];
        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $languageSubpath = '/Resources/Private/Language/';

        $languageFile = 'EXT:' . TT_PRODUCTS_EXT . '/Resources/Private/Language/Modfunc/locallang_modfunc2.xlf';
        $this->getLanguageService()->includeLLFile($languageFile);
        $assigns['LLPrefix'] = 'LLL:' . $languageFile . ':';

        $languageFile = 'EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf';
        $this->getLanguageService()->includeLLFile($languageFile);

        $execute = GeneralUtility::_GP('execute');

        if ($execute) {
            $createLanguages = GeneralUtility::makeInstance(\JambageCom\TtProducts\Utility\CreateLanguagesUtility::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:' . TT_PRODUCTS_EXT . '/Resources/Private/Templates/CreateLanguagesFinished.html'
            ));
            $createResult = false;
            $errorLanguageCodeArray = [];
            $infoArray = [];

            if (
                isset($_REQUEST['id'])
            ) {
                $createResult =
                    $createLanguages->createAll(
                        $infoArray,
                        $errorLanguageCodeArray,
                        $_REQUEST['id']
                    );
            }

            if (
                $createResult
            ) {
                $rows = [];
                if (isset($infoArray['rows'])) {
                    $rows = $infoArray['rows'];
                }
                $information = '';

                $tableArray = ['tt_products_texts'];
                foreach ($tableArray as $table) {
                    if (
                        isset($rows[$table]) &&
                        $rows[$table] > 0
                    ) {
                        $tableOut = $GLOBALS['LANG']->getLL($table);
                        $information .= sprintf($GLOBALS['LANG']->getLL('created_table'), intval($rows[$table]), $tableOut);
                        $information .= '<br>';
                    }
                }
                if ($information == '') {
                    $information = $GLOBALS['LANG']->getLL('no_alternative_product');
                }
            } elseif (
                isset($errorLanguageCodeArray) && is_array($errorLanguageCodeArray)
            ) {
                foreach ($errorLanguageCodeArray as $table => $codeArray) {
                    $code = '';
                    $parameter = '';
                    if (isset($codeArray['code'])) {
                        $code = $codeArray['code'];
                    }
                    if (isset($codeArray['parameter'])) {
                        $parameter = $codeArray['parameter'];
                    }

                    switch ($code) {
                        case 'no_texts':
                            $information .= $GLOBALS['LANG']->getLL($code);
                            break;
                        case 'no_alternative_product':
                            $information .= sprintf($GLOBALS['LANG']->getLL($code), $parameter);
                            break;
                    }
                }
            }
            $assigns['information'] = $information;
        } else {
            // CSH
            $assigns['cshItem'] = BackendUtility::cshItem('_MOD_web_func', 'tt_products.func2');
            $assigns['pid_src'] = $_REQUEST['id'];

            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:' . TT_PRODUCTS_EXT . '/Resources/Private/Templates/CreateLanguagesWizard.html'
            ));
        }

        $view->assignMultiple($assigns);
        $out = $view->render();

        return $out;
    }

    /**
     * Same as \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj().
     *
     * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
     */
    public function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this->pObj, $this->extClassConf);
            // Re-write:
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
        }
    }

    /**
     * Calls the main function inside ANOTHER sub-submodule which might exist.
     */
    public function extObjContent()
    {
        if (is_object($this->extObj)) {
            return $this->extObj->main();
        }
    }

    /**
     * Dummy function - but is used to set up additional menu items for this submodule.
     *
     * @return array A MOD_MENU array which will be merged together with the one from the parent object
     *
     * @see init(), \TYPO3\CMS\Frontend\Controller\PageInformationController::modMenu()
     */
    public function modMenu()
    {
        return [];
    }

    /**
     * Returns LanguageService.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
