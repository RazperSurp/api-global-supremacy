<?php

namespace app\controllers;

use Yii;
use yii\filters\{AccessControl, VerbFilter};
use yii\web\{Controller, Response, HttpException};

use thiagoalessio\TesseractOCR\TesseractOCR;

use app\models\core\{Service};


/**
 * # ServiceController
 * 
 * Контроллер, отвечающий за администрирование системы.
 */
class ServiceController extends Controller {
    /**
     * Зеркало для вызова приватного метода актуализации проекта
     *
     * @param   ?string $mode  Режим актуализации (см. `ServiceController::ACTUALIZATION_MODES`)
     * @return  array   Результаты выполнения shell-инструкций
     */
    public function actionActualize(?string $mode = NULL) {
        return Service::actualize($mode);
    }
    /**
     * Инициализация ключа от базы данных
     * 
     * @param   string  $param  Запрашиваемый параметр
     * @return  mixed
     */
    public function actionGetParam(string $param) {
        return Service::get($param);
    }
}