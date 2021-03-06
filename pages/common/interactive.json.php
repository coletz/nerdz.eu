<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';

use NERDZ\Core\User;
use NERDZ\Core\Search;

$search = new Search();

if (!isset($searchMethod) || !method_exists($search, $searchMethod)) {
    die(NERDZ\Core\Utils::JSONResponse('error', 'No-sense error'));
}

$user = new User();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('LOGIN')));
}

$count = isset($_GET['count']) && is_numeric($_GET['count']) ? (int) $_GET['count'] : 10;
$q = isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : '';
if ($q === '') {
    die(NERDZ\Core\Utils::JSONResponse('error', 'Invalid search'));
}

die(NERDZ\Core\Utils::JSONResponse($search->$searchMethod($q, $count)));
