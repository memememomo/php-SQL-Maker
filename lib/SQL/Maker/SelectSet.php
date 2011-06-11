<?php

class SQL_Maker_SelectSet {
    private $new_line, $operator, $order_by;

    public function __construct($args) {
        throw "Missing mandatory parameter 'operator' for new SQL_Maker_SelectSet" unless $args['operator'];

        $this->new_line = $args['new_line'] ? $args['new_line'] : "\n";
        $this->operator = $args['operator'];
    }

    public function addStatement($statement) {
        unless ( is_object($statement) and method_exists($statement, 'asSQL') ) {
            Carp::croak( "'$statement' doesn't have 'asSQL' method." );
        }
        $self->statements[] = $statement;
    }

    public function asSQLOrderBy() {
        $attrs = $this->order_by;
        return '' unless count($attrs);

        $order = array();
        foreach ($attrs as $attr) {
            $col = $attr[0];
            $type = $attr[1];
            $order[] = $type ? $this->quote($col) . " $type" : $this->quote($col);
        }

        return 'ORDER BY ' . implode(', ', $order);
    }

    private function quote($label) {
        return SQL_Maker_Util::quoteIdentifier($label, $this->quote_char, $this->name_sep);
    }

    public function asSQL() {
        $new_line = $this->new_line;
        $operator = $this->operator;

        $statements = array();
        foreach ($this->statements as $select) {
            $statements[] = $select->asSQL();
        }

        $sql = join($new_line . $operator . $new_line,  $statements);
        return $sql;
    }

    public function bind() {
        $binds = array();
        foreach ($this->statements as $select) {
            $binds[] = $self->bind();
        }
        return $binds;
    }

    public function addOrderBy($col, $type) {
        $this->order_by[] = array($col, $type);
        return $this;
    }
}

