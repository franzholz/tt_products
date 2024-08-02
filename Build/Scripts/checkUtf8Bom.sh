#!/usr/bin/env bash

#########################
#
# Check all UTF-8 files do not contain BOM.
#
# It expects to be run from the core root.
#
##########################

FILES=`find . -type f \
    ! -path ".build/*" \
    ! -path ".cache/*" \
    ! -path ".ddev/*" \
    ! -path ".deployer/*" \
    ! -path ".gitlab/*" \
    ! -path ".vscode/*" \
    ! -path "./typo3conf/*" \
    ! -path "./typo3temp/*" \
    ! -path "./vendor/*" \
    ! -path "./public/*" \
    ! -path "./.git/*" \
    ! -path "./.php_cs.cache" \
    ! -path "./Documentation/_static/*" \
    -print0 | xargs -0 -n1 -P8 file {} | grep 'UTF-8 Unicode (with BOM)'`

if [ -n "${FILES}" ]; then
    echo "Found UTF-8 files with BOM:";
    echo ${FILES};
    exit 1;
fi

exit 0
