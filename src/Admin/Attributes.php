<?php

namespace Tigon\DmsConnect\Admin;

use Attribute;
use Tigon\DmsConnect\Includes\DMS_Connector;

class Attributes
{
    public static $locations = [
        "T1" => [
            "address" => "2333 Bethlehem Pike",
            "city" => "Hatfield",
            "state" => "Pennsylvania",
            "st" => "PA",
            "zip" => "19440",
            "city_id" => 1149,
            "state_id" => 1148,
            "phone" => "215-595-8736",
            "url" => ""

        ],
        "T2" => [
            "address" => "101 NJ-50",
            "city" => "Ocean View",
            "state" => "New Jersey",
            "st" => "NJ",
            "zip" => "08230",
            "city_id" => 1153,
            "state_id" => 1152,
            "phone" => "609-840-0404",
            "url" => ""
        ],
        "T3" => [
            "address" => "1712 Pennsylvania 940",
            "city" => "Pocono Pines",
            "state" => "Pennsylvania",
            "st" => "PA",
            "zip" => "18350",
            "city_id" => 1151,
            "state_id" => 1148,
            "phone" => "570-643-0152",
            "url" => ""
        ],
        "T4" => [
            "address" => "5158 N Dupont Hwy",
            "city" => "Dover",
            "state" => "Delaware",
            "st" => "DE",
            "zip" => "19901",
            "city_id" => 1155,
            "state_id" => 1154,
            "phone" => "302-546-0010",
            "url" => ""
        ],
        "T5" => [
            "address" => "1225 N Keyser Ave #2",
            "city" => "Scranton Wilkes-Barre",
            "city_short" => "Scranton",
            "state" => "Pennsylvania",
            "st" => "PA",
            "zip" => "18504",
            "city_id" => 3428,
            "state_id" => 1148,
            "phone" => "570-344-4443",
            "url" => ""
        ],
        "T6" => [
            "address" => "2700 S Wilmington St",
            "city" => "Raleigh",
            "state" => "North Carolina",
            "st" => "NC",
            "zip" => "27603",
            "city_id" => 95733,
            "state_id" => 72938,
            "phone" => "984-489-0298",
            "url" => ""
        ],
        "T7" => [
            "address" => "52129 State Road 933",
            "city" => "South Bend",
            "state" => "Indiana",
            "st" => "IN",
            "zip" => "46637",
            "city_id" => 95747,
            "state_id" => 72821,
            "phone" => "574-703-0456",
            "url" => ""
        ],
        "T8" => [
            "address" => "2810 George Washington Memorial Hwy",
            "city" => "Gloucester Point",
            "state" => "Virginia",
            "st" => "VA",
            "zip" => "23072",
            "city_id" => 98579,
            "state_id" => 72816,
            "phone" => "804-792-0234",
            "url" => ""
        ],
        "T9" => [
            "address" => "299 E. Gulf to Lake Hwy",
            "city" => "Lecanto",
            "state" => "Florida",
            "st" => "FL",
            "zip" => "34461",
            "city_id" => 95738,
            "state_id" => 72775,
            "phone" => "352-453-0345",
            "url" => ""
        ],
        "T10" => [
            "address" => "10420 Airport Hwy",
            "city" => "Swanton",
            "state" => "Ohio",
            "st" => "OH",
            "zip" => "43558",
            "city_id" => 95756,
            "state_id" => 72921,
            "phone" => "419-402-8400",
            "url" => ""
        ],
        "T11" => [
            "address" => "4166 North Rd",
            "city" => "Orangeburg",
            "state" => "South Carolina",
            "st" => "SC",
            "zip" => "29118",
            "city_id" => 95725,
            "state_id" => 72873,
            "phone" => "803-596-0246",
            "url" => ""
        ],
        "T12" => [
            "address" => "52129 State Road 933",
            "city" => "South Bend",
            "state" => "Indiana",
            "st" => "IN",
            "zip" => "46637",
            "city_id" => 95747,
            "state_id" => 72821,
            "phone" => "574-703-0456",
            "url" => ""
        ],
        "T13" => [
            "address" => "1101 Virginia Beach Blvd",
            "city" => "Virginia Beach",
            "state" => "Virginia",
            "st" => "VA",
            "zip" => "23451",
            "city_id" => 74871,
            "state_id" => 72816,
            "phone" => "1-844-844-6638",
            "url" => ""
        ]
    ];

    public static function load_custom_locations()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tigon_dms_config';
        $json = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'locations_json'");
        if (!$json) {
            return;
        }

        $custom_locations = json_decode($json, true);
        if (!is_array($custom_locations)) {
            return;
        }

        foreach ($custom_locations as $location_id => $location_data) {
            if (!is_array($location_data)) {
                continue;
            }

            if (!preg_match('/^T\d+$/', $location_id)) {
                continue;
            }

            self::$locations[$location_id] = array_merge(
                self::$locations[$location_id] ?? [],
                [
                    'address' => $location_data['address'] ?? '',
                    'city' => $location_data['city'] ?? ($location_data['city_short'] ?? ''),
                    'city_short' => $location_data['city_short'] ?? null,
                    'state' => $location_data['state'] ?? '',
                    'st' => $location_data['st'] ?? '',
                    'zip' => $location_data['zip'] ?? '',
                    'city_id' => intval($location_data['city_id'] ?? 0),
                    'state_id' => intval($location_data['state_id'] ?? 0),
                    'phone' => $location_data['phone'] ?? '',
                    'url' => $location_data['url'] ?? '',
                    'google_cid' => $location_data['google_cid'] ?? '',
                    'facebook_url' => $location_data['facebook_url'] ?? '',
                    'youtube_url' => $location_data['youtube_url'] ?? '',
                ]
            );
        }
    }

    //Automatically propagated
    public $categories = [];
    public $tags = [];
    public $attributes = [];
    public $tabs = [];
    public $manufacturers_taxonomy = [];
    public $models_taxonomy = [];
    public $sound_systems_taxonomy = [];
    public $vehicle_classes_taxonomy = [];
    public $added_features_taxonomy = [];
    public $drivetrains_taxonomy = [];
    public $inventory_status_taxonomy = [];
    public $custom_options = [];

    public function __construct()
    {
        $this->categories = Attributes::get_categories();
        $this->tags = Attributes::get_tags();
        $this->tabs = Attributes::get_tabs();
        $this->attributes = Attributes::get_attributes();
        $this->manufacturers_taxonomy = Attributes::get_manufacturers();
        $this->models_taxonomy = Attributes::get_models();
        $this->sound_systems_taxonomy = Attributes::get_sound_systems();
        $this->vehicle_classes_taxonomy = Attributes::get_classes();
        $this->added_features_taxonomy = Attributes::get_features();
        $this->drivetrains_taxonomy = Attributes::get_drivetrains();
        $this->inventory_status_taxonomy = Attributes::get_inventory_statuses();
        $this->custom_options = Attributes::get_custom_options();
    }

    /**
     * Gets all current categories, and returns them as an associative array
     * @return array
     */
    private static function get_categories()
    {
        $categories = array();
        $category_list = get_categories([
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ]);
        foreach($category_list as $category) {
            $categories[strtoupper($category->name)] = $category->term_id;
        }
        return $categories;
    }

    /**
     * Gets all current tags, and returns them as an associative array
     * @return array
     */
    private static function get_tags()
    {
        $tags = array();
        $tag_list = get_tags([
            'taxonomy' => 'product_tag',
            'hide_empty' => false
        ]);
        foreach($tag_list as $tag) {
            $tags[strtoupper($tag->name)] = $tag->term_id;
        }
        return $tags;
    }

    /**
     * Gets all current attributes, and returns their id and terms as an associative array
     * "id" => int id
     * string [term name] => int id
     * @return array
     */
    private static function get_attributes()
    {
        $attributes = array();
        $attr_list = wc_get_attribute_taxonomies();
        foreach($attr_list as $attr) {
            $attributes[$attr->attribute_name] = [
                'id' => $attr->attribute_id,
                'label' => $attr->attribute_label,
                'options' => array(),
                'object' => [
                    'name' => 'pa_'.$attr->attribute_name,
                    'value' => '',
                    'position' => 0,
                    'is_visible' => 1,
                    'is_variation' => 0,
                    'is_taxonomy' => 1
                ]
            ];
            foreach(get_terms(array(
                'taxonomy' => 'pa_' . $attr->attribute_name,
                'fields' => 'all',
                'hide_empty' => false
            )) as $term) {
                $attributes[$attr->attribute_name]['options'][strtoupper($term->name)] = $term->term_id;
            }
        }
        return $attributes;
    }

    /**
     * Gets all terms in the manufacturers taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_manufacturers()
    {
        $manufacturers_taxonomy = array();
        $manufacturers = get_terms([
            'taxonomy' => 'manufacturers',
            'hide_empty' => false
        ]);
        foreach($manufacturers as $term) {
            $manufacturers_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $manufacturers_taxonomy;
    }

    /**
     * Gets all terms in the models taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_models()
    {
        $models_taxonomy = array();
        $models = get_terms([
            'taxonomy' => 'models',
            'hide_empty' => false
        ]);
        foreach($models as $term) {
            $models_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $models_taxonomy;
    }

    /**
     * Gets all terms in the models taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_classes()
    {
        $classes_taxonomy = array();
        $classes = get_terms([
            'taxonomy' => 'vehicle-class',
            'hide_empty' => false
        ]);
        foreach($classes as $term) {
            $classes_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $classes_taxonomy;
    }

    /**
     * Gets all terms in the added features taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_features()
    {
        $features_taxonomy = array();
        $features = get_terms([
            'taxonomy' => 'added-features',
            'hide_empty' => false
        ]);
        foreach($features as $term) {
            $features_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $features_taxonomy;
    }

    /**
     * Gets all terms in the sound systems taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_sound_systems()
    {
        $sound_systems_taxonomy = array();
        $sound_systems = get_terms([
            'taxonomy' => 'sound-systems',
            'hide_empty' => false
        ]);
        foreach($sound_systems as $term) {
            $sound_systems_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $sound_systems_taxonomy;
    }

    /**
     * Gets all terms in the drivetrain taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_drivetrains()
    {
        $drivetrain_taxonomy = array();
        $drivetrains = get_terms([
            'taxonomy' => 'drivetrain',
            'hide_empty' => false
        ]);
        foreach($drivetrains as $term) {
            $drivetrain_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $drivetrain_taxonomy;
    }

    /**
     * Gets all terms in the drivetrain taxonomy, and returns them as an associative array
     * @return array
     */
    private static function get_inventory_statuses()
    {
        $inventory_status_taxonomy = array();
        $statuses = get_terms([
            'taxonomy' => 'inventory-status',
            'hide_empty' => false
        ]);
        foreach($statuses as $term) {
            $inventory_status_taxonomy[strtoupper($term->name)] = $term->term_id;
        }
        return $inventory_status_taxonomy;
    }

    /**
     * Gets all saved custom tabs, and returns them as an associative array
     * @return array
     */
    private static function get_tabs() {
        $tabs = array();
        $saved_tabs = get_option( 'yikes_woo_reusable_products_tabs' );
        foreach($saved_tabs as $tab) {
            $tabs[$tab['tab_name']] = [
                'tab_id' => $tab['tab_id'],
                'tab_title' => $tab['tab_title'],
                'tab_content' => $tab['tab_content'],
            ];
        }
        return $tabs;
    }

    /**
     * Gets all saved custom options, and returns them as an associative array
     * @return array
     */
    private static function get_custom_options() {
        $options_list = array();
        $options = get_posts([
            'post_type' => 'wcpa_pt_forms',
            'numberposts' => -1
        ]);
        foreach($options as $term) {
            $options_list[$term->post_title] = $term->ID;
        }
        return $options_list;
    }

    private static function categories_sanitize(string $response) {
        $bom = pack('H*','EFBBBF');
        
        $response = preg_replace("/^$bom/", '', $response);
        $response = preg_replace('/\\\u00ae/', '®', $response);
        $response = stripcslashes($response);
        $response = preg_replace('/[[:cntrl:]]/', '', $response);
        $response = trim($response, "\xEF\xBB\xBF");

        $response = preg_replace('/<((\S+?)(?=[\s>]))[\s\S]+?<\/\1>/', '', $response);
        $response = preg_replace('/"(og_)?description":".+?",/', '', $response);
        $response = preg_replace('/"yoast_head":".+?(?="\_links)/', '', $response);
        $response = preg_replace('/{"id":[0-9]+?,"key":"yikes_woo_products_tabs.+?}"},/', '', $response);
        $response = preg_replace('/"","value":"ECOXGEAR SOUNDEXTREME 8"/', '', $response);
        $response = preg_replace('/"[\[\{\(][\s\S]*?[\]\}\)]"/', '""', $response);

        return $response;
    }

    private static function tags_sanitize(string $response) {
        $response = preg_replace('/\\\u00ae/', '®', $response);
        $response = stripcslashes($response);
        $response = preg_replace('/[[:cntrl:]]/', '', $response);
        $response = trim($response, "\xEF\xBB\xBF");

        $response = preg_replace('/<.+?>/', '', $response);
        // $response = preg_replace('/"(og_)?description":".+?",/', '', $response);
        $response = preg_replace('/("yoast_head":").+?(?="yoast_head)/', '', $response);

        return $response;
    }
}
