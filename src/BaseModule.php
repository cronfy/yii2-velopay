<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 23.10.17
 * Time: 18:34
 */

namespace cronfy\yii2Velopay;

use cronfy\yii2Velopay\common\misc\BusinessLogic;
use yii\base\Module;

/**
 * @property BusinessLogic $businessLogic
 */
class BaseModule extends Module {

    public $businessLogicClass;

    protected $_businessLogic;
    public function getBusinessLogic() {
        if ($this->_businessLogic === null) {
            $this->_businessLogic = new $this->businessLogicClass;
        }

        return $this->_businessLogic;
    }
}