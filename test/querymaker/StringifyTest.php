<?php

class MyDateTime extends DateTime {
    public function __tostring() {
        return $this->format('Y-m-d H:i:s');
    }
}

class StringifyTest extends PHPUnit_Framework_TestCase {

    public function test_sql_and_hashref() {
        $q = sql_and(array(
            'a' => new MyDateTime('2025-01-01 00:00:00'),
            'b' => 1,
        ));

        $this->assertEquals('(`a` = ?) AND (`b` = ?)', $q->asSql());
        $this->assertEquals('2025-01-01 00:00:00,1', implode(',', $q->bind()));
    }

    public function test_sql_or_valuelist() {
        $q = sql_or('a', array(
            new MyDateTime('2014-01-01 00:00:00'),
            new MyDateTime('2015-01-01 00:00:00'),
        ));
        $this->assertEquals('(`a` = ?) OR (`a` = ?)', $q->asSql());
        $this->assertEquals('2014-01-01 00:00:00,2015-01-01 00:00:00', implode(',', $q->bind()));
    }

    public function test_sql_in() {
        $q = sql_in('a', array(
            new MyDateTime('2014-01-01 00:00:00'),
            new MyDateTime('2015-01-01 00:00:00'),
        ));
        $this->assertEquals('`a` IN (?,?)', $q->asSql());
        $this->assertEquals('2014-01-01 00:00:00,2015-01-01 00:00:00', implode(',', $q->bind()));
    }
}
