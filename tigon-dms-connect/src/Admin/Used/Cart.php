<?php

namespace Tigon\DmsConnect\Admin\Used;

use WP_Error;

class Cart extends \Tigon\DmsConnect\Abstracts\Abstract_Cart
{
    /**
     * Check if input cart is valid
     *
     * @return true|WP_Error
     */
    protected function verify_data()
    {
        $this->sku = $this->cart['vinNo'] ? $this->cart['vinNo'] : $this->cart['serialNo'];
        if (!$this->sku && !$this->cart['pid'])
            return new WP_Error($this->cart['_id'] . ' No serialNo or vinNo defined.');

        return true;
    }

    protected function field_overrides()
    {
    }
}
