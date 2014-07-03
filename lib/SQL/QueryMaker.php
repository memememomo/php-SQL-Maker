<?php

class SQL_QueryMaker {

    private $_bind = array();
    private $_column;
    private $_as_sql;
    private $_bind;

    public function sqlOp() {
        $_args = func_num_args();

        $args = array_pop($_args);
        $expr = array_pop($_args);

        list($num_args, $builder) = _compile_builder($expr);
        if ( $num_args != count($args) ) {
            Throw new Exception("the operator expects {$num_args} but got " . count($args));
        }

        return _sqlOp("sql_op", $builder, array_shift($_args), $args);
    }

    public function _sqlOp($builder, $column, $args) {
        return SQL_QueryMaker::_new($column, function($column, $quote_cb) use ($builder) {
            if ( is_null($column) ) {
                Throw new Exception("no column binding for $fn(args...)");
            }
            $term = $builder($quote_cb($column));
            return $term;
        }, $args);
    }

    public function sqlRaw($sql, $bind) {
        return SQL_QueryMaker::_new(null, function() use ($sql) {
            return $sql;
        }, $bind);
    }

    public function _compile_builder($expr) {
    }

    public function _new($column, $as_sql, $bind) {

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
            $quote_cb = function($label) { return $this->quoteIdentifier($label) }
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
