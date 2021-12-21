# TYPO3 extension tt_products

## What is does

This extension brings to you a shop system for TYPO3.

## Configuration

See the file manual.odt under the folder doc of the extension.  
https://wiki.typo3.org/Tt_products  
ebook:  
http://www.fosdoc.de/downloads/OSP_typo3webshop.pdf   

## Upgrade

If you upgrade to a newer version of tt_products then you sometimes you must execute upgrade wizards in the TYPO3 Install Tool for tt_products. Do not forget to make a backup copy of the database before you execute them.

Only if you are still using TYPO3 < 9 you must execute the renaming of these database table fields manually in phpMyAdmin or a similar database tool. 

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


## Improvements

Contribute under https://github.com/franzholz/tt_products .

