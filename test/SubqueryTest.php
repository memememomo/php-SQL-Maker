<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');

require_once('SQL/Maker.php');

class SubqueryTest extends PHPUnit_Framework_TestCase {

    public function testSelectSubqueryDriverSqlite() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $stmt1 = null;
        $stmt2 = null;

        $stmt1 = $builder->selectQuery('sakura', array('hoge', 'fuga'), array('fuga' => 'piyo', 'zun' => 'doko'));
        $this->assertEquals("SELECT \"hoge\", \"fuga\"\nFROM \"sakura\"\nWHERE (\"fuga\" = ?) AND (\"zun\" = ?)", $stmt1->asSql());
        $this->assertEquals("piyo,doko", implode(',', $stmt1->bind()));


        $stmt2 = $builder->selectQuery(array(array($stmt1,'stmt1')), array('foo', 'bar'), array('bar' => 'baz', 'john' => 'man'));
        $this->assertEquals("SELECT \"foo\", \"bar\"\nFROM (SELECT \"hoge\", \"fuga\"\nFROM \"sakura\"\nWHERE (\"fuga\" = ?) AND (\"zun\" = ?)) \"stmt1\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)", $stmt2->asSql());
        $this->assertEquals("piyo,doko,baz,man", implode(',', $stmt2->bind()));


        $stmt3 = $builder->selectQuery(array(array($stmt2, 'stmt2')), array('baz'), array('baz' => 'bar'), array('order_by' => 'yo'));
        $this->assertEquals("SELECT \"baz\"\nFROM (SELECT \"foo\", \"bar\"\nFROM (SELECT \"hoge\", \"fuga\"\nFROM \"sakura\"\nWHERE (\"fuga\" = ?) AND (\"zun\" = ?)) \"stmt1\"\nWHERE (\"bar\" = ?) AND (\"john\" = ?)) \"stmt2\"\nWHERE (\"baz\" = ?)\nORDER BY yo", $stmt3->asSql());
        $this->assertEquals("piyo,doko,baz,man,bar", implode(',', $stmt3->bind()));

        $stmt = $builder->newSelect();
        $stmt->addSelect( 'id' );
        $stmt->addWhere('foo', 'bar');
        $stmt->addFrom( $stmt, 'itself' );

        $this->assertEquals("SELECT \"id\"\nFROM (SELECT \"id\"\nFROM \nWHERE (\"foo\" = ?)) \"itself\"\nWHERE (\"foo\" = ?)", $stmt->asSql());
        $this->assertEquals("bar,bar", implode(',', $stmt->bind()));

    }


    public function testSubqueryAndJoin() {
        $subquery = new SQL_Maker_Select(array( 'quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $subquery->addSelect('*');
        $subquery->addFrom( 'foo' );
        $subquery->addWhere('hoge', 'fuga');

        $stmt = new SQL_Maker_Select(array( 'quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));

        $stmt->addJoin(
                       array( $subquery, 'bar' ),
                       array(
                             'type'      => 'inner',
                             'table'     => 'baz',
                             'alias'     => 'b1',
                             'condition' => 'bar.baz_id = b1.baz_id'
                             )
                       );
        $this->assertEquals("FROM (SELECT * FROM foo WHERE (hoge = ?)) bar INNER JOIN baz b1 ON bar.baz_id = b1.baz_id", $stmt->asSql());
        $this->assertEquals("fuga", implode(',', $stmt->bind()));
    }

    public function testComplex() {
        $s1 = new SQL_Maker_Select(array( 'quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $s1->addSelect('*');
        $s1->addFrom( 'foo' );
        $s1->addWhere('hoge', 'fuga');

        $s2 = new SQL_Maker_Select(array( 'quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $s2->addSelect('*');
        $s2->addFrom( $s1, 'f' );
        $s2->addWhere('piyo', 'puyo');
        $stmt = new SQL_Maker_Select(array( 'quote_char' => '', 'name_sep' => '.', 'new_line' => ' '));
        $stmt->addJoin(
                       array( $s2, 'bar' ),
                       array(
                             'type'      => 'inner',
                             'table'     => 'baz',
                             'alias'     => 'b1',
                             'condition' => 'bar.baz_id = b1.baz_id'
                             )
                       );
        $this->assertEquals("FROM (SELECT * FROM (SELECT * FROM foo WHERE (hoge = ?)) f WHERE (piyo = ?)) bar INNER JOIN baz b1 ON bar.baz_id = b1.baz_id", $stmt->asSql());
        $this->assertEquals("fuga,puyo", implode(',', $stmt->bind()));
    }
}