<?php

namespace Lumen\Curd\Lifecycle;

interface EditAfterHooks
{
    /**
     * Modify post processing
     * @return mixed
     */
    public function __editAfterHooks();
}
