<?php

require_once('SQL/Maker/Select.php');

class SQL_Maker_Select_Oracle extends SQL_Maker_Select {

    public function asLimit() {
        return '';
    }

    public function asSql($stmt) {
        $limit = $stmt->limit;
        $offset = $stmt->offset;

        if ( ! is_null($limit) && ! is_null($offset) ) {
            $stmt->addSelect( "ROW_NUMBER() OVER (ORDER BY 1) R" );
        }

        $sql = $stmt->asSql($args);

        if ( ! is_null($limit) ) {
            $sql = "SELECT * FROM ( $sql ) WHERE ";
            if ( ! is_null($offset) ) {
                $sql = $sql . " $ BETWEEN $offset + 1 AND $limit + $offset";
            } else {
                $sql = $sql . " rownum <= $limit";
            }
        }
        return $sql;
    }
}

