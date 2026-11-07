(function () {
  'use strict';
  var tbody = document.getElementById('payment-rows');
  var add = document.getElementById('add-payment');
  var status = document.getElementById('payment-row-status');
  if (!tbody || !add) return;
  function renumber() {
    Array.from(tbody.rows).forEach(function (row, index) {
      row.querySelectorAll('input').forEach(function (input) {
        input.name = 'rows[' + index + '][' + input.dataset.field + ']';
        var label = { name: 'Naam lid', iban: 'IBAN-nummer', payer_name: 'Naam rekeninghouder' }[input.dataset.field];
        input.setAttribute('aria-label', label + ' rij ' + (index + 1));
      });
      var remove = row.querySelector('.remove-payment');
      remove.disabled = tbody.rows.length === 1;
      remove.setAttribute('aria-label', 'Rij ' + (index + 1) + ' verwijderen');
    });
    add.disabled = tbody.rows.length >= 100;
  }
  add.addEventListener('click', function (event) {
    event.preventDefault();
    if (tbody.rows.length >= 100) return;
    var row = tbody.rows[0].cloneNode(true);
    row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
    tbody.appendChild(row); renumber(); row.querySelector('input').focus();
    status.textContent = tbody.rows.length + ' rijen in de lijst.';
  });
  tbody.addEventListener('click', function (event) {
    var remove = event.target.closest('.remove-payment');
    if (!remove || tbody.rows.length === 1) return;
    var row = remove.closest('tr'); var next = row.nextElementSibling || row.previousElementSibling;
    row.remove(); renumber(); if (next) next.querySelector('input').focus();
    status.textContent = tbody.rows.length + ' rijen in de lijst.';
  });
  renumber();
}());
