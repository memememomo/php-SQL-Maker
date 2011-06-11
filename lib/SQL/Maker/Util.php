<?php

class SQL_Maker_Util {
    static public function quoteIdentifier($label, $quote_char, $name_sep) {
        if (strcmp($label, '*') == 0) {
            return $label;
        }

        if ( ! $name_sep ){
            return $label;
        }

        $new_list = array();
        $list = explode($name_sep, $label);
        foreach ($list as $l) {
            $new_list[] = $quote_char . $l . $quote_char;
        }
        return implode($name_sep, $new_list);
    }
}
