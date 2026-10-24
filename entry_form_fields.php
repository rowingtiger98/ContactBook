<?php
/**
 * entry_form_fields.php
 *
 * The fields of an entry's edit form (name, address, phone, emails, photos and
 * notes), shared by edit_entry.php (admins) and edit_my_entry.php (the person who
 * made the entry, from their emailed link). Include it inside the form's
 * <div class="row uniform">. Expects:
 *   $entry      - the fields, as from find_entry() / clean_entry_form()
 *   $emails     - $entry['emails'], with at least one (possibly empty) item
 *   $photos     - the entry's current photo filenames (list_entry_photos())
 *   $book_uuid, $entry_uuid - for the photo addresses
 *   $form_error - the error being shown, or ''
 * Works with add_entry.js (email +/- buttons, photo previews, saving spinner).
 */
?>
							<div class="12u$">
								<label for="entry_name">Name</label>
								<input type="text" name="entry_name" id="entry_name" value="<?php echo htmlspecialchars($entry['name']); ?>"
									maxlength="<?php echo ENTRY_NAME_MAX_LENGTH; ?>" autocomplete="off" required />
							</div>
							<div class="12u$">
								<label for="entry_street">Street Address</label>
								<input type="text" name="entry_street" id="entry_street" value="<?php echo htmlspecialchars($entry['street']); ?>"
									maxlength="<?php echo ENTRY_STREET_MAX_LENGTH; ?>" autocomplete="off" />
							</div>
							<div class="6u 12u$(small)">
								<label for="entry_city">City</label>
								<input type="text" name="entry_city" id="entry_city" value="<?php echo htmlspecialchars($entry['city']); ?>"
									maxlength="<?php echo ENTRY_CITY_MAX_LENGTH; ?>" autocomplete="off" />
							</div>
							<div class="3u 6u(small)">
								<label for="entry_state">State</label>
								<input type="text" name="entry_state" id="entry_state" value="<?php echo htmlspecialchars($entry['state']); ?>"
									maxlength="<?php echo ENTRY_STATE_MAX_LENGTH; ?>" autocomplete="off" />
							</div>
							<div class="3u$ 6u$(small)">
								<label for="entry_zip">Zip</label>
								<input type="text" name="entry_zip" id="entry_zip" value="<?php echo htmlspecialchars($entry['zip']); ?>"
									maxlength="<?php echo ENTRY_ZIP_MAX_LENGTH; ?>" autocomplete="off" />
							</div>
							<div class="12u$">
								<label for="entry_phone">Phone</label>
								<input type="tel" name="entry_phone" id="entry_phone" value="<?php echo htmlspecialchars($entry['phone']); ?>"
									maxlength="<?php echo ENTRY_PHONE_MAX_LENGTH; ?>" placeholder="(555) 123-4567" autocomplete="off" />
							</div>
							<div class="12u$">
								<label for="entry_email_1">Email</label>
								<div id="entry_email_list" data-max="<?php echo ENTRY_EMAILS_MAX_COUNT; ?>">
								<?php foreach ($emails as $i => $email) { ?>
									<div class="entry_email_row">
										<input type="email" name="entry_emails[]" id="entry_email_<?php echo $i + 1; ?>"
											value="<?php echo htmlspecialchars($email); ?>" autocomplete="off"
											<?php if ($i === 0) { ?>required<?php } else { ?>aria-label="Email <?php echo $i + 1; ?>"<?php } ?> />
										<button type="button" class="button small entry_email_remove" title="Remove this email"
											aria-label="Remove this email"<?php if ($i === 0) { ?> hidden<?php } ?>><i class="fa fa-minus" aria-hidden="true"></i></button>
									</div>
								<?php } ?>
								</div>
								<button type="button" id="entry_email_add" class="button small" title="Add another email address" hidden>
									<i class="fa fa-plus" aria-hidden="true"></i> Add another email
								</button>
							</div>
							<div class="12u$">
								<label for="entry_photos">Photos</label>
								<?php if ($photos) { ?>
								<div class="entry_photo_current">
									<?php foreach ($photos as $i => $photo) { ?>
									<div class="entry_photo_item">
										<a href="<?php echo htmlspecialchars(entry_photo_url($book_uuid, $entry_uuid, $photo)); ?>" target="_blank" rel="noopener" title="Open full size">
											<img src="<?php echo htmlspecialchars(entry_photo_url($book_uuid, $entry_uuid, $photo)); ?>" alt="Photo <?php echo $i + 1; ?>" />
										</a>
										<input type="checkbox" name="remove_photos[]" id="remove_photo_<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($photo); ?>" />
										<label for="remove_photo_<?php echo $i + 1; ?>">Remove</label>
									</div>
									<?php } ?>
								</div>
								<?php } ?>
								<input type="file" name="entry_photos[]" id="entry_photos" accept="image/jpeg,image/png,image/webp" multiple
									data-max-count="<?php echo ENTRY_PHOTOS_MAX_COUNT; ?>" data-max-bytes="<?php echo BOOK_PHOTO_MAX_BYTES; ?>" />
								<p class="field_note">Add photos (JPEG, PNG or WebP - large photos are made smaller automatically).
								An entry can have up to <?php echo ENTRY_PHOTOS_MAX_COUNT; ?> photos in all.
								<?php if ($form_error !== '') { ?>If you chose photos, please choose them again.<?php } ?></p>
								<div id="entry_photo_previews" class="entry_photo_previews"></div>
							</div>
							<div class="12u$">
								<label for="entry_notes">Notes</label>
								<textarea name="entry_notes" id="entry_notes" rows="5" maxlength="<?php echo ENTRY_NOTES_MAX_LENGTH; ?>"><?php echo htmlspecialchars($entry['notes']); ?></textarea>
							</div>
