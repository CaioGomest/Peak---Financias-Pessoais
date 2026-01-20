<?php
if (!defined('FUSO_HORARIO_PADRAO'))
    define('FUSO_HORARIO_PADRAO', 'America/Sao_Paulo');

date_default_timezone_set(FUSO_HORARIO_PADRAO);
@ini_set('date.timezone', FUSO_HORARIO_PADRAO);
?>