// -------------------------------------------------------------------------------------
// create_book.js
// Client-side helpers for the book photo on the Create a New Book page (create_book.php)
// and the Book Details form on the Manage a Book page (manage_book.php).
// -------------------------------------------------------------------------------------

var BOOK_PHOTO_MAX_BYTES = 10 * 1048576;  // keep in step with bookFunctions.php

// -------------------------------------------------------------------------------------
// Function: 	ShowBookPhotoPreview
// Description: Shows a preview of the chosen photo. With no photo chosen it shows the
//              book's current photo (data-current-src, on Manage a Book) unless
//              "Remove this photo" is ticked, or else the camera placeholder. Clears the
//              choice if it's still too large after shrinking (the server re-checks on Save).
// -------------------------------------------------------------------------------------
function ShowBookPhotoPreview() {
  var input = document.getElementById('book_photo');
  var preview = document.getElementById('book_photo_preview');
  var placeholder = document.getElementById('book_photo_placeholder');
  var remove = document.getElementById('remove_photo');
  var currentSrc = preview.getAttribute('data-current-src');
  var file = input.files && input.files[0];

  if (preview.src && preview.src.indexOf('blob:') === 0) {
    URL.revokeObjectURL(preview.src);
  }

  if (file && file.size > BOOK_PHOTO_MAX_BYTES) {
    alert('That photo is too large. Please choose one under ' + (BOOK_PHOTO_MAX_BYTES / 1048576) + ' MB.');
    input.value = '';
    file = null;
  }

  if (remove) {
    // A new photo replaces the current one, so "remove" doesn't apply.
    remove.disabled = !!file;
    if (file) {
      remove.checked = false;
    }
  }

  var src = file ? URL.createObjectURL(file) : ((currentSrc && !(remove && remove.checked)) ? currentSrc : '');
  if (src) {
    preview.src = src;
    preview.hidden = false;
    placeholder.hidden = true;
  } else {
    preview.removeAttribute('src');
    preview.hidden = true;
    placeholder.hidden = false;
  }
}

// -------------------------------------------------------------------------------------
// Function: 	ShrinkBookPhoto
// Description: Shrinks a newly chosen photo (photo_resize.js), then updates the preview.
// -------------------------------------------------------------------------------------
function ShrinkBookPhoto() {
  ShrinkChosenPhotos(this).then(function(files) {
    if (files !== null) {
      ShowBookPhotoPreview();
    }
  });
}

// -------------------------------------------------------------------------------------
// Function: 	WaitForBookPhoto
// Description: If Save is pressed while the photo is still being shrunk, sends the form
//              once it's ready.
// -------------------------------------------------------------------------------------
function WaitForBookPhoto(event) {
  WaitForPhotos(this, event);
}

$(document).ready(function() {
  $('#book_photo').on('change', ShrinkBookPhoto);
  $('#book_photo').closest('form').on('submit', WaitForBookPhoto);
  $('#remove_photo').on('change', ShowBookPhotoPreview);
});
