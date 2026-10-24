<?php
/*-------------------------------------------------------------*/
/*                                                             */
/*  MAIL FUNCTIONS                                             */
/*                                                             */
/*  Every email the site sends goes through send_site_email(), */
/*  using PHP mail() (Bluehost supports it out of the box).    */
/*  Messages come from MAIL_FROM (config.php), which must be   */
/*  an address on the site's own domain - mail claiming to be  */
/*  from gmail.com etc. is rejected or marked as spam.         */
/*                                                             */
/*-------------------------------------------------------------*/
require_once __DIR__ . '/config.php';

/* *********************************************************** */
/* Function:    send_site_email                                */
/* Description: Sends an HTML email with a plain-text copy     */
/*              (spam filters distrust HTML-only mail). The    */
/*              sender is "Contact Book <MAIL_FROM>", and the  */
/*              envelope sender (-f) is MAIL_FROM too, so      */
/*              bounces come back to it and SPF checks match   */
/*              the From address.                              */
/* Inputs:      $to - recipient address (already validated).   */
/*              $subject - plain text; may contain accents.    */
/*              $html - the message body as HTML.              */
/* Returns:     true if mail() accepted the message.           */
/* *********************************************************** */
function send_site_email(string $to, string $subject, string $html): bool {
    $from_name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Contact Book';
    if (filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL) === false) {
        error_log('ContactBook: MAIL_FROM in config.php is not a valid email address');
        return false;
    }

    // Plain-text copy: each link written out after its text (or alone, if its text is
    // the address), line breaks kept and paragraphs separated by blank lines.
    $text = preg_replace_callback('/<a\s[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is', function (array $m): string {
        return ($m[2] === $m[1]) ? $m[1] : $m[2] . ': ' . $m[1];
    }, $html);
    $text = preg_replace(['/<br\s*\/?>/i', '/<\/p>/i'], ["\n", "\n\n"], $text);
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    $boundary = 'cb-' . bin2hex(random_bytes(12));
    $body = "--$boundary\r\n"
          . "Content-Type: text/plain; charset=utf-8\r\n"
          . "Content-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($text))
          . "--$boundary\r\n"
          . "Content-Type: text/html; charset=utf-8\r\n"
          . "Content-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode('<html><body>' . $html . '</body></html>'))
          . "--$boundary--\r\n";

    $headers = 'MIME-Version: 1.0' . "\r\n"
             . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n"
             . 'From: ' . mb_encode_mimeheader($from_name, 'UTF-8') . ' <' . MAIL_FROM . '>' . "\r\n";

    return mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $body, $headers, '-f' . MAIL_FROM);
}
