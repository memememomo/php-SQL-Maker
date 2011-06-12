<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');

require_once('SQL/Maker.php');

class InsertTest extends PHPUnit_Framework_TestCase {

    public function testEmptyString() {
        $builder = new SQL_Maker(array('new_line' => '', 'driver' => 'mysql'));
        $this->assertEquals($builder->new_line, '');
    }

}