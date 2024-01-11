<?php

namespace app\controllers;

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
use app\services\DocumentInService;
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
class DocumentInController extends Controller
{
    private $documentInService;

    public function __construct(DocumentInService $service)
    {
        $this->documentInService = $service ? : new DocumentInService();
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Сводная таблица документов
     * @return mixed
     */
    public function actionIndex($sort = null, $archive = null, $type = null)
    {
        $session = Yii::$app->session;
        if ($archive !== null && $type !== null)
            $session->set("archiveIn", "1");
        if ($archive === null && $type !== null)
            $session->remove("archiveIn");

        $searchModel = new SearchDocumentIn($archive);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $sort);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Карточка документа с полной информацией
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Регистрация нового документа (письма)
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new DocumentInWork();


        if ($model->load(Yii::$app->request->post())) {
            $model->prepare();
            $model->scanFile = UploadedFile::getInstance($model, 'scanFile');
            $model->applicationFiles = UploadedFile::getInstances($model, 'applicationFiles');
            $model->docFiles = UploadedFile::getInstances($model, 'docFiles');

            if ($model->validate())
            {
                $model->getDocumentNumber();
                $model->uploadFiles();

                $model->save();
                Logger::WriteLog(Yii::$app->user->identity->getId(), 'Добавлен входящий документ '.$model->document_theme);
            }
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Создание резерва документа
     * @return mixed
     */

    public function actionCreateReserve()
    {

        $model = new DocumentInWork();
        $model->prepareReserve();
        Yii::$app->session->addFlash('success', 'Резерв успешно добавлен');
        $model->save(false);
        Logger::WriteLog(Yii::$app->user->identity->getId(), 'Добавлен резерв входящего документа '.$model->local_number.'/'.$model->local_postfix);
        return $this->redirect('index.php?r=document-in/index');
    }

    /**
     * Редактирование существующего документа
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $model->scanFile = $model->scan;

        $links = InOutDocsWork::find()->where(['document_in_id' => $model->id])->one();
        if ($links !== null)
            $model->needAnswer = 1;

        if($model->load(Yii::$app->request->post()))
        {
            $model->scanFile = UploadedFile::getInstance($model, 'scanFile');
            $model->applicationFiles = UploadedFile::getInstances($model, 'applicationFiles');
            $model->docFiles = UploadedFile::getInstances($model, 'docFiles');
            if ($model->validate(false)) {
                $model->uploadFiles();
                $model->save(false);
                Logger::WriteLog(Yii::$app->user->identity->getId(), 'Изменен входящий документ '.$model->document_theme);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление документа
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $name = $this->findModel($id)->document_theme;
        Logger::WriteLog(Yii::$app->user->identity->getId(), 'Удален входящий документ '.$name);
        $this->findModel($id)->delete();
        Yii::$app->session->addFlash('success', 'Документ "'.$name.'" успешно удален');

        return $this->redirect(['index']);
    }

    // Скачивание файла через интерфейс
    public function actionGetFile($fileName = null, $modelId = null, $type = null)
    {
        $model = DocumentInWork::find()->where(['id' => $modelId])->one();
        $model->downloadFile($fileName, $type);
    }

    // Удаление файла через интерфейс
    public function actionDeleteFile($fileName = null, $modelId = null, $type = null)
    {

        $model = DocumentInWork::find()->where(['id' => $modelId])->one();

        if ($type == 'scan')
        {
            $model->deleteFile($model->scan);

            $model->scan = '';
            $model->save(false);
            return $this->redirect('index?r=document-in/update&id='.$modelId);
        }

        if ($fileName !== null && !Yii::$app->user->isGuest && $modelId !== null)
        {

            $result = '';
            $type == 'app' ? $split = explode(" ", $model->applications) : $split = explode(" ", $model->doc);
            $deleteFile = '';
            for ($i = 0; $i < count($split) - 1; $i++)
            {
                if ($split[$i] !== $fileName)
                {
                    $result = $result.$split[$i].' ';
                }
                else
                {
                    $model->deleteFile($split[$i]);
                    $deleteFile = $split[$i];
                }
            }

            $type == 'app' ? $model->applications = $result : $model->doc = $result;
            
            $model->save(false);
            Logger::WriteLog(Yii::$app->user->identity->getId(), 'Удален файл '.$deleteFile);
            return $this->redirect('index?r=document-in/update&id='.$modelId);
        }
        return $this->redirect('index.php?r=document-in/update&id='.$modelId);
    }

    // Создание зависимого выпадающего списка
    public function actionSubcat()
    {
        echo $this->documentInService->generateDependentList(Yii::$app->request->post('id'));
    }


    //Проверка на права доступа к CRUD-операциям
    public function beforeAction($action)
    {
        if (Yii::$app->user->isGuest)
            return $this->redirect(['/site/login']);
        if (!RoleBaseAccess::CheckAccess($action->controller->id, $action->id, Yii::$app->user->identity->getId())) {
            return $this->redirect(['/site/error-access']);
        }
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }
}
