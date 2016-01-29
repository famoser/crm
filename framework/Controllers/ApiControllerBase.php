<?php
/**
 * Created by PhpStorm.
 * User: Florian Moser
 * Date: 29.01.2016
 * Time: 13:09
 */

namespace famoser\WAdminCrm\Framework\Controllers;


use famoser\WAdminCrm\Framework\Views\RawView;

class ApiControllerBase extends ControllerBase
{
    public function Display()
    {
        if ($this->params[0] == "log") {
            if (isset($request) && isset($request["message"]) && isset($request["loglevel"]))
                DoLog($request["message"], $request["loglevel"]);
            $this->setView(new RawView("/Framework/Templates/Parts/message_template.php"));
        }

        return $this->evaluateView();
    }

}