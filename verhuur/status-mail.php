<?php
/** Send only after the status write and its database locks have completed. */
function adminStatusConfirmationMail($db, $rentalId) {
    global $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_WEBSITE, $VERIFY_BASE_URL,
        $HUURVOORWAARDEN_URL, $HUURVOOORWAARDEN_URL, $HUUROVEREENKOMST_BASE_URL, $PUBLIC_SITE_URL;
    try {
        $stmt = $db->prepare('SELECT h.naam,h.email,v.confirm FROM verhuur_verhuring v JOIN verhuur_huurder h ON h.id=v.huurder_id WHERE v.id=?');
        $stmt->bind_param('i', $rentalId);
        $stmt->execute();
        $stmt->bind_result($name, $email, $key);
        $found = $stmt->fetch();
        $stmt->close();
        if (!$found || !is_string($key) || trim($key) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Confirmation data unavailable');
        }
        $modern = __DIR__ . '/../php/portal-rental-mail.php';
        require_once is_file($modern) ? $modern : __DIR__ . '/../php/mail_layer.php';
        $result = sendDocuments($name, $email, $key);
        if (!is_array($result) || empty($result[0])) {
            throw new RuntimeException('Confirmation delivery failed');
        }
        return '<p class="success" role="status">De bevestigingsmail met verhuurdocumenten is verstuurd naar de huurder.</p>';
    } catch (Throwable $error) {
        error_log('Scouting Veghel: manual confirmation mail failed (' . get_class($error) . ').');
        return '<p class="error" role="alert">De status is opgeslagen, maar de bevestigingsmail kon niet worden verstuurd. Controleer het maillog en de contactgegevens; opnieuw opslaan verstuurt geen nieuwe mail.</p>';
    }
}
