<?php

//check if we are running within the DokuWiki environment
if (!defined("DOKU_INC")){
    die();
}

//user pages
$conf["khum1_userpage"]    = true; //TRUE: use/show user pages
$conf["khum1_userpage_ns"] = "user:"; //namespace to use for user page storage

