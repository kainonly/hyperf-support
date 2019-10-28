<?php

namespace Lumen\Curd\Lifecycle;

interface ListsBeforeHooks
{
    /**
     * Paging data acquisition preprocessing
     * @return boolean
     */
    public function __listsBeforeHooks();
}
