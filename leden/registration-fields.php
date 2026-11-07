<?php
return json_decode(<<<'FIELDS'
[
  {
    "title": "Lid",
    "fields": [
      {
        "key": "first_names",
        "label": "Voornaam",
        "type": "text",
        "required": true
      },
      {
        "key": "middle_name",
        "label": "Tussenvoegsel(s)",
        "type": "text",
        "required": false
      },
      {
        "key": "last_name",
        "label": "Achternaam",
        "type": "text",
        "required": true
      },
      {
        "key": "gender",
        "label": "Geslacht",
        "type": "select",
        "required": true,
        "options": {
          "vrouwelijk": "Vrouwelijk",
          "mannelijk": "Mannelijk",
          "anders": "Anders",
          "niet_zeggen": "Wil ik niet zeggen"
        }
      },
      {
        "key": "birth_date",
        "label": "Geboortedatum",
        "type": "date",
        "required": true
      },
      {
        "key": "member_postcode",
        "label": "Postcode",
        "type": "text",
        "required": true
      },
      {
        "key": "member_number",
        "label": "Huisnummer (alleen nummer)",
        "type": "number",
        "required": true
      },
      {
        "key": "member_addition",
        "label": "Toevoeging",
        "type": "text",
        "required": false
      },
      {
        "key": "member_street",
        "label": "Straat",
        "type": "text",
        "required": true
      },
      {
        "key": "member_city",
        "label": "Woonplaats",
        "type": "text",
        "required": true
      },
      {
        "key": "member_country",
        "label": "Land",
        "type": "text",
        "required": false
      },
      {
        "key": "member_email",
        "label": "Eigen e-mailadres",
        "type": "email",
        "required": false
      },
      {
        "key": "member_phone",
        "label": "Mobiel nummer",
        "type": "tel",
        "required": false
      },
      {
        "key": "date",
        "label": "Startdatum",
        "type": "date",
        "required": true
      }
    ]
  },
  {
    "title": "Voogd 1",
    "fields": [
      {
        "key": "guardian1_first_names",
        "label": "Voornaam",
        "type": "text",
        "required": true
      },
      {
        "key": "guardian1_middle_name",
        "label": "Tussenvoegsel(s)",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian1_last_name",
        "label": "Achternaam",
        "type": "text",
        "required": true
      },
      {
        "key": "guardian1_gender",
        "label": "Geslacht",
        "type": "select",
        "required": true,
        "options": {
          "vrouwelijk": "Vrouwelijk",
          "mannelijk": "Mannelijk",
          "anders": "Anders",
          "niet_zeggen": "Wil ik niet zeggen"
        }
      },
      {
        "key": "guardian1_relation",
        "label": "Relatie",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian1_email",
        "label": "Emailadres",
        "type": "email",
        "required": true
      },
      {
        "key": "guardian1_phone",
        "label": "Telefoonnummer",
        "type": "tel",
        "required": true
      },
      {
        "key": "guardian1_postcode",
        "label": "Postcode",
        "type": "text",
        "required": true
      },
      {
        "key": "guardian1_number",
        "label": "Huisnummer (alleen nummer)",
        "type": "number",
        "required": true
      },
      {
        "key": "guardian1_addition",
        "label": "Toevoeging",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian1_street",
        "label": "Straat",
        "type": "text",
        "required": true
      },
      {
        "key": "guardian1_city",
        "label": "Woonplaats",
        "type": "text",
        "required": true
      },
      {
        "key": "guardian1_country",
        "label": "Land",
        "type": "text",
        "required": false
      }
    ]
  },
  {
    "title": "Voogd 2",
    "fields": [
      {
        "key": "guardian2_name",
        "label": "Naam",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_relation",
        "label": "Relatie",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_email",
        "label": "E-mailadres",
        "type": "email",
        "required": false
      },
      {
        "key": "guardian2_phone",
        "label": "Mobiel nummer",
        "type": "tel",
        "required": false
      },
      {
        "key": "guardian2_street",
        "label": "Straat",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_number",
        "label": "Huisnummer",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_addition",
        "label": "Toevoeging",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_postcode",
        "label": "Postcode",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_city",
        "label": "Woonplaats",
        "type": "text",
        "required": false
      },
      {
        "key": "guardian2_country",
        "label": "Land",
        "type": "text",
        "required": false
      }
    ]
  },
  {
    "title": "Financieel",
    "fields": [
      {
        "key": "iban",
        "label": "IBAN-nummer",
        "type": "text",
        "required": true
      },
      {
        "key": "payer_name",
        "label": "Naam rekeninghouder",
        "type": "text",
        "required": true
      },
      {
        "key": "payer_email",
        "label": "E-mailadres betaler",
        "type": "email",
        "required": true
      }
    ]
  },
  {
    "title": "Ingevuld door leider",
    "fields": [
      {
        "key": "leader_name",
        "label": "Naam leider",
        "type": "text",
        "required": true
      },
      {
        "key": "leader_email",
        "label": "E-mailadres leider",
        "type": "email",
        "required": true
      }
    ]
  }
]
FIELDS
, true);
