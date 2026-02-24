<?php

namespace Tigon\DmsConnect\Admin;

class Ajax_Settings_Controller
{
    function __construct()
    {
    }

    public static function save_settings($input)
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);
            global $wpdb;
            $table_name = $wpdb->prefix . 'tigon_dms_config';

           // Data from AJAX request
            // AJAX produces unwanted slashes
            $github_token = stripcslashes($_REQUEST['data']['github_token']);
            
            $dms_url = strtolower(stripcslashes($_REQUEST['data']['dms_url']));
            if($dms_url && substr($dms_url,0,4) !== 'http') $dms_url = 'https://'.$dms_url;
            if(substr($dms_url,-1) === '/') $dms_url = substr_replace($dms_url,'',-1);

            $user_token = stripcslashes($_REQUEST['data']['user_token']);

            $file_source = stripcslashes($_REQUEST['data']['file_source']);
            if($file_source && substr($file_source,0,4) !== 'http') $file_source = 'https://'.$file_source;
            if(substr($file_source,-1) === '/') $file_source = substr_replace($file_source,'',-1);

            // Ensure rows exist
            // Ensure rows exist — use COUNT(1) instead of SELECT * for efficiency
            $required_options = ['github_token', 'dms_url', 'user_token', 'auth_token', 'file_source'];
            $placeholders = implode(',', array_fill(0, count($required_options), '%s'));
            $existing_options = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM $table_name WHERE option_name IN ($placeholders)",
                    $required_options
                )
            );
            $existing_set = array_flip($existing_options);
            foreach ($required_options as $opt) {
                if (!isset($existing_set[$opt])) {
                    $wpdb->insert($table_name, ['option_name' => $opt]);
                }
            }

            // Write setting to DB
            if(!empty($github_token)) {
                $wpdb->update($table_name, ['option_value' => $github_token], ['option_name' => 'github_token']);
            }
            if(!empty($dms_url)){
                $wpdb->update($table_name, ['option_value' => $dms_url], ['option_name' => 'dms_url']);
                $wpdb->update($table_name, ['option_value' => ''], ['option_name' => 'auth_token']);
            }
            if(!empty($user_token)) {
                $wpdb->update($table_name, ['option_value' => $user_token], ['option_name' => 'user_token']);
            }
            if(!empty($file_source)) {
                $wpdb->update($table_name, ['option_value' => $file_source], ['option_name' => 'file_source']);
            }

            echo true;
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        exit;
    }

    public static function get_dms_props()
    {
        $boolean_svg = preg_replace(
            '/#000000/',
            '#333333',
            file_get_contents(__DIR__ . '/../../assets/images/boolean.svg')
        );
        $string_svg = preg_replace(
            '/#000000/',
            '#333333',
            file_get_contents(__DIR__ . '/../../assets/images/string.svg')
        );
        $number_svg = preg_replace(
            '/#000000/',
            '#333333',
            file_get_contents(__DIR__ . '/../../assets/images/number.svg')
        );
        

        echo '
        <ul id="myUL">
            <li><span class="caret">Cart Type</span>
                <ul class="nested">
                    <li class="dms-value" code="{make}">'.$string_svg.'Make</li>
                    <li class="dms-value" code="{model}">'.$string_svg.'Model</li>
                    <li class="dms-value" code="{year}">'.$number_svg.'Year</li>
                </ul>
            </li>
            <li class="dms-value" code="{retailPrice}">'.$number_svg.'Retail Price</li>
            <li class="dms-value" code="{isElectric}">'.$boolean_svg.'Is Electric</li>
            <li><span class="caret">Cart Attributes</span>
                <ul class="nested">
                    <li class="dms-value" code="{cartColor}">'.$string_svg.'Cart Color</li>
                    <li class="dms-value" code="{seatColor}">'.$string_svg.'Seat Color</li>
                    <li class="dms-value" code="{tireRimSize}">'.$number_svg.'Tire Rim Size</li>
                    <li class="dms-value" code="{tireType}">'.$string_svg.'Tire Type</li>
                    <li class="dms-value" code="{hasSoundSystem}">'.$boolean_svg.'Has Sound System</li>
                    <li class="dms-value" code="{isLifted}">'.$boolean_svg.'Is Lifted</li>
                    <li class="dms-value" code="{hasHitch}">'.$boolean_svg.'Has Hitch</li>
                    <li class="dms-value" code="{hasExtended Top}">'.$boolean_svg.'Has Extended Top</li>
                    <li class="dms-value" code="{passengers}">'.$number_svg.'Passengers</li>
                </ul>
            </li>
            <li><span class="caret">Battery</span>
                <ul class="nested">
                    <li class="dms-value" code="{isDC}">'.$boolean_svg.'Is DC</li>
                    <li class="dms-value" code="{year}">'.$number_svg.'Year</li>
                    <li class="dms-value" code="{brand}">'.$string_svg.'Brand</li>
                    <li class="dms-value" code="{batteryType}">'.$string_svg.'Battery Type</li>
                    <li class="dms-value" code="{serialNumber}">'.$string_svg.'Serial Number</li>
                    <li class="dms-value" code="{ampHours}">'.$number_svg.'Amp Hours</li>
                    <li class="dms-value" code="{batteryVoltage}">'.$number_svg.'Battery Voltage</li>
                    <li class="dms-value" code="{packVoltage}">'.$number_svg.'Pack Voltage</li>
                    <li class="dms-value" code="{warrantyLength}">'.$number_svg.'Warranty Length</li>
                </ul>
            </li>
            <li><span class="caret">Engine</span>
                <ul class="nested">
                    <li class="dms-value" code="{make}">'.$string_svg.'Make</li>
                    <li class="dms-value" code="{model}">'.$string_svg.'Model</li>
                    <li class="dms-value" code="{horsepower}">'.$number_svg.'Horsepower</li>
                    <li class="dms-value" code="{stroke}">'.$number_svg.'Stroke</li>
                </ul>
            </li>
            <li><span class="caret">Cart Location</span>
                <ul class="nested">
                    <li class="dms-value" code="{locationId}">'.$string_svg.'Location ID</li>
                    <li class="dms-value" code="{city}">'.$string_svg.'City</li>
                    <li class="dms-value" code="{state}">'.$string_svg.'State</li>
                    <li class="dms-value" code="{stateAbbr}">'.$string_svg.'State Abbreviation</li>
                </ul>
            </li>
            <li class="dms-value" code="{transferLocation}">'.$string_svg.'Transfer Location</li>
            <li class="dms-value" code="{serialNumber}">'.$string_svg.'Serial Number</li>
            <li class="dms-value" code="{vinNumber}">'.$string_svg.'VIN Number</li>
            <li><span class="caret">Title</span>
                <ul class="nested">
                    <li class="dms-value" code="{isStreetLegal}">'.$boolean_svg.'Is Street Legal</li>
                    <li class="dms-value" code="{isTitleInPossesion}">'.$boolean_svg.'Is Title In Possesion</li>
                    <li class="dms-value" code="{storeId}">'.$boolean_svg.'Store ID</li>
                </ul>
            </li>
            <li><span class="caret">RFS Status</span>
                <ul class="nested">
                    <li class="dms-value" code="{isRfs}">'.$boolean_svg.'Is RFS</li>
                    <li class="dms-value" code="{notRfsOption}">'.$boolean_svg.'Not RFS Option</li>
                    <li class="dms-value" code="{notRfsDescription}">'.$string_svg.'Not RFS Description</li>
                </ul>
            </li>
            <li class="dms-value" code="{inventoryTimestamp}">'.$number_svg.'Inventory Timestamp</li>
            <li class="dms-value" code="{serviceTimestamp}">'.$number_svg.'Service Timestamp</li>
            <li class="dms-value" code="{invoiceNo}">'.$number_svg.'Invoice No</li>
            <li><span class="caret">Floor Planned</span>
                <ul class="nested">
                    <li class="dms-value" code="{isFloorPlanned}">'.$boolean_svg.'Is Floor Planned</li>
                    <li class="dms-value" code="{floorPlannedTimestamp}">'.$boolean_svg.'Floor Planned Timestamp</li>
                </ul>
            </li>
            <li class="dms-value" code="{currentOwner}">'.$string_svg.'Current Owner</li>
            <li><span class="caret">Trade In Info</span>
                <ul class="nested">
                    <li class="dms-value" code="{customerId}">'.$number_svg.'Customer ID</li>
                    <li class="dms-value" code="{value}">'.$number_svg.'Value</li>
                    <li class="dms-value" code="{timestamp}">'.$number_svg.'Timestamp</li>
                </ul>
            </li>
            <li><span class="caret">Overhead Cost</span>
                <ul class="nested">
                    <li class="dms-value" code="{cartCost}">'.$number_svg.'Cart Cost</li>
                    <li class="dms-value" code="{shippingCost}">'.$number_svg.'Shipping Cost</li>
                    <li class="dms-value" code="{isvCost}">'.$number_svg.'ISV Cost</li>
                </ul>
            </li>
            <li class="dms-value" code="{isUsed}">'.$boolean_svg.'Is Used</li>
            <li class="dms-value" code="{isOnLot}">'.$boolean_svg.'Is On Lot</li>
            <li class="dms-value" code="{isDelivered}">'.$boolean_svg.'Is Delivered</li>
            <li class="dms-value" code="{odometer}">'.$number_svg.'Odometer</li>
            <li class="dms-value" code="{isService}">'.$boolean_svg.'Is Service</li>
            <li class="dms-value" code="{isInBoneyard}">'.$boolean_svg.'Is In Boneyard</li>
            <li class="dms-value" code="{isInStock}">'.$boolean_svg.'Is In Stock</li>
            <li class="dms-value" code="{warrantyLength}">'.$number_svg.'Warranty Length</li>
            <li class="dms-value" code="{isComplete}">'.$boolean_svg.'Is Complete</li>
            <li class="dms-value" code="{imageURLs}">'.$string_svg.'Image URLs</li>
            <li class="dms-value" code="{categories}">'.$string_svg.'Categories</li>
            <li class="dms-value" code="{internalCartImageUrls}">'.$string_svg.'Internal Cart Image URLs</li>
            <li><span class="caret">Advertising</span>
                <ul class="nested">
                    <li class="dms-value" code="{websiteUrl}">'.$string_svg.'Website URL</li>
                    <li class="dms-value" code="{onWebsite}">'.$boolean_svg.'On Website</li>
                    <li class="dms-value" code="{needOnWebsite}">'.$boolean_svg.'Need On Website</li>
                    <li><span class="caret">Facebook Accounts</span>
                        <ul class="nested">
                            <li class="dms-value" code="{accountName}">'.$string_svg.'Account Name</li>
                            <li class="dms-value" code="{postingDate}">'.$number_svg.'Posting Date</li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li class="dms-value" code="{pid}">'.$number_svg.'PID</li>
        </ul> 
        ';
        exit;
    }
}