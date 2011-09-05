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

        if ( is_array( $val ) &&  SQL_Maker_Util::is_hash( $val ) ) {
            foreach ($val as $op => $v) { }

            if ( (strcmp($op, 'IN') === 0 || strcmp($op, 'NOT IN') === 0) && is_array($v) && !SQL_Maker_Util::is_hash($v) ) {
                if ( count($v) === 0 ) {
                    if ( strcmp($op, 'IN') === 0 ) {
                        // make_term('foo', array('IN' => array())) => 0=1
                        return array('0=1', array());
                    }
                    else {
                        // make_term('foo', array('NOT IN' => array())) => 1=1
                        return array('1=1', array());
                    }
                }
                else {
                    // make_term('foo', array( 'IN' => array(1,2,3) ) ) => foo IN (1,2,3)
                    $num = count($v);
                    $b = array();
                    for ($i = 0; $i < $num; $i++) {
                        $b[] = '?';
                    }

                    $term = $this->quote($col) . " $op (" . implode(', ', $b) . ')';

                    return array($term, $v);
                }
            }
            else if ( ( strcmp($op, 'IN') === 0 || strcmp($op, 'NOT IN') === 0) && SQL_Maker::is_scalar($v) ) {
                // make_term('foo', array( 'IN' => SQL_Maker::scalar(array('SELECT foo FROM bar'))) => foo IN (SELECT foo FROM bar)
                $values = $v->raw();
                $term = $this->quote($col) . " $op (" . array_shift($values) . ')';
                return array($term, $values);
            }
            else if ( ( strcmp($op, 'BETWEEN') === 0 ) && is_array($v) && !SQL_Maker_Util::is_hash($v) ) {
                if ( count($v) !== 2 ) {
                    throw new Exception("USAGE: make_term('foo', array('BETWEEN => array(SQL_Maker::scalar($a), SQL_Maker::scalar($b)))");
                }

                return array($this->quote($col) . " BETWEEN ? AND ?", $v);
            }
            else {
                if ( SQL_Maker::is_scalar($v) ) {
                    // make_term('foo', array('<', array('DATE_SUB(NOW(), INTERVAL 3 DAY)')))
                    return array($this->quote($col) . " $op " . $v->raw(), array());
                }
                else {
                    // make_term('foo', array( '<' => 3 )) => foo < 3
                    return array($this->quote($col) . " $op ?", array($v));
                }
            }
        }
        else if ( is_array( $val ) ) {
            // make_term('foo', array( '-and' => array(1,2,3))) => (foo = 1) AND (foo = 2) AND (foo = 3)
            $ref = is_array($val[0]);
            if ($ref || strcmp($val[0], '-and') === 0 ) {
                $logic = 'OR';
                $values = $val;

                if ( !$ref && strcmp($val[0], '-and') === 0 ) {
                    $logic = 'AND';
                    array_shift($values);
                }

                $b     = array();
                $terms = array();
                foreach ($values as $v) {
                    list( $term, $bind ) = $this->makeTerm( $col, $v );
                    $terms[] = "($term)";
                    $b       = array_merge($b, $bind);
                }
                $term = implode(" $logic ", $terms);
                return array($term, $b);
            }
            else {
                // make_term('foo' => array(1,2,3)) => foo IN (1,2,3)
                $num = count($val);
                $b = array();
                for ($i = 0; $i < $num; $i++) {
                    $b[] = '?';
                }
                $term = $this->quote($col) . " IN (" . implode(', ', $b) . ')';

                return array($term, $val);
            }
        }
        else if ( SQL_Maker::is_scalar($val) ) {
            $v = $val->raw();

            if ( is_array($v) ) {
                $query = array_shift($v);
                $arr   = $v;
                return array($this->quote($col) . " $query", $arr);
            }
            else {
                // make_term('foo', SQL_Maker::scalar("> 3") => foo > 3
                return array($this->quote($col) . " $v", array());
            }
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
IN:        array('foo', 'bar')
OUT QUERY: '`foo` = ?'
OUT BIND:  array('bar')

IN:        array('foo',array('bar','baz'))
OUT QUERY: '`foo` IN (?, ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo',array('IN' => array('bar','baz')))
OUT QUERY: '`foo` IN (?, ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo',array('NOT IN' => array('bar','baz')))
OUT QUERY: '`foo` NOT IN (?, ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo', array('!=' => 'bar'))
OUT QUERY: '`foo` != ?'
OUT BIND:  array('bar')

IN:        array('foo', SQL_Maker::scalar('IS NOT NULL'))
OUT QUERY: '`foo` IS NOT NULL'
OUT BIND:  array()

IN:        array('foo', array('BETWEEN' => array('1','2')))
OUT QUERY: '`foo` BETWEEN ? AND ?'
OUT BIND:  array('1','2')

IN:        array('foo', array('LIKE' => 'xaic%'))
OUT QUERY: '`foo` LIKE ?'
OUT BIND:  array('xaic%')

IN:        array('foo', array(array('>' => 'bar'),array('<' => 'baz')))
OUT QUERY: '(`foo` > ?) OR (`foo` < ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo', array('-and',array('>' => 'bar'),array('<' => 'baz')))
OUT QUERY: '(`foo` > ?) AND (`foo` < ?)'
OUT BIND:  array('bar','baz')

IN:        array('foo', array('-and','foo','bar','baz'))
OUT QUERY: '(`foo` = ?) AND (`foo` = ?) AND (`foo` = ?)'
OUT BIND:  array('foo','bar','baz')

IN:        array('foo_id', SQL_Maker::scalar(array('IN (SELECT foo_id FROM bar WHERE t=?)',44)))
OUT QUERY: '`foo_id` IN (SELECT foo_id FROM bar WHERE t=?)'
OUT BIND:  array('44')

IN:        array('foo_id', array('IN' => SQL_Maker::scalar(array('SELECT foo_id FROM bar WHERE t=?',44))))
OUT QUERY: '`foo_id` IN (SELECT foo_id FROM bar WHERE t=?)'
OUT BIND:  array('44')

IN:        array('foo_id', SQL_Maker::scalar(array('MATCH (col1, col2) AGAINST (?)','apples')))
OUT QUERY: '`foo_id` MATCH (col1, col2) AGAINST (?)'
OUT BIND:  array('apples')

IN:        array('foo_id', null)
OUT QUERY: '`foo_id` IS NULL'
OUT BIND:  array()

IN:        array('foo_id', array('IN' => array()))
OUT QUERY: '0=1'
OUT BIND:  array()

IN:        array('foo_id', array('NOT IN' => array()))
OUT QUERY: '1=1'
OUT BIND:  array()

IN TODO:        array('foo_id', sql_type(\3, SQL_INTEGER)]
OUT QUERY TODO: '`foo_id` = ?'
OUT BIND TODO:  sql_type(\3, SQL_INTEGER)

IN:        array('created_on', array('>' => SQL_Maker::scalar('DATE_SUB(NOW(), INTERVAL 1 DAY)')))
OUT QUERY: '`created_on` > DATE_SUB(NOW(), INTERVAL 1 DAY)'
OUT BIND: array()

*/