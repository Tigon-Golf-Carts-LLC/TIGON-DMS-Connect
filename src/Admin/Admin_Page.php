<?php

namespace Tigon\DmsConnect\Admin;

use Tigon\DmsConnect\Includes\DMS_Connector;
use Tigon\DmsConnect\Includes\Product_Fields;

class Admin_Page
{

    private function __construct()
    {
    }

    /**
     * Add Tigon DMS Connect to Admin Sidebar
     * @return void
     */
    public static function add_menu_page()
    {
        $svg = file_get_contents(TIGON_DMS_PLUGIN_DIR . 'assets/images/tigondb.svg');
        $data_uri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        $page_title = "Tigon DMS Connect";
        $menu_title = "DMS Connect";
        $capability = "manage_options";
        $menu_slug = "tigon-dms-connect";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::diagnostic_page';
        $icon_url = $data_uri;
        $position = 55;
        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url, $position);
        self::add_import_page();
        self::add_settings_page();
        self::add_database_objects_page();
        self::add_field_mapping_page();
        self::add_sync_page();
    }

    /**
     * Add Import submenu
     * @return void
     */
    public static function add_import_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title = "Tigon DMS Import";
        $menu_title = "Import";
        $capability = "manage_options";
        $menu_slug = "import";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::import_page';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    /**
     * Add Settings submenu
     * @return void
     */
    public static function add_settings_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title = "Tigon DMS Settings";
        $menu_title = "Settings";
        $capability = "manage_options";
        $menu_slug = "settings";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::settings_page';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    /**
     * Add Database Objects submenu
     *
     * @return void
     */
    public static function add_database_objects_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title  = "DMS Database Objects";
        $menu_title  = "Database Objects";
        $capability  = "manage_options";
        $menu_slug   = "database-objects";
        $callback    = 'Tigon\DmsConnect\Admin\Admin_Page::database_objects_page';

        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    /**
     * Add Field Mapping submenu
     */
    public static function add_field_mapping_page()
    {
        add_submenu_page(
            'tigon-dms-connect',
            'DMS Field Mapping',
            'Field Mapping',
            'manage_options',
            'field-mapping',
            'Tigon\DmsConnect\Admin\Admin_Page::field_mapping_page'
        );
    }

    /**
     * Add Sync submenu
     * @return void
     */
    public static function add_sync_page()
    {
        add_submenu_page(
            'tigon-dms-connect',
            'DMS Inventory Sync',
            'Sync',
            'manage_options',
            'dms-inventory-sync',
            'Tigon\DmsConnect\Admin\Admin_Page::sync_page'
        );
    }

    /**
     * Field Mapping admin page.
     *
     * Displays the DMS → WooCommerce field mapping editor.
     * Users can browse all DMS payload paths, select WooCommerce targets,
     * choose transforms, and save persistent mapping rules.
     */
    public static function field_mapping_page()
    {
        // Ensure table exists (handles upgrades from older versions)
        \Tigon\DmsConnect\Admin\Field_Mapping::install();

        $nonce = wp_create_nonce('tigon_dms_field_mapping_nonce');
        $mappings    = \Tigon\DmsConnect\Admin\Field_Mapping::get_all();
        $dms_fields  = \Tigon\DmsConnect\Admin\Field_Mapping::get_known_dms_fields();
        $woo_targets = \Tigon\DmsConnect\Admin\Field_Mapping::get_known_woo_targets();

        $transforms = [
            'direct'        => 'Direct (pass-through)',
            'uppercase'     => 'UPPERCASE',
            'lowercase'     => 'lowercase',
            'ucwords'       => 'Ucwords (Title Case)',
            'boolean_yesno' => 'Boolean &rarr; Yes/No',
            'boolean_label' => 'Boolean &rarr; Custom Labels',
            'prefix'        => 'Prefix (prepend text)',
            'suffix'        => 'Suffix (append text)',
            'template'      => 'Template ({value} placeholder)',
            'static'        => 'Static Value',
        ];

        self::page_header();

        echo '
        <div class="body" style="display:flex; flex-direction:column;">
            <div class="action-box-group">
                <div class="action-box primary" style="flex-direction:column; gap:1rem; flex:1;">
                    <h2>DMS &rarr; WooCommerce Field Mapping</h2>
                    <p>Map DMS API payload fields to WordPress/WooCommerce fields. These mappings are applied during import and sync operations.</p>

                    <div id="tigon-mapping-editor">
                        <table class="widefat striped" id="tigon-mapping-table">
                            <thead>
                                <tr>
                                    <th style="width:30px;">#</th>
                                    <th>DMS Field Path</th>
                                    <th>&rarr;</th>
                                    <th>Target Type</th>
                                    <th>WooCommerce Target</th>
                                    <th>Transform</th>
                                    <th>Config</th>
                                    <th style="width:60px;">Enabled</th>
                                    <th style="width:100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tigon-mapping-rows">';

        if (empty($mappings)) {
            echo '
                                <tr id="tigon-no-mappings-row">
                                    <td colspan="9" style="text-align:center; padding:2rem;">
                                        No custom field mappings configured yet. Add one below or use the defaults.<br>
                                        <small>The built-in mapping logic (categories, attributes, pricing, images) continues to work without custom mappings.</small>
                                    </td>
                                </tr>';
        } else {
            foreach ($mappings as $m) {
                $mid = intval($m['mapping_id']);
                $enabled_checked = $m['is_enabled'] ? 'checked' : '';
                echo '
                                <tr data-mapping-id="' . $mid . '">
                                    <td>' . $mid . '</td>
                                    <td><code>' . esc_html($m['dms_path']) . '</code></td>
                                    <td>&rarr;</td>
                                    <td>' . esc_html($m['target_type']) . '</td>
                                    <td><code>' . esc_html($m['woo_target']) . '</code></td>
                                    <td>' . esc_html($m['transform']) . '</td>
                                    <td><code>' . esc_html($m['transform_cfg']) . '</code></td>
                                    <td><input type="checkbox" ' . $enabled_checked . ' disabled /></td>
                                    <td>
                                        <button class="button button-small tigon-edit-mapping" data-id="' . $mid . '">Edit</button>
                                        <button class="button button-small tigon-delete-mapping" data-id="' . $mid . '" style="color:#a00;">Del</button>
                                    </td>
                                </tr>';
            }
        }

        echo '
                            </tbody>
                        </table>
                    </div>

                    <hr style="margin:1rem 0;">
                    <h3 id="tigon-form-title">Add New Mapping</h3>
                    <div class="tigon-mapping-form" style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.5rem; max-width:800px;">
                        <input type="hidden" id="tigon-mapping-id" value="0" />

                        <div>
                            <label><strong>DMS Field Path</strong></label><br>
                            <select id="tigon-dms-path" style="width:100%;">
                                <option value="">— Select DMS field —</option>';
        foreach ($dms_fields as $f) {
            echo '<option value="' . esc_attr($f) . '">' . esc_html($f) . '</option>';
        }
        echo '
                                <option value="__custom__">Custom path…</option>
                            </select>
                            <input type="text" id="tigon-dms-path-custom" placeholder="e.g. myCustom.nested.field" style="width:100%; display:none; margin-top:4px;" />
                        </div>

                        <div>
                            <label><strong>Target Type</strong></label><br>
                            <select id="tigon-target-type" style="width:100%;">
                                <option value="postmeta">Post Meta</option>
                                <option value="post">Post Field</option>
                                <option value="taxonomy">Taxonomy</option>
                            </select>
                        </div>

                        <div>
                            <label><strong>WooCommerce Target</strong></label><br>
                            <select id="tigon-woo-target" style="width:100%;">';
        // Default to postmeta options
        echo '<option value="">— Select target —</option>';
        foreach ($woo_targets['postmeta'] as $t) {
            echo '<option value="' . esc_attr($t) . '">' . esc_html($t) . '</option>';
        }
        echo '
                                <option value="__custom__">Custom key…</option>
                            </select>
                            <input type="text" id="tigon-woo-target-custom" placeholder="e.g. _my_custom_meta" style="width:100%; display:none; margin-top:4px;" />
                        </div>

                        <div>
                            <label><strong>Transform</strong></label><br>
                            <select id="tigon-transform" style="width:100%;">';
        foreach ($transforms as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . $label . '</option>';
        }
        echo '
                            </select>
                        </div>

                        <div style="grid-column:span 2;">
                            <label><strong>Transform Config</strong> <small>(optional — used by prefix/suffix/template/boolean_label/static)</small></label><br>
                            <input type="text" id="tigon-transform-cfg" style="width:100%;" placeholder="e.g. {value} Amp Hours, or [&quot;ELECTRIC&quot;,&quot;GAS&quot;]" />
                        </div>

                        <div>
                            <label>
                                <input type="checkbox" id="tigon-is-enabled" checked />
                                <strong>Enabled</strong>
                            </label>
                        </div>

                        <div style="text-align:right;">
                            <button class="button button-secondary" id="tigon-cancel-edit" style="display:none;">Cancel</button>
                            <button class="button button-primary" id="tigon-save-mapping">Save Mapping</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-box-group" style="margin-top:1.5rem;">
                <div class="action-box primary" style="flex:1; flex-direction:column; gap:0.75rem;">
                    <h2>DMS Payload Field Reference</h2>
                    <p>These are the known fields in the DMS API response. Use these paths in the mapping above.</p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:0.5rem;">';

        $groups = [
            'Cart Type'    => array_filter($dms_fields, fn($f) => str_starts_with($f, 'cartType.')),
            'Attributes'   => array_filter($dms_fields, fn($f) => str_starts_with($f, 'cartAttributes.')),
            'Battery'      => array_filter($dms_fields, fn($f) => str_starts_with($f, 'battery.')),
            'Engine'       => array_filter($dms_fields, fn($f) => str_starts_with($f, 'engine.')),
            'Location'     => array_filter($dms_fields, fn($f) => str_starts_with($f, 'cartLocation.')),
            'Title/Legal'  => array_filter($dms_fields, fn($f) => str_starts_with($f, 'title.')),
            'Advertising'  => array_filter($dms_fields, fn($f) => str_starts_with($f, 'advertising.')),
            'Top-Level'    => array_filter($dms_fields, fn($f) => !str_contains($f, '.')),
        ];

        foreach ($groups as $group_name => $fields) {
            echo '<div style="background:#f8f9fa; padding:0.75rem; border-radius:4px;">
                    <strong>' . esc_html($group_name) . '</strong><br>';
            foreach ($fields as $f) {
                echo '<code style="font-size:0.85em;">' . esc_html($f) . '</code><br>';
            }
            echo '</div>';
        }

        echo '
                    </div>
                </div>

                <div class="action-box primary" style="flex:1; flex-direction:column; gap:0.75rem;">
                    <h2>WooCommerce Target Reference</h2>
                    <p>Available WooCommerce fields grouped by type.</p>';

        foreach ($woo_targets as $type => $targets) {
            echo '<div style="background:#f8f9fa; padding:0.75rem; border-radius:4px; margin-bottom:0.5rem;">
                    <strong>' . esc_html(ucfirst($type)) . '</strong><br>';
            foreach ($targets as $t) {
                echo '<code style="font-size:0.85em;">' . esc_html($t) . '</code><br>';
            }
            echo '</div>';
        }

        echo '
                </div>
            </div>
        </div>

        <script>
        (function($) {
            var nonce = ' . wp_json_encode($nonce) . ';
            var wooTargets = ' . wp_json_encode($woo_targets) . ';

            // Toggle custom DMS path input
            $("#tigon-dms-path").on("change", function() {
                $("#tigon-dms-path-custom").toggle($(this).val() === "__custom__");
            });

            // Toggle custom WooCommerce target input
            $("#tigon-woo-target").on("change", function() {
                $("#tigon-woo-target-custom").toggle($(this).val() === "__custom__");
            });

            // Update WooCommerce target dropdown based on target type
            $("#tigon-target-type").on("change", function() {
                var type = $(this).val();
                var targets = wooTargets[type] || [];
                var $select = $("#tigon-woo-target");
                $select.empty().append(\'<option value="">— Select target —</option>\');
                targets.forEach(function(t) {
                    $select.append(\'<option value="\' + t + \'">\' + t + \'</option>\');
                });
                $select.append(\'<option value="__custom__">Custom key…</option>\');
                $("#tigon-woo-target-custom").hide().val("");
            });

            // Save mapping
            $("#tigon-save-mapping").on("click", function() {
                var dmsPath = $("#tigon-dms-path").val();
                if (dmsPath === "__custom__") dmsPath = $("#tigon-dms-path-custom").val();
                var wooTarget = $("#tigon-woo-target").val();
                if (wooTarget === "__custom__") wooTarget = $("#tigon-woo-target-custom").val();

                if (!dmsPath || !wooTarget) {
                    alert("Please select both a DMS field and a WooCommerce target.");
                    return;
                }

                var data = {
                    action: "tigon_dms_save_field_mapping",
                    nonce: nonce,
                    mapping_id: $("#tigon-mapping-id").val(),
                    dms_path: dmsPath,
                    woo_target: wooTarget,
                    target_type: $("#tigon-target-type").val(),
                    transform: $("#tigon-transform").val(),
                    transform_cfg: $("#tigon-transform-cfg").val(),
                    is_enabled: $("#tigon-is-enabled").is(":checked") ? 1 : 0,
                    sort_order: 0
                };

                $.post(globals.ajaxurl, data, function(resp) {
                    if (resp.success) {
                        location.reload();
                    } else {
                        alert("Error: " + (resp.data || "Unknown error"));
                    }
                });
            });

            // Delete mapping
            $(document).on("click", ".tigon-delete-mapping", function() {
                if (!confirm("Delete this mapping?")) return;
                var id = $(this).data("id");
                $.post(globals.ajaxurl, {
                    action: "tigon_dms_delete_field_mapping",
                    nonce: nonce,
                    mapping_id: id
                }, function(resp) {
                    if (resp.success) location.reload();
                    else alert("Error deleting mapping");
                });
            });

            // Edit mapping — populate form
            $(document).on("click", ".tigon-edit-mapping", function() {
                var $row = $(this).closest("tr");
                var id = $(this).data("id");

                $("#tigon-mapping-id").val(id);
                $("#tigon-form-title").text("Edit Mapping #" + id);
                $("#tigon-cancel-edit").show();

                // Parse values from the table row cells
                var cells = $row.find("td");
                var dmsPath    = cells.eq(1).text().trim();
                var targetType = cells.eq(3).text().trim();
                var wooTarget  = cells.eq(4).text().trim();
                var transform  = cells.eq(5).text().trim();
                var cfg        = cells.eq(6).text().trim();
                var enabled    = cells.eq(7).find("input").is(":checked");

                // Set target type first to trigger option rebuild
                $("#tigon-target-type").val(targetType).trigger("change");

                // Set DMS path
                if ($("#tigon-dms-path option[value=\'" + dmsPath + "\']").length) {
                    $("#tigon-dms-path").val(dmsPath);
                    $("#tigon-dms-path-custom").hide();
                } else {
                    $("#tigon-dms-path").val("__custom__");
                    $("#tigon-dms-path-custom").show().val(dmsPath);
                }

                // Set WooCommerce target (after type change rebuilt options)
                setTimeout(function() {
                    if ($("#tigon-woo-target option[value=\'" + wooTarget + "\']").length) {
                        $("#tigon-woo-target").val(wooTarget);
                        $("#tigon-woo-target-custom").hide();
                    } else {
                        $("#tigon-woo-target").val("__custom__");
                        $("#tigon-woo-target-custom").show().val(wooTarget);
                    }
                }, 50);

                $("#tigon-transform").val(transform);
                $("#tigon-transform-cfg").val(cfg);
                $("#tigon-is-enabled").prop("checked", enabled);

                // Scroll to form
                $("html, body").animate({ scrollTop: $("#tigon-form-title").offset().top - 50 }, 300);
            });

            // Cancel edit — reset form
            $("#tigon-cancel-edit").on("click", function() {
                $("#tigon-mapping-id").val(0);
                $("#tigon-form-title").text("Add New Mapping");
                $(this).hide();
                $("#tigon-dms-path").val("");
                $("#tigon-dms-path-custom").hide().val("");
                $("#tigon-woo-target").val("");
                $("#tigon-woo-target-custom").hide().val("");
                $("#tigon-target-type").val("postmeta").trigger("change");
                $("#tigon-transform").val("direct");
                $("#tigon-transform-cfg").val("");
                $("#tigon-is-enabled").prop("checked", true);
            });
        })(jQuery);
        </script>
        ';
    }

    /**
     * Sync admin page.
     *
     * Manual + scheduled inventory sync and DMS Connect mapped sync.
     */
    public static function sync_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $interval_updated = false;
        $sync_interval = class_exists('\DMS_Sync') ? \DMS_Sync::get_sync_interval() : 6;

        // Handle interval update
        if (isset($_POST['dms_update_interval']) && check_admin_referer('dms_update_interval', 'dms_interval_nonce')) {
            $new_interval = isset($_POST['sync_interval']) ? (int) $_POST['sync_interval'] : 6;
            $new_interval = max(1, min(168, $new_interval));
            \DMS_Sync::set_sync_interval($new_interval);
            \tigon_dms_reschedule_sync();
            $sync_interval = $new_interval;
            $interval_updated = true;
        }

        $next_sync = wp_next_scheduled('tigon_dms_sync_inventory');
        $selective_nonce = wp_create_nonce('tigon_dms_sync_selective_nonce');
        $mapped_nonce = wp_create_nonce('tigon_dms_sync_mapped_nonce');

        self::page_header();

        echo '
        <div class="body" style="display:flex; flex-direction:column;">

            <!-- ====== SYNC INVENTORY ====== -->
            <div class="action-box-group" style="grid-template-columns:1fr; grid-template-rows:auto;">
                <div class="action-box primary" style="flex-direction:column; gap:1rem; align-items:flex-start;">
                    <h2 style="margin:0;">Sync Inventory</h2>
                    <p>Sync carts from the DMS API into WooCommerce products. Choose which inventory type to sync.</p>

                    <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:center;">
                        <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer; font-weight:600; padding:0.5rem 1rem; border:2px solid var(--nav-color); border-radius:0.5rem;">
                            <input type="radio" name="sync_type" value="all" checked style="width:18px; height:18px;" />
                            All Carts
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer; font-weight:600; padding:0.5rem 1rem; border:2px solid var(--nav-color); border-radius:0.5rem;">
                            <input type="radio" name="sync_type" value="new" style="width:18px; height:18px;" />
                            New Only
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer; font-weight:600; padding:0.5rem 1rem; border:2px solid var(--nav-color); border-radius:0.5rem;">
                            <input type="radio" name="sync_type" value="used" style="width:18px; height:18px;" />
                            Used Only
                        </label>
                    </div>

                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <button type="button" id="dms-sync-btn" class="button button-primary" style="height:auto; min-width:auto; background-color:var(--accent-color); color:var(--font-light); padding:0.6rem 2rem; font-size:14px;">
                            Sync Now
                        </button>
                        <span id="dms-sync-spinner" class="spinner" style="float:none; margin-top:0;"></span>
                    </div>
                    <div id="dms-sync-results" style="display:none; width:100%;"></div>
                </div>
            </div>

            <!-- ====== SCHEDULED SYNC ====== -->
            <div class="action-box-group" style="grid-template-columns:1fr; grid-template-rows:auto;">
                <div class="action-box primary" style="flex-direction:column; gap:1rem; align-items:flex-start;">';

        if ($interval_updated) {
            echo '
                    <div style="background:#d4edda; border:1px solid #c3e6cb; padding:0.75rem; border-radius:4px; width:100%;">
                        <p style="margin:0;">Sync interval updated successfully.</p>
                    </div>';
        }

        echo '
                    <h2 style="margin:0;">Scheduled Sync</h2>
                    <form method="post" action="" style="display:flex; flex-direction:column; gap:0.75rem; align-items:flex-start;">
                        ' . wp_nonce_field('dms_update_interval', 'dms_interval_nonce', true, false) . '
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <label for="sync_interval"><strong>Sync Interval (hours):</strong></label>
                            <input type="number" id="sync_interval" name="sync_interval" value="' . esc_attr($sync_interval) . '" min="1" max="168" step="1" style="width:80px;" />
                        </div>
                        <p style="margin:0;"><small>How often to automatically sync inventory (1&ndash;168 hours).</small></p>';

        if ($next_sync) {
            echo '
                        <p style="margin:0;"><strong>Next Scheduled Sync:</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_sync)) . '</p>';
        }

        echo '
                        <button type="submit" name="dms_update_interval" class="button button-primary" style="height:auto; min-width:auto; background-color:var(--accent-color); color:var(--font-light); padding:0.5rem 1.5rem;">
                            Update Interval
                        </button>
                    </form>
                </div>
            </div>

            <!-- ====== SYNC MAPPED INVENTORY ====== -->
            <div class="action-box-group" style="grid-template-columns:1fr; grid-template-rows:auto;">
                <div class="action-box primary" style="flex-direction:column; gap:1rem; align-items:flex-start;">
                    <h2 style="margin:0;">Sync Mapped Inventory (DMS Connect)</h2>
                    <p>Re-sync all DMS carts using the DMS Connect mapping engine. This uses the authenticated API and applies database object mapping (attributes, taxonomies, images, SEO, etc).</p>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <button type="button" id="dms-mapped-sync-btn" class="button button-primary" style="height:auto; min-width:auto; background-color:var(--accent-color); color:var(--font-light); padding:0.6rem 2rem; font-size:14px;">
                            Sync Mapped Inventory
                        </button>
                        <span id="dms-mapped-sync-spinner" class="spinner" style="float:none; margin-top:0;"></span>
                    </div>
                    <div id="dms-mapped-sync-results" style="display:none; width:100%;"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Highlight selected radio option
            $("input[name=sync_type]").on("change", function() {
                $("input[name=sync_type]").closest("label").css("border-color", "var(--nav-color)").css("background", "transparent");
                $(this).closest("label").css("border-color", "var(--main-color)").css("background", "#f9ecec");
            }).filter(":checked").trigger("change");

            // Selective sync (New / Used / All)
            $("#dms-sync-btn").on("click", function() {
                var $btn = $(this);
                var $spinner = $("#dms-sync-spinner");
                var $results = $("#dms-sync-results");
                var syncType = $("input[name=sync_type]:checked").val();
                var labels = {"all": "All Carts", "new": "New Carts", "used": "Used Carts"};

                $btn.prop("disabled", true).text("Syncing " + labels[syncType] + "...");
                $spinner.addClass("is-active");
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "tigon_dms_sync_selective",
                        nonce: ' . wp_json_encode($selective_nonce) . ',
                        sync_type: syncType
                    },
                    timeout: 600000,
                    success: function(response) {
                        $spinner.removeClass("is-active");
                        $btn.prop("disabled", false).text("Sync Now");
                        if (response.success && response.data) {
                            var s = response.data;
                            var html = \'<div style="background:#d4edda;border:1px solid #c3e6cb;padding:1rem;border-radius:4px;">\';
                            html += "<h3 style=\'margin-top:0;\'>Sync Completed (" + labels[s.sync_type] + ")</h3>";
                            html += "<ul style=\'list-style:disc;padding-left:1.5rem;\'>";
                            html += "<li><strong>Total processed:</strong> " + s.total + "</li>";
                            html += "<li><strong>Created:</strong> " + s.created + "</li>";
                            html += "<li><strong>Updated:</strong> " + s.updated + "</li>";
                            html += "<li><strong>Errors:</strong> " + s.errors + "</li>";
                            html += "</ul>";
                            if (s.error_details && s.error_details.length > 0) {
                                html += "<details><summary>Error details (" + s.error_details.length + ")</summary><ul style=\'list-style:disc;padding-left:1.5rem;\'>";
                                s.error_details.slice(0, 50).forEach(function(e) {
                                    html += "<li>" + $("<span>").text(e).html() + "</li>";
                                });
                                if (s.error_details.length > 50) html += "<li>...and " + (s.error_details.length - 50) + " more</li>";
                                html += "</ul></details>";
                            }
                            html += "</div>";
                            $results.html(html).show();
                        } else {
                            $results.html(\'<div style="background:#f8d7da;border:1px solid #f5c6cb;padding:1rem;border-radius:4px;"><p><strong>Sync failed:</strong> \' + (response.data || "Unknown error") + "</p></div>").show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $spinner.removeClass("is-active");
                        $btn.prop("disabled", false).text("Sync Now");
                        $results.html(\'<div style="background:#f8d7da;border:1px solid #f5c6cb;padding:1rem;border-radius:4px;"><p><strong>Request failed:</strong> \' + error + "</p></div>").show();
                    }
                });
            });

            // Mapped sync
            $("#dms-mapped-sync-btn").on("click", function() {
                var $btn = $(this);
                var $spinner = $("#dms-mapped-sync-spinner");
                var $results = $("#dms-mapped-sync-results");

                $btn.prop("disabled", true).text("Syncing...");
                $spinner.addClass("is-active");
                $results.hide();

                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "tigon_dms_sync_mapped",
                        nonce: ' . wp_json_encode($mapped_nonce) . '
                    },
                    timeout: 600000,
                    success: function(response) {
                        $spinner.removeClass("is-active");
                        $btn.prop("disabled", false).text("Sync Mapped Inventory");
                        if (response.success && response.data) {
                            var s = response.data;
                            var html = \'<div style="background:#d4edda;border:1px solid #c3e6cb;padding:1rem;border-radius:4px;"><h3 style="margin-top:0;">Mapped Sync Completed</h3><ul style="list-style:disc;padding-left:1.5rem;">\';
                            html += "<li><strong>Total:</strong> " + s.total + "</li>";
                            html += "<li><strong>Updated:</strong> " + s.updated + "</li>";
                            html += "<li><strong>Created:</strong> " + s.created + "</li>";
                            html += "<li><strong>Skipped:</strong> " + s.skipped + "</li>";
                            html += "<li><strong>Errors:</strong> " + s.errors + "</li>";
                            html += "</ul>";
                            if (s.error_details && s.error_details.length > 0) {
                                html += "<details><summary>Error details</summary><ul style=\'list-style:disc;padding-left:1.5rem;\'>";
                                s.error_details.forEach(function(e) {
                                    html += "<li>" + $("<span>").text(e).html() + "</li>";
                                });
                                html += "</ul></details>";
                            }
                            html += "</div>";
                            $results.html(html).show();
                        } else {
                            $results.html(\'<div style="background:#f8d7da;border:1px solid #f5c6cb;padding:1rem;border-radius:4px;"><p><strong>Sync failed:</strong> \' + (response.data || "Unknown error") + "</p></div>").show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $spinner.removeClass("is-active");
                        $btn.prop("disabled", false).text("Sync Mapped Inventory");
                        $results.html(\'<div style="background:#f8d7da;border:1px solid #f5c6cb;padding:1rem;border-radius:4px;"><p><strong>Request failed:</strong> \' + error + "</p></div>").show();
                    }
                });
            });
        });
        </script>
        ';
    }

    public static function page_header()
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/admin.css');
        $svg = preg_replace(
            '/#FFFFFF/',
            '#F4F4F9',
            file_get_contents(__DIR__ . '/../../assets/images/tigondb.svg')
        );

        echo '
        <meta name="viewport" content="height=device-height, width=device-width, initial-scale=1.0, minimum-scale=1.0, target-densitydpi=device-dpi">
        <style>' . $css . '</style>

        <div class="header">
            <span class="tigon-dms-icon">' . $svg . '</span>
            <h1>Tigon DMS Connect</h1>
        </div>';
    }

    public static function diagnostic_page()
    {
    ### Database Operations ###

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        
        // Get new carts of inventory type ACTIVE NEW INVENTORY as Slugs
        $new_carts = $wpdb->get_results('
            SELECT post_name
            FROM `'.$wpdb->prefix.'posts`
            WHERE ID IN
            (
                SELECT object_id FROM '.$wpdb->prefix.'term_relationships
                WHERE term_taxonomy_id = 4549
            )
            AND ID IN
            (
                SELECT post_id FROM '.$wpdb->prefix.'postmeta
                WHERE meta_key = "_stock_status"
                AND meta_value = "instock"
            )
            ORDER BY ID ASC;
        ');
        $new_carts = array_map(function($cart) {
            return $cart->post_name;
        }, $new_carts);

        // Get used carts of inventory type ACTIVE USED INVENTORY as SKUs
        $used_carts = $wpdb->get_results('
            SELECT meta_value
            FROM `'.$wpdb->prefix.'postmeta`
            WHERE post_id IN
            (
                SELECT * FROM
                (
                    SELECT object_id FROM
                    `'.$wpdb->prefix.'term_relationships` WHERE '.$wpdb->prefix.'term_relationships.term_taxonomy_id = 4553
                ) AS subquery
            )
            AND meta_key = "_sku";
        ');
        $used_carts = array_map(function($cart) {
            return $cart->meta_value;
        }, $used_carts);

    ### New Operations ###

        // Get new cart data from DMS
        // Result is encoded to string for array_unique
        $dms_new = json_decode(DMS_Connector::request('{"isUsed":false, "needOnWebsite": true, "isInStock": true, "isInBoneyard": false}', '/chimera/lookup', 'POST'), true)??[];
        $dms_new_unique = array_map(function($cart) {
            if (str_contains(strtoupper($cart['serialNo']), 'DELETE') || str_contains(strtoupper($cart['vinNo']), 'DELETE'))
                return '--DELETE--';

            $location_id = $cart['cartLocation']['locationId'];
            if (!isset(Attributes::$locations[$location_id])) {
                return '--DELETE--';
            }
            $loc = Attributes::$locations[$location_id];
            $city = $loc['city_short'] ?? $loc['city'];

            $make = preg_replace('/\s+/', '-', trim(preg_replace('/\+/', ' plus ', $cart['cartType']['make'])));
            $model = preg_replace('/\s+/', '-', trim(preg_replace('/\+/', ' plus ', $cart['cartType']['model'])));
            $color = preg_replace('/\s+/', '-', $cart['cartAttributes']['cartColor']);
            $seat = preg_replace('/\s+/', '-', $cart['cartAttributes']['seatColor']);
            $location = preg_replace('/\s+/', '-', $city . "-" . $loc['st']);
            $year = preg_replace('/\s+/', '-', $cart['cartType']['year']);
            return json_encode(['url' => strtolower("$make-$model-$color-seat-$seat-$location"), 'year' => $year]);
        }, $dms_new);
        // Remove invalid carts
        $dms_new_unique = array_filter($dms_new_unique, function($cart) {
            return ($cart !== '--DELETE--');
        });
        // Remove duplicate cart types
        $dms_new_unique = array_unique($dms_new_unique);

        // Decode filtered carts so we can work with their data
        $dms_new_unique = array_map(function($cart) {
            return json_decode($cart, true);
        }, $dms_new_unique);

        // Format urls such that the newest cart of each type does not have the year appended
        $dms_new_unique = array_map(function($cart) use (&$dms_new_unique){
            $should_have_year = false;
            // Loop through each other cart
            if(gettype($cart) === 'array') foreach ($dms_new_unique as &$other) {
                // If it hasn't been formatted already
                if (gettype($other) === 'array') {
                    // If it's the same type
                    if ($other['url'] === $cart['url']) {
                        // If other is older, append year to other
                        if ($cart['year'] > $other['year']) {
                            $other = $other['url'] . '-' . $other['year'];
                        // If self is older, append year to self
                        } else if ($cart['year'] < $other['year']) {
                            $should_have_year = true;
                        }
                    }
                }
            }
            return $cart['url'] . ($should_have_year?('-'.$cart['year']):'');
        }, $dms_new_unique);
        
        // Carts on site which are not on DMS
        $extra_new_pids = $new_carts;
        foreach($dms_new_unique as $url) {
            $pos = array_search($url, $extra_new_pids);
            array_splice($extra_new_pids, $pos, 1);
        }
        $extra_new_pids = array_filter(array_map(function($slug) {
            $page = get_page_by_path($slug, OBJECT, 'product');
            return $page ? $page->ID : null;
        }, $extra_new_pids));
        // Carts on DMS which are not on site
        $missing_new_skus = array_values(array_diff($dms_new_unique, $new_carts));

    ### Used Operation ###

        // Get used cart data from DMS
        $dms_used = json_decode(DMS_Connector::request('{"isUsed":true, "isInStock": true, "isInBoneyard": false, "needOnWebsite": true}', '/chimera/lookup', 'POST'), true)??[];
        $dms_used_skus = array_map(function($cart) {
            return $cart['vinNo']?$cart['vinNo']:$cart['serialNo'];
        }, $dms_used);

        $extra_used_pids = array_values(array_diff($used_carts, $dms_used_skus));
        $extra_used_pids = array_map(function($sku) {
            return wc_get_product_id_by_sku( $sku );
        }, $extra_used_pids);
        $missing_used_skus = array_values(array_diff($dms_used_skus, $used_carts));

    ### Data Parsing ###

        $new_cart_pages = count($new_carts);
        $new_not_on_site = count($missing_new_skus);
        $new_not_on_dms = count($extra_new_pids);
        $new_cart_dms = count($dms_new_unique);

        $new_total = $new_cart_pages + $new_not_on_site;
        $new_accurate = $new_total > 0 ? ($new_cart_pages - $new_not_on_dms) / $new_total * 100 : 0;
        $new_extra = $new_total > 0 ? ($new_cart_pages / $new_total) * 100 : 0;

        $used_cart_pages = count($used_carts);
        $used_not_on_site = count($missing_used_skus);
        $used_not_on_dms = count($extra_used_pids);
        $used_cart_dms = count($dms_used);

        $used_total = $used_cart_pages + $used_not_on_site;
        $used_accurate = $used_total > 0 ? ($used_cart_pages - $used_not_on_dms) / $used_total * 100 : 0;
        $used_extra = $used_total > 0 ? ($used_cart_pages / $used_total) * 100 : 0;

    ### Inventory Breakdown ###

        // Build breakdowns from raw DMS data (new + used combined)
        $all_dms = array_merge($dms_new, $dms_used);
        $total_dms = count($all_dms);
        $total_new_dms = count($dms_new);
        $total_used_dms = count($dms_used);

        // By location
        $by_location_new = [];
        $by_location_used = [];
        foreach ($dms_new as $c) {
            $loc = $c['cartLocation']['locationId'] ?? 'Unknown';
            $city = \DMS_API::get_city_by_store_id($loc);
            $by_location_new[$city] = ($by_location_new[$city] ?? 0) + 1;
        }
        foreach ($dms_used as $c) {
            $loc = $c['cartLocation']['locationId'] ?? 'Unknown';
            $city = \DMS_API::get_city_by_store_id($loc);
            $by_location_used[$city] = ($by_location_used[$city] ?? 0) + 1;
        }
        arsort($by_location_new);
        arsort($by_location_used);

        // By manufacturer (make)
        $by_make_new = [];
        $by_make_used = [];
        foreach ($dms_new as $c) {
            $make = $c['cartType']['make'] ?? 'Unknown';
            $by_make_new[$make] = ($by_make_new[$make] ?? 0) + 1;
        }
        foreach ($dms_used as $c) {
            $make = $c['cartType']['make'] ?? 'Unknown';
            $by_make_used[$make] = ($by_make_used[$make] ?? 0) + 1;
        }
        arsort($by_make_new);
        arsort($by_make_used);

        // By model
        $by_model_new = [];
        $by_model_used = [];
        foreach ($dms_new as $c) {
            $make = $c['cartType']['make'] ?? '';
            $model = $c['cartType']['model'] ?? 'Unknown';
            $key = trim($make . ' ' . $model);
            $by_model_new[$key] = ($by_model_new[$key] ?? 0) + 1;
        }
        foreach ($dms_used as $c) {
            $make = $c['cartType']['make'] ?? '';
            $model = $c['cartType']['model'] ?? 'Unknown';
            $key = trim($make . ' ' . $model);
            $by_model_used[$key] = ($by_model_used[$key] ?? 0) + 1;
        }
        arsort($by_model_new);
        arsort($by_model_used);

    ### HTML Element Formatting ###

        $missing_new_string = '<p>Missing New: [ '.implode(', ', $missing_new_skus).' ]</p>';
        $new_links = [];
        foreach($extra_new_pids as $pid) {
            array_push($new_links,
                '<a href="' .
                site_url() . "/wp-admin/post.php?post=$pid&action=edit&classic-editor" .
                '">' . $pid . '</a>');
        }
        $extra_new_string = '<p>Extra New: [ '.implode(', ', $new_links).' ]</p>';

        $missing_used_string = '<p>Missing Used: [ '.implode(', ', $missing_used_skus).' ]</p>';
        $used_links = [];
        foreach($extra_used_pids as $pid) {
            array_push($used_links,
                '<a href="' .
                site_url() . "/wp-admin/post.php?post=$pid&action=edit&classic-editor" .
                '">' . $pid . '</a>');
        }
        $extra_used_string = '<p>Extra Used: [ '.implode(', ', $used_links).' ]</p>';

    ### Output ###

        self::page_header();

        echo '
        <div class="body" style="display:flex; flex-direction: column;">

            <!-- ====== DMS INVENTORY DASHBOARD ====== -->
            <div class="action-box" style="flex-direction:column; gap:1rem; width:100%; margin-bottom:1rem;">
                <h2 style="margin:0;">DMS Inventory Dashboard</h2>
                <p style="margin:0; color:#666;">Live inventory counts from the DMS system</p>

                <div class="dms-dashboard">

                    <!-- Overview card -->
                    <div class="dash-card">
                        <h3>Inventory Overview</h3>
                        <div class="stat-row total-row">
                            <span>Total DMS Carts</span>
                            <span class="stat-value">' . number_format($total_dms) . '</span>
                        </div>
                        <div class="stat-row">
                            <span>New Carts</span>
                            <span class="stat-value">' . number_format($total_new_dms) . '</span>
                        </div>
                        <div class="stat-row">
                            <span>Used Carts</span>
                            <span class="stat-value">' . number_format($total_used_dms) . '</span>
                        </div>
                        <div class="stat-row">
                            <span>WooCommerce New Products</span>
                            <span class="stat-value">' . number_format($new_cart_pages) . '</span>
                        </div>
                        <div class="stat-row">
                            <span>WooCommerce Used Products</span>
                            <span class="stat-value">' . number_format($used_cart_pages) . '</span>
                        </div>
                    </div>

                    <!-- By Location card -->
                    <div class="dash-card">
                        <h3>By Location</h3>
                        <div class="dash-scrollable">';

        // Location breakdown — New
        if (!empty($by_location_new)) {
            echo '<div style="font-weight:600; padding:0.35rem 0; color:var(--accent-color);">New</div>';
            foreach ($by_location_new as $city => $count) {
                echo '<div class="stat-row"><span>' . esc_html($city) . '</span><span class="stat-value">' . $count . '</span></div>';
            }
        }
        // Location breakdown — Used
        if (!empty($by_location_used)) {
            echo '<div style="font-weight:600; padding:0.35rem 0; color:var(--accent-color); margin-top:0.5rem;">Used</div>';
            foreach ($by_location_used as $city => $count) {
                echo '<div class="stat-row"><span>' . esc_html($city) . '</span><span class="stat-value">' . $count . '</span></div>';
            }
        }

        echo '
                        </div>
                    </div>

                    <!-- By Manufacturer card -->
                    <div class="dash-card">
                        <h3>By Manufacturer</h3>
                        <div class="dash-scrollable">';

        if (!empty($by_make_new)) {
            echo '<div style="font-weight:600; padding:0.35rem 0; color:var(--accent-color);">New</div>';
            foreach ($by_make_new as $make => $count) {
                echo '<div class="stat-row"><span>' . esc_html($make) . '</span><span class="stat-value">' . $count . '</span></div>';
            }
        }
        if (!empty($by_make_used)) {
            echo '<div style="font-weight:600; padding:0.35rem 0; color:var(--accent-color); margin-top:0.5rem;">Used</div>';
            foreach ($by_make_used as $make => $count) {
                echo '<div class="stat-row"><span>' . esc_html($make) . '</span><span class="stat-value">' . $count . '</span></div>';
            }
        }

        echo '
                        </div>
                    </div>

                    <!-- By Model card -->
                    <div class="dash-card">
                        <h3>By Model</h3>
                        <div class="dash-scrollable">';

        if (!empty($by_model_new)) {
            echo '<div style="font-weight:600; padding:0.35rem 0; color:var(--accent-color);">New</div>';
            foreach ($by_model_new as $model => $count) {
                echo '<div class="stat-row"><span>' . esc_html($model) . '</span><span class="stat-value">' . $count . '</span></div>';
            }
        }
        if (!empty($by_model_used)) {
            echo '<div style="font-weight:600; padding:0.35rem 0; color:var(--accent-color); margin-top:0.5rem;">Used</div>';
            foreach ($by_model_used as $model => $count) {
                echo '<div class="stat-row"><span>' . esc_html($model) . '</span><span class="stat-value">' . $count . '</span></div>';
            }
        }

        echo '
                        </div>
                    </div>

                </div>
            </div>

            <!-- ====== SITE vs DMS COMPARISON ====== -->
            <div class="action-box-group">
                <div class="action-box primary" id="new-status">
                    <h2>New Carts</h2>
                    <p>Pages on site: '.$new_cart_pages.'</p>
                    <p>URLs on DMS: '.$new_cart_dms.'</p>
                    <p>Missing site pages: '.$new_not_on_site.'</p>
                    <p>Extra site pages: '.$new_not_on_dms.'</p>
                </div>
                <div class="action-box secondary">
                    '.$missing_new_string.'
                    '.$extra_new_string.'
                </div>
                <div class="action-box primary" id="new-chart" style="flex-direction:row; gap: 2.5rem">
                    <div class="action-box-column chart-container">
                        <div class="pie-chart" style="--percent-accurate: '.$new_accurate.'%; --percent-extra: '.$new_extra.'%;">
                            New Carts
                        </div>
                    </div>
                    <div class="action-box-column chart-legend">
                        <div style="--color: var(--good)">Inventory on site and DMS</div>
                        <div style="--color: var(--warning)">Inventory not on DMS</div>
                        <div style="--color: var(--bad)">Inventory not on site</div>
                    </div>
                </div>
            </div>
            <div class="action-box-group">
                <div class="action-box primary">
                    <h2>Used Carts</h2>
                    <p>Pages on site: '.$used_cart_pages.'</p>
                    <p>Carts on DMS: '.$used_cart_dms.'</p>
                    <p>Missing site pages: '.$used_not_on_site.'</p>
                    <p>Extra site pages: '.$used_not_on_dms.'</p>
                </div>
                <div class="action-box secondary">
                    '.$missing_used_string.'
                    '.$extra_used_string.'
                </div>
                <div class="action-box primary" id="used-chart" style="flex-direction:row; gap: 2.5rem">
                    <div class="action-box-column chart-container">
                        <div class="pie-chart" style="--percent-accurate: '.$used_accurate.'%; --percent-extra: '.$used_extra.'%;">
                            Used Carts
                        </div>
                    </div>
                    <div class="action-box-column chart-legend">
                        <div style="--color: var(--good)">Inventory on site and DMS</div>
                        <div style="--color: var(--warning)">Inventory not on DMS</div>
                        <div style="--color: var(--bad)">Inventory not on site</div>
                    </div>
                </div>
            </div>
        </div>
        ';
    }

    public static function import_page()
    {
        $nonce = wp_create_nonce("tigon_dms_run_import_nonce");
        $link = admin_url('admin-ajax.php?action=tigon_dms_run_import&nonce=' . $nonce);

        self::page_header();

        echo '
        <div class="body" style="display:flex;">
            <!--<div class="action-box" style="flex-direction:row;">
                <a id="new" class="tigon_dms_action tigon_dms_import" data-nonce="' . $nonce . '" href="' . $link . '"><button>Import New Carts</button></a>
            </div>-->
            <div class="tabbed-panel">
                <div class="tigon-dms-nav" style="flex-direction:row;">
                    <button class="tigon-dms-tab active" id="used-tab">Used Carts</button>
                    <button class="tigon-dms-tab" id="new-tab">New Carts</button>
                </div>

                <div class="action-box" id="used-panel">
                    <div class="action-box-column">
                        <div id="mobile-title">Import Used Cart Data From DMS</div>
                        <h3>Import settings</h3>
                        <div>Input the VIN or Serial Number of a cart on the DMS to be imported.</div>
                        <div class="form"><form>
                            <div>
                                <span>VIN Number:</span>
                                <input type="text" list="vin" class="input-list" style="float:right" name="VIN Number" id="txt-vin" />
                                <datalist id="vin">
                                </datalist>
                            </div>
                            <div>
                                <span>Serial Number:</span>
                                <input type="text" list="serial" class="input-list" style="float:right" name="Serial Number" id="txt-serial" />
                                <datalist id="serial">
                                </datalist>
                            </div>
                            <div>
                                <span>
                                    <span>Import All Used Carts?</span>
                                    <input type="checkbox" id="chk-all-carts" />
                                </span>
                            </div>
                        </div>
                    </form></div>
                    <div class="action-box-column">
                        <h3>Import Used Cart Data from DMS</h3>
                        <div id="warning" class="warning"><i>This process may take several minutes. This should only be done in the case that a large portion of the inventory is missing or outdated.</i></div>
                        <a id="used" class="tigon_dms_action tigon_dms_import" data-nonce="' . $nonce . '" href="' . $link . '"><button>Import Selected</button></a>
                    </div>
                </div>

                <div class="action-box" id="new-panel">
                    <div class="action-box-column" style="max-width:28rem; align-self:unset">
                        <h3>Import New Cart Data from DMS</h3>
                        <div class="warning"><i>This process may take several minutes. This should only be done in the case that a large portion of the inventory is missing or outdated.</i></div>
                        <a id="new" class="tigon_dms_action tigon_dms_import" data-nonce="' . $nonce . '" href="' . $link . '"><button>Import All</button></a>
                    </div>

                    <div class="action-box-column">
                    ' . self::checkboxes() . '
                    </div>
                </div>
            </div>
            <div class="action-box" id="result-box">
                <div id="progress-bar-container"><div id="progress-bar"></div></div>
                <div id="progress-text"></div>
                <hr id="result-separator">
                <div id="result"></div>
                <div id="errors"></div>
            </div>
        </div>
        ';
    }

    /**
     * Database Objects inspector page
     *
     * Lists WooCommerce products created from DMS (identified by _dms_cart_id meta)
     * and shows a side‑by‑side view of the stored DMS payload and the corresponding
     * WordPress Database_Object representation for a selected product.
     *
     * @return void
     */
    public static function database_objects_page()
    {
        self::page_header();

        // Find DMS-backed products (those with _dms_cart_id meta)
        $query = new \WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => 50,
            'post_status'    => ['publish', 'draft', 'pending'],
            'meta_query'     => [
                [
                    'key'     => '_dms_cart_id',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        $selected_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

        echo '
        <div class="body" style="display:flex; flex-direction:column;">
            <div class="action-box-group">
                <div class="action-box primary" style="flex-direction:column; gap:1rem;">
                    <h2>DMS Products on this Site</h2>
                    <p>Select a product to inspect its DMS payload and WordPress database object.</p>
                    <form method="get" style="margin-bottom:1rem;">
                        <input type="hidden" name="page" value="database-objects" />
                        <label for="product_id"><strong>Product ID:</strong></label>
                        <input type="number" id="product_id" name="product_id" value="' . ($selected_id ? esc_attr($selected_id) : '') . '" style="width:120px; margin-left:0.5rem;" />
                        <button class="button button-primary" type="submit">Load</button>
                    </form>
                    <div class="tigon-dms-table-wrapper">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Title</th>
                                    <th>DMS Cart ID</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pid        = get_the_ID();
                $cart_id    = get_post_meta($pid, '_dms_cart_id', true);
                $is_current = ($pid === $selected_id);

                echo '
                                <tr' . ($is_current ? ' style="background:#f0f6ff;"' : '') . '>
                                    <td>' . esc_html($pid) . '</td>
                                    <td>' . esc_html(get_the_title()) . '</td>
                                    <td>' . esc_html($cart_id) . '</td>
                                    <td><a href="' . esc_url(add_query_arg(['page' => 'database-objects', 'product_id' => $pid], admin_url('admin.php'))) . '">Inspect</a></td>
                                </tr>';
            }
            wp_reset_postdata();
        } else {
            echo '
                                <tr>
                                    <td colspan="4">No DMS-backed products found (products with <code>_dms_cart_id</code> meta).</td>
                                </tr>';
        }

        echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>';

        // Detail view for a selected product
        if ($selected_id) {
            $dms_payload_raw = get_post_meta($selected_id, '_dms_payload', true);
            $dms_payload     = $dms_payload_raw ? json_decode($dms_payload_raw, true) : null;

            // Safely build Database_Object representation if possible
            $database_data = [];
            if (class_exists('Tigon\DmsConnect\Admin\Database_Object')) {
                try {
                    $db_object = \Tigon\DmsConnect\Admin\Database_Object::get_from_wpdb($selected_id);
                    if ($db_object instanceof \Tigon\DmsConnect\Admin\Database_Object) {
                        $database_data = $db_object->data ?? [];
                    }
                } catch (\Throwable $e) {
                    $database_data = ['error' => $e->getMessage()];
                }
            }

            $dms_json = $dms_payload ? wp_json_encode($dms_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'No stored DMS payload found for this product.';
            $db_json  = !empty($database_data) ? wp_json_encode($database_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'Unable to build Database_Object for this product.';

            echo '
            <div class="action-box-group" style="margin-top:1.5rem;">
                <div class="action-box primary" style="flex:1; min-width:0;">
                    <h2>API Payload (Stored DMS Data)</h2>
                    <p>This is the raw DMS payload stored in <code>_dms_payload</code> for product ID ' . esc_html($selected_id) . '.</p>
                    <pre style="max-height:400px; overflow:auto; background:#111827; color:#e5e7eb; padding:1rem; border-radius:4px;">' . esc_html($dms_json) . '</pre>
                </div>
                <div class="action-box primary" style="flex:1; min-width:0;">
                    <h2>WordPress Database Object</h2>
                    <p>This is the normalized database object representation used by the DMS Connect engine.</p>
                    <pre style="max-height:400px; overflow:auto; background:#111827; color:#e5e7eb; padding:1rem; border-radius:4px;">' . esc_html($db_json) . '</pre>
                </div>
            </div>';
        }

        echo '
        </div>
        ';
    }

    public static function settings_page()
    {
        $nonce = wp_create_nonce("tigon_dms_run_import_nonce");
        $link = admin_url('admin-ajax.php?action=tigon_dms_save_settings&nonce=' . $nonce);

        $dms_url = 'dms.com';
        $api_key = 'key';
        $file_source = 'cdn.com';

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'tigon_dms_config';

        $github_token = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'github_token'") ?? 'e.g. github_pat_...';
        $github_token = substr($github_token, 0, 16) . '•••••••••••••••' . substr($github_token, -5);

        $dms_url = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'dms_url'") ?? 'e.g. https://api.your-dms.com';

        $api_key = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'user_token'") ?? 'e.g. 00000000-0000-0000-000000000000';
        $shown_key = substr($api_key, -6);
        $api_key = substr_replace(preg_replace('/[^-]/', '•', $api_key), $shown_key, -6, 6);
        
        $file_source = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'file_source'") ?? 'e.g. https://s3.amazonaws.com/your.bucket.s3';

        self::page_header();

        // Default schema templates
        $default_name              = '{^make}® {^model} {cartColor} in {city}, {stateAbbr}';
        $default_slug              = '{make}-{model}-{cartColor}-seat-{seatColor}-{city}-{state}';
        $default_image_name        = '{^make}® {^model} {cartColor} in {city}, {stateAbbr} image';
        $default_monroney_name     = '{^make}® {^model} {cartColor} in {city}, {stateAbbr} monroney';
        $default_description       = 'Lorem ipsum dolor sit amet';
        $default_short_description = 'Lorem ipsum';

        // Load saved schema templates (fall back to defaults)
        $schema_name              = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'schema_name'") ?? $default_name;
        $schema_slug              = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'schema_slug'") ?? $default_slug;
        $schema_image_name        = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'schema_image_name'") ?? $default_image_name;
        $schema_monroney_name     = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'schema_monroney_name'") ?? $default_monroney_name;
        $schema_description       = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'schema_description'") ?? $default_description;
        $schema_short_description = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'schema_short_description'") ?? $default_short_description;

        echo '
        <div class="body" style="display:flex; flex-direction:column;">
            <div class="tabbed-panel">
                <div class="tigon-dms-nav" style="flex-direction:row;">
                    <button class="tigon-dms-tab active" id="general-tab">General</button>
                    <button class="tigon-dms-tab" id="schema-tab">Schema</button>
                </div>

                <div class="action-box" id="general">
                    <h3>General Configuration</h3>
                    <div class="settings form">
                        <div>
                            <span>GitHub Access Token:</span>
                            <input type="text" style="float:right" id="txt-github-token" placeholder="' . $github_token . '" />
                        </div>
                        <div>
                            <span>DMS API Address:</span>
                            <input type="text" style="float:right" id="txt-url" placeholder="' . $dms_url . '" />
                        </div>
                        <div>
                            <span>DMS Amplify User ID:</span>
                            <input type="text" style="float:right" id="txt-api-key" placeholder="' . $api_key . '"></textarea>
                        </div>
                        <div>
                            <span>File source:</span>
                            <input type="text" style="float:right" id="txt-file-source" placeholder="' . $file_source . '"></textarea>
                        </div>
                    </div>
                    <a id="save" class="tigon_dms_action tigon_dms_save" data-nonce="' . $nonce . '"><button>Save Settings</button></a>
                </div>

                <div class="action-box" id="schema">
                    <h3>Schema Setup</h3>
                    <div class="drag-n-drop">
                        <div class="settings form" id="attr-form">
                            <div>
                                <span>Name Schema:</span>
                                <input type="text" style="float:right" id="schema-name" value="'.esc_attr($schema_name).'" placeholder="'.esc_attr($default_name).'" />
                            </div>
                            
                            <div>
                                <span>Slug Schema:</span>
                                <input type="text" style="float:right" id="schema-slug" value="'.esc_attr($schema_slug).'" placeholder="'.esc_attr($default_slug).'" />
                            </div>
                            
                            <div>
                                <span>Image Name:</span>
                                <input type="text" style="float:right" id="schema-image-name" value="'.esc_attr($schema_image_name).'" placeholder="'.esc_attr($default_image_name).'" />
                            </div>
                            
                            <div>
                                <span>Monroney Name:</span>
                                <input type="text" style="float:right" id="schema-monroney-name" value="'.esc_attr($schema_monroney_name).'" placeholder="'.esc_attr($default_monroney_name).'" />
                            </div>
                            
                            <div>
                                <span>Description:</span>
                                <input type="text" style="float:right" id="schema-description" value="'.esc_attr($schema_description).'" placeholder="'.esc_attr($default_description).'" />
                            </div>
                            
                            <div>
                                <span>Short Description:</span>
                                <input type="text" style="float:right" id="schema-short-description" value="'.esc_attr($schema_short_description).'" placeholder="'.esc_attr($default_short_description).'" />
                            </div>
                            
                            <!--<div>
                                <span>Field Overrides:</span>
                                <input type="text" style="float:right" id="Field Overrides" placeholder="'.$dms_url.'" />
                            </div>-->

                            <div>
                                <span class="caret">Catgories:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Attributes:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Taxonomies:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div style="flex-direction:column;">
                                <span class="caret" style="align-self:flex-start">Custom Tabs:</span>
                                <span class="nested">
                                    <div>
                                        <span>Short Description:</span>
                                        <input type="text" style="float:right" id="Short Description" placeholder="'.$dms_url.'" />
                                    </div>
                                    <div>
                                        <span>Short Description:</span>
                                        <input type="text" style="float:right" id="Short Description" placeholder="'.$dms_url.'" />
                                    </div>
                                </span>
                            </div>
                        </div>
                        <div id="dms-schema">
                            loading dms properties...
                        </div>
                    </div>
                    <a id="save" class="tigon_dms_action tigon_dms_save" data-nonce="' . $nonce . '"><button>Save Settings</button></a>
                </div>
            </div>
                </div>
        </div>
        ';
    }

    private static function checkboxes(): string
    {
        $product_fields = Product_Fields::get_options();
        $component = '
            <div class="import-fields-head">
                <div class="cb-container top-level" style="margin-right:-3.4rem; flex-grow:0;">
                    <input type="checkbox"/>
                    All
                </div>
                <h2 style="margin:auto;">Force Overwrite Data</h2>
                <span class="caret" style="margin-left:-1rem"></span>
            </div>
            <div class="checkbox-list hidden">
        ';
        foreach ($product_fields as $field) {
            $component = $component .
                '<div class="cb-container import-field" constant="' .
                strtoupper($field) .
                '"><input type="checkbox" /> ' .
                ucwords(str_replace('_', ' ', $field)) .
                '</div>';
        }
        $component = $component . '</div>';

        return $component;
    }
}
