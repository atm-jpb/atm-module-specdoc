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
 * \file    lib/specanddoc_docspecobjectmanager.lib.php
 * \ingroup specanddoc
 * \brief   Library files with common functions for DocSpecObjectManager
 */

/**
 * Prepare array of tabs for DocSpecObjectManager
 *
 * @param	DocSpecObjectManager	$object		DocSpecObjectManager
 * @return 	array					Array of tabs
 */
function docspecobjectmanagerPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("specanddoc@specanddoc");

	$showtabofpagecontact = 0;
	$showtabofpagenote = 0;
	$showtabofpagedocument = 0;
	$showtabofpageagenda = 0;

	$h = 0;
	$head = array();



	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@specanddoc:/specanddoc/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@specanddoc:/specanddoc/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'docspecobjectmanager@specanddoc');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'docspecobjectmanager@specanddoc', 'remove');

	return $head;
}
