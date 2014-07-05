<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../../lib');

require_once('SQL/QueryMaker.php');

class RefsInBindTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot bind an array
     */
    public function test_sql_eq() {
        sql_eq('foo', array(1,2,3));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot bind an array
     */
    public function test_sql_in() {
        sql_in('foo', array(array(1,2,3),4));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot bind an array
     */
    public function test_sql_and() {
        sql_and('a', array(array(1,2),3));
    }
}
