<?php

namespace Lumen\Curd\Lifecycle;

interface OriginListsBeforeHooks
{
    /**
     * List data acquisition preprocessing
     * @return boolean
     */
    public function __originListsBeforeHooks();
}
