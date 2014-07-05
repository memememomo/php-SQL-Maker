<?php

class DeleteTest extends PHPUnit_Framework_TestCase {


    // driver sqlite
    public function testDriverSqliteSimple() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $where = array();
        $where[] = array('bar'  => 'baz');
        $where[] = array('john' => 'man');

        list($sql, $binds) = $builder->delete('foo', $where);
        $this->assertEquals('DELETE FROM "foo" WHERE ("bar" = ?) AND ("john" = ?)', $sql);
        $this->assertEquals('baz,man', implode(',', $binds));
    }

    public function testDriverSqliteDeleteAll() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->delete('foo');
        $this->assertEquals($sql, 'DELETE FROM "foo"');
        $this->assertEquals('', implode(',', $binds));
    }


    // driver mysql
    public function testDriverMysqlSimple() {
        $builder = new SQL_Maker(array('driver' => 'mysql'));

        $where = array();
        $where[] = array('bar'  => 'baz');
        $where[] = array('john' => 'man');

        list($sql, $binds) = $builder->delete('foo', $where);
        $this->assertEquals($sql, 'DELETE FROM `foo` WHERE (`bar` = ?) AND (`john` = ?)', $sql);
        $this->assertEquals('baz,man', implode(',', $binds));
    }

    public function testDriverMysqlDeleteAll() {
        $builder = new SQL_Maker(array('driver' => 'mysql'));
        list($sql, $binds) = $builder->delete('foo');
        $this->assertEquals($sql, 'DELETE FROM `foo`');
        $this->assertEquals('', implode(',', $binds));
    }


    // driver mysql, quote_char: "", new_line: " "
    public function testDriverMysqlQuoteCharNewLineSimple() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));

        $where = array();
        $where[] = array('bar'  => 'baz');
        $where[] = array('john' => 'man');

        list($sql, $binds) = $builder->delete('foo', $where);
        $this->assertEquals('DELETE FROM foo WHERE (bar = ?) AND (john = ?)', $sql);
        $this->assertEquals('baz,man', implode(',', $binds));
    }

    public function testDriverMysqlQuoteCharNewLineDeleteAll() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));
        list($sql, $binds) = $builder->delete('foo');
        $this->assertEquals('DELETE FROM foo', $sql);
        $this->assertEquals('', implode(',', $binds));
    }
}
