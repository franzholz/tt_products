..  include:: /Includes.rst.txt
..  highlight:: typoscript

..  index::
    TypoScript; Constants
..  _configuration-typoscript-constants:

Constants
=========

Enable / disable some options
-----------------------------

..  confval:: enableThis

    :type: bool
    :Default: 0

    If :typoscript:`1`, something is enabled...

    Example::

       plugin.tt_products {
          enableThis = 1
       }


TypoScript Constants Reference
------------------------------

You must use the `plugin.tt_products` TypoScript prefix.
Most constants have an identical term under setup. However no :ref:`t3tsref:stdwrap` is possible
for constants. They can contain only numbers and strings possibly separated by commas.
`(C)` is added if the Constant looks different than the Setup.
`(S)` means that only Setup is available.
