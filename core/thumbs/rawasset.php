<?php

    // Width=60&Height=62&ImageFormat=png&AssetID=8

    $width = $_GET['Width'];
    $height = $_GET['Height'];
    $assetid = $_GET['AssetID'];

    echo "/thumbs/?id=$assetid&sx=$width&sy=$height";
?>