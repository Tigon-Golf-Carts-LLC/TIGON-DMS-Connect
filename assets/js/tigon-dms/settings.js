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
        var settings = {
            "github_token": jQuery("#txt-github-token").val(),
            "dms_url": jQuery("#txt-url").val(),
            "user_token": jQuery("#txt-api-key").val(),
            "file_source": jQuery("#txt-file-source").val()
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
});