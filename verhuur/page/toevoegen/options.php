<?php
require_once __DIR__ . '/../../../php/portal-document.php';
require_once dirname(__DIR__, 3) . '/admin/security.php';
adminRequireLogin();
?>
<?php echo beheerDocumentStart(
    'Zoek-formulier',
    '',
    '',
    ['/verhuur/css/toevoegen-options.css'],
    []
); ?>
<h2>Snel toevoegen</h2>
		<table id="directory_listing">
			<tr><td><a href="gebruikers.php?a=add" target="results">Gebruiker toevoegen</a></td></tr>
			<tr><td>Verhuring toevoegen:</td></tr>
			<tr><td><a href="verhuring.php?p=eenmalig" target="item">Eenmalige verhuring</a></td></tr>
			<!-- <tr><td><a href="verhuring.php?p=repeterend" target="item">Repeterende verhuring</a></td></tr> -->
		</table>
	</body>
</html>
