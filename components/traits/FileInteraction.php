<?php

namespace app\components\traits;

use app\models\common\Company;
use app\models\common\DocumentIn;
use app\models\common\InOutDocs;
use app\models\common\People;
use app\models\common\Position;
use app\models\common\User;
use app\models\components\FileWizard;
use Yii;
use ZipStream\File;


trait FileInteraction
{
    // Загрузка файлов на сервер или Яндекс Диск (условная стратегия)
    function downloadFile($fileName = null, $type = null)
    {
        $filePath = '/upload/files/'.Yii::$app->controller->id;
        $filePath .= $type == null ? '/' : '/'.$type.'/';

        $downloadServ = new FileDownloadServer($filePath, $fileName);
        $downloadYadi = new FileDownloadYandexDisk($filePath, $fileName);

        $downloadServ->LoadFile();
        if (!$downloadServ->success) $downloadYadi->LoadFile();
        else return \Yii::$app->response->sendFile($downloadServ->file);

        if (!$downloadYadi->success) throw new \Exception('File not found');
        else
        {

            $fp = fopen('php://output', 'r');

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $downloadYadi->filename);
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $downloadYadi->file->size);

            $downloadYadi->file->download($fp);

            fseek($fp, 0);

        }
    }

    // Выгрузка файлов с сервера
    function uploadFile($path, $filename, $fileObj)
    {
        $path = '@app/upload/'.$path;
        $res = mb_ereg_replace('[ ]{1,}', '_', $filename);
        $res = mb_ereg_replace('[^а-яА-Я0-9._]{1}', '', $res);
        $res = FileWizard::CutFilename($res);

        $fileObj->saveAs($path.$res.'.'.$fileObj->extension);
    }

    // Удаление файла с сервера
    function deleteFile($filepath)
    {
        unlink($filepath);
    }
}
