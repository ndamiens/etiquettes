<?php
session_start();
require_once("etiquettes.php");

if (!isset($_SESSION['params'])) {
	$_SESSION['params'] = array(
		"width" => 210,
		"height" => 297,
		"colonnes" => 3,
		"lignes" => 9,
		"lmax" => 30,
		"taille_police" => 3.3,
		"marge" => 5,
		"interligne" => 4,
		"fichier" => null
	);
}

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'def_fichier':
			$f = tempnam("/tmp/","etq");
			unlink($f);
			move_uploaded_file($_FILES['csv']['tmp_name'], $f);
			$_SESSION['params']['fichier'] = $f;
			page();
			break;
		case 'pdf':
			$source = new Source($_SESSION['params']['fichier']);
			$planche = new Planche($_SESSION['params'], $source);
			$planche->pdf();
			break;
		default:
			page();
	}
} else {
	page();
}

exit();
$planche = new Planche(
	array(
		"width" => 210,
		"height" => 297,
		"colonnes" => 3,
		"lignes" => 9,
		"lmax" => 30,
		"taille_police" => 3.3,
		"marge" => 5,
		"interligne" => 4
	),
	new Source("test.csv")
);

$planche->ajoute_squelette();
$planche->ajoute_etiquette();
echo $planche->doc()->saveXML();


function page() {
?>
<html>
<head></head>
<body>
<form enctype="multipart/form-data" method="post" action="index.php">
	<input type="hidden" name="action" value="def_fichier"/>
	<input type="file" name="csv" />
	<input type="submit" value="enregistrer">
</form>
<form method="post" action="index.php">
	<input type="hidden" name="action" value="pdf"/>
	<input type="submit" value="pdf"/>
</form>
<pre><?php print_r($_SESSION); ?></pre>
<pre><?php print_r($_FILES); ?></pre>
</body>
</html>
<?php
}
?>
