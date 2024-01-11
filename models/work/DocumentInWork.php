<?php

namespace app\models\work;

use app\models\common\Company;
use app\models\common\DocumentIn;
use app\models\common\InOutDocs;
use app\models\common\People;
use app\models\common\Position;
use app\models\common\User;
use app\models\components\FileWizard;
use Yii;
use ZipStream\File;
use app\components\traits\FileInteraction;

use Arhitector\Yandex\Disk;

class DocumentInWork extends DocumentIn
{
    use FileInteraction;

    // Вспомогательные поля
    public $signedString;
    public $getString;

    public $scanFile;
    public $docFiles;
    public $applicationFiles;

    public $dateAnswer;
    public $nameAnswer;
    // --------------------


    public function rules()
    {
        return [
            [['scanFile'], 'file', 'extensions' => 'png, jpg, pdf, zip, rar, 7z, tag', 'skipOnEmpty' => true],
            [['docFiles'], 'file', 'extensions' => 'xls, xlsx, doc, docx, zip, rar, 7z, tag', 'skipOnEmpty' => true, 'maxFiles' => 10],
            [['applicationFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'ppt, pptx, xls, xlsx, pdf, png, jpg, doc, docx, zip, rar, 7z, tag', 'maxFiles' => 10],

            [['signedString', 'getString'], 'string', 'message' => 'Введите корректные ФИО'],
            [['dateAnswer', 'nameAnswer'], 'string'],
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

    //-----------------------------------

    public function __construct()
    {
        
    }

    // Подготовка к сохранению стандартного документа
    public function prepare()
    {
        $model->local_number = 0;
        $model->signed_id = null;
        $model->target = null;
        $model->get_id = null;
        $model->applications = '';
        $model->scan = '';
    }

    // Подготовка к сохранению резерва
    public function prepareReserve()
    {
        $model->document_theme = 'Резерв';

        $model->local_date = date("Y-m-d");
        $model->real_date = '1999-01-01';
        $model->scan = '';
        $model->applications = '';
        $model->register_id = Yii::$app->user->identity->getId();
        $model->getDocumentNumber();
    }

    // Обертка для сохранения всех прикрепленных файлов
    public function uploadFiles()
    {
        if ($model->scanFile != null)
            $model->uploadScanFile();
        if ($model->applicationFiles != null)
            $model->uploadApplicationFiles();
        if ($model->docFiles != null)
            $model->uploadDocFiles();
    }

    // Сохранение скана документа
    private function uploadScanFile()
    {

        $path = '@app/upload/files/document-in/scan/';
        $date = $this->local_date;
        $new_date = '';
        $filename = '';
        for ($i = 0; $i < strlen($date); ++$i)
            if ($date[$i] != '-')
                $new_date = $new_date.$date[$i];
        if ($this->company->short_name !== '')
        {
            $filename = 'Вх.'.$new_date.'_'.$this->local_number.'_'.$this->company->short_name.'_'.$this->document_theme;
        }
        else
        {
            $filename = 'Вх.'.$new_date.'_'.$this->local_number.'_'.$this->company->name.'_'.$this->document_theme;
        }

        $this->scan = $res.'.'.$this->scanFile->extension;

        $this->uploadFile($path, $filename, $this->scanFile->extension);
    }

    // Сохранение файлов приложений
    private function uploadApplicationFiles($upd = null)
    {
        $path = '@app/upload/files/document-in/apps/';
        $result = '';
        $counter = 0;
        if (strlen($this->doc) > 4)
            $counter = count(explode(" ", $this->applications)) - 1;
        foreach ($this->applicationFiles as $file) {
            $counter++;
            $date = $this->local_date;
            $new_date = '';
            for ($i = 0; $i < strlen($date); ++$i)
                if ($date[$i] != '-')
                    $new_date = $new_date.$date[$i];
            if ($this->company->short_name !== '')
            {
                $filename = 'Приложение'.$counter.'_Вх.'.$new_date.'_'.$this->local_number.'_'.$this->company->short_name.'_'.$this->document_theme;
            }
            else
            {
                $filename = 'Приложение'.$counter.'_Вх.'.$new_date.'_'.$this->local_number.'_'.$this->company->name.'_'.$this->document_theme;
            }

            $this->uploadFile($path, $filename, $this->scanFile->extension);

            $result = $result.$res . '.' . $file->extension.' ';
        }
        if ($upd == null)
            $this->applications = $result;
        else
            $this->applications = $this->applications.$result;
        return true;
    }

    // Сохранение редакитируемых файлов
    private function uploadDocFiles($upd = null)
    {
        $path = '@app/upload/files/document-in/docs/';
        $result = '';
        $counter = 0;
        if (strlen($this->doc) > 4)
            $counter = count(explode(" ", $this->doc)) - 1;
        foreach ($this->docFiles as $file) {
            $counter++;
            $date = $this->local_date;
            $new_date = '';
            for ($i = 0; $i < strlen($date); ++$i)
                if ($date[$i] != '-')
                    $new_date = $new_date.$date[$i];
            if ($this->company->short_name !== '')
            {
                $filename = 'Ред'.$counter.'_Вх.'.$new_date.'_'.$this->local_number.'_'.$this->company->short_name.'_'.$this->document_theme;
            }
            else
            {
                $filename = 'Ред'.$counter.'_Вх.'.$new_date.'_'.$this->local_number.'_'.$this->company->name.'_'.$this->document_theme;
            }
            
            $this->uploadFile($path, $filename, $this->scanFile->extension);

            $result = $result.$res . '.' . $file->extension.' ';
        }
        if ($upd == null)
            $this->doc = $result;
        else
            $this->doc = $this->doc.$result;
        return true;
    }

    // Генерация номера документа
    // --Даты документов и их номера должны образовывать две равнозначно неубывающие последовательности
    public function getDocumentNumber()
    {
        $docs = DocumentIn::find()->orderBy(['local_date' => SORT_DESC])->all();
        if (date('Y') !== substr($docs[0]->local_date, 0, 4))
            $this->local_number = 1;
        else
        {
            $docs = DocumentIn::find()->where(['like', 'local_date', date('Y')])->orderBy(['local_number' => SORT_ASC, 'local_postfix' => SORT_ASC])->all();
            if (end($docs)->local_date > $this->local_date && $this->document_theme != 'Резерв')
            {
                $tempId = 0;
                $tempPre = 0;
                if (count($docs) == 0)
                    $tempId = 1;
                for ($i = count($docs) - 1; $i >= 0; $i--)
                {
                    if ($docs[$i]->local_date <= $this->local_date)
                    {
                        $tempId = $docs[$i]->local_number;
                        if ($docs[$i]->local_postfix != null)
                            $tempPre = $docs[$i]->local_postfix + 1;
                        else
                            $tempPre = 1;
                        break;
                    }
                }

                $this->local_number = $tempId;
                $this->local_postfix = $tempPre;
                Yii::$app->session->addFlash('warning', 'Добавленный документ должен был быть зарегистрирован раньше. Номер документа: '.$this->local_number.'/'.$this->local_postfix);
            }
            else
            {
                if (count($docs) == 0)
                    $this->local_number = 1;
                else
                {
                    $this->local_number = end($docs)->local_number + 1;
                }
            }
        }

    }


    //-----------------------------------

    // -- Триггерные функции модели --

    public function beforeSave($insert)
    {
        $fioSigned = explode(" ", $this->signedString);
        $fioGet = explode(" ", $this->getString);

        $fioSignedDb = People::find()->where(['secondname' => $fioSigned[0]])
            ->andWhere(['firstname' => $fioSigned[1]])
            ->andWhere(['patronymic' => $fioSigned[2]])->one();
        $fioGetDb = User::find()->where(['secondname' => $fioGet[0]])
            ->andWhere(['firstname' => $fioGet[1]])
            ->andWhere(['patronymic' => $fioGet[2]])->one();
        if ($fioSignedDb !== null)
            $this->signed_id = $fioSignedDb->id;

        if ($fioGetDb !== null)
            $this->get_id = $fioGetDb->id;

        $this->register_id = Yii::$app->user->identity->getId();

        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }
    

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
        if ($this->needAnswer == 1 && InOutDocs::find()->where(['document_in_id' => $this->id])->one() == null)
        {
            $newLink = new InOutDocs();
            $newLink->document_in_id = $this->id;
            $newLink->date = $this->dateAnswer;
            $newLink->people_id = $this->nameAnswer;
            $newLink->save();
        }
        else
        {
            $newLink = InOutDocs::find()->where(['document_in_id' => $this->id])->one() ;
            if ($newLink !== null)
            {
                $newLink->document_in_id = $this->id;
                $newLink->date = $this->dateAnswer;
                $newLink->people_id = $this->nameAnswer;
                $newLink->save();
            }
        }
        if ($changedAttributes["needAnswer"] == 1 && count($changedAttributes) !== 1 && $this->needAnswer == 0)
        {
            $links = InOutDocs::find()->where(['document_in_id' => $this->id])->one();
            if ($links !== null)
                $links->delete();
        }
    }

    public function beforeDelete()
    {
        $links = InOutDocs::find()->where(['document_in_id' => $this->id])->all();
        foreach ($links as $linkOne)
        {
            $linkOne->delete();
        }
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    // -------------------------------
}
