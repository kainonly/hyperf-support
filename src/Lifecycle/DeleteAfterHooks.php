<?php

namespace Lumen\Curd\Lifecycle;

interface DeleteAfterHooks
{
    /**
     * Delete post processing
     * @return mixed
     */
    public function __deleteAfterHooks();
}
