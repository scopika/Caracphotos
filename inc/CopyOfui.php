<?php
//autorisation
require_once 'pre.php';
require_once 'auth.php';
include_once realpath(dirname(__FILE__)) . '/../../../../fonctions/authplugins.php';
autorisation('caracphotos');

include_once(realpath(dirname(__FILE__)) . "/../Image.caracdisp.class.php");
$idproduit = !empty($produit->id) ? $produit->id : '';
?>

<script type="text/javascript">
$(document).ready(function(){
	$('.caracphotos_valider').click(function() {
		$(this).parents('form:eq(0)').submit();
		return false;
	});
});
</script>

<div class="entete_liste_config">
	<div class="titre">ASSOCIER DES IMAGES AUX CARACTERISTIQUES</div>
	<div class="fonction_valider"><a href="#" class="caracphotos_valider">VALIDER LES MODIFICATIONS</a></div>
</div>

<div class="blocs_pliants_prod caracphotos" id="pliantcaracphotos">

	<?php 
	$caracTitre = null;
	foreach((array) $caracdisps as $caracdisp) {
		
		// Changement de caractéristique ?
		if($caracdisp->caractitre != $caracTitre) {
			$caracTitre = $caracdisp->caractitre;
			?>
			<ul class="ligne1">
				<li class="cellule">Caractéristique &quot;<?php echo $caracdisp->caractitre; ?>&quot;</li>
			</ul>
			<?php 
		} ?>

		<ul class="lignesimple">
			<li class="cellule">
				<?php 
				$imageDesc = new Imagedesc();
				$imageCaracdisp = new ImageCaracdisp();
				$photos = $imageCaracdisp->charger_caracdisp_photos($caracdisp->id, $idproduit);
				if(!empty($photos[0])) { 
				    ?>
				    <strong><?php echo $caracdisp->titre . ' (' . count($photos) . ' photo' . ((count($photos) > 1) ? 's' : '') . ')'; ?></strong>
    				<div class="blocs_pliants_photo" id="pliantsphotos">
        				<ul>
    					    <?php 
        					foreach((array) $photos as $row) {
        						$imageDesc->charger($row->id, $lang);
        						?>
        						<li class="lignesimple">
        							<input type="hidden" name="caracphotos_photo[<?php echo $row->id; ?>]" value="caracdisp_photos_modifier" />
        							<div class="cellule_designation" style="height:208px;">&nbsp;</div>
        							<div class="cellule_photos" style="height:200px; overflow:hidden;">
        								<img src="../client/plugins/caracphotos/fonctions/redimlive.php?type=caracdisp&nomorig=<?php echo($row->fichier); ?>&amp;width=208&amp;height=&amp;opacite=" border="0" alt="-" />
        							</div>
        							<div class="cellule_supp">
        								<a href="<?php echo $caracphotos_urlRetour; ?>&id_photo=<?php echo($row->id); ?>&amp;caracphotos_action=caracdisp_photos_supprimer">
        									<img src="gfx/supprimer.gif" width="9" height="9" border="0" alt="Supprimer" />
        								</a>
        							</div>
        						</li>
        						<li class="lignesimple">
        							<div class="cellule_designation" style="height:30px;">Titre</div>
        							<div class="cellule">
        								<input type="text" name="caracdisp_photo_<?php echo($row->id); ?>_titre" style="width:219px;" class="form" value="<?php echo $imageDesc->titre; ?>" />
        							</div>
        						</li>
        						<li class="lignesimple">
        							<div class="cellule_designation" style="height:50px;">Chapo</div>
        							<div class="cellule">
        								<textarea name="caracdisp_photo_<?php echo($row->id); ?>_chapo" rows="2" cols="" class="form" style="width:219px;"><?php echo $imageDesc->chapo; ?></textarea>
        							</div>
        						</li>
        						<li class="lignesimple">
        							<div class="cellule_designation" style="height:65px;">Description</div>
        							<div class="cellule"><textarea name="caracdisp_photo_<?php echo($row->id); ?>_description" class="form" rows="3" cols="" style="width:219px;"><?php echo $imageDesc->description; ?></textarea></div>
        						</li>
        						<li class="lignesimple">
        							<div class="cellule_designation" style="height:30px;">Classement</div>
        							<div class="cellule">
        								<div class="classement">
        									<a href="<?php echo $caracphotos_urlRetour; ?>&amp;caracphotos_action=caracdisp_photos_order&amp;id_photo=<?php echo($row->id); ?>&amp;sens=M"><img src="gfx/up.gif" border="0" alt="-" /></a>
        								</div>
        								<div class="classement">
        									<a href="<?php echo $caracphotos_urlRetour; ?>&amp;caracphotos_action=caracdisp_photos_order&amp;id_photo=<?php echo($row->id); ?>&amp;sens=D"><img src="gfx/dn.gif" border="0" alt="-" /></a>
        								</div>
        							</div>
        						</li>
        						<?php
        					} // endforeach $photos
        					?>
        				</ul>
					</div> <!-- / .blocs_pliants_photo -->
					<?php 
				} ?>
			</li>
			<li class="cellule" style="text-align:right">
				<input type="hidden" name="caracphotos_action[<?php echo $caracdisp->id; ?>]" value="caracdisp_upload" />
			    <input type="file" size="18" name="caracphotos_caracdisp_<?php echo $caracdisp->id; ?>_upload" />
			</li>
		</ul>
		
		<?php
	} // end foreach $caracdisps
	?>
</div>