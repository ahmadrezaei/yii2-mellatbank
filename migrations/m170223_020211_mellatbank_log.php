<?php

use yii\db\Migration;

class m170223_020211_mellatbank_log extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%mellatbank_log}}', [
            'id' => $this->primaryKey(),
            'saleReferenceId' => $this->integer(),
            'CardHolderPan' => $this->string(20),
            'data' => $this->text(),
            'status' => $this->smallInteger()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%mellatbank_log}}');
    }
}
