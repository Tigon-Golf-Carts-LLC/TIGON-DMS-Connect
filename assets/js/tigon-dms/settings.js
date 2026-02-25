import { global } from './globals.js';

jQuery(document).ready(() => {
    var activeInput;
    var inputIndex;

    jQuery(".body").attr('style', "display:flex;flex-direction:column;");

    jQuery(".form input").click(e => {
        activeInput = jQuery(e.target);
    });

    jQuery(".form input").blur(e => {
        inputIndex = e.target.selectionEnd;
    });

    jQuery(".form input").on("input", e => {
        var regex = /{.+?}/g;
        let match;
        var values = []; 
        while ((match = regex.exec(e.target.value)) !== null) {
            values.push({"value":match[0], "index":match.index});
        }

        console.log(values);
    });

    var dmsProps = jQuery.ajax({
        dataType: 'json',
        url: global.ajaxurl,
        data: { action: "tigon_dms_get_dms_props" },
        complete: function(res) {
            jQuery("#dms-schema").html(res.responseText);
            jQuery(".caret").click(e => {
                jQuery(e.target).toggleClass("caret-down");
                jQuery(".nested", jQuery(e.target).parent()).first().toggleClass("active");
            });

            jQuery(".dms-value").click(e => {
                var data = e.target.getAttribute("code");
                activeInput.val((index, val) => {
                    newVal = val.substring(0, inputIndex) + data + val.substring(inputIndex);
                    return newVal;
                });
                activeInput.focus();
                document.activeElement.selectionStart = inputIndex + data.length;
                document.activeElement.selectionEnd = inputIndex + data.length;
                activeInput.trigger("input");
            });
        }
    });

    jQuery(".tigon_dms_save").click(e => {
        jQuery(".tigon_dms_action button").prop('disabled', true);

        const locations = {};
        jQuery('#location-mapping-rows .location-row').each((_, row) => {
            const code = jQuery(row).data('location-code');
            const item = {};
            let hasValue = false;

            jQuery(row).find('.loc-field').each((__, input) => {
                const key = jQuery(input).data('key');
                const value = (jQuery(input).val() || '').toString().trim();
                if (value !== '') {
                    item[key] = value;
                    hasValue = true;
                }
            });

            if (hasValue) {
                locations[code] = item;
            }
        });
        jQuery('#txt-locations-json').val(JSON.stringify(locations));

        var settings = {
            "github_token": jQuery("#txt-github-token").val(),
            "dms_url": jQuery("#txt-url").val(),
            "user_token": jQuery("#txt-api-key").val(),
            "file_source": jQuery("#txt-file-source").val(),
            "locations_json": jQuery("#txt-locations-json").val()
        }

        jQuery.ajax({
            dataType: 'json',
            url: global.ajaxurl,
            data: { action: "tigon_dms_save_settings", data: settings }
        }).then(response => {
            location.reload();
        });
    });

    if(window.location.hash) {
        var hash = window.location.hash.substring(1);
        if(hash == "general") {
            jQuery("#general-tab").addClass("active");
            jQuery("#general").attr('style', 'display:flex;');
        }
        if(hash == "schema") {
            jQuery("#schema-tab").addClass("active");
            jQuery("#schema").attr('style', 'display:flex;');
        }
    } else {
        jQuery("#general-tab").addClass("active");
        jQuery("#general").attr('style', 'display:flex;');
    }


    jQuery("#general-tab").click(e => {
        jQuery(".tigon-dms-tab").removeClass("active");
        jQuery("#general-tab").addClass("active");
        jQuery(".tabbed-panel .action-box").attr('style', 'display:none;');
        jQuery("#general").attr('style', 'display:flex;');

        history.replaceState(undefined, '', "#general")
    });


    jQuery("#schema-tab").click(e => {
        jQuery(".tigon-dms-tab").removeClass("active");
        jQuery("#schema-tab").addClass("active");
        jQuery(".tabbed-panel .action-box").attr('style', 'display:none;');
        jQuery("#schema").attr('style', 'display:flex;');

        history.replaceState(undefined, '', "#schema")
    });

    const runOperation = (action) => {
        jQuery("#ops-result").text("Running...");
        jQuery.ajax({
            dataType: 'json',
            url: global.ajaxurl,
            data: { action }
        }).then((response) => {
            jQuery("#ops-result").text(JSON.stringify(response.stats || response));
        }).catch((err) => {
            jQuery("#ops-result").text(`Error: ${err.statusText || 'Unknown error'}`);
        });
    };

    jQuery("#btn-refresh-active").click(() => runOperation('tigon_dms_refresh_active_inventory'));
    jQuery("#btn-repull-dms").click(() => runOperation('tigon_dms_repull_dms_inventory'));
});
