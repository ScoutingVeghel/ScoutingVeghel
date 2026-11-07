<?php
require_once __DIR__ . '/portal-mail-text.php';
require_once __DIR__ . '/portal-mail-template.php';
require_once __DIR__ . '/portal-leden-mail-templates.php';
function mailHtmlCatalog() {
    return ledenMailCatalog() + [
        'verhuur.optie' => [
            'label' => 'Optie bevestigen',
            'description' => 'Aan de huurder na een nieuwe aanvraag.',
            'variables' => ['naam', 'activiteit', 'periode', 'personen', 'bevestig_url'],
            'required' => ['bevestig_url']
        ],
        'verhuur.documenten' => [
            'label' => 'Reservering bevestigd',
            'description' => 'Aan de huurder na bevestiging, met de verhuurdocumenten.',
            'variables' => ['naam', 'overeenkomst_url', 'voorwaarden_url'],
            'required' => ['overeenkomst_url', 'voorwaarden_url']
        ],
        'verhuur.team.nieuw' => [
            'label' => 'Nieuwe aanvraag · verhuurteam',
            'description' => 'Aan het verhuurteam bij een nieuwe aanvraag.',
            'variables' => [
                'nummer',
                'status',
                'naam',
                'contactpersoon',
                'email',
                'telefoon',
                'type_verhuur',
                'aankomst',
                'vertrek',
                'personen',
                'bericht',
                'beheer_url',
                'overlap_waarschuwing'
            ],
            'required' => ['beheer_url', 'overlap_waarschuwing']
        ],
        'verhuur.team.bevestigd' => [
            'label' => 'Bevestiging · verhuurteam',
            'description' => 'Aan het verhuurteam na bevestiging door de huurder.',
            'variables' => [
                'nummer',
                'status',
                'naam',
                'contactpersoon',
                'email',
                'telefoon',
                'type_verhuur',
                'aankomst',
                'vertrek',
                'personen',
                'bericht',
                'beheer_url',
                'overlap_waarschuwing'
            ],
            'required' => ['beheer_url']
        ],
        'contact.afzender' => [
            'label' => 'Contact · afzender',
            'description' => 'Ontvangstbevestiging aan de afzender van het contactformulier.',
            'variables' => ['naam', 'email', 'groep', 'bericht'],
            'required' => ['bericht']
        ],
        'leden.nieuw' => [
            'label' => 'Leden · nieuwe inschrijving',
            'description' =>
                'Voor de ledenadministratie nadat de leiding een inschrijfformulier heeft ingevuld. Kopieer onderwerp en HTML naar Power Automate; deze site verstuurt deze mail niet.',
            'variables' => ['speltak'],
            'required' => []
        ],
        'leden.ingeschreven' => [
            'label' => 'Leden · inschrijving afgerond',
            'description' =>
                'Bevestiging dat de ledenadministratie de inschrijving heeft verwerkt. Kopieer onderwerp en HTML naar Power Automate; deze site verstuurt deze mail niet.',
            'variables' => ['speltak'],
            'required' => []
        ],
        'leden.poetsen' => [
            'label' => 'Poetsherinnering',
            'description' =>
                'Herinnering aan de speltak die deze maand moet poetsen, met een extra taak en de poetschecklist. Kopieer onderwerp en HTML naar Power Automate.',
            'variables' => ['speltak', 'taak'],
            'required' => []
        ],
        'contact.groep' => [
            'label' => 'Contact · ontvanger',
            'description' => 'Contactbericht aan de gekozen groep of contactpersoon.',
            'variables' => ['naam', 'email', 'groep', 'bericht'],
            'required' => ['bericht', 'email']
        ]
    ];
}
function mailHtmlParagraph($text) {
    return '<p style="margin:0 0 20px;font-size:15px;line-height:1.6;">' . $text . '</p>';
}
function mailHtmlButton($url, $label) {
    return '<p style="margin:0 0 24px;"><a href="{{' .
        $url .
        '}}" style="display:inline-block;padding:13px 20px;border-radius:5px;background:#355d40;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">' .
        $label .
        '</a></p>';
}
function mailHtmlDetails($fields) {
    $parts = [];
    foreach ($fields as $label => $variable) {
        $parts[] = '<strong>' . $label . '</strong><br>{{' . $variable . '}}';
    }
    return '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;background:#f5f6f1;border-radius:6px;width:100%;">' .
        '<tr>' .
        '<td style="padding:16px 18px;font-size:14px;line-height:1.7;overflow-wrap:anywhere;">' .
        implode('<br><br>', $parts) .
        '</td></tr></table>';
}
function mailHtmlDefault($id) {
    if (strpos($id, 'ledenadministratie.') === 0) return ledenMailDefault($id);
    if (!isset(mailHtmlCatalog()[$id])) {
        throw new InvalidArgumentException('Onbekende mail.');
    }
    $module = explode('.', $id)[0];
    $content = mailHtmlParagraph('Beste {{naam}},');
    if ($id === 'verhuur.optie') {
        $title = mailText($id . '.titel');
        $subject = mailText($id . '.onderwerp');
        $content .=
            mailHtmlParagraph(mailTextHtml($id . '.intro')) .
            mailHtmlDetails([
                'Activiteit' => 'activiteit',
                'Periode' => 'periode',
                'Aantal personen' => 'personen'
            ]) .
            mailHtmlButton('bevestig_url', 'Bevestig mijn optie') .
            mailHtmlParagraph(
                'Werkt de knop niet? Open dan deze link:<br><a href="{{bevestig_url}}">{{bevestig_url}}</a>'
            ) .
            mailHtmlParagraph(mailTextHtml($id . '.vervallen'));
    } elseif ($id === 'verhuur.documenten') {
        $title = mailText($id . '.titel');
        $subject = mailText($id . '.onderwerp');
        $content .=
            mailHtmlParagraph(mailTextHtml($id . '.intro')) .
            mailHtmlButton('overeenkomst_url', 'Bekijk de huurovereenkomst') .
            mailHtmlParagraph(
                'De <a href="{{voorwaarden_url}}">huurvoorwaarden</a> staan ook op onze website.'
            ) .
            mailHtmlParagraph(
                'Werkt de knop niet? Kopieer deze link naar je browser:<br><a href="{{overeenkomst_url}}">{{overeenkomst_url}}</a>'
            );
    } elseif (strpos($id, 'verhuur.team.') === 0) {
        $title = mailText($id . '.titel');
        $subject = $title . ' | Scouting Veghel';
        $content =
            mailHtmlParagraph('Beste verhuurteam,') .
            mailHtmlParagraph(mailTextHtml($id . '.intro')) .
            '{{overlap_waarschuwing}}' .
            mailHtmlDetails([
                'Reserveringnummer' => 'nummer',
                'Status' => 'status',
                'Naam / groep' => 'naam',
                'Contactpersoon' => 'contactpersoon',
                'E-mailadres' => 'email',
                'Telefoon' => 'telefoon',
                'Type verhuur' => 'type_verhuur',
                'Aankomst' => 'aankomst',
                'Vertrek' => 'vertrek',
                'Aantal personen' => 'personen',
                'Bericht / toelichting' => 'bericht'
            ]) .
            mailHtmlButton('beheer_url', 'Bekijk de reservering') .
            mailHtmlParagraph(
                'Log in bij beheer om de aanvraag te bekijken. Gebruik Beantwoorden om contact op te nemen met de aanvrager.'
            );
    } elseif ($id === 'leden.poetsen') {
        $module = 'poetsen';
        $title = 'Herinnering poetsbeurt';
        $subject = 'Poetsherinnering | {{speltak}} | Scouting Veghel';
        $content =
            mailHtmlParagraph('Beste {{speltak}},') .
            mailHtmlParagraph(
                'Hierbij een herinnering dat jullie deze maand moeten poetsen. De taak die deze week extra op de planning staat is:'
            ) .
            mailHtmlParagraph('<strong>{{taak}}</strong>') .
            mailHtmlParagraph(
                'De checklist is te vinden op onze website: <a href="https://www.scoutingveghel.nl/docs/poets_checklist.pdf">bekijk de poetschecklist</a>.'
            );
    } elseif ($module === 'leden') {
        $completed = $id === 'leden.ingeschreven';
        $title = $completed ? '✓ Lid succesvol ingeschreven!' : 'Nieuwe inschrijving!';
        $subject =
            ($completed ? 'Lid succesvol ingeschreven' : 'Nieuwe inschrijving') .
            ' | {{speltak}} | Scouting Veghel';
        $content =
            mailHtmlParagraph($completed ? 'Hoi allemaal,' : 'Hoi Marjan en Fons,') .
            mailHtmlParagraph(
                $completed
                    ? 'Goed nieuws! De inschrijving van het nieuwe lid is verwerkt in de ledenadministratie van Scouting Veghel.'
                    : 'Er is zojuist door de leiding van een speltak een nieuw inschrijfformulier ingevuld.'
            ) .
            mailHtmlDetails([
                $completed ? 'INGESCHREVEN BIJ SPELTAK' : 'NIEUWE INSCHRIJVING VOOR' => 'speltak'
            ]) .
            '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;width:100%;background:#f5f6f1;border-radius:6px;"><tr><td style="padding:16px 18px;font-size:14px;line-height:1.7;">' .
            '<strong>' .
            ($completed ? 'Inschrijving afgerond' : 'Actie voor de ledenadministratie') .
            '</strong><br>' .
            ($completed
                ? 'Het nieuwe lid is administratief ingeschreven. Vanuit de ledenadministratie is voor deze inschrijving geen verdere actie nodig.'
                : 'Het formulier is door de leiding ingevuld. Het nieuwe lid kan nu worden ingeschreven in de ledenadministratie van Scouting Veghel.') .
            '</td></tr></table>' .
            mailHtmlParagraph(
                $completed
                    ? 'Bedankt voor het aanmelden en veel scoutingplezier gewenst!'
                    : 'Alvast bedankt voor het verwerken!'
            );
    } else {
        $sender = $id === 'contact.afzender';
        $title = mailText($id . '.titel');
        $subject = $sender
            ? '[SV-Contact]: Bevestiging van je bericht aan {{groep}}'
            : '[SV-Contact]: Nieuw bericht voor {{groep}} van {{naam}}';
        if (!$sender) {
            $content = mailHtmlParagraph('Beste {{groep}},');
        }
        $content .=
            mailHtmlParagraph(
                $sender
                    ? 'Je hebt het contactformulier van Scouting Veghel ingevuld. Je bericht is verstuurd naar: <strong>{{groep}}</strong>. Hieronder vind je de ingevulde gegevens.'
                    : 'Er is een bericht via het contactformulier binnengekomen voor <strong>{{groep}}</strong>.'
            ) .
            mailHtmlDetails([
                'Naam' => 'naam',
                'E-mailadres' => 'email',
                'Voor wie' => 'groep',
                'Bericht' => 'bericht'
            ]) .
            mailHtmlParagraph(
                mailTextHtml($id . '.antwoord') .
                    ($sender
                        ? ' Je kunt deze e-mail beantwoorden om contact op te nemen met {{groep}}.'
                        : '')
            );
    }
    $content .= mailHtmlParagraph(
        $module === 'leden'
            ? 'Met vriendelijke scoutinggroet,<br><strong>De digitale ledenadministratie ⚜</strong>'
            : 'Met vriendelijke groet,<br>' .
                ($module === 'contact'
                    ? 'Scouting Veghel'
                    : ($module === 'poetsen'
                        ? 'Scouting Veghel'
                        : (strpos($id, 'verhuur.team.') === 0
                            ? 'Website Scouting Veghel'
                            : 'Verhuurteam Scouting Veghel')))
    );
    $html = siteMailTemplate($module, $title, $content);
    return str_replace(
        '<head>',
        '<head><title>' .
            htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '</title>',
        $html
    );
}
function mailHtmlSource($id) {
    return mailTextOverrides()[$id . '.html'] ?? mailHtmlDefault($id);
}
function mailHtmlDocument($html) {
    if (!class_exists('DOMDocument')) {
        throw new RuntimeException('De PHP DOM-extensie is nodig voor HTML-sjablonen.');
    }
    $previous = libxml_use_internal_errors(true);
    try {
        $doc = new DOMDocument();
        $ok = $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
        if (!$ok) {
            throw new InvalidArgumentException('Vul een geldig HTML-document in.');
        }
        return $doc;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}
function mailHtmlValidate($id, $html) {
    $catalog = mailHtmlCatalog();
    if (!isset($catalog[$id])) {
        throw new InvalidArgumentException('Onbekende mail.');
    }
    if (
        !is_string($html) ||
        trim($html) === '' ||
        strlen($html) > 60000 ||
        preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $html)
    ) {
        throw new InvalidArgumentException('Vul een HTML-sjabloon in van maximaal 60.000 bytes.');
    }
    $doc = mailHtmlDocument($html);
    if ($doc->getElementsByTagName('title')->length !== 1 || !preg_match('/<body\b/i', $html)) {
        throw new InvalidArgumentException(
            'Gebruik één <title> voor het onderwerp en een <body> voor de mail.'
        );
    }
    $title = $doc->getElementsByTagName('title')->item(0)->textContent;
    if (trim($title) === '' || strlen($title) > 500 || preg_match('/[\r\n]/', $title)) {
        throw new InvalidArgumentException(
            'Vul in <title> een onderwerp in op één regel (maximaal 500 bytes).'
        );
    }
    $tags = [
        'html',
        'head',
        'title',
        'meta',
        'style',
        'body',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'td',
        'th',
        'caption',
        'colgroup',
        'col',
        'p',
        'div',
        'span',
        'a',
        'img',
        'br',
        'hr',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'small',
        'ul',
        'ol',
        'li',
        'blockquote',
        'pre',
        'code',
        'center',
        'font',
        'sup',
        'sub'
    ];
    foreach ($doc->getElementsByTagName('*') as $node) {
        if (!in_array(strtolower($node->tagName), $tags, true)) {
            throw new InvalidArgumentException(
                'Dit HTML-element is niet toegestaan: ' . $node->tagName . '.'
            );
        }
        if (strtolower($node->tagName) === 'style' && strpos($node->textContent, '{{') !== false) {
            throw new InvalidArgumentException(
                'Gebruik invoegvelden in de mailtekst, niet in CSS.'
            );
        }
        if (
            strtolower($node->tagName) === 'title' &&
            preg_match('/\{\{(bericht|overlap_waarschuwing)\}\}/', $node->textContent)
        ) {
            throw new InvalidArgumentException(
                'Gebruik bericht en overlapwaarschuwing alleen in de mailtekst.'
            );
        }
        if ($node->tagName === 'meta' && $node->hasAttribute('http-equiv')) {
            throw new InvalidArgumentException('Een meta http-equiv is niet toegestaan.');
        }
        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);
            if (
                strpos($name, 'on') === 0 ||
                in_array(
                    $name,
                    ['srcdoc', 'srcset', 'background', 'action', 'formaction', 'xlink:href'],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'Dit HTML-attribuut is niet toegestaan: ' . $name . '.'
                );
            }
            preg_match_all('/\{\{(.*?)\}\}/s', $value, $attributeKeys);
            foreach ($attributeKeys[1] as $key) {
                $safeUrl = $name === 'href' && substr($key, -4) === '_url';
                $safeEmail =
                    $name === 'href' && $key === 'email' && strpos($value, 'mailto:') === 0;
                if (!$safeUrl && !$safeEmail) {
                    throw new InvalidArgumentException(
                        'Gebruik invoegvelden in attributen alleen voor de bijbehorende link.'
                    );
                }
            }
            if (in_array($name, ['href', 'src'], true)) {
                $url = preg_replace('/\{\{[a-z_]+\}\}/', 'https://voorbeeld.invalid/', $value);
                if (
                    !preg_match(
                        '~^(https?://|mailto:|cid:scouting-veghel-logo$|#[a-z0-9_-]+$)~i',
                        $url
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Gebruik voor links een https-URL, mailto-adres of beschikbaar invoegveld; voor het logo cid:scouting-veghel-logo.'
                    );
                }
            }
        }
    }
    preg_match_all('/\{\{(.*?)\}\}/s', $html, $matches);
    foreach ($matches[1] as $key) {
        if (!in_array($key, $catalog[$id]['variables'], true)) {
            throw new InvalidArgumentException('Onbekend invoegveld: {{' . $key . '}}.');
        }
    }
    foreach ($catalog[$id]['required'] as $key) {
        if (strpos($html, '{{' . $key . '}}') === false) {
            throw new InvalidArgumentException('Behoud het vereiste invoegveld {{' . $key . '}}.');
        }
    }
    // Never permit data or trusted HTML fragments inside tag attributes, styles or the subject.
    preg_match_all(
        '/<[^>]+>|<style\b[^>]*>.*?<\/style>|<title\b[^>]*>.*?<\/title>/is',
        $html,
        $contexts
    );
    foreach ($contexts[0] as $context) {
        preg_match_all('/\{\{(.*?)\}\}/s', $context, $keys);
        foreach ($keys[1] as $key) {
            if (
                in_array($key, ['bericht', 'overlap_waarschuwing'], true) ||
                stripos($context, '<style') === 0
            ) {
                throw new InvalidArgumentException(
                    '{{' . $key . '}} mag alleen in de zichtbare mailtekst staan.'
                );
            }
        }
    }
    return $html;
}
function mailHtmlRender($id, $values, $source = null) {
    $source = $source ?? mailHtmlSource($id);
    $replacements = [];
    foreach (mailHtmlCatalog()[$id]['variables'] as $key) {
        $value = $values[$key] ?? '';
        $replacements['{{' . $key . '}}'] =
            $key === 'overlap_waarschuwing'
                ? $value
                : ($key === 'bericht'
                    ? nl2br(htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
                    : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
    return strtr($source, $replacements);
}
function mailHtmlSubject($html) {
    $doc = mailHtmlDocument($html);
    $title = $doc->getElementsByTagName('title')->item(0);
    if (!$title) {
        throw new RuntimeException('Het mailonderwerp ontbreekt.');
    }
    return trim(preg_replace('/[\r\n]+/', ' ', $title->textContent));
}
function mailHtmlWithoutSubject($html) {
    return preg_replace('/<title\b[^>]*>.*?<\/title\s*>/is', '', $html);
}
function mailHtmlWithSubject($html, $subject) {
    if (
        !is_string($subject) ||
        trim($subject) === '' ||
        strlen($subject) > 500 ||
        preg_match('/[\x00-\x1f\x7f]/', $subject)
    ) {
        throw new InvalidArgumentException(
            'Vul een onderwerp in op één regel (maximaal 500 bytes).'
        );
    }
    $html = mailHtmlWithoutSubject($html);
    if (!preg_match('/<head\b[^>]*>/i', $html)) {
        throw new InvalidArgumentException('Behoud het <head>-blok in de mailinhoud.');
    }
    return preg_replace_callback(
        '/<head\b[^>]*>/i',
        function ($match) use ($subject) {
            return $match[0] .
                '<title>' .
                htmlspecialchars(trim($subject), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                '</title>';
        },
        $html,
        1
    );
}
function mailHtmlExampleValues() {
    return [
        'naam' => 'Scouting Voorbeeld',
        'speltak' => 'Bevers',
        'naam_lid' => 'Sam Voorbeeld',
        'leider' => 'Robin Voorbeeld',
        'nieuwe_groep' => 'Scouts',
        'datum' => '01-11-2026',
        'taak' => 'Grote zaal dweilen',
        'activiteit' => 'Weekendkamp',
        'periode' => 'Van 15-05-2027 10:00 tot 16-05-2027 17:00',
        'personen' => '24',
        'bevestig_url' => 'https://voorbeeld.invalid/bevestigen',
        'overeenkomst_url' => 'https://voorbeeld.invalid/huurovereenkomst',
        'voorwaarden_url' => 'https://voorbeeld.invalid/huurvoorwaarden.pdf',
        'nummer' => '1234',
        'status' => 'Optie – wacht op bevestiging',
        'contactpersoon' => 'Robin Voorbeeld',
        'email' => 'robin@example.invalid',
        'telefoon' => '0612345678',
        'type_verhuur' => 'Extern',
        'aankomst' => '15-05-2027 10:00',
        'vertrek' => '16-05-2027 17:00',
        'bericht' => "Dit is een voorbeeldbericht.\nWe komen graag een weekend kamperen.",
        'groep' => 'Verhuurteam',
        'beheer_url' => 'https://voorbeeld.invalid/beheer',
        'overlap_waarschuwing' => mailHtmlParagraph(
            '<strong>Let op:</strong> er staat al een reservering in deze periode. Controleer de overlap in beheer.'
        )
    ];
}
function mailHtmlPreview($id, $source) {
    mailHtmlValidate($id, $source);
    $values = mailHtmlExampleValues();
    if ($id === 'verhuur.team.bevestigd') {
        $values['status'] = 'Bevestigd door huurder';
        $values['overlap_waarschuwing'] = '';
    }
    $html = mailHtmlRender($id, $values, $source);
    $logo =
        'data:image/png;base64,' .
        base64_encode(file_get_contents(__DIR__ . '/../admin/assets/scouting-veghel-mail-logo.png'));
    $html = str_replace(['cid:scouting-veghel-logo', siteMailPublicLogoUrl()], $logo, $html);
    // Preview is inert and never loads external resources, navigates or submits forms.
    $policy =
        "default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'";
    return preg_replace(
        '/<head\b[^>]*>/i',
        '$0<meta http-equiv="Content-Security-Policy" content="' .
            htmlspecialchars($policy, ENT_QUOTES, 'UTF-8') .
            '">',
        $html,
        1
    );
}

// Only add whitespace at block boundaries. Keep inline text, attributes, CSS and preformatted content intact.
function mailHtmlPretty($html) {
    $pattern =
        '~(<!--.*?-->|<(?:pre|style)\b[^>]*>.*?</(?:pre|style)\s*>|<![^>]*>|</?[a-zA-Z](?:[^>"\x27]|"[^"]*"|\x27[^\x27]*\x27)*>)~is';
    $tokens = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $blocks = [
        'html',
        'head',
        'body',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'td',
        'th',
        'caption',
        'colgroup',
        'div',
        'p',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'ul',
        'ol',
        'li',
        'blockquote',
        'center'
    ];
    $lines = [];
    $line = '';
    $depth = 0;
    $flush = function () use (&$lines, &$line, &$depth) {
        if (trim($line) !== '') {
            $lines[] = str_repeat('  ', max(0, $depth)) . trim($line);
        }
        $line = '';
    };
    foreach ($tokens as $token) {
        if (preg_match('~^<(pre|style)\b|^<!--|^<!~i', $token)) {
            $flush();
            $lines[] = str_repeat('  ', max(0, $depth)) . $token;
            continue;
        }
        if (preg_match('~^<(/?)([a-zA-Z0-9]+)\b~', $token, $match)) {
            $tag = strtolower($match[2]);
            $closing = $match[1] === '/';
            if (in_array($tag, $blocks, true)) {
                if ($closing) {
                    $flush();
                    $depth = max(0, $depth - 1);
                    $lines[] = str_repeat('  ', $depth) . $token;
                } else {
                    $flush();
                    $line = $token;
                    $flush();
                    $depth++;
                }
                continue;
            }
            if (
                in_array($tag, ['meta', 'title', 'col', 'hr'], true) &&
                !$closing &&
                $tag !== 'title'
            ) {
                $flush();
                $lines[] = str_repeat('  ', max(0, $depth)) . $token;
                continue;
            }
            if ($tag === 'title' && $closing) {
                $line .= $token;
                $flush();
                continue;
            }
        }
        $line .= $token;
    }
    $flush();
    return implode("\n", $lines) . "\n";
}
