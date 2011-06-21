<?php

class SQL_Maker_Condition {
    public $sql, $bind, $quote_char, $name_sep;

    public function __construct($args = array()) {
        $this->sql =
            array_key_exists('sql', $args)
            ? $args['sql']
            : array();

        $this->bind =
            array_key_exists('bind', $args)
            ? $args['bind']
            : array();

        $this->quote_char =
            array_key_exists('quote_char', $args)
            ? $args['quote_char']
            : '';

        $this->name_sep =
            array_key_exists('name_sep', $args)
            ? $args['name_sep']
            : '';
    }

    private function quote($label) {
        if ( is_array($label) ) {
            return $label[0];
        }

        return
            SQL_Maker_Util::quoteIdentifier($label, $this->quote_char, $this->name_sep);
    }

    private function makeTerm($col, $val) {
        if ( is_array( $val ) ) {
            if ( SQL_Maker_Util::is_hash( $val ) ) {
                foreach ($val as $op => $v) {
                }

                $op = strtoupper($op);
                if ( ( strcmp($op, 'IN') === 0
                       || strcmp($op, 'NOT IN') === 0)
                     && is_array($v) ) {

                    if ( count($v) === 0 ) {
                        if ( strcmp($op, 'IN') === 0 ) {
                            // makeTerm('foo' array('IN' => array())) => 0=1
                            return array('0=1', array());
                        }
                        else {
                            // makeTerm('foo', array('NOT IN' => array())) => 1=1
                            return array('1=1', array());
                        }
                    } else {
                        // makeTerm('foo', array( 'IN' => array(1,2,3) )) => foo IN (1,2,3)
                        $num = count($v);
                        $b = array();
                        for ($i = 0; $i < $num; $i++) {
                            $b[] = '?';
                        }

                        $term = $this->quote($col) . " $op (" . implode(', ', $b) . ')';

                        return array($term, $v);
                    }

                }
                elseif ( ( strcmp($op, 'BETWEEN') === 0 )
                        && is_array($v) ) {

                    if ( count($v) !== 2 ) {
                        throw new Exception("USAGE: makeTerm('foo', array('BETWEEN => array($a, $b)))");
                    }

                    return array($this->quote($col) . " BETWEEN ? AND ?", $v);

                }
                else {
                    // makeTerm('foo', array( '<', 3 )) => foo < 3
                    return array($this->quote($col) . " $op ?", array($v));
                }
            } else {
                // makeTerm(foo, array(-and => array(1,2,3))) => (foo = 1) AND (foo = 2) AND (foo = 3)
                if ( strcmp($val[0], '-and') === 0 ) {
                    $logic = 'OR';
                    $values = $val;
                    if ( strcmp($val[0], '-and') === 0 ) {
                        $logic = 'AND';
                        array_shift($values);
                    }

                    $bind = array();
                    $terms = array();

                    foreach ($values as $v) {
                        list( $term, $b ) = $this->makeTerm( $col, $v );
                        $terms[] = "($term)";
                        $bind = array_merge($bind, $b);
                    }

                    $term = implode(" $logic ", $terms);
                    return array($term, $bind);
                }
                else {
                    // makeTerm(foo, array(1,2,3)) => foo IN (1,2,3)
                    $term = $this->quote($col);
                    $term .= " IN (";
                    for ($i = 0; $i < count($val); $i++) {
                        $term .= '?, ';
                    }
                    $term = substr($term, 0, -2);
                    $term .= ')';

                    return array($term, $val);
                }
            }
        }
        elseif ( SQL_Maker::is_raw( $val ) ) {
            $v = $val->string;
            return array($this->quote($col) . " $v", array());
        }
        else {
            if ( is_null($val) ) {
                // makeTerm(foo, null) => foo IS NULL
                return array($this->quote($col) . " IS NULL", array());
            } else {
                // makeTerm(foo, "3") => foo = 3
                return array($this->quote($col) . " = ?", array($val));
            }
        }
    }

    public function add($col, $val) {
        list( $term, $bind ) = $this->makeTerm( $col, $val );
        $this->sql[] = "($term)";
        $this->bind = array_merge( $this->bind, $bind );

        return $this;
    }

    public function composeAnd($other) {
        return
            new SQL_Maker_Condition(array(
                                          'sql' => array('(' . $this->asSql() . ') AND (' . $other->asSql() . ')'),
                                          'bind' => array_merge($this->bind, $other->bind),
                                          ));
    }

    public function composeOr($other) {
        return
            new SQL_Maker_Condition(array(
                                          'sql' => array('(' . $this->asSql() . ') OR (' . $other->asSql() . ')'),
                                          'bind' => array_merge($this->bind, $other->bind),
                                          ));
    }

    public function asSql() {
        return implode(' AND ', $this->sql);
    }

    public function bind() {
        return $this->bind;
    }
}


/* CHEAT SHEET
IN:        array('foo','bar')
OUT QUERY: '`foo` = ?'
OUT BIND:  array('bar')

IN:        array('foo',array('bar','baz'))
OUT QUERY: '`foo` IN (?, ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo',array('IN' => array('bar','baz')))
OUT QUERY: '`foo` IN (?, ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo',array('not IN' => array('bar','baz')))
OUT QUERY: '`foo` NOT IN (?, ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo',array('!=' => 'bar'))
OUT QUERY: '`foo` != ?'
OUT BIND:  array('bar')

IN TODO:        array('foo',array('IS NOT NULL'))
OUT QUERY TODO: '`foo` IS NOT NULL'
OUT BIND TODO:  array()

IN:        array('foo',array('between' => array('1','2')))
OUT QUERY: '`foo` BETWEEN ? AND ?'
OUT BIND:  array('1','2')

IN:        array('foo',array('like' => 'xaic%'))
OUT QUERY: '`foo` LIKE ?'
OUT BIND:  array('xaic%')

IN TODO:        array('foo',array(array('>' => 'bar'),array('<' => 'baz')))
OUT QUERY TODO: '(`foo` > ?) OR (`foo` < ?)'
OUT BIND TODO:  array('bar','baz')

IN:        array('foo',array('-and',array('>' => 'bar'),array('<' => 'baz')))
OUT QUERY: '(`foo` > ?) AND (`foo` < ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo',array('-and','foo','bar','baz'))
OUT QUERY: '(`foo` = ?) AND (`foo` = ?) AND (`foo` = ?)'
OUT BIND:  array('foo','bar','baz')

IN TODO:        array('foo_id',array('IN (SELECT foo_id FROM bar WHERE t=?)',44))
OUT QUERY TODO: '`foo_id` IN (SELECT foo_id FROM bar WHERE t=?)'
OUT BIND TODO:  array('44')

IN TODO:        array('foo_id', array('IN' => array('SELECT foo_id FROM bar WHERE t=?',44)))
OUT QUERY TODO: '`foo_id` IN (SELECT foo_id FROM bar WHERE t=?)'
OUT BIND TODO:  array('44')

IN TODO:        array('foo_id',array('MATCH (col1, col2) AGAINST (?)','apples'))
OUT QUERY TODO: '`foo_id` MATCH (col1, col2) AGAINST (?)'
OUT BIND TODO:  array('apples')

IN:        array('foo_id',null)
OUT QUERY: '`foo_id` IS NULL'
OUT BIND:  array()

IN TODO:        array('foo_id',array('IN' => array()))
OUT QUERY TODO: '0=1'
OUT BIND TODO:  array()

IN TODO:        array('foo_id',array('NOT IN' => array()))
OUT QUERY TODO: '1=1'
OUT BIND TODO:  array()

IN TODO:        array('foo_id', sql_type(\3, SQL_INTEGER)]
OUT QUERY TODO: '`foo_id` = ?'
OUT BIND TODO:  sql_type(\3, SQL_INTEGER)

*/