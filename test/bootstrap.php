<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');
ini_set('date.timezone', 'Asia/Tokyo');

require_once('SQL/Maker.php');
require_once('SQL/QueryMaker.php');

error_reporting(E_ALL|E_STRICT);
