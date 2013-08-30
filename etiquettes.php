<?php
/*  
    Etiquettes - makes PDF for printing labels 
    Copyright (C) 2013 Nicolas Damiens

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Source {
	public $titres;

	public function __construct($fichier, $template='') {
		$f = fopen($fichier, "r");
		$this->titres = fgetcsv($f);
		foreach ($this->titres as $k=>$v) {
			$this->titres[$k] = $v;
		}
		$this->fh = $f;
		$this->templates_lignes = explode("\n", $template);
	}

	public function lignes($lmax) {
		$lignes = array();
		$data = fgetcsv($this->fh);
		foreach ($this->templates_lignes as $s_cols) {
			$txt = '';
			$cols = explode(',', $s_cols);
			foreach ($cols as $c) {
				$n = array_search(trim($c), $this->titres);
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
			$this->et_w = floor($this->params["width"]/$this->params["colonnes"]*100)/100;
		return $this->et_w;
	}

	public function et_h() {
		if (!isset($this->et_h)) {
			$h = $this->params["height"];
			if (!empty($this->params["marge_hautbas"])) {
				$h = $h - ($this->params["marge_hautbas"]*2);
			}
			$this->et_h = floor($h/$this->params["lignes"]*100)/100;
		}
		return $this->et_h;
	}

	public function et_origine($i,$j) {
		return array(
			$i*$this->et_w(),
			$j*$this->et_h()+$this->params["marge_hautbas"]
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
				$y += $this->params["marge_haut_interieur"]; // petite tolérance pour pas être sur le bord
				$x += $marge;
				foreach ($this->source_donnees->lignes($this->params['lmax']) as $ligne) {
					$txt = $this->doc->createElement("text",$ligne);
					$y += $interligne;
					$txt->setAttribute("x", "{$x}mm");
					$txt->setAttribute("y", "{$y}mm");
					$txt->setAttribute("width", $this->et_w()."mm");
					$txt->setAttribute("height", $this->et_h()."mm");
					$txt->setAttribute("style", "font-size:{$taille_police}mm;font-family:sans-serif;");
					$this->svg->appendChild($txt);
				}
			}
		}
	}

	public function pdf($debug=false) {
		$pages = array();
		$page = 1;
		$f = tempnam("/tmp/","et_pdf");
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
			if ($debug)
				$this->ajoute_squelette();
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
