<?php

namespace cigoadmin;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register()
    {
        $this->commands([
            'cigoadmin:install'  => '\\cigoadmin\\command\\Install',
        ]);
    }
}
