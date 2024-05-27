<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
 * Copyright (C) 2024 SuperAdmin
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/docspecobjectmanager.class.php
 * \ingroup     specanddoc
 * \brief       This file is a CRUD class file for DocSpecObjectManager (Create/Read/Update/Delete)
 */


// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once (__DIR__ . '/docspecobject.class.php');
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for DocSpecObjectManager
 */
class DocSpecObjectManager extends CommonObject
{
	
	
	/**
	 * @var string ID of module.
	 */
	public $module = 'specanddoc';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'docspecobjectmanager';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'specanddoc_docspecobjectmanager';

	/**
	 * @var int  	Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for docspecobjectmanager. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'docspecobjectmanager@specanddoc' if picto is file 'img/object_docspecobjectmanager.png'.
	 */
	public $picto = 'fa-file';
	public $urlToGeneratorPage; 
	public $TConfig = [];
	public $TGlobalContextManaged = [];
	public $currentContext;

	const CLASS_PATH_FOLDER = '/class/';
	const ALL_ELEMENTS = '/*';
	const HELPER_SPECS_FILE_NAME ='specs.md' ;
	const HELPER_DOCS_FILE_NAME ='docs.md' ;
	const UP_ONE_DIRECTORY ='..';

	//const SUFFIXE = '_helper';
	const CLASS_OBJECT_INDEX = 0;
	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->urlToGeneratorPage = DOL_MAIN_URL_ROOT . $conf->file->dol_url_root['alt0']  . "/" . "specanddoc" . '/core_gen_web.php';
		$this->setGlobalContext();


	}

	/**
	 * @todo implementer la liste exaustive des contexts
	 * Utilisé dans le fichier d'action 
	 */
	private function setGlobalContext(){
		global $conf, $hookmanager;
		// PROJET
		$this->TGlobalContextManaged["projectsindex"] = "projectsindex";
		$this->TGlobalContextManaged["projectlist"] = "projectlist";
		// PROPAL
		$this->TGlobalContextManaged["propalcard"] = "propalcard";
		
		/** @todo fonction qui viendrait lire un fichier de context non std  qui serait alimenté par le hook llxheader  pour verifier s'il est déjà recensé */
			
	}

	/**
	 * SETTER currentContext
	 * $context string 
	 */
	public function  setCurrentContext(string $context) : DocSpecObjectManager {
		$this->currentContext = $context;

		return $this;
	}

	public function  getCurrentContext() : string {
		
		return $this->currentContext;
	}


	public function getLangsTransContext() : string {
		global $langs;	
		return   $langs->trans($this->getCurrentContext());
	}
	/**
	 * 
	 */
	public function init(){

		$this->loadClassFromActiveCustomModule();
		$this->loadResourcesFromClass();
	}
	
	/**
	 * charge en memoire TConfig les .md depuis les modules actifs dans custom.  et crée les classes docspecobject
	 */
	private function  loadClassFromActiveCustomModule(){
		global $dolibarr_main_document_root_alt;
		
		// Extracts files and directories that match a pattern
		$items = glob(  $dolibarr_main_document_root_alt . $this::ALL_ELEMENTS);
		
		foreach ($items as $item) {	
			if (is_dir($item) && $this->candidateToproceed($item) ) {
				$this->TConfig[basename($item)] = array();
				$this->TConfig[basename($item)]['object'] = new DocSpecObject( basename($item));

				if ($this->FileExistsWithName($item, $this::HELPER_SPECS_FILE_NAME )){
					$this->TConfig[basename($item)]['specsFile']  =  $this::HELPER_SPECS_FILE_NAME;

				}   
				if ($this->FileExistsWithName($item, $this::HELPER_DOCS_FILE_NAME )){
					$this->TConfig[basename($item)]['docsFile'] =  $this::HELPER_DOCS_FILE_NAME;
				}	
			}
		}
		
	}


	/**
	 * @param string $item
	 */
	private function candidateToproceed($item): bool{
		global $conf;

		return !empty($conf->{basename($item)}->enabled) && ($this->FileExistsWithName($item, $this::HELPER_SPECS_FILE_NAME ) || $this->FileExistsWithName($item, $this::HELPER_DOCS_FILE_NAME)) ;
	}
	/**
	 * Charge les fichiers .md  pour chaque object enfant dans Tconfig si ils existent
	 */
	private function loadResourcesFromClass(): DocSpecObjectManager{
		if (is_array($this->TConfig)){
			foreach ($this->TConfig as $key => $wrappers) {		
				foreach($wrappers as $keywrapper => $wrapper){
					/** @todo voir pour ne pas avoir à mettre $object */
					if ($keywrapper == 'object'){
						$this->TConfig[$key]['specs']=  $wrapper->loadResourcesFile($wrapper::RESOURCE_TYPE_SPECS, $this->currentContext);	
						/** @todo  on peut faire la même avec une doc */
						//$this->TConfig[$key]['docs']=  $wrapper->loadResourcesFile($wrapper::RESOURCE_TYPE_DOCS, $this->currentContext);	
					}		
				}
			}
		}
		return $this;
	}
	
	/**
	 * getter
	 */
	public function getUrlToGenerator() : string {
		global $conf;

		return  DOL_MAIN_URL_ROOT . $conf->file->dol_url_root['alt0']  . "/" . "specanddoc" . '/core_gen_web.php';
	}

	/**
	 * @todo à modifier ...  si on cherche le fichier sans une partie suffixe  un is_file suffit
	 * @param string $folder
	 * @param string $$fileName
	 */
	public function FileExistsWithName($folder, $fileName) : string|bool {

		$files ="";

		if(is_dir($folder)) {
			// Obtient la liste des fichiers dans le répertoire
			$files = scandir($folder);
		}
		
		
		// Expression régulière pour vérifier le nomdufichier
		$regex = "/{$fileName}$/";
		
		if (is_array($files)){
			// Parcours des fichiers pour vérifier 
			foreach ($files as $file) {
				// Vérifie si le fichier correspond à l'expression régulière
				if (preg_match($regex, $file)) {
					return $file; // Le fichier existe
				}
			}
		}
		// Le fichier avec le suffixe n'existe pas
		return false;
	}

	/**
	 * @param string $currentcontext
	 * @return array
	 */
	public function getModuleNameInteractingWithContext($currentContext) : array  {

		$Tname = [];

		foreach ($this->TConfig as $key => $wrappers) {		
			foreach($wrappers as $keywrapper => $wrapper){
				/** @todo voir pour ne pas avoir à mettre $object */
				if ($keywrapper == 'object'){
					$Tname[] = $key;
				}
			}
		}
		
			return $this->formatNames($Tname);
	}
	/**
	 * @param array $Tname
	 */
	public function  formatNames($Tnames) : array{
			foreach ($Tnames as $key => $value) {
				$Tnames[$key] = '<strong>'.$value.'</strong>';
			}
			return $Tnames;
	}
}