<?php

namespace app\models\common;

use app\models\components\FileWizard;
use Yii;
use ZipStream\File;

/**
 * This is the model class for table "document_in".
 *
 * @property int $id
 * @property int $local_number
 * @property int $local_postfix
 * @property string $local_date
 * @property string $real_number
 * @property string $real_date
 * @property int $correspondent_id
 * @property int $position_id
 * @property int $company_id
 * @property string $document_theme
 * @property int $signed_id
 * @property string $target
 * @property int $get_id
 * @property int $send_method_id
 * @property string $scan
 * @property string $doc
 * @property string $applications
 * @property int $register_id
 * @property string $key_words
 * @property boolean|null $needAnswer
 *
 * @property Company $company
 * @property User $get
 * @property Position $position
 * @property User $register
 * @property People $signed
 * @property SendMethod $sendMethod
 * @property People $correspondent
 */
class DocumentIn extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'document_in';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['local_date', 'real_date', 'send_method_id', 'position_id', 'company_id', 'document_theme', 'signed_id', 'target', 'get_id', 'register_id'], 'required'],
            [['local_number', 'position_id', 'company_id', 'signed_id', 'get_id', 'register_id', 'correspondent_id', 'local_postfix'], 'integer'],
            [['needAnswer'], 'boolean'],
            [['local_date', 'real_date'], 'safe'],
            [['document_theme', 'target', 'scan', 'applications', 'key_words', 'real_number'], 'string', 'max' => 1000],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'id']],
            [['get_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['get_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::className(), 'targetAttribute' => ['position_id' => 'id']],
            [['register_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['register_id' => 'id']],
            [['signed_id'], 'exist', 'skipOnError' => true, 'targetClass' => People::className(), 'targetAttribute' => ['signed_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'local_number' => '№ п/п',
            'local_date' => 'Дата поступления документа',
            'real_number' => 'Регистрационный номер входящего документа ',
            'real_date' => 'Дата входящего документа ',
            'position_id' => 'Должность',
            'company_id' => 'Организация',
            'document_theme' => 'Тема документа',
            'signed_id' => 'Кем подписан',
            'target' => 'Кому адресован',
            'get_id' => 'Кем получен',
            'scan' => 'Скан',
            'applications' => 'Приложения',
            'register_id' => 'Регистратор документа',
            'key_words' => 'Ключевые слова',
            'needAnswer' => 'Требуется ответ'
        ];
    }

    /**
     * Gets query for [[Company]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * Gets query for [[Get]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGet()
    {
        return $this->hasOne(User::className(), ['id' => 'get_id']);
    }

    /**
     * Gets query for [[Position]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Position::className(), ['id' => 'position_id']);
    }

    /**
     * Gets query for [[Register]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegister()
    {
        return $this->hasOne(User::className(), ['id' => 'register_id']);
    }

    /**
     * Gets query for [[Signed]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSigned()
    {
        return $this->hasOne(People::className(), ['id' => 'signed_id']);
    }

    /**
     * Gets query for [[SendMethod]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSendMethod()
    {
        return $this->hasOne(SendMethod::className(), ['id' => 'send_method_id']);
    }

    /**
     * Gets query for [[Correspondent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCorrespondent()
    {
        return $this->hasOne(People::className(), ['id' => 'correspondent_id']);
    }
}
