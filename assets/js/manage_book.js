// -------------------------------------------------------------------------------------
// manage_book.js
// Client-side helpers for the Manage a Book page (manage_book.php): the book chooser's
// Manage button and the entries table. Without JavaScript both still work - Manage is
// always on, and Mark Entered reloads the page instead.
// -------------------------------------------------------------------------------------

// -------------------------------------------------------------------------------------
// Function: 	MarkEntryEntered
// Description: Sends a Mark Entered form in the background (mark_entry_entered.php
//              answers with JSON), then refreshes that row: the status icon becomes the
//              check mark and the button is switched off. If the answer isn't JSON -
//              for example the login has timed out - the form is sent normally, so the
//              user sees the login page or the message.
// -------------------------------------------------------------------------------------
function MarkEntryEntered(event) {
  var form = this;
  var button = $(form).find('.entry_mark_entered');
  event.preventDefault();
  if (button.prop('disabled')) {
    return;
  }
  button.prop('disabled', true);

  $.ajax({
    url: form.action,
    method: 'POST',
    data: $(form).serialize(),
    dataType: 'json',
    headers: { Accept: 'application/json' }
  }).done(function(result) {
    var row = $(form).closest('tr');
    row.find('.entries_status').html(result.status_html);
    button.attr('title', 'Already entered');
  }).fail(function(xhr) {
    var result = xhr.responseJSON;
    if (result && result.message) {
      button.prop('disabled', false);
      alert(result.message);
    } else {
      form.submit();
    }
  });
}

// -------------------------------------------------------------------------------------
// Function: 	UpdateManageButton
// Description: The Manage button is white and switched off until a book is chosen in
//              the dropdown (other than the one already open), then black and ready.
//              The page shows it white; without JavaScript it stays usable.
// -------------------------------------------------------------------------------------
function UpdateManageButton() {
  var select = $('#book');
  var chosen = select.val() || '';
  var ready = chosen !== '' && chosen !== String(select.data('current') || '');
  $('#manage_book_go').prop('disabled', !ready).toggleClass('special', ready);
}

$(document).ready(function() {
  $('.entries_table').on('submit', '.entry_mark_form', MarkEntryEntered);
  $('#book').on('change', UpdateManageButton);
  $(window).on('pageshow', UpdateManageButton);  // the browser may restore an earlier choice
  UpdateManageButton();
});
