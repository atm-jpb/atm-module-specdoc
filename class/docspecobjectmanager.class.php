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
	const HELPER_CLASS_FILE_NAME ='helper.class.php' ;
	const SUFFIXE = '_helper';
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

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('specanddoc', 'docspecobjectmanager', 'read')) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}


		$this->setGlobalContext();


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
	 * charge en memoire TConfig les classes helper depuis les modules actifs dans custom qui ont la class enfant déclaréé dans le dossier class
	 */
	private function  loadClassFromActiveCustomModule(){

		global $conf;
		
		$directory = '..'; // Replace with the actual directory path
		// Extracts files and directories that match a pattern
		$items = glob($directory . $this::ALL_ELEMENTS);
		foreach ($items as $item) {	
			if (is_dir($item)) {
			   $rep = $item . $this::CLASS_PATH_FOLDER;
				 if ($this->FileExistsWithSuffix($rep, $this::HELPER_CLASS_FILE_NAME )){
					   if ( !empty($conf->{basename($item)}->enabled)){
						$this->TConfig[basename($item)] = $this::HELPER_CLASS_FILE_NAME;
					   }   
				 }    	
			}
		}
		// affectation des classes candidates dans l'array de config de l'objet courant
		foreach ($this->TConfig as $key => $value) {
			dol_include_once('/'.$key. $this::CLASS_PATH_FOLDER . $this::HELPER_CLASS_FILE_NAME);
			$className = $key . $this::SUFFIXE;

			/**  @todo on doit check si le context courant est déclaré dans l'enfant  pour loader la class */
			$this->TConfig[$key] = array();
			$this->TConfig[$key][] = new  $className($this->db);
			$this->TConfig[$key][] = 'test';
		}		
	}

	/**
	 * Charge les fichiers .md  pour chaque object enfant dans Tconfig si ils existent
	 */
	private function loadResourcesFromClass(){

		if (is_array($this->TConfig)){
			
			foreach ($this->TConfig as $key => $wrapper) {	
				$TConfig[$key]['specs'] = $wrapper[$this::CLASS_OBJECT_INDEX]->loadResources($wrapper[$this::CLASS_OBJECT_INDEX]::RESOURCE_TYPE_SPECS);
				$TConfig[$key]['docs'] =  $wrapper[$this::CLASS_OBJECT_INDEX]->loadResources($wrapper[$this::CLASS_OBJECT_INDEX]::RESOURCE_TYPE_DOCS);
			}
		}

		return $this;
	}
	/**
	 * @todo la classe enfant doit implementer celle-ci
	 */
	private function setGlobalContext(){

		$this->TGlobalContextManaged["projectsindex"] = "projectsindex";
		$this->TGlobalContextManaged["projectlist"] = "projectlist";
		// PROPAL
		$this->TGlobalContextManaged["propalcard"] = "propalcard";
		

	}
	/**
	 * getter
	 */
	public function getUrlToGenerator(){
		global $conf;

		return  DOL_MAIN_URL_ROOT . $conf->file->dol_url_root['alt0']  . "/" . "specanddoc" . '/core_gen_web.php';
	}

	/**
	 * 
	 */
	public function FileExistsWithSuffix($folder, $suffixe) {

		$files ="";

		if(is_dir($folder)) {
			// Obtient la liste des fichiers dans le répertoire
			$files = scandir($folder);
		}
		
		
		// Expression régulière pour vérifier le suffixe
		$regex = "/{$suffixe}$/";
		
		if (is_array($files)){
			// Parcours des fichiers pour vérifier le suffixe
			foreach ($files as $file) {
				// Vérifie si le fichier correspond à l'expression régulière
				if (preg_match($regex, $file)) {
					return $file; // Le fichier avec le suffixe existe
				}
			}
		}
		
		
		// Le fichier avec le suffixe n'existe pas
		return false;
	}


	
}