<?php

require_once('SQL/Maker/Select.php');
require_once('SQL/Maker/Select/Oracle.php');
require_once('SQL/Maker/Condition.php');
require_once('SQL/Maker/Util.php');

class SQL_Maker {
    public $quote_char, $name_sep, $new_line, $driver, $select_class;

    public function __construct($args) {
        if ( ! array_key_exists('driver', $args) ) {
            throw "'driver' is required for creating new instance of $this ";
        }

        $driver = $args['driver'];

        if ( ! array_key_exists('quote_char', $args) ) {
            if ( strcmp($driver, "mysql") === 0 ) {
                $this->quote_char = '`';
            } else {
                $this->quote_char = '"';
            }
        } else {
            $this->quote_char = $args['quote_char'];
        }

        $this->select_class =
            strcmp($driver, 'Oracle') === 0
            ? 'SQL_Maker_Select_Oracle'
            : 'SQL_Maker_Select';

        $this->name_sep =
            array_key_exists('name_sep', $args)
            ? $args['name_sep']
            : '.';

        $this->new_line =
            array_key_exists('new_line', $args)
            ? $args['new_line']
            : "\n";

        $this->driver = $driver;
    }

    public function newCondition() {
        return new SQL_Maker_Condition(array(
                                             'quote_char' => $this->quote_char,
                                             'name_sep'   => $this->name_sep,
                                             ));
    }

    public function newSelect($args = array()) {
        $class = $this->select_class;
        return new $class(array_merge(
                                      array(
                                            'name_sep'   => $this->name_sep,
                                            'quote_char' => $this->quote_char,
                                            'new_line'   => $this->new_line,
                                            ),
                                      $args
                                      )
                          );
    }


    public function insert($table, $values, $opt = array()) {
        $prefix =
            array_key_exists('prefix', $opt)
            ? $opt['prefix']
            : 'INSERT INTO';

        $values = SQL_Maker_Util::to_array($values);

        $quoted_table = $this->quote($table);

        $columns = array();
        $bind_columns = array();
        $quoted_columns = array();

        for ($i = 0; $i < count($values); $i++) {
            $pair = $values[ $i ];

            if ( SQL_Maker_Util::is_hash( $pair ) ) {
                foreach ($pair as $col => $val) {}
            } else {
                $col = $pair[0];
                $val = $pair[1];
            }

            $quoted_columns[] = $this->quote($col);
            if (is_array($val)) {
                $count = count($val);

                if ($count == 1) {
                    // $builder->insert(foo, array(created_on => array('NOW()')))
                    $columns[] = $val[0];
                } else if ($count >= 2) {
                    // $builder->insert(foo, array(created_on => array('UNIX_TIMESTAMP(?)', '2011-04-12 00:34:12')))
                    $stmt = array_shift($val);
                    $sub_bind = $val;

                    $columns[] = $stmt;
                    $bind_columns = array_merge($bind_columns, $sub_bind);
                }
            }
            else {
                // normal values
                $columns[] = '?';
                $bind_columns[] = $val;
            }
        }

        $sql  = "$prefix $quoted_table" . $this->new_line;
        $sql .= '(' . implode(', ', $quoted_columns) . ')' . $this->new_line .
                'VALUES (' . implode(', ', $columns) . ')';

        return array($sql, $bind_columns);
    }

    public function quote($label) {
        return SQL_Maker_Util::quoteIdentifier($label, $this->quote_char, $this->name_sep);
    }

    public function delete($table, $where = array()) {
        $w = $this->makeWhereClause($where);
        $quoted_table = $this->quote($table);
        $sql = "DELETE FROM $quoted_table" . $w[0];
        return array($sql, $w[1]);
    }

    public function update($table, $args, $where = array()) {

        $args = SQL_Maker_Util::to_array( $args );
        $columns = array();
        $bind_columns = array();
        // make "SET" clause
        for ($i = 0; $i < count($args); $i++) {
            $pair = $args[ $i ];

            $col = null;
            $val = null;
            if ( SQL_Maker_Util::is_hash( $pair ) ) {
                foreach ($pair as $c => $v) {
                    $col = $c;
                    $val = $v;
                }
            }
            else {
                $col = $pair[0];
                $val = $pair[1];
            }

            $quoted_col = $this->quote($col);
            if (is_array($val)) {
                $count = count($val);

                if ($count == 1) {
                    // $builder->update('foo', array( created_on => array('NOW()') ))
                    $columns[] = "$quoted_col = " . $val[0];
                }
                else if ($count >= 2) {
                    // $builder->update('foo', array( 'VALUES(foo) + ?', 10 ) )
                    $stmt = array_shift($val);
                    $sub_bind = $val;

                    $columns[] = "$quoted_col = " . $stmt;
                    $bind_columns = array_merge($bind_columns, $sub_bind);
                }
            }
            else {
                // normal values
                $columns[] = "$quoted_col = ?";
                $bind_columns[] = $val;
            }
        }

        $w = $this->makeWhereClause($where);
        $bind_columns = array_merge($bind_columns, $w[1]);

        $quoted_table = $this->quote($table);
        $sql = "UPDATE $quoted_table SET " . implode(', ', $columns) . $w[0];
        return array($sql, $bind_columns);
    }

    public function makeWhereClause($where) {
        $w = new SQL_Maker_Condition(array(
                                           'quote_char' => $this->quote_char,
                                           'name_sep'   => $this->name_sep
                                           ));

        $where = SQL_Maker_Util::to_array($where);
        for ($i = 0; $i < count($where); $i++) {
            if ( SQL_Maker_Util::is_hash($where[$i]) ) {
                foreach ($where[$i] as $col => $val) {
                }
            } else {
                $col = $where[$i][0];
                $val = $where[$i][1];
            }

            $w->add($col, $val);
        }
        $sql = $w->asSql(1);
        return
            $sql
            ? array(" WHERE $sql", $w->bind())
            : array('', $w->bind());
    }

    // list($stmt, $bind) = $sql->select($table, $fields, $where, $opt)
    public function select($table, $fields, $where = array(), $opt = array())  {
        $stmt = $this->selectQuery($table, $fields, $where, $opt);
        return array($stmt->asSql(), $stmt->bind());
    }

    public function selectQuery($table, $fields, $where = array(), $opt = array()) {
        if ( ! is_array($fields) ) {
            throw new Exception("SQL::Maker::select_query: $fields should be array");
        }

        $stmt = $this->newSelect(array(
                                       'select' => $fields,
                                       ));

        if ( ! is_array($table) ) {
            // $table = 'foo'
            $stmt->addFrom( $table );
        }
        else {
            // $table = [ 'foo', [ bar => 'b' ] ]
            for ($i = 0; $i < count($table); $i++) {
                if ( is_array($table[$i]) ) {
                    if ( SQL_Maker_Util::is_hash( $table[$i] ) ) {
                        foreach ($table[$i] as $tbl => $alias) {
                            $stmt->addFrom($tbl, $alias);
                        }
                    }
                    else {
                        $stmt->addFrom($table[$i][0], $table[$i][1]);
                    }
                }
                else {
                    $stmt->addFrom($table[$i]);
                }
            }
        }

        if ( array_key_exists('prefix', $opt) ) {
            $stmt->prefix = $opt['prefix'];
        }

        if ( $where ) {
            $where = SQL_Maker_Util::to_array( $where );
            for ($i = 0; $i < count($where); $i++) {
                if ( SQL_Maker_Util::is_hash( $where[$i] ) ) {
                    foreach ($where[$i] as $col => $val) {}
                } else {
                    $col = $where[$i][0];
                    $val = $where[$i][1];
                }
                $stmt->addWhere($col, $val);
            }
        }

        if ( array_key_exists('order_by', $opt) ) {
            $o = $opt['order_by'];

            if ( is_array( $o ) ) {

                // array(foo => 'DESC') -> array(array(foo => 'DESC'))
                if ( SQL_Maker_Util::is_hash( $o ) ) {
                    $new_o = array();
                    foreach ($o as $col => $type) {
                        $new_o[] = array($col => $type);
                    }
                    $o = $new_o;
                }

                for ($i = 0; $i < count($o); $i++) {
                    if ( is_array($o[$i]) ) {
                        // Skinny-ish array(array(foo => 'DESC'), array(bar => 'ASC'))
                        foreach ($o[$i] as $col => $type) {
                            $stmt->addOrderBy($col, $type);
                        }
                    } else {
                        // just array('foo DESC', 'bar ASC')
                        $stmt->addOrderBy(array($o[$i]));
                    }
                }

            } else {
                // just 'foo DESC, bar ASC'
                $stmt->addOrderBy(array($o));
            }
        }

        if ( array_key_exists('limit', $opt) ) {
            $stmt->limit( $opt['limit'] );
        }

        if ( array_key_exists('offset', $opt) ) {
            $stmt->offset( $opt['offset'] );
        }

        if ( array_key_exists('having', $opt) ) {
            $terms = $opt['having'];
            for ($i = 0; $i < count($terms); $i++) {
                $col = $terms[$i];
                $val = $terms[$i+1];

                $stmt->add_having(array($col => $val));
            }
        }

        if ( array_key_exists('for_update', $opt) ) {
            $stmt->for_update(1);
        }

        return $stmt;
    }


    public function scalar($str) {
        return new SQL_Maker_Scalar($str);
    }


    public function is_scalar($string) {
        if ( is_object( $string ) ) {
            $class_name = get_class( $string );
            if ( strcmp($class_name, 'SQL_Maker_Scalar') === 0 ) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

}


class SQL_Maker_Scalar {
    public $data;

    public function __construct($d) {
        $this->data = $d;
    }

    public function raw() {
        return $this->data;
    }
}