// -------------------------------------------------------------------------------------
// account_reset.js
// Client-side helpers for the password reset page (account_reset.php).
// -------------------------------------------------------------------------------------

// -------------------------------------------------------------------------------------
// Function: 	CheckPasswordMatch
// Description: Colors the confirmation box and disables the Reset Password button
//              until both password boxes match. The server re-checks on submit.
// -------------------------------------------------------------------------------------
function CheckPasswordMatch() {
  var password1 = $("#new_password").val();
  var password2 = $("#new_password_confirm").val();
  var matches = (password2 !== '' && password1 === password2);

  $("#new_password_confirm").css('color', (password2 === '') ? '' : (matches ? 'green' : '#ff0000'));
  $("#reset_password_button")
    .prop('disabled', !matches)
    .val(matches || password2 === '' ? 'Reset Password' : 'Reset Password - Password Mismatch');
}

$(document).ready(function() {
  if ($("#new_password_confirm").length) {
    $("#new_password, #new_password_confirm").on('input', CheckPasswordMatch);
  }
});
