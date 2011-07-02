<?php

require_once('SQL/Maker.php');

class SQL_Maker_Select_Oracle extends SQL_Maker_Select {

    public function asLimit() {
        return '';
    }

    public function asSql() {
        $limit = $this->limit;
        $offset = $this->offset;

        if ( ! is_null($limit) && ! is_null($offset) ) {
            $this->addSelect( SQL_Maker::scalar("ROW_NUMBER() OVER (ORDER BY 1) R") );
        }

        $sql = parent::asSql();

        if ( ! is_null($limit) ) {
            $sql = "SELECT * FROM ( $sql ) WHERE ";
            if ( ! is_null($offset) ) {
                $sql = $sql . " R BETWEEN $offset + 1 AND $limit + $offset";
            } else {
                $sql = $sql . " rownum <= $limit";
            }
        }

        return $sql;
    }
}

