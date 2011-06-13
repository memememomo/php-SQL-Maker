<?php
ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');

require('SQL/Maker.php');

class SimpleTest extends PHPUnit_Framework_TestCase {

    public function testSelectQuerySqlite() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where[] = array('bar', 'baz');
        $where[] = array('john', 'man');

        $opt = array();
        $opt['order_by'] = 'yo';

        $stmt = $builder->selectQuery($table, $fields, $where, $opt);
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM \"foo\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)\nORDER BY yo", $stmt->asSql());
        $this->assertEquals('baz,man', implode(',', $stmt->bind()));
    }

    public function testSelectQueryMysql() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));

        $table = 'foo';

        $fields = array('foo', 'bar');

        $where = array();
        $where[] = array('bar', 'baz');
        $where[] = array('john', 'man');

        $opt = array();
        $opt['order_by'] = 'yo';

        $stmt = $builder->selectQuery($table, $fields, $where, $opt);

        $this->assertEquals('SELECT foo, bar FROM foo WHERE (bar = ?) AND (john = ?) ORDER BY yo', $stmt->asSql());
        $this->assertEquals("baz,man", implode(',', $stmt->bind()));
    }

    public function testNewCondition() {
        $builder = new SQL_Maker(array('driver' => 'sqlite', 'quote_char' => '`', 'name_sep' => '.'));
        $cond = $builder->newCondition();
        $this->assertEquals('SQL_Maker_Condition', get_class($cond));
        $this->assertEquals('`', $cond->quote_char);
        $this->assertEquals('.', $cond->name_sep);
    }

    public function testNewSelectSqlite() {
        $builder = new SQL_Maker(array('driver' => 'sqlite', 'quote_char' => '`', 'name_sep' => '.'));
        $select = $builder->newSelect();
        $this->assertEquals('SQL_Maker_Select', get_class($select));
        $this->assertEquals('`', $select->quote_char);
        $this->assertEquals('.', $select->name_sep);
        $this->assertEquals("\n", $select->new_line);
    }

    public function testNewSelectSQLiteQuoteCharNewLine() {
        $builder = new SQL_Maker(array('driver' => 'sqlite', 'quote_char' => '`', 'name_sep' => '.'));
        $select = $builder->newSelect();
        $this->assertEquals('SQL_Maker_Select', get_class($select));
        $this->assertEquals('`', $select->quote_char);
        $this->assertEquals('.', $select->name_sep);
        $this->assertEquals("\n", $select->new_line);
    }

    public function testNewSelectMysqlQuoteCharNewLine() {
        $builder = new SQL_Maker(array('driver' => 'sqlite', 'quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $select = $builder->newSelect();
        $this->assertEquals('SQL_Maker_Select', get_class($select));
        $this->assertEquals('', $select->quote_char);
        $this->assertEquals('.', $select->name_sep);
        $this->assertEquals(' ', $select->new_line);
    }
}



