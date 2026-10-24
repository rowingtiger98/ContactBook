// -------------------------------------------------------------------------------------
// add_entry.js
// Client-side helpers for the Add Your Information page (add_entry.php).
// Without JavaScript the page still works, with a single email box.
// -------------------------------------------------------------------------------------

// -------------------------------------------------------------------------------------
// Function: 	UpdateEmailRows
// Description: Renumbers the email boxes, shows a remove button on every box but the
//              first, and hides the "+" button once the limit (data-max, kept in step
//              with ENTRY_EMAILS_MAX_COUNT in entryFunctions.php) is reached.
// -------------------------------------------------------------------------------------
function UpdateEmailRows() {
  var list = $('#entry_email_list');
  var rows = list.children('.entry_email_row');

  rows.each(function(i) {
    var input = $(this).find('input');
    input.attr('id', 'entry_email_' + (i + 1));
    if (i === 0) {
      input.removeAttr('aria-label');
    } else {
      input.attr('aria-label', 'Email ' + (i + 1));
    }
    $(this).find('.entry_email_remove').prop('hidden', i === 0);
  });

  $('#entry_email_add').prop('hidden', rows.length >= parseInt(list.data('max'), 10));
}

// -------------------------------------------------------------------------------------
// Function: 	AddEmailRow
// Description: Adds another, empty email box below the others and moves to it.
// -------------------------------------------------------------------------------------
function AddEmailRow() {
  var row = $('#entry_email_list .entry_email_row').first().clone();
  row.find('input').val('').attr('autocomplete', 'off').prop('required', false);  // only the first box is required
  $('#entry_email_list').append(row);
  UpdateEmailRows();
  row.find('input').trigger('focus');
}

// -------------------------------------------------------------------------------------
// Function: 	RemoveEmailRow
// Description: Removes the email box next to the "-" button that was pressed.
// -------------------------------------------------------------------------------------
function RemoveEmailRow() {
  $(this).closest('.entry_email_row').remove();
  UpdateEmailRows();
  $('#entry_email_add').trigger('focus');
}

// -------------------------------------------------------------------------------------
// Function: 	ShowEntryPhotoPreviews
// Description: Shrinks the chosen photos (photo_resize.js), then shows a small preview of
//              each. Clears the choice right away if there are too many photos or one is
//              still too large (data-max-count and data-max-bytes come from
//              entryFunctions.php; the server re-checks on Save).
// -------------------------------------------------------------------------------------
function ShowEntryPhotoPreviews() {
  var input = this;
  var previews = $('#entry_photo_previews');
  var maxCount = parseInt($(input).data('max-count'), 10);
  var maxBytes = parseInt($(input).data('max-bytes'), 10);

  previews.find('img').each(function() {
    URL.revokeObjectURL(this.src);
  });
  previews.empty();

  if ((input.files || []).length > maxCount) {
    alert('Please choose at most ' + maxCount + ' photos.');
    input.value = '';
    return;
  }

  ShrinkChosenPhotos(input).then(function(files) {
    if (files === null) {
      return;  // a newer choice replaced this one
    }
    for (var i = 0; i < files.length; i++) {
      if (files[i].size > maxBytes) {
        alert('"' + files[i].name + '" is too large. Please choose photos under ' + (maxBytes / 1048576) + ' MB.');
        input.value = '';
        return;
      }
    }
    for (var j = 0; j < files.length; j++) {
      $('<img>', { src: URL.createObjectURL(files[j]), alt: files[j].name, title: files[j].name }).appendTo(previews);
    }
  });
}

// -------------------------------------------------------------------------------------
// Function: 	ShowSavingSpinner
// Description: Covers the page with a spinner once Save is pressed, until the browser
//              loads the result (the thank-you page, or the form with an error). Also
//              stops a second press from saving the entry twice. The Save button isn't
//              disabled, because a disabled button isn't sent and add_entry.php checks
//              for it. If photos are still being shrunk, the form is sent when they're ready.
// -------------------------------------------------------------------------------------
function ShowSavingSpinner(event) {
  if ($(this).data('saving')) {
    event.preventDefault();
    return;
  }
  $(this).data('saving', true);
  $('#entry_saving').prop('hidden', false);
  WaitForPhotos(this, event, { save_entry: '1' });  // sends the form once photos are shrunk
}

// -------------------------------------------------------------------------------------
// Function: 	HideSavingSpinner
// Description: Browsers can restore a page from their back/forward cache exactly as it
//              was left - spinner and all. Reset it so the form can be used again.
// -------------------------------------------------------------------------------------
function HideSavingSpinner(event) {
  if (event.originalEvent && event.originalEvent.persisted) {
    $('#entry_form').data('saving', false);
    $('#entry_saving').prop('hidden', true);
  }
}

$(document).ready(function() {
  $('#entry_email_add').on('click', AddEmailRow);
  $('#entry_email_list').on('click', '.entry_email_remove', RemoveEmailRow);
  $('#entry_photos').on('change', ShowEntryPhotoPreviews);
  $('#entry_form').on('submit', ShowSavingSpinner);
  $(window).on('pageshow', HideSavingSpinner);
  UpdateEmailRows();
});
