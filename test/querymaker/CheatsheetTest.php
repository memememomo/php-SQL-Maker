<?php

class CheatsheetTest extends PHPUnit_Framework_TestCase {

    public function testCheatsheet() {
        if (!($fp = fopen("lib/SQL/QueryMaker.php", "r"))) {
            die("cannot open file");
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
                eval("\$term = $matches[1];");
            }

            if ( preg_match("/OUT QUERY:(.+)/", $line, $matches) ) {
                eval("\$query = $matches[1];");
            }

            if ( preg_match("/OUT BIND:(.+)/", $line, $matches) ) {
                eval("\$bind = $matches[1];");
                $this->check($term, $query, $bind);
            }
        }

        fclose($fp);
    }

    public function check($term, $expected_term, $expected_bind) {
        $sql = $term->asSql();
        $bind = $term->bind();

        $this->assertEquals($expected_term, $sql);
        $this->assertEquals($expected_bind, $bind);
    }
}
