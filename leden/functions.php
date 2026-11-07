<?php
function le($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function lq($sql, $types = '', $values = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$values);
    $st->execute();
    return $st;
}
function ltext($name, $max) {
    $v = $_POST[$name] ?? '';
    if (!is_string($v) || trim($v) === '' || strlen(trim($v)) > $max) throw new InvalidArgumentException('Vul alle gegevens correct in.');
    return trim($v);
}
function ldate() {
    $v = ltext('date', 10); $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v || $v > date('Y-m-d')) throw new InvalidArgumentException('Kies een geldige datum, uiterlijk vandaag. Toekomstige mutaties worden nog niet ondersteund.');
    return $v;
}
function liban($v) {
    $v = strtoupper(preg_replace('/\s+/', '', $v));
    if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/D', $v)) throw new InvalidArgumentException('Ongeldig IBAN.');
    $s = substr($v,4).substr($v,0,4); $n = 0;
    foreach (str_split($s) as $c) foreach (str_split(ctype_alpha($c) ? (string)(ord($c)-55) : $c) as $digit) $n = ($n*10+(int)$digit)%97;
    if ($n !== 1 || (substr($v,0,2) === 'NL' && strlen($v) !== 18)) throw new InvalidArgumentException('Ongeldig IBAN.');
    return $v;
}

function lpeople($people, $selected=0) { foreach($people as $p) if (!$p['left_on']) echo '<option value="'.(int)$p['id'].'" '.((int)$p['id']===(int)$selected?'selected':'').'>'.le($p['name'].' ('.$p['role'].')').'</option>'; }
function lgroups($groups) { foreach($groups as $g) echo '<option value="'.(int)$g['id'].'">'.le($g['name']).'</option>'; }
function ldateInput() { echo '<label>Datum <input type="date" name="date" value="'.date('Y-m-d').'" max="'.date('Y-m-d').'" required></label>'; }
function lbilling() { echo '<label>IBAN <input name="iban" maxlength="50" autocomplete="off" required></label><label>Naam rekeninghouder <input name="payer_name" maxlength="200" required></label><label>E-mail betaler <input name="payer_email" type="email" maxlength="254" required></label>'; }
