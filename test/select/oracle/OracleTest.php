<?php

class OracleTest extends PHPUnit_Framework_TestCase {

    public function testOracle() {
        $sel = new SQL_Maker_Select_Oracle(
                                           array(
                                                 'new_line' => ' '
                                                 )
                                           );
        $sel
            ->addSelect('foo')
            ->addFrom('user')
            ->limit(10)
            ->offset(20);

        $this->assertEquals("SELECT * FROM ( SELECT foo, ROW_NUMBER() OVER (ORDER BY 1) R FROM user LIMIT 10 OFFSET 20 ) WHERE  R BETWEEN 20 + 1 AND 10 + 20", $sel->asSql());

    }

}
