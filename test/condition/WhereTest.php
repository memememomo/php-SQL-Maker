<?php
ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../../lib');

require('SQL/Maker.php');


class WhereTest extends PHPUnit_Framework_TestCase {

    public function testAnd() {
        list($w1, $w2) = $this->prepare();

        $and = $w1->composeAnd($w2);
        $this->assertEquals('((x = ?) AND (y = ?)) AND ((a = ?) AND (b = ?))', $and->asSql());
        $this->assertEquals('1, 2, 3, 4', implode(', ', $and->bind()));

        $and->add('z', 99);
        $this->assertEquals('((x = ?) AND (y = ?)) AND ((a = ?) AND (b = ?)) AND (z = ?)', $and->asSql());
        $this->assertEquals('1, 2, 3, 4, 99', implode(', ', $and->bind()));
    }

    public function testOr() {
        list($w1, $w2) = $this->prepare();

        $or = $w1->composeOr($w2);
        $this->assertEquals('((x = ?) AND (y = ?)) OR ((a = ?) AND (b = ?))', $or->asSql());
        $this->assertEquals('1, 2, 3, 4', implode(', ', $or->bind()));

        $or->add('z', 99);
        $this->assertEquals('((x = ?) AND (y = ?)) OR ((a = ?) AND (b = ?)) AND (z = ?)', $or->asSql());
        $this->assertEquals('1, 2, 3, 4, 99', implode(', ', $or->bind()));
    }

    public function prepare() {

        $w1 = new SQL_Maker_Condition();
        $w1->add('x', 1);
        $w1->add('y', 2);


        $w2 = new SQL_Maker_Condition();
        $w2->add('a', 3);
        $w2->add('b', 4);

        return array($w1, $w2);
    }
}