<?php

class SQL_QueryMaker {

    private $_bind = array();
    private $_column;
    private $_as_sql;


    public function __call($fn, array $fn_args) {

        if ($fn === "sqlAnd" || $fn === "sqlOr") {
            $op = $fn;
            $op = strtoupper(preg_replace('/^sql/', '', $op));

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
                        $value = SQL_QueryMaker::sqlEq($column, $value);
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
        } elseif ($fn === "sqlIn" || $fn === "sqlNotIn") {
            $op = $fn;
            $op = strtoupper(preg_replace('/^sql/', '', $op));

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
        } elseif (preg_match('/^sql/', $fn)) {
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

            $op = preg_replace('/^sql/', '', $fn);
            if ( ! $FNOP[$op] ) {
                throw new Exception("");
            }

            list($num_args, $builder) = SQL_QueryMaker::_compileBuilder($FNOP[$op]);

            $column = count($fn_args) > $num_args ? array_shift($fn_args) : null;

            if ($num_args != count($fn_args)) {
                throw new Exception("the operator expects {$num_args} parameters, but got " . count($fn_args));
            }

            return SQL_QueryMaker::_sqlOp($fn, $builder, $column, $fn_args);
        } else {
            throw new Exception("");
        }
    }

    public static function sqlOp() {
        $_args = func_num_args();

        $args = array_pop($_args);
        $expr = array_pop($_args);

        list($num_args, $builder) = _compile_builder($expr);
        if ( $num_args != count($args) ) {
            Throw new Exception("the operator expects {$num_args} but got " . count($args));
        }

        return _sqlOp("sql_op", $builder, array_shift($_args), $args);
    }

    public static function _sqlOp($builder, $column, $args) {
        return SQL_QueryMaker::_new($column, function($column, $quote_cb) use ($builder) {
            if ( is_null($column) ) {
                Throw new Exception("no column binding for $fn(args...)");
            }
            $term = $builder($quote_cb($column));
            return $term;
        }, $args);
    }

    public static function sqlRaw($sql, $bind) {
        return SQL_QueryMaker::_new(null, function() use ($sql) {
            return $sql;
        }, $bind);
    }

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
