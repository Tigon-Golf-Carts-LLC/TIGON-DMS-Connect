<?php

namespace Tigon\DmsConnect\Admin\New;

use ErrorException;
use Tigon\DmsConnect\Admin\Attributes;

use Tigon\DmsConnect\Core;
use WP_Error;

class Cart extends \Tigon\DmsConnect\Abstracts\Abstract_Cart
{
    private $not_default = false;

    /**
     * Check if input cart is valid
     *
     * @return true|\WP_Error
     */
    protected function verify_data()
    {
        if (empty($this->cart['cartType']['make']) && !empty($this->cart['manufacturerMd'])) {
            $this->cart['cartType']['make'] = $this->cart['manufacturerMd'];
        }
        if (empty($this->cart['cartType']['model']) && !empty($this->cart['modelMd'])) {
            $this->cart['cartType']['model'] = $this->cart['modelMd'];
        }

        $this->cart['cartType']['make'] = $this->cart['cartType']['make'] ?? 'Tigon';
        $this->cart['cartType']['model'] = $this->cart['cartType']['model'] ?? 'Golf Cart';
        $this->cart['cartAttributes']['cartColor'] = $this->cart['cartAttributes']['cartColor'] ?? 'Unknown';
        $this->cart['cartAttributes']['seatColor'] = $this->cart['cartAttributes']['seatColor'] ?? 'Unknown';

        $resolved_location_id = Attributes::resolve_location_id($this->cart['cartLocation'] ?? []);
        $this->cart['cartLocation']['locationId'] = $resolved_location_id;
        $location = Attributes::$locations[$resolved_location_id] ?? [];
        $location_city = $location['city'] ?? 'Tigon';

        $this->sku = strtoupper(
            preg_replace('/\s/', '', substr((string)$this->cart['cartType']['make'], 0, 3)) .
            preg_replace('/\s/', '', substr((string)$this->cart['cartType']['model'], 0, 3)) .
            preg_replace('/\s/', '', substr((string)$this->cart['cartAttributes']['cartColor'], 0, 3)) .
            preg_replace('/\s/', '', substr((string)$this->cart['cartAttributes']['seatColor'], 0, 3)) .
            preg_replace('/\s/', '', substr((string)$location_city, 0, 3))
        );

        if (!empty($this->cart['isInStock']) && empty($this->cart['isInBoneyard'])) {
            if (!empty($this->cart['serialNo'])) {
                $this->sku = preg_replace('/\s/', '', (string)$this->cart['serialNo']);
                $this->not_default = true;
            }
            if (!empty($this->cart['vinNo'])) {
                $this->sku = preg_replace('/\s/', '', (string)$this->cart['vinNo']);
                $this->not_default = true;
            }
        }

        if(!$this->not_default) {
            $this->cart['_id'] = null;
        }

        return true;
    }
    
    /**
     * Initialize Slug,
     * define brand_hyphenated, pattern_hyphenated, color_hyphenated, location_hyphenated
     *
     * @return void
     */
    protected function create_slug()
    {
        $make = (string)($this->cart['cartType']['make'] ?? $this->cart['manufacturerMd'] ?? 'Tigon');
        $model = (string)($this->cart['cartType']['model'] ?? $this->cart['modelMd'] ?? 'Golf Cart');
        $cart_color = (string)($this->cart['cartAttributes']['cartColor'] ?? 'Unknown');
        $seat_color = (string)($this->cart['cartAttributes']['seatColor'] ?? 'Unknown');

        $location = Attributes::$locations[$this->location_id] ?? [];
        $location_city = (string)($location['city'] ?? 'Tigon');
        $location_st = (string)($location['st'] ?? 'FL');

        $this->brand_hyphenated = preg_replace('/\s+/', '-', $make);
        $this->pattern_hyphenated = preg_replace('/\s+/', '-', $model);
        $this->color_hyphenated = preg_replace('/\s+/', '-', $cart_color);
        $this->seat_color_hyphenated = preg_replace('/\s+/', '-', $seat_color);
        $this->location_hyphenated = preg_replace('/\s+/', '-', $location_city . "-" . $location_st);

        $website_url = trim((string)($this->cart['advertising']['websiteUrl'] ?? ''));
        if($website_url !== '' && $website_url !== 'default') {
            $path = parse_url($website_url, PHP_URL_PATH);
            $slug = basename(rtrim((string)$path, '/'));
            $slug = preg_replace('/\+/', '-plus-', $slug);
            $this->slug = $slug ?: strtolower(implode('-', [
                $this->brand_hyphenated,
                $this->pattern_hyphenated,
                $this->color_hyphenated,
                'seat',
                $this->seat_color_hyphenated,
                $this->location_hyphenated
            ]));
        } else {
            $this->slug = strtolower(implode('-', [
                $this->brand_hyphenated,
                $this->pattern_hyphenated,
                $this->color_hyphenated,
                'seat',
                $this->seat_color_hyphenated,
                $this->location_hyphenated
            ]));
        }
        // throw new ErrorException($this->slug);
    }

    /**
     * Sideload and/or Initialize images
     *
     * @return void
     */
    protected function fetch_images()
    {
        $this->images = null;
    }

    protected function generate_image_name($i)
    {
        return 'woocommerce-placeholder';
    }

    protected function field_overrides()
    {
        $this->published = 'draft';
        if (!$this->not_default) {
            $this->in_stock = 'outofstock';
            $this->monroney_sticker = '';
        }
    }
}
