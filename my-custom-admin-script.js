jQuery(document).ready(function($) {
    var custom_uploader;

    function initializeUploader($button) {
        $button.on('click', function(e) {
            e.preventDefault();
            // If the uploader object has already been created, open it
            if (custom_uploader) {
                custom_uploader.open();
                return;
            }
            // Create the media frame
            custom_uploader = wp.media({
                title: 'Choose PDFs',
                button: {
                    text: 'Use these PDFs'
                },
                multiple: true, // Allow multiple files
                library: {
                    type: 'application/pdf' // Filter to PDFs
                }
            });
            // When files are selected, grab the URLs and set them as the values of the inputs
            custom_uploader.on('select', function() {
                var attachments = custom_uploader.state().get('selection').toJSON();
                $.each(attachments, function(index, attachment) {
                    var $row = $('<div class="pdf-row" data-id="' + attachment.id + '" style="display: flex; justify-content: flex-start; align-items: center; gap: 5px; margin-bottom: 10px; border-bottom: 1px solid black; padding-bottom:10px;"></div>');
                    var pdfIconUrl = wp.media.view.settings.mimeTypeIcons && wp.media.view.settings.mimeTypeIcons.pdf 
                        ? wp.media.view.settings.mimeTypeIcons.pdf 
                        : '/path-to-your-fallback-icon/pdf-icon.png'; // Replace with a valid fallback URL
                    var fileName = attachment.filename || 'No name';
                    $row.append('<input type="hidden" name="pdf_ids[]" value="' + attachment.id + '" />');
                    $row.append('<img src="' + pdfIconUrl + '" style="max-width: 36px;" alt="PDF Icon" class="pdf-icon" />');
                    $row.append('<span class="pdf-name">' + fileName + '</span>');
                    $row.append('<input type="button" class="delete_pdf_button button" value="Delete" />');
                    $('#pdfs-container').prepend($row);
                });
                updateRowEvents();
            });
            // Open the media frame
            custom_uploader.open();
        });
    }

    function updateRowEvents() {
        // Initialize uploader for add button
        initializeUploader($('#add_pdf_button'));

        // Bind delete functionality using event delegation
        $(document).on('click', '.delete_pdf_button', function(e) {
            e.preventDefault();
            $(this).closest('.pdf-row').remove();
        });
    }

    // Initialize jQuery UI Sortable
    $('#pdfs-container').sortable({
        placeholder: 'ui-state-highlight',
        cursor: 'move', // Change cursor when dragging
        start: function(event, ui) {
            ui.placeholder.css({
                height: ui.helper.outerHeight(),
                backgroundColor: '#f0f0f0'
            });
            $('body').css('cursor', 'move'); // Change cursor when dragging starts
        },
        stop: function(event, ui) {
            $('body').css('cursor', 'auto'); // Revert cursor when dragging stops
            // The items have been reordered. Send an AJAX request to save the new order
            var ids = [];
            $('#pdfs-container .pdf-row').each(function() {
                ids.push($(this).data('id'));
            });

            // Send the new order to the server via AJAX
            var data = {
                action: 'update_pdf_order',
                ids: ids,
                post_id: wpvars.post_id, // Pass the post ID if needed
                nonce: wpvars.nonce // Pass a nonce for security
            };

            $.post(wpvars.ajax_url, data, function(response) {
                // Handle response if needed
                console.log(response);
            });
        }
    }).disableSelection();

    // Initialize row events after sortable initialization
    updateRowEvents();
});
