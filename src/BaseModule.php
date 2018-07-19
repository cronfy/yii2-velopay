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

    public function getControllerPath()
    {
        // Переопределяем, как мы будем искать контроллеры модуля, так как родной
        // метод Yii не подходит, вот почему:
        //
        // Yii определяет путь к контроллеру через алиас по controllerNamespace.
        // То есть, есть модуль cronfy/somemd, в нем есть
        // приложение console (вложенный модуль cronfy\somemd\console\Module).
        // В console есть контроллер cronfy\somemd\console\controllers\InitController.
        // Yii, при поиске контроллера для cronfy\somemd\console\Module, возьмет controllerNamespace
        // - это будет cronfy\somemd\console\Module\controllers - и приставит к нему собачку,
        // будто это алиас: @cronfy\somemd\console\Module\controllers.
        // И в том месте, куда отрезолвится алиас, будет искать классы контроллера.
        //
        // Алиас создавать не хочется, так как это лишняя сущность, которая может
        // конфликтовать с другими алиасами (мы - модуль и не знаем, какие алиасы
        // уже используются в приложении). Поэтому определим путь к контроллерам
        // своим способом.

        // ищем контроллер в папке controllers/ относительно класса Module
        $rc = new \ReflectionClass(get_class($this));
        return dirname($rc->getFileName()) . '/controllers';
    }

}