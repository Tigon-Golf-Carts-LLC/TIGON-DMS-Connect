<?php

namespace Tigon\DmsConnect\Admin;

use ErrorException;
use WP_Error;
use Tigon\DmsConnect\Admin\Database_Object;
use Tigon\DmsConnect\Admin\Database_Write_Controller;

abstract class REST_Import_Controller extends \Tigon\DmsConnect\Abstracts\Abstract_Import_Controller
{
    private function __construct()
    {
    }
}
