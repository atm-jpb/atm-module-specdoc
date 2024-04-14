<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       specanddoc/specanddocindex.php
 *	\ingroup    specanddoc
 *	\brief      Home page of specanddoc top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once (__DIR__ . '/class/docspecobjectmanager.class.php');
$hookmanager->initHooks('specdocgenerator');
$langs->loadLangs(array("specanddoc@specanddoc"));


$action = GETPOST('action', 'aZ09');
$context = GETPOST('context','alpha');
$originHelpPage = GETPOST('originhelppage','alpha');
$now = dol_now();


$docSpecGenerator  = new DocSpecObjectManager($db);

$langContext = $docSpecGenerator->setCurrentContext($context)
                                ->getLangsContext();

var_dump($langContext);





/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

// on cache temporairement le menu gauche pour cette page  (pas de dol_setconst)
$conf->dol_hide_leftmenu = 1;


llxHeader("", $langs->trans("SpecAndDocArea") , $originHelpPage, '', 0, 0, '', '', '', 'mod-specanddoc page-index');

print load_fiche_titre($langs->trans("SpecAndDocArea"  ), '', 'specanddoc.png@specanddoc');
print load_fiche_titre($langContext, '', 'specanddoc.png@specanddoc');
?>
<div class="fichecenter"><div class="fichethirdleft">
    <?php
    
        echo 'table des matières';
    ?>
    </div>

    <div class="fichetwothirdright">
        <?php
            var_dump($context);
            // on passe en revue tous les dossier dans custom.

            $directory = '..'; // Replace with the actual directory path
            // Extracts files and directories that match a pattern
            $items = glob($directory . '/*');
            foreach ($items as $item) {
                if (is_file($item)) {
                   // echo "File: {$item}\n";
                }
                if (is_dir($item)) {
                    //$itemsClass = glob($item . '/class/');    
                    //var_dump('test : ' . $item);
                    //var_dump('test : ' . $item);
                   print '<pre>' . var_export($item . '/class/',true) . '</pre>' ;
                   $rep = $item . '/class/';
                  
                     if ($docSpecGenerator->fichierExisteAvecSuffixe($rep, $suffixe )){
                           echo 'oui dans : ' .  $item . '/class/';
                     }else{
                        echo 'non dans : ' .  $item . '/class/';
                     }       
                    //echo "Directory1: {$item}\n";
                }
            }
           
           
        ?>
    </div>
</div>

<?php
// End of page
llxFooter();
$db->close();