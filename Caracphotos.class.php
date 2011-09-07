<?php
include_once(realpath(dirname(__FILE__)) . "/../../../classes/PluginsClassiques.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Caracdispdesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Imagedesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Caracdisp.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Caracdispdesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Caracteristique.class.php");
include_once(realpath(dirname(__FILE__)) . "/../../../classes/Caracteristiquedesc.class.php");
include_once(realpath(dirname(__FILE__)) . "/Imagecaracdisp.class.php");

class Caracphotos extends PluginsClassiques{

	/**
	 * Installation
	 * @see PluginsClassiques::init()
	 */
	function init(){
		// ajout d'un champs 'caracdispdesc' dans la table image
		$this->query('ALTER TABLE  `image` ADD  `caracdispdesc` INT( 11 ) UNSIGNED NOT NULL AFTER  `dossier` , ADD INDEX (  `caracdispdesc` );');

		// ajout d'un champs 'caracdispdesc_prod' dans la table image
		// On ne peut pas utiliser le champs 'produit' déjà présent, car ça fait foirer la boucle IMAGE de base.
		$this->query('ALTER TABLE  `image` ADD  `caracdispdesc_prod` INT( 11 ) UNSIGNED NOT NULL AFTER  `caracdispdesc` , ADD INDEX (  `caracdispdesc_prod` );');

		// dossier d'upload
		if(!is_dir(realpath(dirname(__FILE__)) . '/../../../client/gfx/photos/caracdisp/')) {
			mkdir(realpath(dirname(__FILE__)) . '/../../../client/gfx/photos/caracdisp/');
		}
		// dossier de cache
		if(!is_dir(realpath(dirname(__FILE__)) . '/../../../client/cache/caracdisp/')) {
			mkdir(realpath(dirname(__FILE__)) . '/../../../client/cache/caracdisp/');
		}
	}

	/**
	 * Désinstallation
	 * @see PluginsClassiques::destroy()
	 */
	function destroy(){
		$this->query('ALTER TABLE  `image` DROP `caracdispdesc`');
		$this->query('ALTER TABLE  `image` DROP `caracdispdesc_prod`');
	}

	/**
	 * Modification d'un produit
	 * @see PluginsClassiques::modprod()
	 */
	function modprod($produit) {
		$this->actions($produit);
	}

	/**
	 * Modification d'une déclinaison
	 * @see PluginsClassiques::modcaracteristique()
	 */
	function modcaracteristique($caracteristique) {
	    $this->actions();
	}

	/**
	 * Traitements des actions
	 * @param Produit $produit
	 */
    function actions (Produit $produit=null) {
		$lang=$_SESSION["util"]->lang;
		if(!empty($_REQUEST['lang']) && preg_match('/^[0-9]{1,}$/', $_REQUEST['lang'])) $lang=$_REQUEST['lang'];
		if(empty($lang)) $lang=1;

		// Instances de classes dont on aura besoin
		$caracdispdescObj = new Caracdispdesc();
		$imagedescObj = new Imagedesc();

		foreach((array) $_POST['caracphotos']['photos'] as $caracdispdescId => $caracphoto) {
		    // upload(s)
		    foreach((array) $_FILES['caracphotos']['tmp_name']['photos'][$caracdispdescId]['uploads'] as $key => $fileTmpName) {
		        $file = array(
		            'name' => $_FILES['caracphotos']['name']['photos'][$caracdispdescId]['uploads'][$key]['data'],
		            'type' => $_FILES['caracphotos']['type']['photos'][$caracdispdescId]['uploads'][$key]['data'],
		            'size' => $_FILES['caracphotos']['size']['photos'][$caracdispdescId]['uploads'][$key]['data'],
		            'tmp_name' => $_FILES['caracphotos']['tmp_name']['photos'][$caracdispdescId]['uploads'][$key]['data'],
		            'error' => $_FILES['caracphotos']['error']['photos'][$caracdispdescId]['uploads'][$key]['data']
		        );

		        if(!empty($file['error'])) continue;
                $image = $this->upload($file, $caracdispdescId, $produit);


		        // Doit-on répercuter l'upload sur toutes les autres langues ?
				if($image && !empty($caracphoto['uploads'][$key]['fallback_langs'])) {
                    $reqCaracdispOtherLangs = $this->query('
                        SELECT d1.id
                        FROM ' .
                        	$caracdispdescObj->table . ' AS d1, ' .
                        	$caracdispdescObj->table . ' AS d2
                        WHERE
                        	d2.id=' . $caracdispdescId . '
                       		AND d2.caracdisp = d1.caracdisp
                        	AND d1.id!='. $caracdispdescId
					);
        			while($row = mysql_fetch_object($reqCaracdispOtherLangs)){
        			    $imageTmp = clone $image;
        			    $imageTmp->id='';
        			    $imageTmp->caracdispdesc = $row->id;
        			    $imageTmp->add();
            		}
				}
		    } // fin des uploads

		    // modification des photos (titre, chapo, description)
    		foreach((array) $caracphoto['photos'] as $imageId => $image) {
    			if(!$imagedescObj->charger($imageId, $lang)) {
    			    $imagedescObj->id = '';
                    $imagedescObj->image = $imageId;
                    $imagedescObj->lang = $lang;
    			}
    			$imagedescObj->titre = empty($image['titre']) ? '' : mysql_real_escape_string($image['titre']);
    			$imagedescObj->chapo = empty($image['chapo']) ? '' : mysql_real_escape_string($image['chapo']);
    			$imagedescObj->description = empty($image['description']) ? '' : mysql_real_escape_string($image['description']);
    			if(!$imagedescObj->id) $imagedescObj->add();
    			else $imagedescObj->maj();
    		}
		}
		return $this;
	}

	/**
	 * Boucle <THELIA type="CARACPHOTOS">
	 * @see PluginsClassiques::boucle()
	 */
	function boucle($texte, $args) {

	    $params = array();
		// param caracteristique
		$params['caracteristique']  = lireTag($args, 'caracteristique', 'int');
		if(!preg_match('/^[0-9]{1,}$/', $params['caracteristique'])) $params['caracteristique'] = '';

		// param caracdisp
		$params['caracdisp']        = lireTag($args, 'caracdisp', 'int');
		if(!preg_match('/^[0-9]{1,}$/', $params['caracdisp'])) $params['caracdisp'] = '';

		// param produit
		$params['produit']          = lireTag($args, 'produit', 'int');
		if(!preg_match('/^[0-9]{1,}$/', $params['produit'])) $params['produit'] = '';

		// param lang
		$params['lang']             = lireTag($args, 'lang', 'int');
		if(empty($params['lang'])) $params['lang'] = $_SESSION['navig']->lang;

		// param fallback
		$params['fallback'] = '|' . lireTag($args, 'fallback');
		$params['fallbacks'] = array();
		$params['fallbacks'] = explode('|', $params['fallback']);
		foreach((array) $params['fallbacks'] as $key => $fallback) {
		    $params['fallbacks'][$key] = explode(',', $fallback);
		}

		// params LIMIT et ORDER BY
		$params['debut']  = lireTag($args,"debut", 'int');
		$params['num']              = lireTag($args,"num", 'int');
		$params['classement']       = lireTag($args, "classement");

		// params config image(s)
		$params['largeur']          = lireTag($args, "largeur", "int_list");
		$params['hauteur']          = lireTag($args, "hauteur", "int_list");
		$params['opacite']          = lireTag($args, "opacite", "int");
		$params['noiretblanc']      = lireTag($args, "noiretblanc", "int");
		$params['miroir']           = lireTag($args, "miroir", "int");

		if(empty($params['caracteristique']) && empty($params['caracdisp']) && empty($params['produit'])) return '';

		// On boucle sur les fallbacks jusqu'à trouver au moins un résultat
		$results = array(); // tableau de résultats
		foreach((array) $params['fallbacks'] as $key => $fallbacks) {
		    $paramsSQL = $params;
		    $paramsSQL['fallbacks'] = $params['fallbacks'][$key];
		    $req = $this->query($this->_boucleSQL($paramsSQL));
		    $total = mysql_num_rows($req);
		    if($total > 0) {
        		while($row = mysql_fetch_object($req)){
        		    $results[] = $row;
        		}
                break;
		    }
		}

		$res = ''; // résultat final;
		$compt = 1;
		$total = count($results);
		foreach((array) $results as $row) {
		    // images : #ID, #TITRE, #CHAPO, #DESCRIPTION
		    $temp = str_replace("#ID", $row->id, $texte);
    		$temp = str_replace("#TITRE",$row->titre,$temp);
			$temp = str_replace("#CHAPO",$row->chapo,$temp);
			$temp = str_replace("#DESCRIPTION",$row->description,$temp);

			// On peut demander l'image dans autant de variantes que l'on veut (largeurs, hauteurs,...),
			// elles seront accessibles dans la boucle via
			// #FICHIER, #IMAGE,
			// #2_FICHIER, #2_IMAGE,
			// #3_FICHIER, #3_IMAGE,
			// etc
			$largeurs     = explode(',', $params['largeur']);
			$hauteurs     = explode(',', $params['hauteur']);
			$opacites     = explode(',', $params['opacite']);
            $noiretblancs = explode(',', $params['noiretblanc']);
            $miroirs      = explode(',', $params['miroir']);
            $biggestArray = count(max($largeurs, $hauteurs, $opacites, $noiretblancs, $miroirs));
            for($i=0; $i<$biggestArray; $i++) {
                $largeur = (!empty($largeurs[$i]) && preg_match('/^[0-9]{1,}$/', $largeurs[$i]) ? $largeurs[$i] : '');
                $hauteur = (!empty($hauteurs[$i]) && preg_match('/^[0-9]{1,}$/', $hauteurs[$i]) ? $hauteurs[$i] : '');
                $opacite = (!empty($opacites[$i]) && preg_match('/^[0-9]{1,}$/', $opacites[$i]) ? $opacites[$i] : '');
                $noiretblanc = (!empty($noiretblancs[$i]) && preg_match('/^[0-9]{1,}$/', $noiretblancs[$i]) ? $noiretblancs[$i] : '');
                $miroir = (!empty($miroirs[$i]) && preg_match('/^[0-9]{1,}$/', $miroirs[$i]) ? $miroirs[$i] : '');


                $baliseIMAGE = array('#' . ($i+1) . '_IMAGE');
                $baliseFICHIER = array('#' . ($i+1) . '_FICHIER');
                if($i==0) { // on garde la syntaxe des balises de la boucle IMAGE pour le premier résultat
                    $baliseIMAGE[] = '#IMAGE';
                    $baliseFICHIER[] = '#FICHIER';
                }

                $temp = str_replace($baliseFICHIER,  "client/gfx/photos/caracdisp/" . $row->fichier, $temp);
                if($largeur || $hauteur || $opacite || $noiretblanc || $miroir) {
                    $nomcache = redim("caracdisp", $row->fichier, $largeur, $hauteur, $opacite, $noiretblanc, $miroir, 0);
                    $temp = str_replace($baliseIMAGE, $nomcache, $temp);
    			} else $temp = str_replace($baliseIMAGE, "client/gfx/photos/caracdisp/" . $row->fichier, $temp);
            }

			// #PRODUIT
			$temp = str_replace("#PRODUIT", $row->caracdispdesc_prod, $temp);

			// caracdisp : #CARACDISP et #CARACDISPTITRE
			$temp = str_replace("#CARACDISP",$row->caracdisp,$temp);
			$temp = str_replace("CARACDISPTITRE",$row->caracdisptitre,$temp);

			// déclinaison : #CARACTERISTIQUE, #CARACTITRE, #CARACCHAPO, #CARACDESCRIPTION
			$temp = str_replace("#CARACTERISTIQUE",$row->caracteristique,$temp);
			$temp = str_replace("#CARACTITRE",$row->caractitre,$temp);
            $temp = str_replace("#CARACCHAPO",$row->caracchapo,$temp);
            $temp = str_replace("#CARACDESCRIPTION",$row->caracdescription,$temp);

            // compteurs
			$temp = str_replace("#COMPT",$compt,$temp);
			$temp = str_replace("#TOTAL",$total,$temp);
            $compt++;
			$res .= $temp;
		}
 		return $res;
	}

	/**
	 * Génère la requête SQL utilisée pour les boucles
	 * @param array $params
	 */
	protected function _boucleSQL($params = array()) {
	    //var_dump($params);
	    // Objets dont on va avoir besoin
		$imageClass = new ImageCaracdisp();
        $imagedescClass = new Imagedesc();
        $caracdispClass = new Caracdisp();
        $caracdispdescClass = new Caracdispdesc();
        $caracteristiqueClass = new Caracteristique();
        $caracteristiquedescClass = new Caracteristiquedesc();

        // fallback langue
        $params['lang'] = (empty($params['lang']) ? 1 : $params['lang']);
        if(in_array('-lang', $params['fallbacks'])) $params['lang']=1;

        // fallback produit
        if(!empty($params['produit']) && in_array('-produit', $params['fallbacks'])) {
            $params['produit']=0;
        }
        //var_dump($params);

		// WHERE ...
		$search = '';
        $search .= ' AND image.caracdispdesc=caracdispdesc.id
        			 AND caracdispdesc.lang = ' . $params['lang'] . '
        			 AND caracdispdesc.caracdisp = caracdisp.id
        			 AND caracteristique.id = caracdisp.caracteristique
        			 AND caracteristiquedesc.caracteristique = caracteristique.id
        			 AND caracteristiquedesc.lang=' . $params['lang'];
        if($params['caracteristique'] != '') {
            $search .= ' AND caracteristique.id=' . $params['caracteristique'];
        }
        if($params['caracdisp'] != '') {
            $search .= ' AND caracdisp.id=' . $params['caracdisp'];
        }
        $search .= ' AND image.caracdispdesc_prod=' . (!empty($params['produit']) ? $params['produit'] : 0);

        // ORDER BY ...
        $order = '';
        switch($params['classement']) {
        	case 'id' :
        		$order = 'image.id ASC';
        		break;
        	case 'idinv' :
        	    $order = 'image.id DESC';
        		break;
        	case 'aleatoire' :
        		$order = 'RAND()';
        		break;
        	case 'manuelinv' :
        		$order = 'image.classement DESC';
        		break;
        	default :
        		$order = 'image.classement ASC';
        }
        $order = ' ORDER BY ' . $order;

        // LIMIT ...
        $limit= '';
        if(!empty($params['debut']) || !empty($params['num'])) {
            $limit_deb = ($params['debut'] != '') ? $params['debut'] : 0;
            $limit_num = ($params['num'] != '') ? $params['num'] : 99999;
            $limit = ' LIMIT ' . $limit_deb . ',' . $limit_num;
        }

        // assemblage de la requête SQL
		$query = '
			SELECT
				image.id,
				image.caracdispdesc_prod,
				image.fichier,
				imagedesc.titre,
				imagedesc.chapo,
				imagedesc.description,
				caracdisp.id AS caracdisp,
				caracdispdesc.titre AS caracdisptitre,
				caracdisp.caracteristique AS caracteristique,
				caracteristiquedesc.titre AS caractitre,
				caracteristiquedesc.chapo AS caracchapo,
				caracteristiquedesc.description AS caracdescription
			FROM ';
		// LEFT OUTER JOIN car toutes les images ne disposent pas d'une correspondance en table imagedesc,
		// et il ne faut pas pour autant les exclure
		$query .= $imageClass->table . ' AS image LEFT OUTER JOIN ' . $imagedescClass->table . ' AS imagedesc ON(image.id=imagedesc.image AND imagedesc.lang=' . $params['lang'] . '),' .
		        $caracdispClass->table . ' AS caracdisp,' .
		        $caracdispdescClass->table . ' AS caracdispdesc,' .
                $caracteristiqueClass->table . ' AS caracteristique, ' .
                $caracteristiquedescClass->table . ' AS caracteristiquedesc
            WHERE 1 ' . $search . $order . $limit;
        //var_dump($query);
		//echo('<br/>');
		return $query;
	}

	/**
	 * Upload d'une photo
	 * @param $_FILES $uploadData
	 * @param int $caracdispdesc
	 * @param int $produit
	 * @return Image $image
	 */
	function upload($uploadData, $caracdispdesc, Produit $produit=null) {

		$photo = $uploadData['tmp_name'];
		$photo_name = $uploadData['name'];

		if(empty($photo)) {
			echo 'Aucune photo transmise';
			return false;
		}

		preg_match("/([^\/]*).((jpg|gif|png|jpeg))/i", $photo_name, $decoupe);
		$fich = eregfic($decoupe[1]);
		$extension = $decoupe[2];
		if($fich == "" || $extension == "") {
			echo 'Fichier non conforme';
			return false;
		}

		$image = new ImageCaracdisp();
		$imagedesc = new Imagedesc();

		$query = "SELECT MAX(classement) AS maxClassement
					FROM $image->table
					WHERE caracdispdesc_prod='" . $produit->id . "'
					AND caracdispdesc='" . $caracdispdesc . "'";
		$resul = $this->query($query);
     	$maxClassement = mysql_result($resul, 0, "maxClassement");

     	$image->caracdispdesc = $caracdispdesc;
		$image->caracdispdesc_prod = empty($produit) ? 0 : $produit->id;
		$image->classement = $maxClassement + 1;

		$lastid = $image->add();
		$image->charger($lastid);
		$image->fichier = ereg_caracspec($fich . "_" . $lastid) . "." . strtolower($extension);
		$image->maj();

		copy($photo, '../client/gfx/photos/caracdisp/' . $image->fichier);

	    modules_fonction("uploadimage", $lastid);
		return $image;
	}

	/**
	 * Génère l'interface utilisateur
	 * @param unknown_type $produit
	 * @param unknown_type $lang
	 */
	public function renderUI(Produit $produit=null, Caracteristique $carac=null, $lang=1) {

		// vérif de la langue
		if(!preg_match('/^[0-9]{1,}$/', $lang)) $lang=1;

		$caracObj 			= new Caracteristique();
		$caracDescObj		= new Caracteristiquedesc();
		$caracDispObj 		= new Caracdisp();
		$caracDispDescObj 	= new Caracdispdesc();
		$langObj 	        = new Lang();
		$resultats = array();

		$query = '
			SELECT
				caracdispdesc.id AS caracdispdesc_id,
				caracdispdesc.caracdisp AS caracdisp_id,
				caracdispdesc.lang AS caracdispdesc_lang,
				caracdispdesc.titre AS caracdispdesc_titre,
				caracdispdesc.classement AS caracdispdesc_classement,
				caracteristiquedesc.titre AS caracteristique_titre,
				caracteristique.id AS caracteristique_id,';
		// On regarde si il y a plus d'une langue paramétrée,
		// auquel cas on va proposer une checkbox pour uploader
		// les photos dans toutes les langues d'un coup
		$query .= '(SELECT id FROM ' . $langObj->table . ' LIMIT 1,1) AS autrelang';

		$query .= '
			FROM
				' . $caracObj->table . ' AS caracteristique,
				' . $caracDescObj->table . ' AS caracteristiquedesc,
				' . $caracDispObj->table . ' AS caracdisp,
				' . $caracDispDescObj->table . ' AS caracdispdesc
			WHERE
				caracteristique.id = caracteristiquedesc.caracteristique';

		if(!empty($carac))
		    $query .= ' AND caracteristique.id =' . $carac->id;

		$query .= '
				AND caracteristiquedesc.caracteristique = caracdisp.caracteristique
				AND caracteristiquedesc.lang=' . $lang . '
				AND caracdisp.id = caracdispdesc.caracdisp
				AND caracdispdesc.lang=' . $lang . '
			ORDER BY
				caracteristique.classement ASC,
				caracdispdesc.classement ASC';
		$CaracdispsReq = $caracObj->query($query);
		//var_dump($query);
		while($row = mysql_fetch_object($CaracdispsReq)) {
			$resultats[] = $row;
		}

		if(!empty($produit)) $caracphotos_urlRetour = 'produit_modifier.php?ref=' . lireParam('ref') . '&rubrique=' . lireParam('rubrique', 'int') . '&lang=' . $lang;
		else $caracphotos_urlRetour = 'caracteristique_modifier.php?id=' . $carac->id;
		include realpath(dirname(__FILE__)) . "/inc/ui.php";
	}
}
