(function () {
  'use strict';
  var slots = document.querySelectorAll('[data-portal-user-slot]');
  if (!slots.length) return;
  fetch('/admin/session.php', { credentials: 'same-origin', cache: 'no-store' })
    .then(function (response) { if (!response.ok) throw new Error('session'); return response.json(); })
    .then(function (state) {
      if (!state.loggedIn) return;
      slots.forEach(function (slot) {
        var details = document.createElement('details'); details.className = 'portal-user-menu';
        var summary = document.createElement('summary');
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', '20'); svg.setAttribute('height', '20'); svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '1.8'); svg.setAttribute('aria-hidden', 'true');
        var circle = document.createElementNS(svg.namespaceURI, 'circle');circle.setAttribute('cx', '12');circle.setAttribute('cy', '8');circle.setAttribute('r', '4');
        var path = document.createElementNS(svg.namespaceURI, 'path');path.setAttribute('d', 'M4 22v-3a8 8 0 0 1 16 0v3');svg.append(circle, path);
        var name = document.createElement('span');name.textContent = state.name || 'Account';summary.append(svg, name);
        var menu = document.createElement('div');menu.className = 'portal-user-dropdown';
        var title = document.createElement('p');title.textContent = state.name || 'Account';menu.appendChild(title);
        [['Accountgegevens','/admin/account.php?portal=leden'],['Wachtwoord wijzigen','/admin/account.php?portal=leden#wachtwoord'],['Tweestapsverificatie','/admin/?module=mfa'],['Uitloggen','/admin/logout.php']].forEach(function (item) { var link=document.createElement('a');link.textContent=item[0];link.href=item[1];menu.appendChild(link); });
        details.append(summary, menu);
        var switcher = document.createElement('nav');
        switcher.className = 'portal-switcher';
        switcher.setAttribute('aria-label', 'Portalen');
        [['admin', 'Admin', '/admin/'], ['beheer', 'Verhuur', '/verhuur/'], ['leden', 'Leden', '/leden/beheer.php']].forEach(function (portal) {
          if (!(state.portals || {})[portal[0]]) return;
          var link = document.createElement('a');
          link.textContent = portal[1];
          link.href = portal[2];
          link.target = '_top';
          switcher.appendChild(link);
        });
        slot.replaceChildren(switcher, details);
      });
    }).catch(function () { /* Keep the login link available if the session cannot be read. */ });
}());
