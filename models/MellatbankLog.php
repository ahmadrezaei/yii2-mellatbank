<?php

namespace ahmadrezaei\yii\mellatbank\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%mellatbank_log}}".
 *
 * @property integer $id
 * @property integer $saleReferenceId
 * @property string $CardHolderPan
 * @property string $data
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 */
class MellatbankLog extends ActiveRecord
{
    const STATUS_SUCCESS = 10;
    const STATUS_PENDING = 5;
    const STATUS_UNSUCCESS = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%mellatbank_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_SUCCESS, self::STATUS_UNSUCCESS]],
            [['status'], 'required'],
            [['saleReferenceId', 'status', 'id'], 'integer'],
            [['data'], 'string'],
            [['CardHolderPan'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'saleReferenceId' => Yii::t('app', 'Sale Reference ID'),
            'CardHolderPan' => Yii::t('app', 'Card Holder Pan'),
            'data' => Yii::t('app', 'Data'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        $count = self::find()->count();
        return ($count < 1);
    }
}