<?php

    //[{"TotalNumberOfResults" : "490"}]


    //?=&=FreeModels&=Relevance

    $query = $_GET['keyword'];
    $type = $_GET['type'];
    $sortby = $_GET['sortBy'];

    $totalnumber = 0;

    header("Content-Type: application/json");

    echo "[{\"TotalNumberOfResults\" : \"$totalnumber\"}]";
?>