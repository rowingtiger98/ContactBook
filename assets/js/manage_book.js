// -------------------------------------------------------------------------------------
// manage_book.js
// Client-side helpers for the entries table on the Manage a Book page (manage_book.php).
// Without JavaScript the Mark Entered buttons still work - the page reloads instead.
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

$(document).ready(function() {
  $('.entries_table').on('submit', '.entry_mark_form', MarkEntryEntered);
});
