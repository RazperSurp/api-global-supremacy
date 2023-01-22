<?php

namespace app\models\core\exceptions;

use app\models\core\ActiveRecord;

class ValidationFailedException extends \yii\web\HttpException {
    /**
     * Constructor.
     * 
     * @param string|null $message error message
     * @param int $code error code
     * @param \Throwable|null $previous The previous exception used for the exception chaining.
     */
    public function __construct(array|string $errors, bool $isPlainText = true) {
        if ($isPlainText) $errors = ActiveRecord::parseValidationErrors($errors);
        parent::__construct($errors);
    }
}
