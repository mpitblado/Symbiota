    	</td>
	</tr>
	<tr>
		<td id="footer"  colspan="3">
			<div>
			<?php
				if($LANG_TAG=='en'){
			?>
				This project made possible by the following organizations:
			<?php
				} else if($LANG_TAG=='es'){
			?>
				Este proyecto hecho posible por las siguientes organizaciones:
			<?php
			} else{
			?>
				Ce projet a été rendu possible par les organisations suivantes:
			<?php
				}
			?>
			</div>
			<p>
				<a href="https://www.gbif.org/" target="_blank"><img src="<?php echo $CLIENT_ROOT; ?>/images/GBIF_logo.png" style="height:50px; padding: 10px" /></a>
				<a href="https://www.nature.org/en-us/about-us/where-we-work/africa/gabon/" target="_blank"><img src="<?php echo $CLIENT_ROOT; ?>/images/TNC_logo.png" style="height:50px; padding: 10px" /></a>
				<a href="https://biokic.asu.edu" target="_blank" title="Biodiversity Knowledge Integration Center"><img src="<?php echo $CLIENT_ROOT; ?>/images/layout/logo-asu-biokic.png" style="height:50px; padding: 10 px" /></a>
			</p>
			<div style="text-align:center;margin-bottom:15px">This project made possible by GBIF Award: <a href="https://www.nsf.gov/awardsearch/showAward?AWD_ID=" target="_blank">#------</a></div>				
 		</td>
	</tr>
</table>
