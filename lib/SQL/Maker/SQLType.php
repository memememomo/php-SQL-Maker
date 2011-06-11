<?php

class SQL_Maker_SQLType {
    private $value_ref, $type;

    static public function sqlType($value_ref, $type) {
        return new SQL_Maker_SQLType(
                                     array(value_ref => $value_ref,
                                           type => $type
                                           )
                                     );
    }

    public function __construct($args) {
        $this->value_ref = $args['value_ref'];
        $this->type = $args['type'];
    }

    public function valueRef() {
        return $this->value_ref;
    }

    public function type() {
        return $this->type;
    }
}

