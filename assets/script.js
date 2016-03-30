(function($, media) {

	var frame,
		ifc_box = $('#ifc_plugin'),
		addImgLink = $('.ifc_add', ifc_box),
		delImgLink = $('.ifc_delete', ifc_box),
		imgIdInput = $(':input:hidden', ifc_box);

	addImgLink.on('click', function(event) {

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if (frame) {
			frame.open();
			return;
		}

		// Create a new media frame
		frame = media({
			title: _wpMediaViewsL10n.ifcTitle,
			button: {
				text: _wpMediaViewsL10n.ifcBtn
			},
			multiple: false // Set to true to allow multiple files to be selected
		});


		// When an image is selected in the media frame...
		frame.on('select', function() {

			// Get media attachment details from the frame state
			var attachment = frame.state().get('selection').first().toJSON();

			// Send the attachment URL to our custom image input field.
			$('span', ifc_box).css('background-image', 'url(' + attachment.url + ')');

			// Send the attachment id to our hidden input
			imgIdInput.val(attachment.id);

			// Hide the add image link
			addImgLink.addClass('hidden');

			// Unhide the remove image link
			delImgLink.removeClass('hidden');
		});

		// Finally, open the modal on click
		frame.open();
	});

	delImgLink.on('click', function(event) {

		event.preventDefault();

		// Clear out the preview image
		$('span', ifc_box).removeAttr('style');

		// Un-hide the add image link
		addImgLink.removeClass('hidden');

		// Hide the delete image link
		delImgLink.addClass('hidden');

		// Delete the image id from the hidden input
		imgIdInput.val('');
	});

	$('#addtag #submit').on('click', function(ev) {

		if ($('#tag-name').val() == '') {
			return;
		}

		setTimeout(function(){
			if ($('#tag-name').val() == '') {
				delImgLink.trigger('click');
			}
		}, 2000);

	});

})(jQuery, wp.media);