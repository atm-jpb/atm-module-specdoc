<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    specanddoc/class/actions_specanddoc.class.php
 * \ingroup specanddoc
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

/**
 * Class ActionsSpecAndDoc
 */
class ActionsSpecAndDoc extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

  //toprightmenu

	public function llxheader(&$parameters, $object, $action){

		global $conf, $user, $langs;


		if ($user->hasRight('specanddoc', 'docspecobjectmanager', 'readSpec')){

			require_once (__DIR__ . '/docspecobjectmanager.class.php');
			$dsm = new DocSpecObjectManager($this->db);
			$dsm->setCurrentontextFromUri();
			$dsm->init();
			if ($dsm->isUrlEditable()) {
				$parameters['help_url'] = $dsm->getUrlToGenerator() . '?context=' . $dsm->getCurrentContext() . '&originhelppage=' . $parameters['help_url'];
			}
		}

		return 0;
	}

	public function printCommonFooter($parameters, $object, $action){
		global $user;

		// changement dynamique de la popin d'aide pour les liens
		if ($user->hasRight('specanddoc', 'docspecobjectmanager', 'readSpec')){
			require_once (__DIR__ . '/docspecobjectmanager.class.php');
			$dsm = new DocSpecObjectManager($this->db);
			$dsm->setCurrentontextFromUri();
			$dsm->init();


			 $names = $dsm->getModuleNameInteractingWithContext();
			 $msg = is_array($names)  && count($names) > 0   ? " <strong>Aide en ligne ATM</strong> :  Documentations et fichiers de spécifications disponibles<br>" . "Les modules suivants : ". implode(", ",$names) . " intéragissent avec le contexte en cours" : "";


			if (!empty($msg) )  {
				?>
			<script>
				$( document ).ready(function() {
					$(".helppresent").parent().parent().prop("title", '<?php echo $msg ?>')
				});
			</script>
			<?php
			}
		}
	}
}
