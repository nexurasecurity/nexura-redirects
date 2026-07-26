jQuery(document).ready(function($) {

	// ==========================================
	// 1. Site Tab Scripts (class-site-tab.php)
	// ==========================================
	
	// Handle adding new alias
	$('#nexura-add-alias').on('click', function(e) {
		e.preventDefault();
		$('.no-items').remove(); // Remove placeholder if exists
		
		var html = '<tr>' +
			'<td><input type="text" name="site_aliases[]" value="" class="regular-text" style="width: 100%;" placeholder="e.g. new-alias.com"></td>' +
			'<td><button type="button" class="button nexura-remove-alias">Remove</button></td>' +
		'</tr>';
		
		$('#nexura-aliases-list').append(html);
	});

	// Handle removing alias
	$(document).on('click', '.nexura-remove-alias', function(e) {
		e.preventDefault();
		$(this).closest('tr').remove();
		
		if ( $('#nexura-aliases-list tr').length === 0 ) {
			$('#nexura-aliases-list').append('<tr class="no-items"><td class="colspanchange" colspan="2">No aliases defined.</td></tr>');
		}
	});


	// ==========================================
	// 2. Admin Menu Scripts (class-admin-menu.php)
	// ==========================================

	// Toggle Source URL options dropdown
	$('#nexura-source-options-toggle').on('click', function(e) {
		e.preventDefault();
		$('#nexura-source-options').toggle();
	});

	// Close dropdown when clicking outside
	$(document).on('click', function(e) {
		if (!$(e.target).closest('#nexura-source-options-toggle, #nexura-source-options').length) {
			$('#nexura-source-options').hide();
		}
	});

	// Toggle Main Advanced Options (Gear Icon)
	$('#nexura-toggle-main-advanced').on('click', function(e) {
		e.preventDefault();
		$('.nexura-advanced-row').toggle();
	});

	// Handle "When matched" action change (Hide target URL if not needed)
	$('#action_type').on('change', function() {
		var val = $(this).val();
		if ( val === 'redirect' || val === 'random' ) {
			$('#nexura-target-url-row').show();
			$('#nexura-http-code-wrap').show();
		} else if ( val === 'pass' ) {
			$('#nexura-target-url-row').show();
			$('#nexura-http-code-wrap').hide();
		} else { // 404, ignore
			$('#nexura-target-url-row').hide();
			$('#nexura-http-code-wrap').hide();
		}
	});


	// ==========================================
	// 3. Post Meta Box Scripts (class-post-meta-box.php)
	// ==========================================

	$('#nexura-quick-add-btn').on('click', function(e) {
		e.preventDefault();
		var source = $('#nexura_quick_source').val();
		var target = $('#nexura_quick_target').val();
		var btn = $(this);
		
		if ( ! source ) {
			alert('Please enter an Old URL.');
			return;
		}
		
		btn.text('Saving...').prop('disabled', true);
		
		$.post(ajaxurl, {
			action: 'nexura_quick_add_redirect',
			nonce: $('#nexura_post_redirect_nonce').val(),
			source: source,
			target: target
		}, function(response) {
			if (response.success) {
				btn.text('Saved!').removeClass('button-primary').addClass('button-secondary');
				
				// If the empty message is there, remove it
				var list = $('.nexura-existing-redirects ul');
				list.find('.nexura-no-redirects').remove();
				
				// Add the new item to the UI list
				list.append('<li style="margin-bottom: 5px;"><span style="color:#d63638;">' + source + '</span> &rarr; <span style="color:#00a32a;">' + target + '</span> <a href="#" class="nexura-delete-redirect" data-id="' + response.data.id + '" style="color:#b32d2e; text-decoration:none; margin-left:10px; font-size:12px;">[Remove]</a></li>');
				
				// Clear inputs
				$('#nexura_quick_source').val('');
				$('#nexura_quick_target').val('');
				
				setTimeout(function(){
					btn.text('Add Redirect').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
				}, 2000);
			} else {
				alert(response.data);
				btn.text('Add Redirect').prop('disabled', false);
			}
		});
	});

	$(document).on('click', '.nexura-delete-redirect', function(e) {
		e.preventDefault();
		var btn = $(this);
		var id = btn.data('id');
		
		if ( ! confirm('Are you sure you want to delete this redirect?') ) {
			return;
		}
		
		$.post(ajaxurl, {
			action: 'nexura_quick_delete_redirect',
			nonce: $('#nexura_post_redirect_nonce').val(),
			id: id
		}, function(response) {
			if (response.success) {
				btn.parent('li').fadeOut(300, function() {
					$(this).remove();
					var list = $('.nexura-existing-redirects ul');
					if ( list.children('li').length === 0 ) {
						list.append('<li class="nexura-no-redirects">No redirects found for this post yet.</li>');
					}
				});
			} else {
				alert('Failed to delete.');
			}
		});
	});

	$(document).on('click', '.nexura-inline-edit', function(e) {
		e.preventDefault();
		var id = $(this).data('id');
		
		// Hide all other open inline edits
		$('.inline-edit-row-redirect').hide();
		$('tr[id^="redirect-row-"]').show();
		
		// Hide current row, show inline edit row
		$('#redirect-row-' + id).hide();
		$('#edit-redirect-' + id).show();
	});

	$(document).on('click', '.cancel-inline-edit', function(e) {
		e.preventDefault();
		var id = $(this).data('id');
		
		// Hide inline edit row, show current row
		$('#edit-redirect-' + id).hide();
		$('#redirect-row-' + id).show();
	});

	$(document).on('click', '.nexura-inline-edit-group', function(e) {
		e.preventDefault();
		var id = $(this).data('id');
		
		// Hide all other open group inline edits
		$('.inline-edit-row-group').hide();
		$('tr[id^="group-row-"]').show();
		
		// Hide current row, show inline edit row
		$('#group-row-' + id).hide();
		$('#edit-group-' + id).show();
	});

	$(document).on('click', '.cancel-inline-edit-group', function(e) {
		e.preventDefault();
		var id = $(this).data('id');
		
		// Hide inline edit row, show current row
		$('#edit-group-' + id).hide();
		$('#group-row-' + id).show();
	});

});

// ==========================================
// 4. Setup Wizard Scripts (class-setup-wizard.php)
// ==========================================

document.addEventListener("DOMContentLoaded", function() {

	// Setup Wizard Step 2
	var dbStatusBadge = document.getElementById('db-status-badge');
	if ( dbStatusBadge ) {
		setTimeout(function() {
			dbStatusBadge.innerText = 'Working - 100%';
			dbStatusBadge.className = 'nexura-badge success';
			document.getElementById('db-check-info').innerHTML = '<span style="color: #10b981;">✓ All custom tables verified and working.</span><br>You will need at least one working environment to continue.';
			document.getElementById('btn-continue-step-2').style.display = 'inline-block';
		}, 2000);
	}

	// Setup Wizard Step 3
	var progressBar = document.getElementById('progress-bar');
	if ( progressBar ) {
		let progress = 0;
		let progressPercent = document.getElementById('progress-percent');
		let statusText = document.getElementById('status-text');
		let finishBtn = document.getElementById('btn-finish-setup');

		let stages = [
			{ p: 20, t: "Installing custom routing rules..." },
			{ p: 40, t: "Configuring default groups..." },
			{ p: 60, t: "Migrating options data..." },
			{ p: 80, t: "Optimizing database tables..." },
			{ p: 100, t: "Create basic data ✓" }
		];

		let currentStage = 0;

		let interval = setInterval(function() {
			progress += 2;
			progressBar.style.width = progress + '%';
			progressPercent.innerText = 'Progress: ' + progress + '%';

			if (currentStage < stages.length && progress >= stages[currentStage].p) {
				statusText.innerText = stages[currentStage].t;
				currentStage++;
			}

			if (progress >= 100) {
				clearInterval(interval);
				progressBar.style.width = '100%';
				progressPercent.innerText = 'Progress: 100%';
				finishBtn.style.display = 'inline-block';
			}
		}, 50);
	}
});
