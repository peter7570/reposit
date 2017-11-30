<?php


namespace frontend\modules\catalog\models;


use common\components\Translate;
use common\models\CatalogProductOptions;
use common\models\Currency;
use common\models\EntityToImage;
use common\models\Section;
use himiklab\sitemap\behaviors\SitemapBehavior;
use frontend\components\MetaTagRegister;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

class CatalogProduct extends \common\models\CatalogProduct
{

    use Translate;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'sitemap' => [
                    'class' => SitemapBehavior::className(),
                    'scope' => function ($model) {
                        /** @var \yii\db\ActiveQuery $model */
                        $model->select(['alias']);
                        $model->andWhere(['published' => 1]);
                    },
                    'dataClosure' => function ($model) {
                        /** @var self $model */
                        return [
                            'loc' => Url::to($model->getPageUrl(), true),
                            'lastmod' => time(),
                            'changefreq' => SitemapBehavior::CHANGEFREQ_DAILY,
                        ];
                    }
                ],
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(Section::className(), ['type_id' => 'section_id']);
    }

    public static function getSendOrderUrl()
    {
        return Url::toRoute(['/catalog/cart/order']);
    }

    public function getChangeOrderUrl()
    {
        return Url::toRoute(['/catalog/cart/change-product']);
    }

    public static function getPageListUrl()
    {
        return Url::toRoute(['/catalog/catalog/index']);
    }

    public function getOrderUrl()
    {
        return Url::toRoute(['/catalog/cart/add', 'id' => $this->id]);
    }

    public function getCalculateUrl()
    {
        return Url::toRoute(['/catalog/cart/calculate-price', 'id' => $this->id]);
    }

    public function getFeatures()
    {
        return Peculiarity::find()->isPublished()
            ->where(['model_id' => $this->id])
            ->orderBy(['position' => SORT_ASC])->all();
    }

    /**
     * @param $options
     * @return float|mixed
     */
    public function calculatePrice($options)
    {
        if (!empty($options)) {
            return $this->calculatePriceWithOptions($options);
        } elseif (!empty($this->new_price)) {
	        return Currency::getPriceInCurrency($this->new_price);
        }
        return $this->calculateOldPrice($options);
    }

    /**
     * @param $options
     * @return float
     */
    public function calculatePriceWithOptions($options)
    {
        $summ = round($this->price);
        $options = CatalogProductOptions::find()->where(['product_id' => $this->id])->andWhere(['option_id' => $options])->all();
        foreach ($options as $option) {
            if ($option) {
                $summ += $option->new_price != null ? $option->new_price : $option->price;
            }
        }

	    $summ = Currency::getPriceInCurrency($summ);

        return $summ;
    }

    public function calculateOldPrice($options)
    {
        $summ = round($this->price);
        $options = CatalogProductOptions::find()->where(['product_id' => $this->id])->andWhere(['option_id' => $options])->all();
        foreach ($options as $option) {
            if ($option) {
                $summ += $option->price;
            }
        }

	    $summ = Currency::getPriceInCurrency($summ);

        return $summ;
    }

    public function getPriceStatus($options, $class = 'price_status price_gray')
    {
        $options = CatalogProductOptions::find()->select('MAX(status)')->where(['product_id' => $this->id])->andWhere(['option_id' => $options])->scalar();

        if ($options == CatalogProductOptions::STATUS_NULL) {
            return '';
        } else {
            $status = ArrayHelper::getValue(CatalogProductOptions::getStatusList(), $options, '');

            return Html::tag('span', '(' . $status . ')', ['class' => $class]);
        }
    }

    public function getPriceStatusClear($options, $class = 'price-status-list')
    {
        $options = CatalogProductOptions::find()->select('MAX(status)')->where(['product_id' => $this->id])->andWhere(['option_id' => $options])->scalar();

        if ($options == CatalogProductOptions::STATUS_NULL) {
            return '';
        } else {
            $status = ArrayHelper::getValue(CatalogProductOptions::getStatusListClear(), $options, '');

            return Html::tag('span', $status, ['class' => $class]);
        }
    }

    public function getContent()
    {
        $content = ContentWidget::find()
            ->where('model_id=:id AND model_name=:mn', [':id' => $this->id, ':mn' => $this->getParentFormName()])
            ->orderBy('position ASC')->all();
        return $content ? $content : [];
    }

    public function getSpecifications()
    {
        return Specifications::find()
            ->isPublished()->where(['model_id' => $this->model_id])
            ->orderBy(['position' => SORT_ASC])->all();
    }

    public function getGallery()
    {
        return EntityToImage::find()
            ->where('model_name=:emn', [':emn' => $this->getParentFormName()])
            ->andWhere('model_id = :id', [':id' => $this->id])
            ->orderBy('position ASC')->all();
    }

    public function getOptions1()
    {
        return ProductOptions::find()->from(['t' => ProductOptions::tableName()])
            ->where([CatalogProductOptions::tableName() . '.product_id' => $this->id])
            ->andWhere(['t.type_id' => ProductOptions::PRODUCT_OPTIONS_TYPE_MEMORY_SIZE])
            ->andWhere(['t.published' => 1])
            ->joinWith('catalogProductOptions')
            ->orderBy([CatalogProductOptions::tableName() . '.position' => SORT_ASC])
            ->all();
    }

    public function getOptions2()
    {
        return ProductOptions::find()->from(['t' => ProductOptions::tableName()])
            ->where([CatalogProductOptions::tableName() . '.product_id' => $this->id])
            ->andWhere(['t.type_id' => ProductOptions::PRODUCT_OPTIONS_TYPE_COATING])
            ->andWhere(['t.published' => 1])
            ->joinWith('catalogProductOptions')
            ->orderBy([CatalogProductOptions::tableName() . '.position' => SORT_ASC])
            ->all();
    }

    public static function generateListPageQuery()
    {
        $query = CatalogProduct::find()->from(['t' => CatalogProduct::tableName()])
            ->where(['t.published' => 1])
            ->orderBy(['t.position' => SORT_ASC]);

        if (\Yii::$app->request->get('section')) {
            $query->andWhere([Section::tableName() . '.alias' => \Yii::$app->request->get('section')])
                ->joinWith(['section']);
        }

        if (\Yii::$app->request->get('model')) {
            $query->andWhere([Models::tableName() . '.alias' => \Yii::$app->request->get('model')])
                ->joinWith(['models']);
        }

        if (\Yii::$app->request->get('collection')) {
            $query->andWhere([Collections::tableName() . '.alias' => \Yii::$app->request->get('collection')])
                ->joinWith(['collections']);
        }

        $language = \Yii::$app->params['code'];

	    $query->andWhere(['like', 'visible_store', ',' . $language . ',']);

        return $query;
    }

    public static function registerListPageMetaTags()
    {
        $share_key = 'CatalogProduct';
        if (\Yii::$app->request->get('section')) {
            $section = Section::find()->where(['alias' => \Yii::$app->request->get('section')])->one();
            if (!$section) {
                throw new NotFoundHttpException();
            }
            MetaTagRegister::register($section);
            $share_key = $section->formName() . '_' . $section->id;
        } else {
            MetaTagRegister::registerByKey('models');
        }
        MetaTagRegister::registerSharePreview($share_key);
    }

    public function setupOption1Cookie($option1, $expires)
    {
        if ($option1) {
            setcookie("option1_id", $option1, $expires, '/');
        } elseif ($option1 = $this->getDefaultOption1()) {
            setcookie("option1_id", $option1->id, $expires, '/');
        } elseif (isset($_COOKIE['option1_id'])) {
            setcookie("option1_id", "", null, '/');
        }
    }

    public function setupOption2Cookie($option2, $expires)
    {
        if ($option2) {
            setcookie("option2_id", $option2, $expires, '/');
        } elseif ($option2 = $this->getDefaultOption2()) {
            setcookie("option2_id", $option2->id, $expires, '/');
        } elseif (isset($_COOKIE['option2_id'])) {
            setcookie("option2_id", "", null, '/');
        }
    }

    public function getDefaultOption1()
    {
        return ProductOptions::find()->from(['t' => ProductOptions::tableName()])
            ->where([CatalogProductOptions::tableName() . '.product_id' => $this->id])
            ->andWhere(['t.type_id' => ProductOptions::PRODUCT_OPTIONS_TYPE_MEMORY_SIZE])
            ->andWhere(['t.published' => 1])
            ->joinWith('catalogProductOptions')
            ->orderBy([CatalogProductOptions::tableName() . '.position' => SORT_ASC])
            ->one();
    }

    public function getDefaultOption2()
    {
        return ProductOptions::find()->from(['t' => ProductOptions::tableName()])
            ->where([CatalogProductOptions::tableName() . '.product_id' => $this->id])
            ->andWhere(['t.type_id' => ProductOptions::PRODUCT_OPTIONS_TYPE_COATING])
            ->andWhere(['t.published' => 1])
            ->joinWith('catalogProductOptions')
            ->orderBy([CatalogProductOptions::tableName() . '.position' => SORT_ASC])
            ->one();
    }

    public function getAbsoluteProductUrl()
    {
        return Url::toRoute(['/catalog/catalog/view', 'alias' => $this->alias], true);
    }

    public static function getModel($model)
    {
        if (!$model->is_accessory) {
            switch ($model->section_id) {
                case Section::SECTION_TYPE_PHONE:
                    $model = CatalogPhone::find()->where(['id' => $model->id])->one();
                    break;
                case Section::SECTION_TYPE_CLOCK:
                    $model = CatalogClock::find()->where(['id' => $model->id])->one();
                    break;
            }
        }
        return $model;
    }

    public function getCartText()
    {
        return \Yii::t('front', 'accessory_cart_text');
    }

    public function getLabel()
    {
        return $this->label . ' ' . $this->sub_label;
    }

    public function getSectionLabel()
    {
        $section = $this->section;
        if ($section) {
            return $section->label;
        }
    }

    public function getLabel3()
    {
        $label3 = explode(' ', $this->label_3, 3);
        $label = '';
        foreach ($label3 as $l) {
            if (!empty($l)) {
                $label .= \yii\helpers\Html::tag('div', \yii\helpers\Html::tag('p', $l), ['class' => 'title']);
            }
        }
        return $label;
    }

} 
