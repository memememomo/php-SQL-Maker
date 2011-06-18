<?php

ini_set('include_path',
        ini_get('include_path')
        .PATH_SEPARATOR
        .dirname(__FILE__).'/../../lib');

require('SQL/Maker.php');


class MakeTermTest extends PHPUnit_Framework_TestCase {

    public function testMakeTerm() {
        if (!($fp = fopen("lib/SQL/Maker/Condition.php", "r"))) {
            die( "cannot open file");
        }

        while (!feof($fp)) {
            $line = fgets($fp);
            if ( preg_match("/CHEAT SHEET/", $line) ) {
                break;
            }
        }

        while (!feof($fp)) {
            $line = fgets($fp);
            if ( preg_match("/IN:(.+)/", $line, $matches) ) {
                eval("\$in = $matches[1];");
            }

            if ( preg_match("/OUT QUERY:(.+)/", $line, $matches) ) {
                eval("\$query = $matches[1];");
            }

            if ( preg_match("/OUT BIND:(.+)/", $line, $matches) ) {
                eval("\$bind = $matches[1];");
                $this->check($in, $query, $bind);
            }
        }

        fclose($fp);
    }


    public function check($source, $expected_term, $expected_bind) {
        echo $expected_term . "\n";

        $cond = new SQL_Maker_Condition(
                                        array(
                                              'quote_char' => '`',
                                              'name_sep'   => '.',
                                              )
                                        );
        $cond->add($source[0], $source[1]);
        $sql = $cond->asSql();
        $sql = preg_replace("/^\(/", "", $sql);
        $sql = preg_replace("/\)$/", "", $sql);

        $this->assertEquals($expected_term, $sql);
        $this->assertEquals($expected_bind, $cond->bind());
    }

}