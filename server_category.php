<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      JF FERRY             <jfefe@aternatik.fr>
 * Copyright (C) 2014      Sebastián Bover Mota <sebassbm.info@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *       \file       htdocs/webservices/server_category.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
//require_once(/*your_route_to_FirePHP.*/"/FirePHPCore/lib/FirePHPCore/FirePHP.class.php");//firephp
//ob_start();//firephp
//$firephp = FirePHP::getInstance(true);//firephp
//$firephp->info('Bibliotecas cargadas.', '');//firephp

dol_syslog("Call Dolibarr webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrCategorie',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL content
$server->wsdl->addComplexType(
	'authentication',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
		'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
		'login' => array('name'=>'login','type'=>'xsd:string'),
		'password' => array('name'=>'password','type'=>'xsd:string'),
		'entity' => array('name'=>'entity','type'=>'xsd:string'),
	)
);

/*
 * Une catégorie
 */
$server->wsdl->addComplexType(
	'categorie',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:string'),
		'id_mere' => array('name'=>'id_mere','type'=>'xsd:string'),
		'label' => array('name'=>'label','type'=>'xsd:string'),
		'description' => array('name'=>'description','type'=>'xsd:string'),
		'socid' => array('name'=>'socid','type'=>'xsd:string'),
		'type' => array('name'=>'type','type'=>'xsd:string'),
		'visible' => array('name'=>'visible','type'=>'xsd:string'),
		'dir'=> array('name'=>'dir','type'=>'xsd:string'),
		'photos' => array('name'=>'photos','type'=>'tns:PhotosArray'),
		'filles' => array('name'=>'filles','type'=>'tns:FillesArray')
	)
);

/*
 * Les catégories filles, sous tableau dez la catégorie
 */
 $server->wsdl->addComplexType(
    'FillesArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:categorie[]')
    ),
    'tns:categorie'
);

//$firephp->info('Fase 0 completada.');//firephp

/*
 * FIXME -> sbm_mod -> result must be defined here
 */
$server->wsdl->addComplexType(
	'CategoriesArray',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'categories' => array(
            'name' => 'category',
            'type' => 'tns:categorie',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
	)
);

//$firephp->info('Fase 1 completada.');//firephp

/*
 * Tableau des catégories

$server->wsdl->addComplexType(
    'categories',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('id'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:categorie[]')
    ),
    'tns:categories'
);
 */

/*
 * Les photos  de la catégorie (un tableau indéxé qui contient les images avec leur vignette)
 */
$server->wsdl->addComplexType(
	'PhotosArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:image[]')
    ),
	''
);

/*
 * Une photo ( nom image / nom_vignette )
 */
$server->wsdl->addComplexType(
 	'image',
 	'complexType',
 	'array',
 	'',
 	'SOAP-ENC:Array',
 	array(),
 	array(
	 	'photo' => array('name'=>'photo','type'=>'xsd:string'),
	 	'photo_vignette' => array('name'=>'photo_vignette','type'=>'xsd:string'),
	 	'imgWidth' => array('name'=>'imgWidth','type'=>'xsd:string'),
	 	'imgHeight' => array('name'=>'imgHeight','type'=>'xsd:string')
	)
);

/*
 * Retour
 */
$server->wsdl->addComplexType(
	'result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
		'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
	)
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
	'getCategory',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result','categorie'=>'tns:categorie'),
	$ns,
    $ns.'#getCategory',
    $styledoc,
    $styleuse,
    'WS to get category'
);
// FIXME -> sbm_mod -> register my function
$server->register(
	'getAllCategories',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','categories'=>'tns:CategoriesArray'),
	$ns,
    $ns.'#getAllCategories',
    $styledoc,
    $styleuse,
    'WS to get detailed list of all categories'
);

//$firephp->info('Fase 2 completada.');//firephp

/**
 * Get category infos and children
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @return	mixed
 */
function getCategory($authentication,$id)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getCategory login=".$authentication['login']." id=".$id);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error && !$id)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id must be provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->categorie->lire)
		{
			$categorie=new Categorie($db);
			$result=$categorie->fetch($id);
			if ($result > 0)
			{
					$dir = (!empty($conf->categorie->dir_output)?$conf->categorie->dir_output:$conf->service->dir_output);
					$pdir = get_exdir($categorie->id,2) . $categorie->id ."/photos/";
					$dir = $dir . '/'. $pdir;

					$cat = array(
						'id' => $categorie->id,
						'id_mere' => $categorie->id_mere,
						'label' => $categorie->label,
						'description' => $categorie->description,
						'socid' => $categorie->socid,
						//'visible'=>$categorie->visible,
						'type' => $categorie->type,
						'dir' => $pdir,
						'photos' => $categorie->liste_photos($dir,$nbmax=10)
			    	);

					$cats = $categorie->get_filles();
					if (count($cats) > 0)
					{
					 	foreach($cats as $fille)
						{
							$dir = (!empty($conf->categorie->dir_output)?$conf->categorie->dir_output:$conf->service->dir_output);
							$pdir = get_exdir($fille->id,2) . $fille->id ."/photos/";
							$dir = $dir . '/'. $pdir;
							$cat['filles'][] = array(
								'id'=>$fille->id,
								'id_mere' => $categorie->id_mere,
								'label'=>$fille->label,
								'description'=>$fille->description,
								'socid'=>$fille->socid,
								//'visible'=>$fille->visible,
								'type'=>$fille->type,
								'dir' => $pdir,
								'photos' => $fille->liste_photos($dir,$nbmax=10)
							);

						}

					}

			    // Create
			    $objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'categorie'=> $cat
			   );
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

/**
 * Get ALL categories infos and children -> sbm_mod
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @return	array(mixed)
 */
function getAllCategories($authentication)//FIXME -> Is there something wrong in this function?
{
	global $db,$conf,$langs;

	dol_syslog("Function: getAllCategories login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

/*	if (! $error && !$id)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id must be provided.";
	}  sbm_mod -> disabled */

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->categorie->lire)
		{
			//sbm_mod -> prepare IDs array
            $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'categorie';
            $resql = $db->query($sql);
			$num = $db->num_rows($resql);
            $i = 0;
			$cat = array();

            //sbm_mod -> loop on each id
            while ($i < $num)
			{
				$categorie=new Categorie($db);
				$obj = $db->fetch_object($resql);
				$id = $obj->id;//sbm_mod -> appended line
				$result = $categorie->fetch($id);
				if ($result > 0)
				{
						$dir = (!empty($conf->categorie->dir_output)?$conf->categorie->dir_output:$conf->service->dir_output);
						$pdir = get_exdir($categorie->id,2) . $categorie->id ."/photos/";
						$dir = $dir . '/'. $pdir;

						$cat[$i] = array(
							'id' => $categorie->id,
							'id_mere' => $categorie->id_mere,
							'label' => $categorie->label,
							'description' => $categorie->description,
							'socid' => $categorie->socid,
							//'visible'=>$categorie->visible,
							'type' => $categorie->type,
							'dir' => $pdir,
							'photos' => $categorie->liste_photos($dir,$nbmax=10)
				    	);

						$cats = $categorie->get_filles();
						if (count($cats) > 0)
						{
						 	foreach($cats as $fille)
							{
								$dir = (!empty($conf->categorie->dir_output)?$conf->categorie->dir_output:$conf->service->dir_output);
								$pdir = get_exdir($fille->id,2) . $fille->id ."/photos/";
								$dir = $dir . '/'. $pdir;
								$cat[$i]['filles'][] = array(
									'id'=>$fille->id,
									'id_mere' => $categorie->id_mere,
									'label'=>$fille->label,
									'description'=>$fille->description,
									'socid'=>$fille->socid,
									//'visible'=>$fille->visible,
									'type'=>$fille->type,
									'dir' => $pdir,
									'photos' => $fille->liste_photos($dir,$nbmax=10)
								);

							}

						}

				    // Create -> sbm_mod -> new key CategoriesArray
				    $objectresp = array(
						'result'=>array('result_code'=>'OK', 'result_label'=>''),
						'CategoriesArray'=> $cat
				   );
				}
				else
				{
					$error++;
					$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
					break
				}
				$i++;
			}//sbm_mod -> end of my loop
/*			// Create -> sbm_mod -> moved creation out of the loop, but it failed anyway
			$objectresp = array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'CategoriesArray'=> $cat
			);*/
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service($HTTP_RAW_POST_DATA);

?>
