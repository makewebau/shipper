<?php

function install()
{
    $assetsDirectory = realpath(getcwd().'/../assets');

    $parentPackageDirectory = realpath(getcwd().'/../../../');

    foreach (['.shipignore', 'ship'] as $asset) {
        copy($assetsDirectory.'/'.$asset, $parentPackageDirectory.'/'.$asset);
    }
}

