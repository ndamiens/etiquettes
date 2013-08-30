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
		"fichier" => null,
		"template" => "",
		"marge_hautbas" => 4,
		"debug" => 0,
		"marge_haut_interieur" => 1
	);
}

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'param':
			$keys = array("width","height","colonnes","lignes","lmax","taille_police","marge","interligne","template","marge_hautbas","debug","marge_haut_interieur");
			foreach ($keys as $k) $_SESSION['params'][$k] = $_POST[$k];
			page();
			break;
		case 'def_fichier':
			$f = tempnam("/tmp/","etq");
			unlink($f);
			move_uploaded_file($_FILES['csv']['tmp_name'], $f);
			$_SESSION['params']['fichier'] = $f;
			page();
			break;
		case 'pdf':
			$source = new Source($_SESSION['params']['fichier'], $_SESSION['params']['template']);
			$planche = new Planche($_SESSION['params'], $source);
			$planche->pdf($_SESSION['params']['debug']==1);
			break;
		default:
			page();
	}
} else {
	page();
}


function page() {
?>
<html>
<head>
	<meta charset="utf8">
</head>
<body>
<p>Vos étiquettes en 3 étapes : 
	<ul>
		<li>Charger le fichier CSV avec vos données (include une colonne de titres)</li>
		<li>Configurer la planche et le template (nom des colonnes séparés par des virgules sur plusieurs lignes)</li>
		<li>Télécharger le PDF</li>
	</ul>
</p>

<fieldset><legend>Enregistrement du fichier CSV</legend>
	<form enctype="multipart/form-data" method="post" action="index.php">
		<input type="hidden" name="action" value="def_fichier"/>
		<input type="file" name="csv" />
		<input type="submit" value="enregistrer">
	</form>
	<?php
	if (file_exists($_SESSION['params']['fichier'])) {
		$s = new Source($_SESSION['params']['fichier']);
		echo "titres des colonnes : ";
		foreach ($s->titres as $titre_col) {
			echo "\"$titre_col\" ";
		}
		echo "<br/>";

	}
	?>
</fieldset>
<fieldset><legend>Paramétrage</legend>
	<form method="post" action="index.php">
		<input type="hidden" name="action" value="param"/>
		<label>Largeur de la page : <input type="text" name="width" value="<?php echo $_SESSION['params']['width']; ?>"/> mm</label><br/>
		<label>Hauteur de la page <input type="text" name="height" value="<?php echo $_SESSION['params']['height']; ?>"/> mm</label><br/>
		<label>Nombre de colonnes d'étiquettes : <input type="text" name="colonnes" value="<?php echo $_SESSION['params']['colonnes']; ?>"/></label><br/>
		<label>Nombre de lignes d'étiquettes : <input type="text" name="lignes" value="<?php echo $_SESSION['params']['lignes']; ?>"/></label><br/>
		<label>Nombre de caractères max sur une ligne : <input type="text" name="lmax" value="<?php echo $_SESSION['params']['lmax']; ?>"/></label><br/>
		<label>Taille de la police : <input type="text" name="taille_police" value="<?php echo $_SESSION['params']['taille_police']; ?>"/> mm</label><br/>
		<label>Hauteur d'une ligne : <input type="text" name="interligne" value="<?php echo $_SESSION['params']['interligne']; ?>"/>mm</label><br/>
		<label>Marge gauche intérieur : <input type="text" name="marge" value="<?php echo $_SESSION['params']['marge']; ?>"/> mm</label><br/>
		<label>Marge haut/bas : <input type="text" name="marge_hautbas" value="<?php echo $_SESSION['params']['marge_hautbas']; ?>"/> mm</label><br/>
		<label>Marge haute intérieur : <input type="text" name="marge_haut_interieur" value="<?php echo $_SESSION['params']['marge_haut_interieur']; ?>"/> mm</label><br/>
		<label>Afficher le contour des étiquettes : <input type="checkbox" name="debug" value="1" <?php echo ($_SESSION['params']['debug']==1)?'checked':''; ?>/></label><br/>
		<label>Template :</label><br/>
		<textarea name="template" style="width:100%; height: 6em;"><?php echo $_SESSION['params']['template']; ?></textarea>
		<input type="submit" value="Enregistrer"/>
	</form>
</fieldset>
<fieldset><legend>Téléchargement</legend>
	<form method="post" action="index.php">
		<input type="hidden" name="action" value="pdf"/>
		<input type="submit" value="pdf"/>
	</form>
</fieldset>
</body>
</html>
<?php
}
?>
