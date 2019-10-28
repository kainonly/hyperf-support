<?php

namespace Lumen\Curd\Lifecycle;

interface EditBeforeHooks
{
    /**
     * Modify preprocessing
     * @return boolean
     */
    public function __editBeforeHooks();
}
