<?php

namespace app\models\core;

use Yii;
use yii\web\HttpException;

/**
 * Кастомное ядро обработчика результатов запросов в базу данных.
 * 
 * Предназначается для:
 * 
 * 1. Наследования всех db-моделей (`app\models\db`) от данного класса.
 * 2. Переопределения алгоритмов, предназначенных для работы результатами запросов в базу данных, таких как
 * `$this->update()`, `$this->delete()` и пр.
 */
class ActiveRecord extends \yii\db\ActiveRecord {
    /** Режим удаления кортежа */
    const MODE_DELETING = 2;

    /**
     * Сохранение изменений модели.
     * 
     * В методе предусмотрено автоматическое изменение времени последенего обновления модели.
     *
     * @return void
     */
    public function save($runValidation = true, $attributeNames = null) {
        $this->updated_at = time();

        return parent::save($runValidation, $attributeNames);
    }

    /**
     * Удаление записи из базы данных.
     * 
     * Алгоритм поддерживает два режима - обратимое и необратимое удаление. В случае, если 
     *
     * @param boolean $isIrreversible
     * @return void
     */
    public function delete(bool $isIrreversible = false) {
        if ($isIrreversible) return parent::delete();
        else return $this->setDeleted();
    }

    /**
     * Функция-обработчик ошибок валидации.
     * 
     * Метод предназначен для обработки полученных ошибок и преобразования их в human-readable вид.
     *
     * @param   array   $errors Ошибки валидации
     * @param   ?string $title  Заголовок для текста c ошибками
     * @return  void
     */
    public static function parseValidationErrors(array $errors = [], ?string $title = null) {
        $errorsList = [];
        if (isset($errors) && is_array($errors)) {
            foreach ($errors as $error)
                foreach ($error as $errorText)
                    $errorsList[] = $errorText;

            return $title ? $title . '<br>' . implode(', <br>', $errorsList) : implode('<br>', $errorsList);
        } else return $title;
    }
    
    /**
     * getFormName
     * 
     * Получение название формы текущего класса
     */
    protected static function getFormName() {
        return substr(self::className(), strripos(self::className(), "\\") + 1);
    }


    /**
     * Перемещения кортежа в корзину в базе данных.
     * 
     * В результате выполнения алгоритма, `$this->deleted` принимает значение `TRUE`,
     * после чего для всех кортежей, полученных по связям типа `1:...` функция вызывается
     * рекурсивно, для обеспечения целостности данных.
     * 
     * @throws  DataNotDeletableException      Если в кортеже нет поля `[[deleted]]`
     * 
     * @return  void
     */
    protected function setDeleted(array $cascadeData = []) {
        $properties = array_keys($this->attributes);
        if (in_array('deleted', $properties) && !$this->deleted) {
            $this->changeRowStatus(self::MODE_DELETING, $cascadeData);
        } else throw new exceptions\DataNotDeletableException();
    }

    /**
     * Функция, предназначенная для каскадного изменения статуса данных в БД.
     *
     * @param integer $mode
     * @param array $cascadeData
     * @return void
     */
    protected function changeRowStatus(int $mode, array $cascadeData) {
        switch ($mode) {
            case self::MODE_DELETING:
                $statusProperty = 'deleted';
                $inArchive = false;
                break;
            default:
                throw new exceptions\UnknownRowStatusModeException();
        }

        $propertyFn = 'set'. ucfirst($statusProperty);

        $tableName = $cascadeData['table_name'] ? $cascadeData['table_name'] : $this->tableName();
        $rowId = $cascadeData['row_id'] ? $cascadeData['row_id'] : $this->id;

        $cascadeData['operation'] = $mode;
        $cascadeData['history'][] = [
            'table_name' => $this->tableName(),
            'rowId' => $this->id
        ];

        if (!Restore::checkRestorable($tableName, $rowId)) {
            Restore::add($tableName, $rowId, $inArchive);
            $cascadeData = [
                'table_name' => $tableName,
                'row_id' => $rowId
            ];
        }

        $relations = preg_filter('/^get(?!all|deleted)(.+)/i', '$1', get_class_methods($this::class));
        foreach ($relations as $getter) {
            $getter = lcfirst($getter);
            if ($this->$getter && is_array($this->$getter)) {
                foreach ($this->$getter as $jqRow)
                    $jqRow->$propertyFn($cascadeData);
            }
        }

        $this->$statusProperty = true;
        if(!$this->save()) {
            self::revertStatus($cascadeData);
            throw new exceptions\ValidationFailedException($this->errors);
        };
    }

    /**
     * Откат изменений, связанных с каскадным удалением данных.
     *
     * @param   array   $cascadeData    Информация об операции
     * 
     * @return  void
     * 
     * @static
     */
    protected static function revertStatus(array $cascadeData = []) {
        switch ($cascadeData['operation']) {
            case self::MODE_DELETING:
                $statusProperty = 'deleted';
            default:
                $statusProperty = 'deleted';
        }

        foreach ($cascadeData['history'] as $modelInfo) {
            $className = '';
            foreach (explode('_', $modelInfo['table_name']) as $piece)
                $className .= ucfirst($piece);

            $model = "\\app\\models\\db\\$className"::findOne($modelInfo['row_id']);
            if ($model) {
                $model->$statusProperty = false;
                $model->save();
            }
        }
    }
}
