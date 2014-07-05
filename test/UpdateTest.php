<?php

class UpdateTest extends PHPUnit_Framework_TestCase {


    // driver: sqlite
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


    // driver: mysql
    public function testDriverMysqlArrayWhereCause() {
        $builder = new SQL_Maker(array('driver' => 'mysql'));

        $table = 'user';

        $set = array();
        $set[] = array('name' => 'john');
        $set[] = array('email' => 'john@example.com');

        $where = array();
        $where['user_id'] = 3;

        list($sql, $binds) = $builder->update($table, $set, $where);
        $this->assertEquals("UPDATE `user` SET `name` = ?, `email` = ? WHERE (`user_id` = ?)", $sql);
        $this->assertEquals('john,john@example.com,3', implode(',', $binds));

    }

    public function testDriverMysqlHashWhereCause() {
        $builder = new SQL_Maker(array('driver' => 'mysql'));

        $table = 'foo';

        $set = array();
        $set['bar']  = 'baz';
        $set['john'] = 'man';

        $where = array();
        $where['yo'] = 'king';

        list($sql, $binds) = $builder->update($table, $set, $where);
        $this->assertEquals("UPDATE `foo` SET `bar` = ?, `john` = ? WHERE (`yo` = ?)", $sql);
        $this->assertEquals('baz,man,king', implode(',', $binds));

    }

    public function testDriverMysqlOrderedHash() {
        $builder = new SQL_Maker(array('driver' => 'mysql'));

        $table = "foo";

        $set = array();
        $set['bar']  = 'baz';
        $set['john'] = 'man';

        list($sql, $binds) = $builder->update($table, $set);
        $this->assertEquals("UPDATE `foo` SET `bar` = ?, `john` = ?", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }

    public function testDriverMysqlLiteralSubQuery() {
        $builder = new SQL_Maker(array('driver' => 'mysql'));

        $table = 'foo';

        $set = array();
        $set[] = array('user_id' => 100);
        $set[] = array('updated_on' => array('FROM_UNIXTIME(?)', 1302241686));
        $set[] = array('counter' => array('counter + 1'));

        list($sql, $binds) = $builder->update($table, $set);
        $this->assertEquals("UPDATE `foo` SET `user_id` = ?, `updated_on` = FROM_UNIXTIME(?), `counter` = counter + 1", $sql);
        $this->assertEquals('100,1302241686', implode(',', $binds));
    }


    // driver: mysql, quote_char: "", new_line: " "
    public function testDriverMysqlQuoteCharNewLineArrayWhereCause() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));

        $table = 'user';

        $set = array();
        $set[] = array('name' => 'john');
        $set[] = array('email' => 'john@example.com');

        $where = array();
        $where['user_id'] = 3;

        list($sql, $binds) = $builder->update($table, $set, $where);
        $this->assertEquals("UPDATE user SET name = ?, email = ? WHERE (user_id = ?)", $sql);
        $this->assertEquals("john,john@example.com,3", implode(',', $binds));
    }

    public function testDriverMysqlQuoteCharNewLineHashWhereCause() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));

        $table = 'foo';

        $set = array();
        $set['bar']  = 'baz';
        $set['john'] = 'man';

        $where = array();
        $where['yo'] = 'king';

        list($sql, $binds) = $builder->update($table, $set, $where);
        $this->assertEquals("UPDATE foo SET bar = ?, john = ? WHERE (yo = ?)", $sql);
        $this->assertEquals('baz,man,king', implode(',', $binds));
    }

    public function testDriverMysqlQuoteCharNewLineHash() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));

        $table = 'foo';

        $set = array();
        $set['bar']  = 'baz';
        $set['john'] = 'man';

        list($sql, $binds) = $builder->update($table, $set);
        $this->assertEquals("UPDATE foo SET bar = ?, john = ?", $sql);
        $this->assertEquals("baz,man", implode(',', $binds));
    }

    public function testDriverMysqlQuoteCharNewLineLiteralSubQuery() {
        $builder = new SQL_Maker(array('driver' => 'mysql', 'quote_char' => '', 'new_line' => ' '));

        $table = 'foo';

        $set = array();
        $set[] = array('user_id' => 100);
        $set[] = array('updated_on' => array('FROM_UNIXTIME(?)', 1302241686));
        $set[] = array('counter' => array('counter + 1'));

        list($sql, $binds) = $builder->update($table, $set);

        $this->assertEquals("UPDATE foo SET user_id = ?, updated_on = FROM_UNIXTIME(?), counter = counter + 1", $sql);
        $this->assertEquals("100,1302241686", implode(',', $binds));

    }
}







