<?php

class SQL_Maker_Condition {
    protected $sql, $bind, $quote_char, $name_sep;

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
        if ( is_array($val) ) {
            // makeTerm(foo, array(-and => array(1,2,3))) => (foo = 1) AND (foo = 2) AND (foo = 3)
            if (1) {

            } else {
                // makeTerm(foo, array(1,2,3)) => foo IN (1,2,3)
                $term = $self->quote($col);
                $term .= " IN (";
                for ($i = 0; $i < count($val); $i++) {
                    $term .= '?, ';
                }
                $term = substr($term, 0, -2);
                $term .= ')';

                return array($term, $val);
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

?>