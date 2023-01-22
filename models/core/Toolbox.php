<?php

namespace app\models\core;

use Yii;

/**
 * Класс предназначен для хранения различных кастомных функций, расширяющих и дополняющих
 * функционал PHP, Yii и так далее.
 */
class Toolbox {
    /**
     * Получение всех таблиц по шаблону миграции вида "m000000_000000_create_%table%_table.php"
     * 
     * @param   bool    $isStandartized Флаг, отвечающий за вид возвращаемых названий. Если `true`, то
     *  возвращаются значения вида `TableName`, если `false`, то `table_name`.
     * @return  array
     */
    public static function tablesList(bool $isStandartized = false) {
        $tables = [];
        foreach (preg_filter('/m[0-9]{6}_[0-9]{6}_create_(.+)_table.php/', '$1', scandir('../migrations/')) as $key => $migrationName) {
            $tables[$key] = $isStandartized ? '' : $migrationName;

            if ($isStandartized) {
                foreach(explode('_', $migrationName) as $piece)
                    $tables[$key] .= ucfirst($piece);
            }
        }
        return $tables;
    }
    /**
     * Рекурсивное удаление всех файлов из директории
     *
     * @return void
     */
    public static function deleteDir($dirPath) {
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
            $dirPath .= '/';

        foreach (glob($dirPath . '*', GLOB_MARK) as $file) {
            if (is_dir($file)) self::deleteDir($file);
            else unlink($file);
        }

        rmdir($dirPath);
    }
    /**
     * Выполнение консольных команд через web-оболочку
     *
     * @param   string  $cmd    Выполняемая команда
     * @param   boolean $yii    Флаг, отвечающий за переключение режима между выполнением консольных команд Yii и консольных команд в системе
     * @return  string
     */
    public static function execute_shell(string $cmd, bool $yii = true) {
        $cmd = !$yii ? "{$cmd}" : Yii::$app->params['phpBinFile'] ." ". Yii::$app->params['yiiConsoleFile'] ." {$cmd}";
        $cmd = self::isWindows()
            ? $cmd = "start /b {$cmd}"
            : $cmd = "{$cmd} > /dev/null 2>&1 &";

        return exec($cmd) ?: 'NULL returned';
    }
    /**
     * Определение операционной системы
     *
     * @return  bool
     */
    public static function isWindows() {
        return PHP_OS == 'WINNT' || PHP_OS == 'WIN32';
    }
    /**
     * Разделение строки на равные части (чанки)
     *
     * @param   string  $string Разделяемая строка
     * @param   integer $chunks Количество чанков
     * @return  array
     */
    public static function chunkString(string $string, int $chunks) {        
        $array = str_split($string);

        foreach (array_chunk($array, $chunks) as $chunk) 
            $result[] = implode($chunk);

        return $result;
    }
}