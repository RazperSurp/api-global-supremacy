<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/** @var yii\web\View $this */
/** @var yii\gii\generators\model\Generator $generator */
/** @var string $tableName full table name */
/** @var string $className class name */
/** @var string $queryClassName query class name */
/** @var yii\db\TableSchema $tableSchema */
/** @var array $properties list of properties (property => [type, name. comment]) */
/** @var string[] $labels list of attribute labels (name => label) */
/** @var string[] $rules list of validation rules */
/** @var array $relations list of relations (name => relation declaration) */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;

/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $deleted' . $name . "\n" ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $archived' . $name . "\n" ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $all' . $name . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [<?= empty($rules) ? '' : ("\n            " . implode(",\n            ", $rules) . ",\n        ") ?>];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }
<?php foreach ($relations as $name => $relation): ?>
    <?php
        // Получаем название таблицы по названию класса
        $pieces = [];
        foreach (preg_split('/(?=[A-Z])/', $relation[1]) as &$piece)
            if ($piece) $pieces[] = lcfirst($piece);
        $relatedTableName = implode('_', $pieces);

        // Отрезаем ; в конце $relation[0] чтобы дописать условия фильтрации
        $relation[0] = str_replace(';', '', $relation[0]);
    ?>

    /**
     * Gets query for actual [[<?= $name ?>]].
     *
     * @return <?= $relationsClassHints[$name] . "\n" ?>
     */
    public function get<?= $name ?>() {
        <?= $relation[0] . '->andOnCondition(["'. $relatedTableName .'"."deleted" => false, "'. $relatedTableName .'"."archived" => false]);' ."\n" ?>
    }

    /**
    * Gets query for deleted [[<?= $name ?>]].
    *
    * @return <?= $relationsClassHints[$name] . "\n" ?>
    */
    public function getDeleted<?= $name ?>() {
        <?= $relation[0] . '->andOnCondition(["'. $relatedTableName .'"."deleted" => true, "'. $relatedTableName .'"."archived" => false]);' ."\n" ?>
    }

    /**
    * Gets query for archived [[<?= $name ?>]].
    *
    * @return <?= $relationsClassHints[$name] . "\n" ?>
    */
    public function getArchived<?= $name ?>() {
        <?= $relation[0] . '->andOnCondition(["'. $relatedTableName .'"."deleted" => false, "'. $relatedTableName .'"."archived" => true]);' ."\n" ?>
    }

    /**
    * Gets query for all [[<?= $name ?>]].
    *
    * @return <?= $relationsClassHints[$name] . "\n" ?>
    */
    public function getAll<?= $name ?>() {
        <?= $relation[0] .';' ."\n" ?>
    }

<?php endforeach; ?>
<?php if ($queryClassName): ?>
<?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
?>
    /**
     * {@inheritdoc}
     * @return <?= $queryClassFullName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>
}
