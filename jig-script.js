jQuery(document).ready(function($) {
    // Log to confirm script is loaded
    console.log('JIG: iframe generator script loaded.');

    // Initialize WordPress Color Picker
    if (typeof $.fn.wpColorPicker === 'function') {
        $('#jig-bg-color-content, #jig-text-color-content, #jig-link-hover-color-content, #jig-iframe-border-color').wpColorPicker({
            change: function(event, ui) {
                // Trigger preview update on color change for a more dynamic experience
                if ($('#jig-generate-button').data('preview-active')) { // Only if a preview has been generated once
                     $('#jig-generate-button').trigger('click');
                }
            },
            clear: function() {
                if ($('#jig-generate-button').data('preview-active')) {
                    $('#jig-generate-button').trigger('click');
                }
            }
        });
        console.log('JIG: ColorPickers initialized.');
    } else {
        console.warn('JIG: wpColorPicker function not available.');
        $('#jig-message-area').text('Fehler: ColorPicker-Skript nicht geladen. Styling-Anpassungen sind möglicherweise eingeschränkt.').addClass('error').show();
    }

    // Button click handler
    $('#jig-generate-button').on('click', function() {
        console.log('JIG: Generate button clicked.');
        $('#jig-message-area').hide().removeClass('error success');

        // Get configuration values
        var bgColorContent = $('#jig-bg-color-content').val() || '#FFFFFF';
        var textColorContent = $('#jig-text-color-content').val() || '#333333';
        var linkHoverColorContent = $('#jig-link-hover-color-content').val() || '#0073aa';
        var iframeBorderColor = $('#jig-iframe-border-color').val() || '#CCCCCC';

        // Validate colors (basic check for hex format)
        var hexColorPattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        if (!hexColorPattern.test(bgColorContent) || !hexColorPattern.test(textColorContent) || 
            !hexColorPattern.test(linkHoverColorContent) || !hexColorPattern.test(iframeBorderColor)) {
            $('#jig-message-area').text('Fehler: Bitte gültige Hex-Farbcodes verwenden (z.B. #FF0000).').addClass('error').show();
            console.error('JIG: Invalid hex color detected.');
            return;
        }
        
        // Check if jig_ajax and its properties are available
        if (typeof jig_ajax === 'undefined' || !jig_ajax.ajax_url || !jig_ajax.nonce || !jig_ajax.current_page_url) {
            $('#jig-message-area').text(jig_ajax.txt_js_error || 'Fehler: Plugin-Konfigurationsdaten (jig_ajax) fehlen oder sind unvollständig. Seite neu laden und erneut versuchen.').addClass('error').show();
            console.error('JIG: jig_ajax object or its properties are missing. Data:', jig_ajax);
            return;
        }

        var sourceUrl = jig_ajax.current_page_url;
        if (!sourceUrl) {
            $('#jig-message-area').text(jig_ajax.txt_error_url || 'Fehler: Die Quell-URL der aktuellen Seite konnte nicht ermittelt werden.').addClass('error').show();
            console.error('JIG: current_page_url is empty.');
            return;
        }

        console.log('JIG: Values collected:', { bgColorContent, textColorContent, linkHoverColorContent, iframeBorderColor, sourceUrl });

        // Construct the iframe content URL for AJAX request
        var iframeContentUrl = new URL(jig_ajax.ajax_url);
        iframeContentUrl.searchParams.append('action', 'jig_get_iframe_content');
        iframeContentUrl.searchParams.append('nonce', jig_ajax.nonce);
        iframeContentUrl.searchParams.append('source_url', sourceUrl);
        iframeContentUrl.searchParams.append('bg_color', bgColorContent);
        iframeContentUrl.searchParams.append('text_color', textColorContent);
        iframeContentUrl.searchParams.append('hover_color', linkHoverColorContent);
        // Note: iframe_border_color is for the iframe tag itself, not its content.

        console.log('JIG: Preview iframe URL:', iframeContentUrl.toString());

        // Update preview iframe
        var $previewIframe = $('#jig-preview-iframe');
        if ($previewIframe.length) {
            $previewIframe.css('border-color', iframeBorderColor); // Update border color of the preview iframe itself
            $previewIframe.attr('src', iframeContentUrl.toString());
            $(this).data('preview-active', true); // Mark that a preview has been generated
            console.log('JIG: Preview iframe src updated.');
        } else {
            console.warn('JIG: Preview iframe #jig-preview-iframe not found.');
        }

        // Generate and display the iframe embed code
        var $generatedCodeTextarea = $('#jig-generated-code');
        if ($generatedCodeTextarea.length) {
            var embedCode = '<iframe src="' + iframeContentUrl.toString() + '" ' +
                            'style="width:100%; height:500px; border:1px solid ' + iframeBorderColor + '; ' +
                            'background-color:' + bgColorContent + ';" ' + // bg for iframe itself for better visual consistency before content loads
                            'frameborder="0" scrolling="auto" ' +
                            'title="Jobliste"></iframe>';
            $generatedCodeTextarea.val(embedCode).focus().select();
            console.log('JIG: Generated code textarea updated.');
            $('#jig-message-area').text('Vorschau aktualisiert und Code generiert!').addClass('success').show();
        } else {
            console.warn('JIG: Generated code textarea #jig-generated-code not found.');
             $('#jig-message-area').text('Textarea für Code nicht gefunden.').addClass('error').show();
        }
    });

    // Initial message if needed, or clear any existing server-side messages
    // $('#jig-message-area').text('Konfigurieren Sie Ihren iFrame und klicken Sie auf "Vorschau & Code generieren".').addClass('info').show();
    console.log('JIG: Script initialization complete.');
});
