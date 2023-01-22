<?php
/**
 * This is the template for generating a controller class file.
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var yii\gii\generators\controller\Generator $generator */

echo "<?php\n";
?>

namespace app\controllers;

class <?= StringHelper::basename($generator->controllerClass) ?> extends \app\models\core\ActiveController {
    public $modelClass = 'app\models\db\<?= preg_filter('/(.+)Controller/', '$1', StringHelper::basename($generator->controllerClass)) ?>';
}