// -------------------------------------------------------------------------------------
// index.js
// Client-side helpers for the front page (index.php). Without JavaScript the search
// box stays hidden and every book card shows.
// -------------------------------------------------------------------------------------

// -------------------------------------------------------------------------------------
// Function: 	NormalizeForSearch
// Description: Lowercases text and drops accents, so "jose" finds "José Núñez".
// -------------------------------------------------------------------------------------
function NormalizeForSearch(text) {
  text = String(text).toLowerCase();
  if (text.normalize) {
    text = text.normalize('NFD').replace(/[̀-ͯ]/g, '');
  }
  return text.replace(/\s+/g, ' ').trim();
}

// -------------------------------------------------------------------------------------
// Function: 	FilterBookCards
// Description: Runs on every letter typed: shows only the cards whose name contains
//              what's in the search box, or a "No books match" note if none do.
// -------------------------------------------------------------------------------------
function FilterBookCards() {
  var search = NormalizeForSearch($('#book_search').val());
  var shown = 0;

  $('#book_cards .book_card_cell').each(function() {
    var match = NormalizeForSearch($(this).data('name')).indexOf(search) !== -1;
    $(this).prop('hidden', !match);
    if (match) {
      shown++;
    }
  });

  $('#book_search_none span').text($('#book_search').val().trim());
  $('#book_search_none').prop('hidden', shown > 0);
}

$(document).ready(function() {
  if ($('#book_cards').length) {
    $('#book_search_box').prop('hidden', false);
    $('#book_search').on('input', FilterBookCards);
    FilterBookCards();  // the browser may have kept text from before a Back
  }
});
