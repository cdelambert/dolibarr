<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       test/phpunit/ModulesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *      \version    $Id$
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

if (empty($user->id))
{
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @covers DolibarrModules
 * @covers modAccounting
 * @covers modAdherent
 * @covers modAgenda
 * @covers modBanque
 * @covers modBarcode
 * @covers modBookmark
 * @covers modBoutique
 * @covers modCashDesk
 * @covers modCategorie
 * @covers modClickToDial
 * @covers modCommande
 * @covers modComptabilite
 * @covers modContrat
 * @covers modDeplacement
 * @covers modDocument
 * @covers modDomain
 * @covers modDon
 * @covers modECM
 * @covers modExpedition
 * @covers modExport
 * @covers modExternalRss
 * @covers modExternalSite
 * @covers modFacture
 * @covers modFckeditor
 * @covers modFicheinter
 * @covers modFournisseur
 * @covers modFTP
 * @covers modGeoIPMaxmind
 * @covers modGravatar
 * @covers modImport
 * @covers modLabel
 * @covers modLdap
 * @covers modMailing
 * @covers modMantis
 * @covers modNotification
 * @covers modPaybox
 * @covers modPaypal
 * @covers modPhenix
 * @covers modPrelevement
 * @covers modProduct
 * @covers modProjet
 * @covers modPropale
 * @covers modService
 * @covers modSociete
 * @covers modStock
 * @covers modSyslog
 * @covers modTax
 * @covers modUser
 * @covers modWebcalendar
 * @covers modWebServices
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ModulesTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return BuildDocTest
	 */
	function ModulesTest()
	{
		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	// Static methods
  	public static function setUpBeforeClass()
    {
    	global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

    	print __METHOD__."\n";
    }
    public static function tearDownAfterClass()
    {
    	global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
    }

	/**
	 */
    protected function setUp()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
    }
	/**
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }

    /**
     */
    public function testModulesInit()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$modulelist=array('Accounting','Adherent','Agenda','Banque','Barcode','Bookmark','Boutique',
		'CashDesk','Categorie','ClickToDial','Commande','Comptabilite','Contrat','Deplacement','Document','Domain','Don',
		'ECM','Expedition','Export','ExternalRss','ExternalSite','FTP','Facture',
		'Fckeditor','Ficheinter','Fournisseur','GeoIPMaxmind','Gravatar','Import','Label','Ldap','Mailing',
		'Mantis','Notification','Paybox','Paypal','Phenix','Prelevement','Product','Projet','Propale',
		'Service','Societe','Stock','Syslog','Tax','User','WebServices','Webcalendar');
		foreach($modulelist as $modlabel)
		{
    		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/mod'.$modlabel.'.class.php');
            $class='mod'.$modlabel;
    		$mod=new $class($db);
            $result=$mod->remove();
            $result=$mod->init();
        	$this->assertLessThan($result, 0);
        	print __METHOD__." result=".$result."\n";
		}

        return 0;
    }

}
?>