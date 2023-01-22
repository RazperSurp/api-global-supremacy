<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%service}}`.
 */
class m000000_000000_create_service_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%service}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(10)->notNull()->unique(),
            'value' => $this->text()->null(),
            'encrypted_value' => $this->binary()->null(),
            'is_readonly' => $this->boolean()->null()->defaultValue(true),
            'type' => $this->string(4)
        ]);

        $this->addColumn('{{%service}}', 'created_at', $this->integer()->null()->defaultValue(new \yii\db\Expression('round(extract(epoch from now()))')));
        $this->addColumn('{{%service}}', 'updated_at', $this->integer()->null()->defaultValue(new \yii\db\Expression('round(extract(epoch from now()))')));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%service}}');
    }
}
