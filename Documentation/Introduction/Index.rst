..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  tip::

    New to reStructuredText and Sphinx?

    Get an introduction:
    https://docs.typo3.org/m/typo3/docs-how-to-document/master/en-us/WritingReST/Index.html

    Use this cheat sheet as reference:
    https://docs.typo3.org/m/typo3/docs-how-to-document/master/en-us/WritingReST/CheatSheet.html

..  _what-it-does:

What does it do?
================

The TYPO3 shop extension gives you the facility for...

*   Product listings with multiple images, details and languages
*   Shopping basket
*   Payment page - The orders will be indicated and can be checked over before the products are finalized.
*   sponsors only: Payment gateways with Payment Library extension - Paypal and Transaction Central
*   Tracking customers order status
*   Automatic creation of bill and delivery sheet
*   Different tax percentages per item, shipping and payment
*   basic stock management
*   Send a CSV for each order to the shop admin (2 choosable file formats)
*   E-Mail-Attachments for the confirmation mails (for example AGB in German = General trading conditions)
*   choosable item variants (colours, sizes, gradings, descriptions, materials and qualities)
*   Force customer to accept the General trading conditions (AGB) per checkbox
*   Offers, highlights and newly added items
*   Special preparation, weight and bulkily (can be used to calculate the shipping fee)
*   Ability to limit payment methods to specific user groups
*   Automatic creation of frontend users at first order
*   Remember items in a memo, when a user is logged in
*   Discount percentage per user
*   Some methods for price calculation with rebate for resellers
*   Display orders: order can be displayed on per fe-user basis (CODE=ORDERS)
*   Creditpoint system: customers can save credit points per each order. Saved points will givethem a discount for newer orders or certain products can be "bought" with these points.
*   Voucher system: if a new customer indicates when registrating that she/he was tipped by another existing customer, this customers gets a credit point bonus. The new customer gets a discount on first order.


..  attention::

    Do not forget to set extension's version number in :file:`Settings.cfg` file,
    in the :code:`release` property.
    It will be automatically picked up on the cover page by the :code:`|release|` substitution.

..  _screenshots:

Screenshots
===========

This chapter should help people understand how the extension works.
Remove it if it is not relevant.

..  figure:: /Images/IntroductionPackage.png
    :class: with-shadow
    :alt: Introduction Package
    :width: 300px

    Introduction Package after installation (caption of the image).

How the Frontend of the Introduction Package looks like after installation (legend of the image).
