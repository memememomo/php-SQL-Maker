<?php

require_once('SQL/Maker.php');
require_once('SQL/Maker/Util.php');
require_once('SQL/Maker/Condition.php');

class SQL_Maker_Select {
    public $prefix, $new;
    public $distinct, $for_update, $quote_char, $name_sep, $new_line, $offset, $limit, $where, $having, $subqueries;

    public function offset($offset) {
        if ($offset) {
            $this->offset = $offset;
            return $this;
        } else {
            return $this->offset;
        }
    }

    public function limit($limit) {
        if ($limit) {
            $this->limit = $limit;
            return $this;
        } else {
            return $this->limit;
        }
    }

    public function initArg($name, $args, $default) {
        $this->$name =
            array_key_exists($name, $args)
            ? $args[ $name ]
            : $default;
    }

    public function __construct($args = array()) {
        $this->initArg('select', $args, array());
        $this->initArg('distinct', $args, 0);
        $this->initArg('select_map', $args, array());
        $this->initArg('select_map_reverse', $args, array());
        $this->initArg('from', $args, array());
        $this->initArg('joins', $args, array());
        $this->initArg('index_hint', $args, array());
        $this->initArg('group_by', $args, array());
        $this->initArg('order_by', $args, array());
        $this->initArg('prefix', $args, 'SELECT ');
        $this->initArg('quote_char', $args, '');
        $this->initArg('name_sep', $args, "");
        $this->initArg('new_line', $args, "\n");
        $this->initArg('subqueries', $args, array());
    }

    public function newCondition() {
        return new SQL_Maker_Condition(array(
                                             'quote_char' => $this->quote_char,
                                             'name_sep'   => $this->name_sep,
                                             )
                                       );
    }

    public function bind() {
        $bind = array();

        if ( $this->subqueries ) {
            $bind = array_merge($bind, $this->subqueries);
        }

        if ( $this->where ) {
            $bind = array_merge($bind, $this->where->bind);
        }

        if ( $this->having ) {
            $bind = array_merge($bind, $this->having->bind);
        }

        return $bind;
    }

    public function addSelect($term, $col = null) {

        if ( !$col ) {
            $col = $term;
        }

        $this->select[] = $term;
        $this->select_map[] = array($term, $col);
        $this->select_map_reverse[] = array($col, $term);

        return $this;
    }

    public function addFrom($table, $alias = '') {
        if ( is_object( $table ) && method_exists( $table, 'asSql' ) ) {
            $this->subqueries = array_merge($this->subqueries, $table->bind());
            $this->from[] = array(array('('.$table->asSql().')'), $alias);
        }
        else {
            $this->from[] = array($table, $alias);
        }
        return $this;
    }

    public function addJoin($table_ref, $joins) {

        if ( is_array( $table_ref ) ) {
            $table = $table_ref[0];
            $alias = $table_ref[1];
        } else {
            $table = $table_ref;
            $alias = null;
        }

        if ( is_object( $table ) && method_exists( $table, 'asSql' ) ) {
            foreach ($table->bind() as $b) {
                $this->subqueries[] = $b;
            }

            $table = array( '(' . $table->asSql() . ')' );
        }

        $this->joins[] = array(
                               'table' => array( $table, $alias ),
                               'joins' => $joins,
                               );

        return $this;
    }

    public function addIndexHint($table, $hint) {
        $type = $hint['type'];
        if ( ! $type ) { $type = 'USE'; }

        $list = $hint['list'];
        if ( ! is_array($list) ) {
            $list = array($list);
        }

        $this->index_hint[$table] = array(
                                          'type' => $type,
                                          'list' => $list
                                          );
        return $this;
    }

    private function quote($label) {
        if ( is_array($label) ) {
            return $label[0];
        }

        if ( SQL_Maker::is_scalar($label) ) {
            return $label->raw();
        }

        return SQL_Maker_Util::quoteIdentifier($label, $this->quote_char, $this->name_sep);
    }

    public function asSql() {
        $sql = '';
        $new_line = $this->new_line;

        if ( count($this->select) ) {
            $sql .= $this->prefix;
            if ( $this->distinct ) { $sql .= 'DISTINCT '; }

            $select_list = array();
            foreach ($this->select as $s) {

                $alias = null;
                foreach ($this->select_map as $map) {
                    if ( $s == $map[0] ) {
                        $alias = $map[1];
                    }
                }

                if ( ! $alias ) {
                    $select_list[] = $this->quote($s);
                } else if ( $alias && $alias == $s ) {
                    $select_list[] = $this->quote($s);
                } else {
                    $select_list[] = $this->quote($s) . ' AS ' . $this->quote($alias);
                }
            }

            $sql .= implode(', ', $select_list) . $new_line;
        }

        $sql .= 'FROM ';

        // Add any explicit JOIN statements before the non-joined tables.
        if ( $this->joins && count($this->joins) ) {
            $initial_table_written = 0;
            foreach ($this->joins as $j) {
                $table = $j['table'];
                $join  = $j['joins'];

                $table = $this->_addIndexHint( $table[0], $table[1] ); // index hint handling
                if ( ! $initial_table_written++ ) { $sql .= $table; }
                $sql .= ' ' . strtoupper($join['type']) . ' JOIN ' . $this->quote($join['table']);
                if ( array_key_exists('alias', $join) ) { $sql .= ' ' . $this->quote($join['alias']); }


                if ( array_key_exists('condition', $join) && ! is_null($join['condition']) ) {
                    if ( is_array( $join['condition'] ) ) {
                        $condition_list = array();
                        foreach ($join['condition'] as $c) {
                            $condition_list[] = $this->quote($c);
                        }

                        $sql .= ' USING (' . implode(', ', $condition_list) . ')';
                    } else {
                        $sql .= ' ON ' . $join['condition'];
                    }
                }
            }
            if ( count($this->from) ) {
                $sql .= ', ';
            }
        }

        if ( $this->from && count( $this->from ) ) {
            $from_list = array();
            foreach ($this->from as $f) {
                $from_list[] = $this->_addIndexHint($f[0], $f[1]);
            }
            $sql .= implode(', ', $from_list);
        }

        $sql .= $new_line;
        if ($this->where)    { $sql .= $this->asSqlWhere();   }
        if ($this->group_by) { $sql .= $this->asSqlGroupBy(); }
        if ($this->having)   { $sql .= $this->asSqlHaving();  }
        if ($this->order_by) { $sql .= $this->asSqlOrderBy(); }
        if ($this->limit)    { $sql .= $this->asSqlLimit();   }

        $sql .= $this->asSqlForUpdate();
        $sql = preg_replace("/".$new_line.'+$/', "", $sql);

        return $sql;
    }

    public function asSqlLimit() {
        $n = $this->limit;
        if ( ! $n ) { return ''; }
        if ( preg_match("/\D/", $n) ) { throw new Exception("Non-numerics in limit clause ($n)"); }

        $offset = $this->offset ? " OFFSET " . (int)$this->offset : "";

        return sprintf("LIMIT %d%s" . $this->new_line, $n, $offset);
    }

    public function addOrderBy($col, $type = '') {
        $this->order_by[] = array($col, $type);
        return $this;
    }

    public function asSqlOrderBy() {
        $attrs = $this->order_by;
        if ( ! count($attrs) ) { return ''; }

        $attr_list = array();
        foreach ($attrs as $attr) {
            $col  = $attr[0];
            $type = $attr[1];

            if ( is_array( $col ) ) {
                $attr_list[] = $col[0];
            } else {
                $attr_list[] = $type ? $this->quote($col) . " $type" : $this->quote($col);
            }
        }

        return 'ORDER BY ' . implode(', ', $attr_list) . $this->new_line;
    }

    public function addGroupBy($group, $order = null) {
        $this->group_by[] = $order ? $this->quote($group) . " $order" : $this->quote($group);
        return $this;
    }

    public function asSqlGroupBy() {
        $elems = $this->group_by;

        if ( count($elems) == 0 ) { return ''; }

        return 'GROUP BY ' . implode(', ', $elems) . $this->new_line;
    }

    public function setWhere($where) {
        $this->where = $where;
        return $this;
    }

    public function addWhere($col, $val) {
        if ( ! $this->where ) {
            $this->where = $this->newCondition();
        }
        $this->where->add($col, $val);
        return $this;
    }

    public function asSqlWhere() {
        $where = $this->where->asSql();
        return $where ? "WHERE $where" . $this->new_line : '';
    }

    public function asSqlHaving() {
        if ( $this->having ) {
            return 'HAVING ' . $this->having->asSql() . $this->newLine;
        } else {
            return '';
        }
    }

    public function addHaving($col, $val) {

        foreach ($this->select_map_reverse as $map) {
            if ( $col == $map[0] ) {
                $col = $map[1];
            }
        }

        if ( ! $this->having ) {
            $this->having = $this->newCondition();
        }
        $this->having->add($col, $val);
        return $this;
    }

    public function asSqlForUpdate() {
        return $this->for_update ? ' FOR UPDATE' : '';
    }

    public function _addIndexHint($tbl_name, $alias = '') {
        $quoted = $alias ? $this->quote($tbl_name) . ' ' . $this->quote($alias) : $this->quote($tbl_name);

        $hint =
            ( ! is_array( $tbl_name) && array_key_exists($tbl_name, $this->index_hint))
            ? $this->index_hint[$tbl_name]
            : '';

        if ( ! $hint || ! is_array( $hint ) ) {
            return $quoted;
        }

        if ($hint['list'] && count( $hint['list'] ) ) {
            $list_list = array();
            foreach ($hint['list'] as $l) {
                $list_list[] = $this->quote($l);
            }

            $type = $hint['type'] ? strtoupper($hint['type']) : strtoupper('USE');
            return $quoted . ' ' . $type . ' INDEX (' .
                implode(',', $list_list) .
                ')';
        }
        return $quoted;
    }
}
