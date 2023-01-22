<?php

namespace app\models\core\exceptions;

class UnknownRowStatusModeException extends \yii\web\HttpException {
    /**
     * Constructor.
     * 
     * @param string|null $message error message
     * @param int $code error code
     * @param \Throwable|null $previous The previous exception used for the exception chaining.
     */
    public function __construct($code = 0, $previous = null) {
        parent::__construct(400, 'Некорректный режим изменения статуса кортежа в БД', $code, $previous);
    }
}