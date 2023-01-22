<?php

namespace app\models\core;

use Yii;
use yii\web\{HttpException};
use yii\helpers\{Json};

/**
 * Класс, предназначенный для обработки и хранения специальных параметров приложения.
 * 
 * # Примечания
 * 
 * В теле предусмотрено множество приватных методов. Для корректной работы приложения не стоит менять модификатор
 * доступа и обращаться к ним напрямую, даже при условии, что они имеют модификатор `static`. Точно так же не стоит
 * обращаться к этим методам где-то ещё внутри тела этого класса кроме тех мест, где они уже вызываются.
 * 
 * Не стоит добавлять новые параметры через стандартный инструментарий моделей-наследников ActiveRecord,
 * так как это чревато нарушениями работы приложения.
 * 
 * @var ACTIVE_RECORD_CORE            Полное название класса, который является кастомным ядром ActiveRecord
 * @var __ACTUALIZATION_EXCEPTIONS    Список игнорируемых таблиц при актуализации
 * @var __ACTUALIZATION_MODES         Режимы актуализации проекта (обновление моделей/контроллеров)
 * @var __ACTUALIZATION_TEMPLATES     Название шаблона, используемого для акутализаци моделей
 * @var __DATA_TYPES                  Параметр, указывающий на то, какой тип данных хранится в поле `[[value]]`
 * 
 * @author  Dmitry Shianov <dshiyanov01@gmail.com>
 */
class Service extends \app\models\core\ActiveRecord {
    /**
     * Полное название класса, который является кастомным ядром обработки результатов запросов в базу данных
     */
    public const ACTIVE_RECORD_CORE = 'app\models\core\ActiveRecord';
    /**
     * Список игнорируемых таблиц при выполнении процедуры актуализации проекта.
     * 
     * 1. При актуализации моделей:
     * - **Migration**
     * 
     * 2. При актуализации контроллеров:
     * - **Service**
     * - **Restore**
     * - **Archive**
     * - **Migration**
     * 
     * # Примечание
     * 
     * При указании списка исключений для **МОДЕЛЕЙ** необходимо указывать названия таблиц согласно миграциям,
     * например `table_name`. Однако, если укзаывается список исключений для **КОНТРОЛЛЕРОВ**, то указывать нужно
     * название **КЛАССОВ** (моделей), например `TableName`.
     */
    private const __ACTUALIZATION_EXCEPTIONS = [
        'model' => [ 'migration' ],
        'controller' => [ 'Service', 'Restore', 'ArchiveSettings', 'Migration' ]
    ];
    /**
     * Список режимов актуализации проекта. Используются при обновлении каких-либо динамических компонент проекта.
     * 
     * - **MODE_MODEL** - обновляются DB-модели (`app/models/db`)
     * - **MODE_CONTROLLER** - обновляются REST-контроллеры (`app/controllers`)
     */
    private const __ACTUALIZATION_MODES = [
        'MODE_MIGRATE' => 'migrate',
        'MODE_MODEL' => 'model',
        'MODE_CONTROLLER' => 'controller'
    ];
    /**
     * Список шаблонов, используемых при генерации динамических компонент при помощи Gii.   
     */
    private const __ACTUALIZATION_TEMPLATES = [
        'model' => 'actual',
        'controller' => 'actual'
    ];
    /**
     * Перечень параметров, хранение которых допустимо в таблице `[[public.service]]`.
     * 
     * - **KEY_CHECK**  - Сообщение, зашифрованное ключом при его инициализации и используемое в качестве эталона
     * - **GH_LOGIN**   - Логин учётной записи в GitHub
     * - **GH_TOKEN**   - Токен, использумый для взаимодействия с репозиторием
     * - **DK_H**       - Хэшированный пароль
     * - **DK_W**       - Бинарный вес пароля от базы данных
     * - **DK_L**       - Длина пароля
     * - **CK_C**       - Количество частей, на которые делится пароль
     */
    private const __ACCEPTABLE_PARAMS = [ ];
    /**
     * Допустимые типы данных для значений в таблице `[[public.service]]`
     * 
     * Необходимо для корректного определения и получения значения запрашиваемого параметра.
     * 
     * - **TYPE_STRING**    - Строчный тип
     * - **TYPE_ARRAY**     - Массив
     * - **TYPE_JSON**      - JSON-строка
     */
    private const __DATA_TYPES = [
        'TYPE_STRING' => 'STR',
        'TYPE_ARRAY' => 'ARR', 
        'TYPE_JSON' => 'JSON'
    ];
    /**
     * Добавление нового параметра в таблицу.
     * 
     * В качестве первого аргумента можно указать массив названий параметров. В таком случае, если второй параметр `$value`
     * является строкой, то это значение будет присвоено всем добавляемым параметрам. Если же `$value` является массивом, то
     * значения будут присовены согласно установлению недостоверного соответствия между элементами массивов по ключу (т.е.
     * `array($request[0] => $value[0], $request[1] => $value[1], ...)` и так далее) с помощью `array_combine($request, $value)`.
     * 
     * # Примечания
     * 
     * В случае, если аргумент `$request` не является массивом, а `$value` - является, то в качестве значения добавляемого
     * параметра используется первый элемент массива значений (`$value[0]`).
     * 
     * Длина массива `$value` "подгоняется" под длину массива `$request`. Из этого следует:
     * 1. Если длина массива значений меньше, чем длина массива параметров, то недостающие элементы заполняются **последним**
     * значением массива (`array_pad($value, count($request), $value[count($value) - 1])`);
     * 2. Если длина массива значений больше, чем длина массива параметров, то переменной присваивается субмассив от хранимого
     * массива, длина которого равна длине массива параметров (`$value = array_slice($value, 0, count($request))`)
     *
     * @param   string|array    $request    Добавляемый параметр (параметры)
     * @param   string|array    $value      Значение параметра (парамтеров)
     * @param   string          $type       Тип данных
     * @param   bool            $isReadonly Флаг, отвечающий за указание на то, доступно ли значение параметра для изменения
     * @param   bool            $isSilent   Флаг, отвечающий за игнорирование ошибок
     * @param   ?string         $dk         Ключ шифрования
     * @return  void
     * 
     * @throws  HttpException 400, если `$request` или `$value` являются пустыми массивами или пустыми строками
     * @throws  HttpException 400, если указан недопустимый параметр
     * @throws  HttpException 403, если ключ неверный
     */
    public static function add(mixed $request, mixed $value, string $type, bool $isReadonly = true, bool $isSilent = false, ?string $dk = null) {
        if (!$request || !$value) throw new HttpException(400);

        if (!is_array($request) && is_array($value)) {
            $value = $value[0];

            self::__setParam($request, $value, $type, $isReadonly, $isSilent, $dk);
        } else {
            if (!is_array($value))
                $value = (array)$value;

            $requestLength = count($request);
            $valuesLength = count($value);
            if ($requestLength > $valuesLength)
                $value = array_pad($value, $requestLength, $value[$valuesLength - 1]);
            else if ($requestLength < $valuesLength)
                $value = array_slice($value, 0, $requestLength);

            foreach (array_combine($request, $value) as $param => $val)
                self::__setParam($param, $val, $type, $isReadonly, $isSilent, $dk);
        }
    }
    /**
     * Изменение существующего параметра в таблице
     *
     * @param   string  $param  Название изменяемого параметра
     * @param   string  $value  Новое значение параметра
     * @param   string  $type   Тип данных, в котором хранится значение параметра
     * @param   ?string $dk     Ключ шифрования
     * @return  void
     * 
     * @throws  HttpException 400, если указан недопустимый параметр
     * @throws  HttpException 404, если кортеж не найден
     * @throws  HttpException 400, если параметр доступен только для чтения
     * @throws  HttpException 403, если ключ неверный
     */
    public static function changeValue(string $param, string $value, string $type, ?string $dk = null) {
        if (in_array($param, self::__ACCEPTABLE_PARAMS) && in_array($type, self::__DATA_TYPES) && $param != self::__KEY_CHECKER_PARAM) {
            $value = self::__parseValue($value, $type, false);
            $property = self::__getTargetField($param);
            
            $model = self::findOne(['name' => $param]);
            if ($model) {
                if (!$model->is_readonly) {
                    $model->$property = $value;

                    $model->save();
                    if (in_array($param, self::__ENCRYPTABLE_PARAMS))
                        self::__dbEncrypt($param, $value);
                } else throw new HttpException(400, 'Parameter is readonly: '. $param);
            } else throw new HttpException(404, 'Parameter is not defined: '. $param);
        } else throw new HttpException(400, 'Configuration is not acceptable: '. $param .', '. $type);
    }
    /**
     * Необратимое удаление существующего параметра из таблицы
     *
     * @param   string  $param    Название параметра
     * @return  void
     * 
     * @throws  HttpException 400, если указан недопустимый параметр
     * @throws  HttpException 404, если кортеж не найден
     */
    public static function drop(string $param) {
        if (in_array($param, self::__ACCEPTABLE_PARAMS)) {
            $model = self::findOne(['name' => $param]);

            if ($model) {
                $model->delete(true);
            } else throw new HttpException(404, 'Parameter is not defined: '. $param);
        } else throw new HttpException(400, 'Parameter is not acceptable: '. $param);
    }
    /**
     * Получение значения существующего параметра в таблице
     *
     * @param   string|array    $request        Название запрашиваемого свойства (свойств).
     * @param   bool            $isAnonimous    Флаг, отвечающий за режим получения данных:
     *  анонимно (возвращается обычный массив) или нет (возвращается ассоциативный массив,
     *  где ключом является название запрашиваемого параметра). **Аргумент не имеет смысла,
     *  если аргумент `$request` не является массивом.** 
     * @param   bool            $isSilent       Флаг, отвечающий за игнорирование ошибок.
     * @return  string|array
     * 
     * @throws  HttpException 400, если указан недопустимый параметр
     * @throws  HttpException 404, если кортеж не найден
     * @throws  HttpException 403, если ключ неверный
     * @throws  HttpException 404, если ключ не был инициализирован
     */
    public static function get(mixed $request, bool $isAnonimous = false, bool $isSilent = false) {
        if (is_array($request)) {
            foreach ($request as $key => $param)
                $results[$isAnonimous ? $key : $param] = self::__getParam($param, $isSilent);
            
            return $results;
        } else return self::__getParam($request, $isSilent);
    }
    /**
     * Актулизация динамических компонент проекта через web-оболочку
     *
     * @param   ?string $mode  Режим актуализации
     * @return  array
     * 
     * @throws  HttpException   400, если передан неизвестный режим актуализации
     */
    public static function actualize(?string $mode) {
        switch ($mode) {
            case NULL:
                $cmdStack = array_merge(self::__actualizeMigrations(), self::__actualizeModels(), self::__actualizeControllers());
                break;
            case self::__ACTUALIZATION_MODES['MODE_MIGRATE']:
                $cmdStack = self::__actualizeMigrations();
                break;
            case self::__ACTUALIZATION_MODES['MODE_MODEL']:
                $cmdStack = self::__actualizeModels();
                break;
            case self::__ACTUALIZATION_MODES['MODE_CONTROLLER']:
                $cmdStack = self::__actualizeControllers();
                break;
            default:
                throw new \yii\web\HttpException(400, 'Passed unknown actualization mode: '. $mode);
        }
        foreach ($cmdStack as $name => $cmd)
            $results[$name] = Toolbox::execute_shell($cmd);

        Toolbox::deleteDir(Yii::getAlias('@app') .'\views');

        return $results;
    }
    /**
     * Актуализация определённых компонент проекта
     *
     * @return array   Выполняемые команды
     */
    private static function __actualizeMigrations() {
        return ['migrate' => 'migrate --interactive="0"'];
    }
    /**
     * Актуализация определённых компонент проекта
     *
     * @return string   Выполняемые команды
     */
    private static function __actualizeModels() {
        $cmdStack = [];

        foreach (Toolbox::tablesList() as $table) {
            if (!in_array($table, self::__ACTUALIZATION_EXCEPTIONS['model'])) {
                $cmdStack['gii/model '. $table] = 'gii/model --tableName="'. $table .'" --overwrite="1" --ns="app\models\db" --baseClass="'. self::ACTIVE_RECORD_CORE .'" --interactive="0" --template="'. self::__ACTUALIZATION_TEMPLATES['model'] .'"';
            }
        }
        return $cmdStack;
    }
    /**
     * Актуализация определённых компонент проекта
     *
     * @return string   Выполняемые команды
     */
    private static function __actualizeControllers() {
        foreach (Toolbox::tablesList(true) as $table) {
            if (!in_array($table, self::__ACTUALIZATION_EXCEPTIONS['controller'])) {
                $cmdStack['gii/controller '. $table] = 'gii/controller --controllerClass="app\controllers\\'. $table .'Controller" --overwrite="1" --interactive="0" --template="'. self::__ACTUALIZATION_TEMPLATES['controller'] .'"';
            }
        }
        return $cmdStack;
    }
    /**
     * Преобразование значения параметра в PHP-friendly аналогичный тип данных согласно указанному в кортеже
     *
     * @param   mixed   $value      Преобразуемое значение
     * @param   string  $type       Тип данных в кортеже
     * @param   bool    $isGetting  Флаг, отвечающий за режим преобразования - в User-friendly или в стандартный вид
     * @return  mixed|string    Результат преобразования
     * 
     * @throws  HttpException 400, Если переданный в качестве аргумента тип данных не достоверен
     * @throws  HttpException 500, если в кортеже указан неизвестный тип данных
     * @throws  HttpException 500, если не удалось распарсить данные
     */
    private static function __parseValue(mixed $value, string $type, bool $isGetting = true) {
        if ($isGetting) {
            switch ($type) {
                case self::__DATA_TYPES['TYPE_STRING']:
                    break;
                case self::__DATA_TYPES['TYPE_ARRAY']:
                    preg_replace("/\['|'\]/", "", $value);
                    $value = explode(',', $value);

                    break;
                case self::__DATA_TYPES['TYPE_JSON']:
                    try { $value = Json::decode($value); }
                    catch (\yii\base\InvalidArgumentException $e) {
                        throw new HttpException(500, 'Unable to parse stored data', $e);
                    }
                    break;
                default:
                    throw new HttpException(500, 'Got unknown data type while service data: '. $type);
            }
        } else {            
            switch ($type) {
                case self::__DATA_TYPES['TYPE_STRING']:
                    break;
                case self::__DATA_TYPES['TYPE_ARRAY']:
                    preg_replace("/'/", "\"", $value);

                    if (is_array($value)) {
                        $value = "['". implode("','", $value) ."']";
                    } else throw new HttpException(400, 'Incorrect data type: '. $type . ', got '. gettype($value));
                    break;
                case self::__DATA_TYPES['TYPE_JSON']:
                    try {
                        $value = Json::encode($value);
                    } catch (\yii\base\InvalidArgumentException $e) {
                        throw new HttpException(400, 'Incorrect data type: '. $type . ', got '. gettype($value), 0, $e);
                    }
                    break;
                default:
                    throw new HttpException(500, 'Got unknown data type while service data: ', $type);
            }
        }
        return $value;
    }
    /**
     * Добавление параметра в таблицу `[[public.service]]`.
     *
     * @param   string|array    $request    Добавляемый параметр (параметры)
     * @param   string|array    $value      Значение параметра (парамтеров)
     * @param   string          $type       Тип данных
     * @param   bool            $isReadonly Флаг, отвечающий за указание на то, доступно ли значение параметра для изменения
     * @param   bool            $isSilent   Флаг, отвечающий за игнорирование ошибок
     * @param   ?string         $dk         Ключ шифрования
     * @return  void
     * 
     * @throws  HttpException 400, если указан недопустимый параметр
     * @throws  HttpException 403, если ключ неверный
     */
    private static function __addParam(mixed $request, mixed $value, string $type, bool $isReadonly, bool $isSilent, ?string $dk) {
        if (in_array($param, self::__ACCEPTABLE_PARAMS) && in_array($type, self::__DATA_TYPES)) {
            $value = self::__parseValue($value, $type, false);
            $property = self::__getTargetField($param);

            $model = self::findOne(['name' => $param]);
            if (!$model) {
                $model = new self();
                $model->name = $param;
                $model->$property = $value;
                $model->type = $type;
                $model->is_readonly = $isReadonly;

                $model->save();
                if (in_array($param, self::__ENCRYPTABLE_PARAMS))
                    $value = self::__dbEncrypt($param, $value, $dk);
            } else if (!$model->is_readonly) {
                $model->$property = $value;

                $model->save();
                if (in_array($param, self::__ENCRYPTABLE_PARAMS))
                    $value = self::__dbEncrypt($param, $value, $dk);
            } else if (!$isSilent) throw new HttpException('400', 'Parameter is already defined: '. $param);
        } else throw new HttpException(400, 'Configuration is not acceptable: '. $param .', '. $type);
    }
    /**
     * Получение значение параметра из таблицы `[[public.service]]`.
     * 
     * @param   string  $param      Запрашиваемый параметр
     * @param   bool    $isSilent   Флаг, отвечающий за игнорирование ошибок.
     * @return  string
     * 
     * @throws  HttpException 400, если указан параметр, который отсутствует в `self::__ACCEPTABLE_PARAMS`
     * @throws  HttpException 404, если параметр не описан в таблице
     */
    private static function __getParam(string $param, bool $isSilent) {
        if (in_array($param, self::__ACCEPTABLE_PARAMS)) {
            $model = self::findOne(['name' => $param]);
            $property = self::__getTargetField($param);
            if ($model) {
                $value = $model->$property;
                if (in_array($param, self::__ENCRYPTABLE_PARAMS))
                    $value = self::__dbDecrypt($param);

                return self::__parseValue($value, $model->type);
            } else if (!$isSilent) throw new HttpException(404, 'Parameter is not defined: '. $param);
        } else if (!$isSilent) throw new HttpException(400, 'Parameter is not acceptable: '. $param);
    }
}