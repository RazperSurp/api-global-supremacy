<?php

namespace app\models\core;

/**
 * Кастомное ядро контроллера для реализации REST API. 
 * 
 * Предназначается для:
 * 
 * 1. Наследования всех REST-контроллеров от этого кастомного ядра. 
 * 2. Переопределения настроек формата, в котором пользователю возвращается ответ с сервера (см. `$this->behaivors()`).
 */
class ActiveController extends \yii\rest\ActiveController {
    /**
     * Настройка формата, в котором возвращается ответ с API.
     *
     * @return void
     */
    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['text/json'] = \yii\web\Response::FORMAT_JSON;
    }
}