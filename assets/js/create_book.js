// -------------------------------------------------------------------------------------
// create_book.js
// Client-side helpers for the Create a New Book page (create_book.php).
// -------------------------------------------------------------------------------------

var BOOK_PHOTO_MAX_BYTES = 10 * 1048576;  // keep in step with bookFunctions.php

// -------------------------------------------------------------------------------------
// Function: 	ShowBookPhotoPreview
// Description: Shows a preview of the chosen photo (or the camera placeholder when
//              there isn't one), and clears the choice right away if it's too large
//              (the server re-checks everything on Save).
// -------------------------------------------------------------------------------------
function ShowBookPhotoPreview() {
  var preview = document.getElementById('book_photo_preview');
  var placeholder = document.getElementById('book_photo_placeholder');
  var file = this.files && this.files[0];

  if (preview.src) {
    URL.revokeObjectURL(preview.src);
  }
  preview.hidden = true;
  preview.removeAttribute('src');
  placeholder.hidden = false;

  if (!file) {
    return;
  }
  if (file.size > BOOK_PHOTO_MAX_BYTES) {
    alert('That photo is too large. Please choose one under ' + (BOOK_PHOTO_MAX_BYTES / 1048576) + ' MB.');
    this.value = '';
    return;
  }

  preview.src = URL.createObjectURL(file);
  preview.hidden = false;
  placeholder.hidden = true;
}

$(document).ready(function() {
  $("#book_photo").on('change', ShowBookPhotoPreview);
});
