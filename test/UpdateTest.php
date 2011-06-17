<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../lib');

require_once('SQL/Maker.php');

class UpdateTest extends PHPUnit_Framework_TestCase {

    public function testdriverSqliteArrayWhereCause() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'user';

        $set = array();
        $set[] = array('name' => 'john');
        $set[] = array('email' => 'john@example.com');

        $where = array();
        $where['user_id'] = 3;

        list($sql, $binds) = $builder->update($table, $set, $where);
        $this->assertEquals("UPDATE \"user\" SET \"name\" = ?, \"email\" = ? WHERE (\"user_id\" = ?)", $sql);
        $this->assertEquals('john,john@example.com,3', implode(',', $binds));
    }


    public function testDriverSqliteOrderedHashWhereCause() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $set = array();
        $set['bar']  = 'baz';
        $set['john'] = 'man';

        $where = array();
        $where['yo'] = 'king';

        list($sql, $binds) = $builder->update($table, $set, $where);
        $this->assertEquals("UPDATE \"foo\" SET \"bar\" = ?, \"john\" = ? WHERE (\"yo\" = ?)", $sql);
        $this->assertEquals('baz,man,king', implode(',', $binds));

    }

    public function testDriverSqliteOrderedHash() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = 'foo';

        $set = array();
        $set['bar']  = 'baz';
        $set['john'] = 'man';

        list($sql, $binds) = $builder->update($table, $set);
        $this->assertEquals("UPDATE \"foo\" SET \"bar\" = ?, \"john\" = ?", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }

    public function testDriverSqliteLiteralSubQuery() {
        $builder = new SQL_Maker(array('driver' => 'sqlite'));

        $table = "foo";

        $set = array();
        $set['user_id'] = 100;
        $set['updated_on'] = array('datetime(?)', 'now');
        $set['counter'] = array('counter + 1');

        list($sql, $binds) = $builder->update( $table, $set );
        $this->assertEquals("UPDATE \"foo\" SET \"user_id\" = ?, \"updated_on\" = datetime(?), \"counter\" = counter + 1", $sql);
        $this->assertEquals("100,now", implode(',', $binds));
    }

}







