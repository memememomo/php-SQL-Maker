<?php

class AndUsingHashTest extends PHPUnit_Framework_TestCase {

    public function testAndUsingHashTest() {
        $q = sql_and(array('foo' => 1, 'bar' => sql_eq(2), 'baz' => sql_lt(3)));
        $this->assertEquals('(`foo` = ?) AND (`bar` = ?) AND (`baz` < ?)', $q->asSql());
        $this->assertEquals(array(1,2,3), $q->bind());
    }
}
