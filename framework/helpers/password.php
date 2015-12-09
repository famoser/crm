<?php
/**
 * Created by PhpStorm.
 * User: Florian Moser
 * Date: 09.12.2015
 * Time: 18:25
 */
function CheckPassword($passwd)
{
    if (strlen($passwd) > 20) {
        DoLog("Passwort zu lang (maximal 20 Zeichen)", LOG_LEVEL_USER_ERROR);
        return false;
    }

    if (strlen($passwd) < 8) {
        DoLog("Passwort zu kurz (mindestens 8 Zeichen)", LOG_LEVEL_USER_ERROR);
        return false;
    }

    if (!preg_match("#[0-9]+#", $passwd)) {
        DoLog("Passwort muss mindestens eine Zahl enthalten", LOG_LEVEL_USER_ERROR);
        return false;
    }

    if (!preg_match("#[a-z]+#", $passwd)) {
        DoLog("Passwort muss mindestens ein Buchstabe enthalten", LOG_LEVEL_USER_ERROR);
        return false;
    }

    return true;
}