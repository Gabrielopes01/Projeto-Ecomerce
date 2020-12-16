<?php

namespace Classes;

class Model{

    private $values = [];


    public function __call($name, $args){   //Metodo invocado e os valores passados nos parenteses

        $method = substr($name, 0, 3);  //Peuqeu os caracteres 0,1,2 (3 espaços)
        $fieldname = substr($name, 3, strlen($name));

        switch ($method) {
            case "get":
                return $this->values[$fieldname];
            break;

            case "set":
                $this->values[$fieldname] = $args[0];
            break;

        }
    }

    public function setData($data = array())
    {

        foreach ($data as $key => $value) {

            $this->{"set".$key}($value);

        }


    }

    public function getValues()
    {

        return $this->values;

    }

}

?>