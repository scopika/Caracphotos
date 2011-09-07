<?php
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Image.class.php");
class Imagecaracdisp extends Image {

	public $caracdispdesc;
	public $caracdispdesc_prod;
	
	function ImageCaracdisp($id = 0){
		$this->bddvars[] = 'caracdispdesc';
		$this->bddvars[] = 'caracdispdesc_prod';
		parent::__construct($id);
	}
	
	function changer_classement($id, $sens){
		if(!$this->charger($id)) return false;

		$remplace = new ImageCaracdisp();
		switch($sens) {
			case 'M' :
				$res = $remplace->getVars('
					SELECT * 
					FROM ' . $this->table . '  
					WHERE 
						caracdispdesc_prod=' . $this->caracdispdesc_prod . ' 
    					AND caracdispdesc=' . $this->caracdispdesc .  '
    					AND classement<' . $this->classement . '
					ORDER BY classement DESC LIMIT 0,1
				');
				break;
			default :
				$res = $remplace->getVars('
					SELECT * 
					FROM ' . $this->table . ' 
					WHERE 
						caracdispdesc_prod=' . $this->caracdispdesc_prod . ' 
    					AND caracdispdesc=' . $this->caracdispdesc .  '
    					AND classement>' . $this->classement . ' 
					ORDER BY classement ASC LIMIT 0,1
				');
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
	 * Recherche les photos associées à une caracdispdesc (et éventuellement un produit) 
	 * @param int $caracdispdesc
	 * @param int $produit
	 * @return array ImageDeclidisp
	 */
	function charger_caracdispdesc_photos($caracdispdesc, $produit=0) {

		if(!preg_match('/^[0-9]{1,}$/', $caracdispdesc)) return 0;
		if(!preg_match('/^[0-9]{1,}$/', $produit)) $produit=0;
		
		$results = array();
		$query = '
			SELECT id 
			FROM ' . $this->table . ' 
			WHERE caracdispdesc=' . $caracdispdesc . '
			AND caracdispdesc_prod=' . $produit . '
			ORDER BY classement ASC';
		$resul = $this->query($query);
		while($row = mysql_fetch_object($resul)){
			$image = new ImageCaracdisp();
			$image->charger($row->id);
			$results[] = $image;
		}
		return $results;
	}	
}
