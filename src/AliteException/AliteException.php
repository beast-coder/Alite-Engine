<?php

namespace Alite\AliteException;

class AliteException {

    public function __construct($msg = "") {
        throw new \Exception($msg);
    }

}
