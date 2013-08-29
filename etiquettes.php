<?php
class Source {
	public function __construct($fichier) {
		$f = fopen($fichier, "r");
		$this->titres = fgetcsv($f);
		$this->fh = $f;
		$this->templates_lignes = array(
			"genre,prénom,nom",
			"fc,int,fonction",
			"structure1",
			"structure2",
			"structure3",
			"adresse1",
			"adresse2",
			"CP,ville"
		);
	}

	public function lignes($lmax) {
		$lignes = array();
		$data = fgetcsv($this->fh);
		foreach ($this->templates_lignes as $s_cols) {
			$txt = '';
			$cols = explode(',', $s_cols);
			foreach ($cols as $c) {
				$n = array_search($c, $this->titres);
				if ($n === false)
					continue;
				if (empty($data[$n]))
					continue;
				$txt .= " {$data[$n]}";
			}
			$txt = trim($txt);
			if (empty($txt))
				continue;
			if (strlen($txt) > $lmax) {
				foreach (explode("\n",wordwrap($txt,$lmax)) as $ligne)
					$lignes[] = $ligne;
			} else {
				$lignes[] = $txt;
			}
		}
		return $lignes;
	}
}

class Planche {
	protected $params;
	protected $doc;
	protected $svg;

	private $et_w;
	private $et_h;

	public function __construct($params, $source_donnees) {
		$this->params = $params;
		$this->source_donnees = $source_donnees;

	}

	public function doc() {
		return $this->doc;
	}

	public function et_w() {
		if (!isset($this->et_w))
			$this->et_w = floor($this->params["width"]/$this->params["colonnes"]);
		return $this->et_w;
	}

	public function et_h() {
		if (!isset($this->et_h))
			$this->et_h = floor($this->params["height"]/$this->params["lignes"]);
		return $this->et_h;
	}

	public function et_origine($i,$j) {
		return array(
			$i*$this->et_w(),
			$j*$this->et_h()
		);
	}

	public function ajoute_squelette() {
		if (!isset($this->doc)) {
			throw new Exception("doc pas créé");
		}
		for ($i=0;$i<$this->params["colonnes"];$i++) {
			for ($j=0;$j<$this->params["lignes"];$j++) {
				list($x,$y) = $this->et_origine($i,$j);
				$rect = $this->doc->createElement("rect");
				$rect->setAttribute("x", "{$x}mm");
				$rect->setAttribute("y", "{$y}mm");
				$rect->setAttribute("width", $this->et_w()."mm");
				$rect->setAttribute("height", $this->et_h()."mm");
				$rect->setAttribute("style", "fill:none;stroke-width:1;stroke:black;");
				$this->svg->appendChild($rect);
			}
		}
	}

	public function ajoute_etiquette() {
		if (!isset($this->doc)) {
			throw new Exception("doc pas créé");
		}
		$interligne = $this->params['interligne'];
		$marge = $this->params['marge'];
		$taille_police = $this->params['taille_police'];

		for ($i=0;$i<$this->params["colonnes"];$i++) {
			for ($j=0;$j<$this->params["lignes"];$j++) {
				list($x,$y) = $this->et_origine($i,$j);
				$x += $marge;
				foreach ($this->source_donnees->lignes($this->params['lmax']) as $ligne) {
					$txt = $this->doc->createElement("text",$ligne);
					$y += $interligne;
					$txt->setAttribute("x", "{$x}mm");
					$txt->setAttribute("y", "{$y}mm");
					$txt->setAttribute("width", $this->et_w()."mm");
					$txt->setAttribute("height", $this->et_h()."mm");
					$txt->setAttribute("style", "font-size:{$taille_police}mm;font-family:sans-serif;text-overflow:clip;");
					$this->svg->appendChild($txt);
				}
			}
		}
	}

	public function pdf($debug=false) {
		$pages = array();
		$page = 1;
		$f = tempnam("/tmp/","et_pdf");
		if ($debug)
			$this->ajoute_squelette();
		while (!feof($this->source_donnees->fh)) {
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;
			$svg = $doc->createElement("svg");
			$svg->setAttribute("xmlns", "http://www.w3.org/2000/svg");
			$svg->setAttribute("version", "1.1");
			$svg->setAttribute("width", "{$this->params["width"]}mm");
			$svg->setAttribute("height","{$this->params["height"]}mm");
			$doc->appendChild($svg);
			$this->svg = $svg;
			$this->doc = $doc;
			$this->ajoute_etiquette();
			file_put_contents("{$f}_xml_{$page}.xml", $this->doc->saveXML());
			system("inkscape {$f}_xml_{$page}.xml --export-pdf {$f}_xml_{$page}.pdf");
			$pages[] = "{$f}_xml_{$page}.pdf";
			$page++;
		}
		$cmd_pdfs = "";
		$cmd_cat = "";
		$p = 0;
		foreach ($pages as $page) {
			$cmd_pdfs .= chr(ord("A")+$p)."=$page ";
			$cmd_cat .= chr(ord("A")+$p)."1 ";
			$p++;

		}
		$cmd = "pdftk $cmd_pdfs cat $cmd_cat output {$f}.pdf";
		system($cmd);
		header('Content-type: application/pdf');
		echo file_get_contents("{$f}.pdf");
	}
}

?>
