<?php

namespace backend\modules\catalog\controllers;

use backend\components\BackendController;
use backend\modules\catalog\models\ContentWidget;
use metalguardian\fileProcessor\components\Image;
use metalguardian\fileProcessor\helpers\FPM;
use metalguardian\fileProcessor\models\File;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\UploadedFile;

/**
 * ContentWidgetController implements the CRUD actions for ContentWidget model.
 */
class ContentWidgetController extends BackendController
{
    public $layout = 'popup';
    /**
     * @return string
     */
    public function getModelClass()
    {
        return ContentWidget::className();
    }

    public function actionForm()
    {
        $model = new ContentWidget();

        $data = \Yii::$app->getRequest()->post($model->formName());

        if ($data) {

            $model->attributes = $data;
            $model->position = 9999;
            $form = $model->getFormConfig();

            $return['replaces'][] = array(
                'what' => '#constructorFieldModal .form',
                'data' => $this->render('_form', array(
                            'model' => $model,
                            'formConfig' => $form,
                            'action' => $model->getCreateUrl()
                        ), true),
            );

            echo Json::encode($return);

            \Yii::$app->end();
        }

        \Yii::$app->end();
    }

    public function actionCreate()
    {
        $model = new ContentWidget();

        $data = \Yii::$app->getRequest()->post($model->formName());

        if ($data) {

            $model->attributes = $data;
            $model->position = 9999;
            if (UploadedFile::getInstance($model, 'image_id'))
                $model->image_id = \metalguardian\fileProcessor\helpers\FPM::transfer()->saveUploadedFile(UploadedFile::getInstance($model, 'image_id'));
            if($this->loadModels($model) && $model->save())
            {

                $return = array();

                $return['replaces'][] = array(
                    'what' => '#constructorFieldModal .error',
                    'data' => "<div class=\"error\"></div>",
                );
                $this->layout = false;
                $return['append'][] = array(
                    'what' => '#constructorField .items',
                    'data' => $this->renderPartial('_row', array(
                                'item' => $model,
                            ), true),
                );
                $return['hide'] = 1;

                echo Json::encode($return);

                \Yii::$app->end();
            }

            $return = array();

            if ($model) {
                if ($model->hasErrors()) {
                    $return['replaces'][] = array(
                        'what' => '#constructorFieldModal .error',
                        'data' => $this->renderPartial('_error', array('model' => $model), true),
                    );
                } else {
                    $return['replaces'][] = array(
                        'what' => '#constructorFieldModal .error',
                        'data' => "<div class=\"error\"></div>",
                    );
                }
            }


            $form = $model->getFormConfig();


            $return['replaces'][] = array(
                'what' => '#constructorFieldModal .form',
                'data' => $this->render('_form', array(
                            'model' => $model,
                            'formConfig' => $form,
                            'action' => $model->getCreateUrl()
                        ), true),
            );

            echo Json::encode($return);

            \Yii::$app->end();
        }

        \Yii::$app->end();
    }

    public function actionUpdate($id)
    {
        $model = ContentWidget::find()->where('id=:id', [':id' => $id])->one();

        $form = $model->getFormConfig();

        $return['replaces'][] = array(
            'what' => '#constructorFieldModal .form',
            'data' => $this->render('_form', array(
                        'model' => $model,
                        'formConfig' => $form,
                        'action' => $model->getSaveUrl()
                    ), true),
        );

        echo Json::encode($return);

        \Yii::$app->end();
    }

    public function actionSave($id)
    {
        $model = ContentWidget::find()->where('id=:id', [':id' => $id])->one();

        $data = \Yii::$app->getRequest()->post($model->formName());

        if ($data) {
            $model->attributes = $data;
            if (UploadedFile::getInstance($model, 'image_id'))
                $model->image_id = \metalguardian\fileProcessor\helpers\FPM::transfer()->saveUploadedFile(UploadedFile::getInstance($model, 'image_id'));

            if ($this->loadModels($model) && $model->save()) {
                $return = array();

                $return['replaces'][] = array(
                    'what' => '#constructorFieldModal .error',
                    'data' => "<div class=\"error\"></div>",
                );
                $this->layout = false;
                $return['replaces'][] = array(
                    'what' => '#constructorField .items .row_' . $model->id,
                    'data' => $this->renderPartial('_row', array(
                                'item' => $model,
                            ), true),
                );
                $return['hide'] = 1;

                echo Json::encode($return);

                \Yii::$app->end();
            }
        }

        $return = array();

        if ($model) {
            if ($model->hasErrors()) {
                $return['replaces'][] = array(
                    'what' => '#constructorFieldModal .error',
                    'data' => $this->renderPartial('_error', array('model' => $model), true),
                );
            } else {
                $return['replaces'][] = array(
                    'what' => '#constructorFieldModal .error',
                    'data' => "<div class=\"error\"></div>",
                );
            }
        }


        $form = $model->getFormConfig();


        $return['replaces'][] = array(
            'what' => '#constructorFieldModal .form',
            'data' => $this->render('_form', array(
                        'model' => $model,
                        'formConfig' => $form,
                        'action' => $model->getSaveUrl()
                    ), true),
        );

        echo Json::encode($return);

        \Yii::$app->end();

    }

    public function actionDelete($id)
    {
        $model = ContentWidget::find()->where('id=:id', [':id' => $id])->one();

        if ($model) {

            if ($model->delete()) {
                $return = array();

                $return['replaces'][] = array(
                    'what' => '#constructorField .items .row_' . $model->id,
                    'data' => '',
                );

                echo Json::encode($return);

                \Yii::$app->end();
            }
        }
    }

    public function actionSort()
    {
        $items = \Yii::$app->getRequest()->post('items', array());

        foreach ($items as $position => $item) {
            \Yii::$app->db->createCommand()
                ->update(
                    ContentWidget::tableName(),
                    [
                        'position' => $position
                    ],
                    'id = :id',
                    [':id' => $item]
                )
                ->execute();
        }
    }

    public function actionCrop()
    {
        $this->layout = 'popup';;
        $fileId = \Yii::$app->request->get('id');

        if (!$fileId) {
            return 'Кроп доступен только после загрузки изображения';
        }


        $return['replaces'][] = array(
            'what' => '#productsImageCropperFieldModal .wrap .container',
            'data' => $this->render('_crop_image', [
                        'id' => $fileId,
                    ], true),
        );
        $return['js'][] = Html::script('hideModal(".field-catalogproduct-images .modal")');

        return Json::encode($return);

        \Yii::$app->end();
    }

    public function actionSaveCroped()
    {
        $data = \Yii::$app->request->post('data');
        $data = $data ? Json::decode($data) : null;

        if ($data) {
            $fileId = $data['fileId'];

            $imageEntity = ContentWidget::find()->where('image_id = :id', [':id' => (int)$fileId])->one();
            $file = \metalguardian\fileProcessor\models\File::find()->where('id = :id', [':id' => (int)$fileId])->one();

            if ($imageEntity) {
                //Find original img path
                $directory = FPM::getOriginalDirectory($imageEntity->image_id);
                FileHelper::createDirectory($directory, 0777, true);
                $fileName =
                    $directory
                    . DIRECTORY_SEPARATOR
                    . FPM::getOriginalFileName(
                        $imageEntity->image_id,
                        $file->base_name,
                        $file->extension
                    );
                //Delete cached image
                FPM::cache()->delete($imageEntity->image_id);
                //Delete thumbs
                $this->clearImageThumbs($file);

                Image::crop($fileName, $data['width'], $data['height'], $data['startX'], $data['startY'])
                    ->save($fileName);

                return Json::encode(
                    [
                        'replaces' => [
                            [
                                'what' => '#preview-image-' . $imageEntity->image_id,
                                'data' => Html::img(
                                        FPM::originalSrc($imageEntity->image_id).'?'.time(),
                                        [
                                            'class' => 'file-preview-image',
                                            'id' => 'preview-image-' . $imageEntity->image_id
                                        ]
                                    )
                            ],
                        ],
                        'js' => Html::script('hideModal(".modal");')
                    ]
                );
            }
        }
    }

    public function actionCropMobile()
    {
        $this->layout = 'popup';;
        $fileId = \Yii::$app->request->get('id');

        if (!$fileId) {
            return 'Кроп доступен только после загрузки изображения';
        }


        $return['replaces'][] = array(
            'what' => '#productsImageCropperFieldModal .wrap .container',
            'data' => $this->render('_crop_mobile_image', [
                        'id' => $fileId,
                    ], true),
        );
        $return['js'][] = Html::script('hideModal(".field-catalogproduct-images .modal")');

        return Json::encode($return);

        \Yii::$app->end();
    }

    public function actionSaveCropedMobile()
    {
        $data = \Yii::$app->request->post('data');
        $data = $data ? Json::decode($data) : null;

        if ($data) {
            $fileId = $data['fileId'];

            $imageEntity = ContentWidget::find()->where('image_id = :id', [':id' => (int)$fileId])->one();
            $file = \metalguardian\fileProcessor\models\File::find()->where('id = :id', [':id' => (int)$fileId])->one();

            if ($imageEntity) {
                //Find original img path
                $directory = FPM::getOriginalDirectory($imageEntity->image_id);
                FileHelper::createDirectory($directory, 0777, true);
                $fileName =
                    $directory
                    . DIRECTORY_SEPARATOR
                    . FPM::getOriginalFileName(
                        $imageEntity->image_id,
                        $file->base_name,
                        $file->extension
                    );
                $newFileId = FPM::transfer()->saveSystemFile($fileName);
                $file = \metalguardian\fileProcessor\models\File::find()->where('id = :id', [':id' => (int)$newFileId])->one();
                $directory = FPM::getOriginalDirectory($newFileId);
                FileHelper::createDirectory($directory, 0777, true);
                $fileName =
                    $directory
                    . DIRECTORY_SEPARATOR
                    . FPM::getOriginalFileName(
                        $newFileId,
                        $file->base_name,
                        $file->extension
                    );
                $imageEntity->mobile_image_id = $newFileId;
                $imageEntity->save();

                Image::crop($fileName, $data['width'], $data['height'], $data['startX'], $data['startY'])
                    ->save($fileName);

                return Json::encode(
                    [
                        'replaces' => [
                            [
                                'what' => '#mobile_image_preview',
                                'data' => Html::img(
                                        FPM::originalSrc($imageEntity->image_id).'?'.time(),
                                        [
                                            'class' => 'file-preview-image',
                                            'id' => 'mobile_image_preview'
                                        ]
                                    )
                            ],
                        ],
                        'js' => Html::script('hideModal(".modal");')
                    ]
                );
            }
        }
    }

    /**
     * Delete all previously generated image thumbs
     *
     * @param File $model
     */
    protected function clearImageThumbs(File $model)
    {
        $fp = \Yii::$app->getModule('fileProcessor');

        if ($fp) {
            $imageSections = $fp->imageSections;

            foreach ($imageSections as $moduleName => $config) {

                foreach ($config as $size => $data) {
                    $thumbnailFile = FPM::getThumbnailDirectory($model->id, $moduleName, $size) . DIRECTORY_SEPARATOR .
                        FPM::getThumbnailFileName($model->id, $model->base_name, $model->extension);

                    if (is_file($thumbnailFile)) {
                        unlink($thumbnailFile);
                    }
                }

            }
        }
    }
}
