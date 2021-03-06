<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/admin/expedition.php
 *	\ingroup    expedition
 *	\brief      Page d'administration/configuration du module Expedition
 *	\version    $Id: expedition.php,v 1.70 2011/07/31 22:23:22 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("sendings");
$langs->load("deliveries");

if (!$user->admin) accessforbidden();

if (empty($conf->global->EXPEDITION_ADDON_NUMBER))
{
    $conf->global->EXPEDITION_ADDON_NUMBER='mod_expedition_safor';
}


/*
 * Actions
 */
if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$exp = new Expedition($db);
	$exp->initAsSpecimen();
	//$exp->fetch_commande();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/expedition/pdf/";
	$file = "pdf_expedition_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_expedition_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($exp,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=expedition&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			$mesg='<div class="error">'.$obj->error.'</div>';
			dol_syslog($obj->error, LOG_ERR);
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
if ($_GET["action"] == 'set')
{
	$type='shipping';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($_GET["value"])."','".$type."',".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'del')
{
	$type='shipping';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$_GET["value"]."'";
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;

	if ($db->query($sql))
	{

	}
}

// Set default model
if ($_GET["action"] == 'setdoc')
{
	$db->begin();

	if (dolibarr_set_const($db, "EXPEDITION_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->EXPEDITION_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$type='shipping';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$db->escape($_GET["value"])."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	$result1=$db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($_GET["value"])."', '".$type."', ".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
	$result2=$db->query($sql);
	if ($result1 && $result2)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

// TODO A quoi servent les methode d'expedition ?
if ($_GET["action"] == 'setmethod' || $_GET["action"] == 'setmod')
{
	$module=$_GET["module"];
	$moduleid=$_GET["moduleid"];
	$statut=$_GET["statut"];

	require_once(DOL_DOCUMENT_ROOT."/includes/modules/expedition/methode_expedition_$module.modules.php");

	$classname = "methode_expedition_$module";
	$expem = new $classname($db);

	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_shipment_mode";
	$sql.= " WHERE rowid = ".$moduleid;

	$resql = $db->query($sql);
	if ($resql && ($statut == 1 || $_GET["action"] == 'setmod'))
	{
		$db->begin();

		$sqlu = "UPDATE ".MAIN_DB_PREFIX."c_shipment_mode";
		$sqlu.= " SET statut=1";
		$sqlu.= " WHERE rowid=".$moduleid;

		$result=$db->query($sqlu);
		if ($result)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}

	if ($statut == 1 || $_GET["action"] == 'setmod')
	{
		$db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_shipment_mode (rowid,code,libelle,description,statut)";
		$sql.= " VALUES (".$moduleid.",'".$expem->code."','".$expem->name."','".$expem->description."',1)";
		$result=$db->query($sql);
		if ($result)
		{
			$db->commit();
		}
		else
		{
			//dol_print_error($db);
			$db->rollback();
		}
	}
	else if ($statut == 0)
	{
		$db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."c_shipment_mode";
		$sql.= " SET statut=0";
		$sql.= " WHERE rowid=".$moduleid;
		$result=$db->query($sql);
		if ($result)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}
}

if ($_GET["action"] == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "EXPEDITION_ADDON",$_GET["module"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'updateMask')
{
	$maskconst=$_POST['maskconstexpedition'];
	$maskvalue=$_POST['maskexpedition'];
	if ($maskconst) dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'setmodel')
{
	dolibarr_set_const($db, "EXPEDITION_ADDON_NUMBER",$_GET["value"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_SHIPPING_DRAFT_WATERMARK')
{
	dolibarr_set_const($db, "SHIPPING_DRAFT_WATERMARK",trim($_POST["SHIPPING_DRAFT_WATERMARK"]),'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_SHIPPING_FREE_TEXT')
{
	dolibarr_set_const($db, "SHIPPING_FREE_TEXT",$_POST["SHIPPING_FREE_TEXT"],'chaine',0,'',$conf->entity);
}


/*
 * View
 */

$html=new Form($db);


llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SendingsSetup"),$linkback,'setup');
print '<br>';


if ($mesg) print $mesg.'<br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
$head[$h][1] = $langs->trans("Sending");
$hselected=$h;
$h++;

if ($conf->global->MAIN_SUBMODULE_LIVRAISON)
{
	$head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
	$head[$h][1] = $langs->trans("Receivings");
	$h++;
}

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 * Numbering module
 */
//print "<br>";

print_titre($langs->trans("SendingsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/includes/modules/expedition/";

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			$var=true;

			while (($file = readdir($handle))!==false)
			{
				if (substr($file, 0, 15) == 'mod_expedition_' && substr($file, dol_strlen($file)-3, 3) == 'php')
				{
					$file = substr($file, 0, dol_strlen($file)-4);

					require_once(DOL_DOCUMENT_ROOT ."/includes/modules/expedition/".$file.".php");

					$module = new $file;

					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{
						$var=!$var;
						print '<tr '.$bc[$var].'><td>'.$module->nom."</td>\n";
						print '<td>';
						print $module->info();
						print '</td>';

                        // Show example of numbering module
                        print '<td nowrap="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp))
                        {
                        	$langs->load('errors');
                        	print $langs->trans($tmp);
                        }
                        else print $tmp;
                        print '</td>'."\n";

						print '<td align="center">';
						if ($conf->global->EXPEDITION_ADDON_NUMBER == "$file")
						{
							print img_picto($langs->trans("Activated"),'on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodel&amp;value='.$file.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
							print img_picto($langs->trans("Disabled"),'off');
							print '</a>';
						}
						print '</td>';

						$expedition=new Expedition($db);
						$expedition->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$facture->type=0;
						$nextval=$module->getNextValue($mysoc,$expedition);
						if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
						{
							$htmltooltip.=''.$langs->trans("NextValue").': ';
							if ($nextval)
							{
								$htmltooltip.=$nextval.'<br>';
							}
							else
							{
								$htmltooltip.=$langs->trans($module->error).'<br>';
							}
						}

						print '<td align="center">';
						print $html->textwithpicto('',$htmltooltip,1,0);
						print '</td>';

						print '</tr>';
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table><br>';


/*
 *  Modeles de documents
 */
print_titre($langs->trans("SendingsReceiptModel"));

// Defini tableau def de modele invoice
$type="shipping";
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;

$resql=$db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/includes/modules/expedition/pdf/";

	if (is_dir($dir))
	{
		$handle=opendir($dir);
		$var=true;

	    if (is_resource($handle))
	    {
	    	while (($file = readdir($handle))!==false)
	    	{
	    		if (substr($file, dol_strlen($file) -12) == '.modules.php' && substr($file,0,15) == 'pdf_expedition_')
	    		{
	    			$name = substr($file, 15, dol_strlen($file) - 27);
	    			$classname = substr($file, 0, dol_strlen($file) - 12);

	    			$var=!$var;
	    			print "<tr $bc[$var]><td>";
	    			print $name;
	    			print "</td><td>\n";
	    			require_once($dir.$file);
	    			$module = new $classname();

	    			print $module->description;
	    			print '</td>';

	    			// Active
	    			if (in_array($name, $def))
	    			{
	    				print "<td align=\"center\">\n";
	    				if ($conf->global->EXPEDITION_ADDON_PDF != $name)
	    				{
	    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
	    					print img_picto($langs->trans("Activated"),'on');
	    					print '</a>';
	    				}
	    				else
	    				{
	    					print img_picto($langs->trans("Activated"),'on');
	    				}
	    				print "</td>";
	    			}
	    			else
	    			{
	    				print "<td align=\"center\">\n";
	    				print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	    				print "</td>";
	    			}

	    			// Default
	    			print "<td align=\"center\">";
	    			if ($conf->global->EXPEDITION_ADDON_PDF == $name)
	    			{
	    				print img_picto($langs->trans("Default"),'on');
	    			}
	    			else
	    			{
	    				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	    			}
	    			print '</td>';

	    			// Info
	    			$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
	    			$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
	    			$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
	    			$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
	    			$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
	    			print '<td align="center">';
	    			print $html->textwithpicto('',$htmltooltip,1,0);
	    			print '</td>';
	    			print '<td align="center">';
	    			print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_object($langs->trans("Preview"),'sending').'</a>';
	    			print '</td>';

	    			print '</tr>';
	    		}
	    	}
	    	closedir($handle);
	    }
	}
}

print '</table>';
print '<br>';


/*
 * Other options
 *
 */
print_titre($langs->trans("OtherOptions"));

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>\n";
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print "<td>&nbsp;</td>\n";
print "</tr>";

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIPPING_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnShippings").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="SHIPPING_FREE_TEXT" class="flat" cols="120">'.$conf->global->SHIPPING_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIPPING_DRAFT_WATERMARK">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraft").'<br>';
print '<input size="50" class="flat" type="text" name="SHIPPING_DRAFT_WATERMARK" value="'.$conf->global->SHIPPING_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

print '</table>';

$db->close();

llxFooter();
?>
