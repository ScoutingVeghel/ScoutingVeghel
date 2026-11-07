<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/subm/PHPMailer/src/Exception.php';
require __DIR__ . '/subm/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/subm/PHPMailer/src/SMTP.php';

require_once __DIR__ . '/portal-smtp.php';
require_once __DIR__ . '/portal-mail-log.php';

/**
 * Send the mail using the environment configured SMTP settings using PHP Mailer
 *
 * @param $toMail The mail address to send it to
 * @param $subject The subject of the mail
 * @param $message The message content of the mail
 * @param $replyToMail [optional] The mail address to reply to
 * @return [bool, message]
 */
function sendMail($toMail, $subject, $message, $replyToMail = '', $inlineImages = [], $fromName = null) {
    // Must be the one configured at the SMTP server (e.g. Office365 mailbox) that is authenticated to sent mails
    return sendMailWithFrom($toMail, $subject, $message, null, $replyToMail, $inlineImages, $fromName);
}

/**
 * INTERNAL use only, due to the authorized fromMail parameter
 *
 * @param $toMail The mail address to send it to
 * @param $subject The subject of the mail
 * @param $message The message content of the mail
 * @param $fromMail THe mail address from which to send it. Note that this must be an authorized address.
 * @param $replyToMail The mail address to reply to
 * @return [bool, message]
 */
function sendMailWithFrom(
    $toMail,
    $subject,
    $message,
    $fromMail,
    $replyToMail,
    $inlineImages = [],
    $fromName = null
) {
    try {
        if ($fromMail === null) {
            $fromMail = smtpConfiguration()['from'];
        }
        $result = deliverMailWithFrom(
            $toMail,
            $subject,
            $message,
            $fromMail,
            $replyToMail,
            $inlineImages,
            $fromName
        );
    } catch (\Throwable $error) {
        $result = [false, 'Message could not be sent.'];
    }
    try {
        mailLogRecord(
            $toMail,
            $subject,
            $fromMail,
            $message,
            !empty($result[0]),
            getenv('SV_ENV') === 'local'
        );
    } catch (\Throwable $error) {
        // A log failure must not invite a duplicate application or contact message.
        error_log('Scouting Veghel: mail log could not be written.');
    }
    return $result;
}

/** Sender names follow the mailbox role; no-reply does not change reply routing. */
function mailSenderName($address, $name = 'Website | Scouting Veghel') {
    $mailbox = strtolower(explode('@', $address, 2)[0]);
    if (in_array($mailbox, ['noreply', 'no-reply', 'no_reply'], true)) {
        return 'noreply | Scouting Veghel';
    }
    if (!is_string($name) || trim($name) === '' || preg_match('/[\r\n]/', $name)) {
        throw new InvalidArgumentException('Ongeldige afzendernaam.');
    }
    return trim($name);
}

function deliverMailWithFrom(
    $toMail,
    $subject,
    $message,
    $fromMail,
    $replyToMail,
    $inlineImages = [],
    $fromName = null
) {
    $smtp = smtpConfiguration();
    $fromName = mailSenderName($fromMail, $fromName ?? $smtp['from_name']);
    if (getenv('SV_ENV') === 'local') {
        $directory = getenv('SV_LOCAL_MAIL_DIR');
        if (!$directory || !is_dir($directory) || !is_writable($directory)) {
            return [false, 'Local mailbox unavailable'];
        }
        $file = $directory . '/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.json';
        $mail = json_encode(
            [
                'to' => $toMail,
                'from' => $fromMail,
                'from_name' => $fromName,
                'reply_to' => $replyToMail,
                'subject' => $subject,
                'html' => $message,
                'inline_images' => $inlineImages
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        if ($mail === false) {
            return [false, 'Local mail encoding failed'];
        }
        $handle = @fopen($file, 'x');
        if (!$handle) {
            return [false, 'Local mail storage failed'];
        }
        chmod($file, 0600);
        $saved = fwrite($handle, $mail) === strlen($mail);
        fclose($handle);
        return [$saved, $saved ? 'Mail saved locally; not sent' : 'Local mail storage failed'];
    }

    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    $result = false;
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp['port'];
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];

        // Must be the one configured at the SMTP server (e.g. Office365 mailbox) that is authenticated to sent mails
        $mail->setFrom($fromMail, $fromName);

        //Mail addresses
        $mail->addAddress($toMail);

        if (!empty($replyToMail)) {
            $mail->addReplyTo($replyToMail);
        }

        foreach ($inlineImages as $image) {
            $mail->addEmbeddedImage($image['path'], $image['cid'], $image['name']);
        }

        //Content
        $mail->isHTML(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Subject = $subject;
        $mail->Body = $message;

        //$mail->SMTPDebug  = 3;
        //$mail->Debugoutput = function($str, $level) {echo "debug level $level; message: $str <br>";};

        $result = $mail->send();
        return [$result, 'Message has been sent'];
    } catch (Exception $e) {
        return [$result, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

?>
