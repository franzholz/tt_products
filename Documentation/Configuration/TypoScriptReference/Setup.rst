..  include:: /Includes.rst.txt
..  highlight:: typoscript
..  index::
    TypoScript; Setup
..  _configuration-typoscript-setup:

Setup
=====

Enable / disable some options
-----------------------------

..  confval:: enableThat

    :type: bool, stdWrap
    :Default: 0

    If :typoscript:`1` then something is enabled...


    Example::

       plugin.tt_products {
          enableThis.field = xyz
       }

View Properties
^^^^^^^^^^^^^^^

.. container:: ts-properties

	=========================== ========================================= ======================= ====================
	Property                    Data type                                 :ref:`t3tsref:stdwrap`  Default
	=========================== ========================================= ======================= ====================
	templateFile_,
	file.templateFile_ (C)      :ref:`t3tsref:data-type-resource`          no                     example_locallang_xml.html
	templateSuffix_ (S)         :ref:`t3tsref:data-type-string`            no
	fe_ (S)                     :ref:`t3tsref:data-type-boolean`           no                     1
	defaultCode_                :ref:`t3tsref:data-type-string`            no                     HELP
	code_                       :ref:`t3tsref:data-type-string`            yes                    HELP
	defaultArticleID_           :ref:`t3tsref:data-type-positive-integer`  no
	defaultProductID_           :ref:`t3tsref:data-type-positive-integer`  no
	defaultCategoryID_          :ref:`t3tsref:data-type-positive-integer`  no
	defaultPageID_              :ref:`t3tsref:data-type-positive-integer`  no
	defaultDAMCategoryID_       :ref:`t3tsref:data-type-positive-integer`  no
	productDAMCategoryID_       :ref:`t3tsref:data-type-positive-integer`  no
	rootAddressID_              :ref:`t3tsref:data-type-positive-integer`  no
	rootCategoryID_             :ref:`t3tsref:data-type-positive-integer`  no
	rootDAMCategoryID_          :ref:`t3tsref:data-type-positive-integer`  no
	rootPageID_                 :ref:`t3tsref:data-type-positive-integer`  no
	recursive_                  :ref:`t3tsref:data-type-positive-integer`  no                     99
	domain_                     :ref:`t3tsref:data-type-string`            no
	altMainMarkers_             array of :ref:`t3tsref:data-type-string`   no
	pid_list_                   :ref:`t3tsref:data-type-string`            yes





+++
	PIDforum_                   :ref:`t3tsref:data-type-positive-integer`  no
	PIDprivacyPolicy_           :ref:`t3tsref:data-type-positive-integer`  no
	iconCode.line_              :ref:`t3tsref:data-type-string`            yes
	iconCode.blank_             :ref:`t3tsref:data-type-string`            yes
	iconCode.thread_            :ref:`t3tsref:data-type-string`            yes
	iconCode.end_               :ref:`t3tsref:data-type-string`            yes
	emoticons_                  :ref:`t3tsref:data-type-boolean`           no                      1
	allowCaching_               :ref:`t3tsref:data-type-boolean`           no
	displayCurrentRecord_       :ref:`t3tsref:data-type-boolean`           no
	wrap1_                      :ref:`t3tsref:stdwrap`                     yes
	wrap2_                      :ref:`t3tsref:stdwrap`                     yes
	color1_                     :ref:`t3tsref:data-type-string`            yes
	color2_                     :ref:`t3tsref:data-type-string`            yes
	color3_                     :ref:`t3tsref:data-type-string`            yes
	=========================== ========================================== ======================= ====================



View Property Details
^^^^^^^^^^^^^^^^^^^^^

.. only:: html

	.. contents::
		:local:
		:depth: 1


.. _ts-plugin-tt-products-templateFile:
.. _ts-plugin-tt-products-file.templateFile:

templateFile (C: file.templateFile)
"""""""""""""""""""""""""""""""""""

:typoscript:`plugin.tt_proucts.templateFile =` :ref:`t3tsref:data-type-resource`

The template-file. | See default file :file:`Resources/Private/Templates/example_locallang_xml.html`.
You can also specify a CODE and ERROR for error cases.
(see display mode)


.. _ts-plugin-tt-products-templateFileSuffix:

templateFileSuffix
""""""""""""""""""

:typoscript:`plugin.tt_proucts.templateSuffix =` :ref:`t3tsref:data-type-string`

This suffix is appended to all template's major subparts.

.. _ts-plugin-tt-products-fe:

fe
""

:typoscript:`plugin.tt_proucts.fe =` :ref:`t3tsref:data-type-boolean`

If FE output is used. You can turn the FE output off.
This is usefull for a callback script (see CODE SCRIPT). No template file is needed in this case.


.. _ts-plugin-tt-products-pidList:

pid_list
""""""""

:typoscript:`plugin.tt_proucts.pid_list =` :ref:`t3tsref:data-type-string`

The pids from where to fetch categories, products and so on. Default is the current page. Accepts multiple pids separated by comma.


.. _ts-plugin-tt-products-defaultCode:

defaultCode
"""""""""""

:typoscript:`plugin.tt_produts.defaultCode =` :ref:`t3tsref:data-type-string`

The default code (see below) if the value is empty. By default it's not set and a help screen will appear. You should not set anything here.


:aspect:`Example:`

..  code-block:: typoscript

   plugin.tt_products {
     defaultCode = HELP
   }


.. _ts-plugin-tt-products-code:

code
""""

:typoscript:`plugin.tt_products.code =` :ref:`t3tsref:data-type-string`

see chapter 'display mode'


.. _ts-plugin-tt-products-defaultArticleID:

defaultArticleID
""""""""""""""""

:typoscript:`plugin.tt_products.defaultArticleID =` :ref:`t3tsref:data-type-positive-integer`

The default article uid number for the single display is used when the link to the script did not contain a 'tt_products[article]' parameter.


.. _ts-plugin-tt-products-defaultProductID:

defaultProductID
""""""""""""""""

:typoscript:`plugin.tt_products.defaultProductID =` :ref:`t3tsref:data-type-positive-integer`

The default product uid number for the single display is used when the link to the script did not contain a :html:`tt_products[product]` parameter.
Set this default value when you get an error message like:
:emphasis:`GET/POST var 'tt_products[product]' was missing.`


.. _ts-plugin-tt-products-defaultCategoryID:

defaultCategoryID
"""""""""""""""""

:typoscript:`plugin.tt_products.defaultCategoryID =` :ref:`t3tsref:data-type-positive-integer`

The default category uid number for the list display is used when the link to the script did not contain
a :html:`tt_products[cat]` parameter. Use this if you want only products of this category displayed in the list view as a default.



.. _ts-plugin-tt-products-defaultPageID:

defaultPageID
"""""""""""""

:typoscript:`plugin.tt_products.defaultPageID =` :ref:`t3tsref:data-type-positive-integer`

The default category uid number for the list display is used when the link to the script did not contain a :html:`tt_products[pid]`
parameter. Use this if you use pages as categories and want only products of this category displayed in the list view as a default.


.. _ts-plugin-tt-products-defaultDAMCategoryID:

defaultDAMCategoryID
""""""""""""""""""""

:typoscript:`plugin.tt_products.defaultDAMCategoryID =` :ref:`t3tsref:data-type-positive-integer`

See defaultCategoryID, but for DAM categories and the :html:`tt_products[damcat]` parameter.


.. _ts-plugin-tt-products-productDAMCategoryID:

productDAMCategoryID
""""""""""""""""""""

:typoscript:`plugin.tt_products.productDAMCategoryID =` :ref:`t3tsref:data-type-positive-integer`

DAM category of products to be used in DAM lists.


.. _ts-plugin-tt-products-rootAddressID:

rootAddressID
"""""""""""""

:typoscript:`plugin.tt_products.rootAddressID =` :ref:`t3tsref:data-type-positive-integer`

The upper most address ID from where you want to start to list addresses.



.. _ts-plugin-tt-products-rootCategoryID:

rootCategoryID
""""""""""""""

:typoscript:`plugin.tt_products.rootCategoryID =` :ref:`t3tsref:data-type-positive-integer`

The upper most category ID from where you want to start to list categories.



.. _ts-plugin-tt-products-rootDAMCategoryID:

rootDAMCategoryID
"""""""""""""""""

:typoscript:`plugin.tt_products.rootDAMCategoryID =` :ref:`t3tsref:data-type-positive-integer`

The upper most DAM category ID from where you want to start to list DAM categories.



.. _ts-plugin-tt-products-rootPageID:

rootPageID
""""""""""

:typoscript:`plugin.tt_products.rootPageID =` :ref:`t3tsref:data-type-positive-integer`

The upper most page ID from where you want to start to list them as categories.


.. _ts-plugin-tt-products-recursive:

recursive
"""""""""

:typoscript:`plugin.tt_products.recursive =` :ref:`t3tsref:data-type-positive-integer`

Number of recursive sublevels of pids to select tt_products from in lists.


.. _ts-plugin-tt-products-domain:

domain
""""""

:typoscript:`plugin.tt_products.domain =` :ref:`t3tsref:data-type-string`

The url of the shop. If not set, it will be detected automatically. Will replace the :html:`###DOMAIN###` marker.



.. _ts-plugin-tt-products-altMainMarkers:

altMainMarkers
""""""""""""""

:typoscript:`plugin.tt_products.altMainMarkers {`
    array of :ref:`t3tsref:data-type-string`
:typoscript:`}`

Lets you specify alternative subpart markers for the various main template designs in the shopping basket system.
This is the list of main subparts you can override:

Properties:
'''''''''''

*   TRACKING_WRONG_NUMBER
*   TRACKING_ENTER_NUMBER
*   BASKET_REQUIRED_INFO_MISSING
*   BASKET_TEMP
*   ITEM_SINGLE_DISPLAY_RECORDINSERT
*   ITEM_SINGLE_DISPLAY
*   ITEM_SEARCH
*   ITEM_LIST_TEMPLATE
*   ITEM_SEARCH_EMPTY
*   BASKET_TEMPLATE
*   BASKET_INFO_TEMPLATE
*   BASKET_PAYMENT_TEMPLATE
*   BASKET_ORDERCONFIRMATION_TEMPLATE
*   EMAIL_PLAINTEXT_TEMPLATE
*   BILL_TEMPLATE
*   DELIVERY_TEMPLATE

/+ stdWrap

Example:
''''''''

..  code-block:: typoscript

    altMainMarkers.BASKET_TEMPLATE =  BASKET_DESIGN2
    altMainMarkers.BASKET_TEMPLATE.wrap = ### | ###

This example changes the main subpart marker for the regular basket display from the default :typoscript:`###BASKET_TEMPLATE###`
to the custom supplied design :typoscript:`###BASKET_DESIGN2###` (found in the same template HTML-file)



	.. _ts-plugin-tt-products-pidList:

pid_list
""""""""

:typoscript:`plugin.tt_products.pid_list =` :ref:`t3tsref:data-type-string`

The pids from where to fetch products, categories and so on. Default is the current page. Accepts multiple pids commaseparated!


	.. _ts-plugin-tt-products-pidList:

pid_list
""""""""

:typoscript:`plugin.tt_products.pid_list =` :ref:`t3tsref:data-type-string`

The pids from where to fetch products, categories and so on. Default is the current page. Accepts multiple pids commaseparated!
