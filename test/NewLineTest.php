<?php

class NewLineTest extends PHPUnit_Framework_TestCase {

    // empty string
    public function testEmptyString() {
        $builder = new SQL_Maker(array('new_line' => '', 'driver' => 'mysql'));
        $this->assertEquals($builder->new_line, '');
    }

}
