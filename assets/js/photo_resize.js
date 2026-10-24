// -------------------------------------------------------------------------------------
// photo_resize.js
// Shrinks chosen photos in the browser before they upload, so photos of any resolution
// (48-200 megapixel phone photos included) upload quickly and fit the server's limits.
// Used by the entry forms (add_entry.js) and the book photo forms (create_book.js).
// The server still checks and re-encodes every photo; if the browser can't shrink a
// photo, the original is sent and the server's limits apply as before.
// -------------------------------------------------------------------------------------

var PHOTO_MAX_DIMENSION = 1600;  // keep in step with BOOK_PHOTO_MAX_DIMENSION in bookFunctions.php
var PHOTO_JPEG_QUALITY  = 0.9;

// -------------------------------------------------------------------------------------
// Function: 	ShrinkPhoto
// Description: Scales one photo down to fit PHOTO_MAX_DIMENSION. Browsers turn phone
//              photos the right way up when they load them, and drawing onto a canvas
//              drops hidden data such as GPS location. PNGs stay PNG (to keep
//              transparency); everything else becomes JPEG.
// Inputs:      file - a File from a file input.
// Returns:     A Promise for the smaller File, or the original if it is already small
//              enough or can't be shrunk here.
// -------------------------------------------------------------------------------------
function ShrinkPhoto(file) {
  return new Promise(function(resolve) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || !window.URL || !HTMLCanvasElement.prototype.toBlob) {
      resolve(file);
      return;
    }
    var url = URL.createObjectURL(file);
    var img = new Image();
    img.onload = function() {
      URL.revokeObjectURL(url);
      var width = img.naturalWidth;
      var height = img.naturalHeight;
      var scale = Math.min(1, PHOTO_MAX_DIMENSION / Math.max(width, height));
      if (scale === 1) {
        resolve(file);  // small enough already
        return;
      }
      var canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(width * scale));
      canvas.height = Math.max(1, Math.round(height * scale));
      var context = canvas.getContext('2d');
      context.imageSmoothingQuality = 'high';
      context.drawImage(img, 0, 0, canvas.width, canvas.height);
      var type = (file.type === 'image/png') ? 'image/png' : 'image/jpeg';
      canvas.toBlob(function(blob) {
        if (!blob) {
          resolve(file);
          return;
        }
        var name = file.name.replace(/\.[^.]*$/, '') + (type === 'image/png' ? '.png' : '.jpg');
        try {
          resolve(new File([blob], name, { type: type, lastModified: file.lastModified }));
        } catch (e) {
          resolve(file);  // very old browsers can't make a File
        }
      }, type, PHOTO_JPEG_QUALITY);
    };
    img.onerror = function() {
      URL.revokeObjectURL(url);
      resolve(file);  // let the server decide
    };
    img.src = url;
  });
}

// -------------------------------------------------------------------------------------
// Function: 	ShrinkChosenPhotos
// Description: Shrinks every photo chosen in a file input and puts the smaller copies
//              back in the input, so they are what the form uploads. While it works,
//              the input's "shrinking" data holds the Promise (see WaitForPhotos).
// Inputs:      input - the file input element.
// Returns:     A Promise for the input's files once done, or null if the choice was
//              changed again in the meantime (only the latest choice counts).
// -------------------------------------------------------------------------------------
function ShrinkChosenPhotos(input) {
  var files = Array.prototype.slice.call(input.files || []);
  var token = {};
  $(input).data('shrink_token', token);

  var promise = Promise.all(files.map(ShrinkPhoto)).then(function(shrunk) {
    if ($(input).data('shrink_token') !== token) {
      return null;
    }
    try {
      var transfer = new DataTransfer();
      shrunk.forEach(function(file) {
        transfer.items.add(file);
      });
      input.files = transfer.files;
    } catch (e) {
      // Browsers without DataTransfer upload the originals.
    }
    $(input).removeData('shrinking');
    return input.files;
  });
  $(input).data('shrinking', promise);
  return promise;
}

// -------------------------------------------------------------------------------------
// Function: 	WaitForPhotos
// Description: Submit handler helper. If photos in the form are still being shrunk,
//              stops this submit and sends the form once they are ready.
// Inputs:      form - the form element. event - the submit event.
//              extra - optional {name: value} added as hidden inputs before sending
//              (form.submit() leaves out the name of the button that was pressed).
// Returns:     true if it is waiting (the caller should show its spinner and stop).
// -------------------------------------------------------------------------------------
function WaitForPhotos(form, event, extra) {
  var pending = $(form).find('input[type="file"]').map(function() {
    return $(this).data('shrinking');
  }).get();
  if (pending.length === 0) {
    return false;
  }
  event.preventDefault();
  Promise.all(pending).then(function() {
    $.each(extra || {}, function(name, value) {
      $('<input>', { type: 'hidden', name: name, value: value }).appendTo(form);
    });
    form.submit();
  });
  return true;
}
