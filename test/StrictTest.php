<?php
ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');

require_once('SQL/Maker.php');

class StrictTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->builder = new SQL_Maker(array(
            'driver' => 'SQLite',
            'strict' => 1,
        ));
    }

    public function testBasic() {
        $this->assertEquals(1, $this->builder->strict);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot pass in a ref as argument in strict mode
     */
    public function test_newCondition() {
        $select = $this->builder->newSelect();
        $this->assertEquals(1, $select->strict);
        $select->newCondition()->add(
            'foo', array(1)
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot pass in a ref as argument in strict mode
     */
    public function test_select() {
        $this->builder->select("user", array('*'), array('name' => array('John', 'Tom')));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot pass in a ref as argument in strict mode
     */
    public function test_insert() {
        $this->builder->insert(
            'user', array('name' => 'John', 'created_on' => array("datetime(now)"))
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot pass in a ref as argument in strict mode
     */
    public function test_delete() {
        $this->builder->delete(
            'user', array('name' => array('John', 'Tom'))
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot pass in a ref as argument in strict mode
     */
    public function test_update_where() {
        $this->builder->update('user', array('name' => "John"), array('user_id' => array(1,2)));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage cannot pass in a ref as argument in strict mode
     */
    public function test_update_set() {
        $this->builder->update('user', array('name' => array('select *')));
    }
}
