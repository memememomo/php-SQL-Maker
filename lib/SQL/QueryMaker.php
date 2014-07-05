<?php

function sql_and() {
    _call_andor(__FUNCTION__, func_get_args());
}

function sql_or() {
    _call_andor(__FUNCTION__, func_get_args());
}

function sql_is_null() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_is_not_null() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_eq() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_ne() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_lt() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_gt() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_le() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_ge() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_like() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_between() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_not_between() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_not() {
    _call_fnop(__FUNCTION__, func_get_args());
}

function sql_op() {
    $_args = func_get_args();

    $args = array_pop($_args);
    $expr = array_pop($_args);

    list($num_args, $builder) = _compile_builder($expr);
    if ( $num_args != count($args) ) {
        Throw new Exception("the operator expects {$num_args} but got " . count($args));
    }

    return _sql_op("sql_op", $builder, array_shift($_args), $args);
}

function sql_raw($sql, $bind) {
    return SQL_QueryMaker::_new(null, function() use ($sql) {
        return $sql;
    }, $bind);
}

function _call_andor($fn, $fn_args) {
    $op = $fn;
    $op = strtoupper(preg_replace('/^sql_/', '', $op));

    $args = array_pop($fn_args);
    $column = array_shift($fn_args);

    if (is_array($args)) {
        if ( ! is_null($column) ) {
            throw new Exception("cannot specify the column name as another argument when the conditions are listed using hashref");
        }

        $conds = array();
        foreach ($args as $column => $value) {
            if (is_object($value) && method_exists($value, 'bindColumn')) {
                $value->bindColumn($column);
            } else {
                $value = sql_eq($column, $value);
            }
            $conds []= $value;
        }
        $args = $conds;
    } else {
        if ( ! is_array($args) ) {
            throw new Exception("arguments to `$op` must be contained in an arrayref or a hashref");
        }
    }

    // build bind
    $bind = array();
    foreach ($args as $arg) {
        if (is_object($arg) && method_exists($arg, 'asSql')) {
            $bind []= $arg->bind();
        } else {
            $bind []= $arg;
        }
    }

    // build and return the compiler
    return SQL_QueryMaker::_new($column, function($column, $quote_cb) use ($args, $fn) {
        if ( count($args) == 0 ) {
            return $op === "AND" ? '0=1' : '1=1';
        }

        $term = array();
        foreach ($args as $arg) {
            if (is_object($arg) && method_exists($arg, 'asSql')) {
                list($term, $bind) = $arg->asSql($column, $quote_cb);
                $term []= "($term)";
            } else {
                if ( ! is_null($column) ) {
                    throw new Exception("no column binding for $fn");
                }
                $term []= '(' . $quote_cb($column) . ' = ?)';
            }
        }
        $term = implode(" $op ", $term);
        return $term;
    }, $bind);
}

function _call_in($fn, $fn_args) {
    $op = $fn;
    $op = strtoupper(preg_replace('/^sql_/', '', $op));

    $args = array_pop($fn_args);
    if ( ! is_array($args) ) {
        throw new Exception("arguments to `$op` must be contained in an arrayref");
    }
    $column = array_shift($fn_args);

    // build bind
    $bind = array();
    foreach ($args as $arg) {
        if (is_object($arg) && method_exists($arg, 'asSql')) {
            $bind []= $arg->bind();
        } else {
            $bind []= $arg;
        }
    }

    // build and return the compiler
    return SQL_QueryMaker::_new($column, function($column, $quote_cb) use ($args, $fn) {
        if ( ! is_null($column) ) {
            throw new Exception("no column binding for $fn");
        }

        if (count($args) === 0) {
            return $op === 'IN' ? '0=1' : '1=1';
        }

        $term = array();
        foreach ($args as $arg) {
            if (is_object($arg) && method_exists('asSql')) {
                $term = $arg->asSql(null, $quote_cb);
                $term []= $term === '?' ? $term : "($term)"; // emit parens only when necessary
            } else {
                $term []= '?';
            }
        }
        $term = $quote_cb($column) . " $op (" . implode(',', $term) . ')';
        return $term;
    }, $bind);
}

function _call_fnop($fn, $fn_args) {
    $FNOP = array(
        'IsNull' => 'IS NULL',
        'IsNotNull' => 'IS NOT NULL',
        'Eq' => '= ?',
        'Ne' => '!= ?',
        'Lt' => '< ?',
        'Gt' => '> ?',
        'Like' => 'LIKE ?',
        'Between' => 'BETWEEN ? AND ?',
        'NotBetween' => 'NOT BETWEEN ? AND ?',
        'Not' => 'NOT @',
    );

    $op = preg_replace('/^sql_/', '', $fn);

    list($num_args, $builder) = SQL_QueryMaker::_compileBuilder($FNOP[$op]);

    $column = count($fn_args) > $num_args ? array_shift($fn_args) : null;

    if ($num_args != count($fn_args)) {
        throw new Exception("the operator expects {$num_args} parameters, but got " . count($fn_args));
    }

    return _sql_op($fn, $builder, $column, $fn_args);
}

function _sql_op($builder, $column, $args) {
    return SQL_QueryMaker::_new($column, function($column, $quote_cb) use ($builder) {
        if ( is_null($column) ) {
            Throw new Exception("no column binding for $fn(args...)");
        }
        $term = $builder($quote_cb($column));
        return $term;
    }, $args);
}


class SQL_QueryMaker {

    private $_bind = array();
    private $_column;
    private $_as_sql;

    public static function _compileBuilder($expr) {
        // substitute the column character
        if ( ! preg_match('/@/', $expr) ) {
            $expr = "@ $expr";
        }

        $num_args = substr_count($expr, '?');
        $exprs = explode('@', $expr, -1);
        $builder = function ($arg) use ($exprs) {
            return implode($arg, $exprs);
        };

        return array($num_args, $builder);
    }

    public static function _new($column, $as_sql, $bind) {
        foreach ($bind as $b) {
            if ( is_array($b) ) {
                Throw new Exception("cannot bind an array");
            }
        }

        return new SQL_QueryMaker(array(
            'column' => $column,
            'as_sql' => $as_sql,
            'bind'   => $bind,
        ));
    }

    public function __construct($args) {
        $this->_column = $args['column'];
        $this->_as_sql = $args['as_sql'];
        $this->_bind   = $args['bind'];
        return $this;
    }

    public function bindColumn($column) {
        if ( ! is_null($column) ) {
            if ( ! is_null($this->_column) ) {
                Throw new Exception('cannot rebind column for \`' . $this->_column . "` to: `$column`");
            }
        }
        $this->_column = $column;
    }

    public function asSql($supplied_colname, $quote_cb) {
        if ( ! is_null($supplied_colname) ) {
            $this->bindColumn($supplied_colname);
        }
        if ( is_null($quote_cb) ) {
            $quote_cb = function($label) { return $this->quoteIdentifier($label); };
        }
        $_as_sql = $this->_as_sql;
        return $_as_sql($this->_column, $quote_cb);
    }

    public function bind() {
        return $this->_bind;
    }

    public function quoteIdentifier($label) {
        $list = array();
        foreach (explode('.', $label) as $l) {
            $list []= '`' . $l . '`';
        }
        return implode('.', $list);
    }
}


/* CHEAT SHEET
IN:        sql_eq(array('foo' => 'bar'))
OUT QUERY: '`foo` = ?'
OUT BIND:  array('bar')

IN:        sql_in(array('foo' => array('bar', 'baz')))
OUT QUERY: '`foo` IN (?,?)'
OUT BIND:  array('bar','baz')

IN:        sql_and(array(sql_eq(array('foo' => 'bar')), sql_eq(array('baz' => 123))))
OUT QUERY: '(`foo` = ?) AND (`baz` = ?)'
OUT BIND:  array('bar',123)

IN:        sql_and(array('foo' => array(sql_ge(3), sql_lt(5))))
OUT QUERY: '(`foo` >= ?) AND (`foo` < ?)'
OUT BIND:  array(3,5)

IN:        sql_or(array(sql_eq(array('foo' => 'bar')), sql_eq(array('baz' => 123))))
OUT QUERY: '(`foo` = ?) OR (`baz` = ?)'
OUT BIND:  array('bar',123)

IN:        sql_or(array('foo' => array('bar', 'baz')))
OUT QUERY: '(`foo` = ?) OR (`foo` = ?)'
OUT BIND:  array('bar','baz')

IN:        sql_is_null('foo')
OUT QUERY: '`foo` IS NULL'
OUT BIND:  array()

IN:        sql_is_not_null('foo')
OUT QUERY: '`foo` IS NOT NULL'
OUT BIND:  array()

IN:        sql_between('foo', 1, 2)
OUT QUERY: '`foo` BETWEEN ? AND ?'
OUT BIND:  array(1,2)

IN:        sql_not('foo')
OUT QUERY: 'NOT `foo`'
OUT BIND:  array()

IN:        sql_op('apples', 'MATCH (@) AGAINST (?)', array('oranges'))
OUT QUERY: 'MATCH (`apples`) AGAINST (?)'
OUT BIND:  array('oranges')

IN:        sql_raw('SELECT * FROM t WHERE id=?',123)
OUT QUERY: 'SELECT * FROM t WHERE id=?'
OUT BIND:  array(123)

IN:        sql_in('foo', => array(123,sql_raw('SELECT id FROM t WHERE cat=?',5)))
OUT QUERY: '`foo` IN (?,(SELECT id FROM t WHERE cat=?))'
OUT BIND:  array(123,5)
*/
