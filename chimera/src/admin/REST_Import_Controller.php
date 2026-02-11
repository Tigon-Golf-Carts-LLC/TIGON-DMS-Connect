<?php

namespace Tigon\Chimera\Admin;

use ErrorException;
use WP_Error;
use Tigon\Chimera\Admin\Database_Object;
use Tigon\Chimera\Admin\Database_Write_Controller;

abstract class REST_Import_Controller extends \Tigon\Chimera\Abstracts\Abstract_Import_Controller
{
    private function __construct()
    {
    }
}
