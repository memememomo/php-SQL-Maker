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

}