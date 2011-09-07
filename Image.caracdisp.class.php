<?php
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Image.class.php");
class ImageCaracdisp extends Image {

	public $caracdisp;
	public $caracdisp_prod;
	
	function ImageCaracdisp($id = 0){
		$this->bddvars[] = 'caracdisp';
		$this->bddvars[] = 'caracdisp_prod';
		parent::__construct($id);
	}
	
	function changer_classement($id, $sens){
		$this->charger($id);
		$remplace = new ImageCaracdisp();
		switch($sens) {
			case 'M' :
				$res = $remplace->getVars("
					SELECT * FROM $this->table 
					WHERE caracdisp_prod=\"" . $this->caracdisp_prod . "\" 
					AND caracdisp=\"" . $this->caracdisp .  "\"
					AND classement<" . $this->classement . " 
					ORDER BY classement DESC LIMIT 0,1
				");
				break;
			default :
				$res = $remplace->getVars("
					SELECT * FROM $this->table 
					WHERE caracdisp_prod=\"" . $this->caracdisp_prod . "\" 
					AND caracdisp=\"" . $this->caracdisp .  "\"
					AND classement>" . $this->classement . " 
					ORDER BY classement ASC LIMIT 0,1
				");
				break;
		}
		if(!$res) return false;
		
		$sauv = $remplace->classement;
		$remplace->classement = $this->classement;
		$this->classement = $sauv;
		
		$remplace->maj();
		$this->maj();
	}
	
	
	/**
	 * Recherche les photos associées à une caracdisp (et éventuellement un produit) 
	 * @param int $caracdisp
	 * @param int $produit
	 * @return array ImageCaracdisp
	 */
	function charger_caracdisp_photos($caracdisp, $produit=0) {
		if(!preg_match('/^[0-9]*$/', $caracdisp)) return 0;
		if(!preg_match('/^[0-9]*$/', $produit)) $produit=0;
		
		$results = array();
		$query = "
			SELECT id 
			FROM $this->table 
			WHERE caracdisp=\"$caracdisp\"
			AND caracdisp_prod=\"$produit\"
			ORDER BY classement ASC
		";
		$resul = $this->query($query);
		while($row = mysql_fetch_object($resul)){
			$image = new ImageCaracdisp();
			$image->charger($row->id);
			$results[] = $image;
		}
		return $results;
	}	
}
