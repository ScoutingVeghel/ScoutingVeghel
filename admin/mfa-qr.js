/* Render authenticator setup locally; never send the provisioning URI to a service. */
(function () {
  'use strict';
  var container = document.getElementById('mfa-qr');
  if (!container) return;
  try {
    var code = qrcode(0, 'M');
    code.addData(container.dataset.provisioningUri, 'Byte');
    code.make();
    var size = code.getModuleCount();
    var ns = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('width', '360');
    svg.setAttribute('height', '360');
    svg.setAttribute('viewBox', '0 0 ' + (size + 8) + ' ' + (size + 8));
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'QR-code voor het instellen van tweestapsverificatie');
    svg.setAttribute('shape-rendering', 'crispEdges');
    var background = document.createElementNS(ns, 'rect');
    background.setAttribute('width', size + 8);
    background.setAttribute('height', size + 8);
    background.setAttribute('fill', '#fff');
    svg.appendChild(background);
    var squares = '';
    for (var row = 0; row < size; row++) {
      for (var col = 0; col < size; col++) {
        if (code.isDark(row, col)) squares += 'M' + (col + 4) + ' ' + (row + 4) + 'h1v1h-1z';
      }
    }
    var path = document.createElementNS(ns, 'path');
    path.setAttribute('d', squares);
    path.setAttribute('fill', '#000');
    svg.appendChild(path);
    container.appendChild(svg);
  } catch (error) {
    document.getElementById('mfa-qr-fallback').hidden = false;
    document.querySelector('.mfa-manual').open = true;
  }
}());
