<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');

require_once('SQL/Maker.php');

class SelectTest extends PHPUnit_Framework_TestCase {

    public function testDriverSqliteColumnsAndTables() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->select('foo', array('*'));
        $this->assertEquals("SELECT *\nFROM \"foo\"", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteColumnsAndTablesWhereCauseHash() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where['bar'] = 'baz';
        $where['john'] = 'man';

        list($sql, $binds) = $builder->select($table, $fields, $where);
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }

    public function testDriverSqliteColumnsAndTablesWhereCauseArray() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where[] = array('bar', 'baz');
        $where[] = array('john', 'man');

        list($sql, $binds) = $builder->select($table, $fields, $where);
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }

    public function testDriverSqliteColumnsAndTablesWhereCauseHashOrderBy() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where['bar'] = 'baz';
        $where['john'] = 'man';

        $opt = array();
        $opt['order_by'] = 'yo';

        list($sql, $binds) = $builder->select($table, $fields, $where, $opt);
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)\nORDER BY yo", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }

    public function testDriverSqliteColumnsAndTablesWhereCauseArrayOrderBy() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where[] = array('bar', 'baz');
        $where[] = array('john', 'man');

        $opt = array();
        $opt['order_by'] = 'yo';

        list($sql, $binds) = $builder->select($table, $fields, $where, $opt);
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)\nORDER BY yo", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }


    public function testDriverSqliteColumnsAndTablesWhereCauseArrayOrderByLimitOffset() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where[] = array('bar', 'baz');
        $where[] = array('john', 'man');

        $opt = array();
        $opt['order_by'] = 'yo';
        $opt['limit'] = 1;
        $opt['offset'] = 3;

        list($sql, $binds) = $builder->select($table, $fields, $where, $opt);
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)\nORDER BY yo\nLIMIT 1 OFFSET 3", $sql);
        $this->assertEquals('baz,man', implode(',', $binds));
    }


    public function testDriverSqliteModifyPrefix() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();

        $opt = array();
        $opt['prefix'] = 'SELECT SQL_CALC_FOUND_ROWS ';

        list($sql, $binds) = $builder->select($table, $fields, $where, $opt);

        $this->assertEquals("SELECT SQL_CALC_FOUND_ROWS \"foo\", \"bar\"\nFROM \"foo\"", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteOrderByScalar() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->select('foo', array('*'), array(), array('order_by' => 'yo'));
        $this->assertEquals("SELECT *\nFROM \"foo\"\nORDER BY yo", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteOrderByHash() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $opt = array();
        $opt['order_by'] = array();
        $opt['order_by'][] = array('yo' => 'DESC');

        list($sql, $binds) = $builder->select('foo', array('*'), array(), $opt);
        $this->assertEquals("SELECT *\nFROM \"foo\"\nORDER BY \"yo\" DESC", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteOrderByArray() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->select('foo', array('*'), array(), array('order_by' => array('yo', 'ya')));
        $this->assertEquals("SELECT *\nFROM \"foo\"\nORDER BY yo, ya", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteOrderByMixed() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->select('foo', array('*'), array(), array('order_by' => array(array('yo' => 'DESC'), 'ya')));
        $this->assertEquals("SELECT *\nFROM \"foo\"\nORDER BY \"yo\" DESC, ya", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteFromMultiFrom() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->select(array('foo', 'bar'), array('*'), array());
        $this->assertEquals("SELECT *\nFROM \"foo\", \"bar\"", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

    public function testDriverSqliteFromMultiFromWithAlias() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));
        list($sql, $binds) = $builder->select(array(array('foo' => 'f'), array('bar' => 'b')), array('*'), array());
        $this->assertEquals("SELECT *\nFROM \"foo\" \"f\", \"bar\" \"b\"", $sql);
        $this->assertEquals('', implode(',', $binds));
    }

}