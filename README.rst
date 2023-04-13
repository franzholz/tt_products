TYPO3 extension tt_products
===========================

What is does
------------

This is a shop system extension for TYPO3 based on PHP 7 and PHP 8.

Configuration
-------------

See the file manual.odt under the folder doc of the extension.

-  `Wiki <https://github.com/franzholz/tt_products/wiki>`__
-  `Ebook <http://www.fosdoc.de/downloads/OSP_typo3webshop.pdf>`__

Upgrade
-------

If you upgrade to a newer version of tt_products then you sometimes you
must execute upgrade wizards in the TYPO3 Install Tool for tt_products.
Do not forget to make a backup copy of the database before you execute
them.

Only if you are still using TYPO3 < 9 you must execute the renaming of
these database table fields manually in phpMyAdmin or a similar database
tool.

::

   tt_products_mm_graduated_price:
   product_uid ==> uid_local
   graduated_price_uid ==> uid_foreign
   productsort ==> sorting
   graduatedsort ==> sorting_foreign

   * tt_products_products_mm_articles:
   articlesort ==>  sorting_foreign

   * sys_products_orders_mm_tt_products:
   sys_products_orders_uid ==> uid_local
   tt_products_uid ==> uid_foreign

Improvements
------------

Contribute under `Github
tt_products <https://github.com/franzholz/tt_products>`__ .
