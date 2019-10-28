<?php

namespace Lumen\Curd\Lifecycle;

interface AddBeforeHooks
{
    /**
     * Add pre-processing
     * @return boolean
     */
    public function __addBeforeHooks();
}
