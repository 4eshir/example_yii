<?php

namespace app\services;

use app\models\components\RoleBaseAccess;
use app\models\work\CompanyWork;
use app\models\work\DocumentOutWork;
use app\models\work\InOutDocsWork;
use app\models\components\Logger;
use app\models\components\UserRBAC;
use app\models\work\PeoplePositionBranchWork;
use app\models\work\PeopleWork;
use app\models\work\PositionWork;
use Arhitector\Yandex\Disk;
use Yii;
use app\models\work\DocumentInWork;
use app\models\SearchDocumentIn;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

use app\models\strategies\FileDownloadStrategy\FileDownloadServer;
use app\models\strategies\FileDownloadStrategy\FileDownloadYandexDisk;

/**
 * Входящие документы организации
 * Регистрация, редактирование и архивирование документов
 */
class DocumentInService
{
    public function __construct()
    {
        
    }

    // Создание разметки для зависимого выпадающего списка
    public function generateDependentList($id)
    {
        $list = '';

        if ($id === "")
        {
            $operations = PositionWork::find()
                ->orderBy(['name' => SORT_ASC])
                ->all();
            foreach ($operations as $operation)
                $list .= "<option value='" . $operation->id . "'>" . $operation->name . "</option>";
            $list .= "|split|";
            $operations = CompanyWork::find()
                ->orderBy(['name' => SORT_ASC])
                ->all();
            foreach ($operations as $operation)
                $list .= "<option value='" . $operation->id . "'>" . $operation->name . "</option>";
        }
        else
        {
            Yii::trace('$id=' . $id, 'значение id=');
            $operationPosts = PeoplePositionBranchWork::find()
                ->where(['people_id' => $id])
                ->count();

            if ($operationPosts > 0) {
                $operations = PeoplePositionBranchWork::find()
                    ->where(['people_id' => $id])
                    ->all();
                foreach ($operations as $operation)
                    $list .= "<option value='" . $operation->position_id . "'>" . $operation->position->name . "</option>";
            } else
                $list .= "<option>-</option>";

            $list .= "|split|";
            $people = PeopleWork::find()->where(['id' => $id])->one();
            $operationPosts = CompanyWork::find()
                ->where(['id' => $people->company_id])
                ->count();

            if ($operationPosts > 0) {
                $operations = CompanyWork::find()
                    ->where(['id' => $people->company_id])
                    ->all();
                foreach ($operations as $operation)
                    $list .= "<option value='" . $operation->id . "'>" . $operation->name . "</option>";
            } else
                $list .= "<option>-</option>";
        }

        return $list;
    }
}
