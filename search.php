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

$user = new NERDZ\Core\User();
$tplcfg = $user->getTemplateCfg();
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));
$q = !empty($_GET['q']) && is_string($_GET['q']) ? htmlspecialchars(trim($_GET['q']), ENT_QUOTES, 'UTF-8') : '';
?>
    <!DOCTYPE html>
    <html lang="<?php echo $user->getBoardLanguage();?>">
    <head>
    <meta name="author" content="Paolo Galeone" />
    <title><?=$user->lang('SEARCH')?> - <?=NERDZ\Core\Utils::getSiteName(); ?> <?php
    if (!empty($q)) {
        echo '⇒ ', $q;
    }?></title>
<?php
$headers = $tplcfg->getTemplateVars('search');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/search.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
