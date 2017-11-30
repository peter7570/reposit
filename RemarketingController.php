<?php
namespace frontend\controllers;

use frontend\modules\catalog\models\FbPixel;
use frontend\modules\catalog\models\Section;
use Yii;
use yii\helpers\Url;
use frontend\components\FrontController;
use frontend\modules\catalog\models\CatalogProduct;
use yii\web\NotFoundHttpException;

class RemarketingController extends FrontController
{

    /**
     * Index page with links
     *
     * @return string
     */
    public function actionIndex()
    {
        \Yii::$app->view->registerMetaTag([
            'name' => 'robots',
            'content' => 'noindex, nofollow'
        ]);

        return $this->render('index');
    }

    public function actionCsv()
    {
        $params = $this->getParams();
        $products = $this->getProducts($params);
        return $this->googleCsv($products, $params);
    }

    public function actionCsvFb()
    {
        $params = $this->getParams();
        $products = $this->getProducts($params);
        return $this->facebookCsv($products, $params);
    }

    public function actionRontarXml()
    {
        $params = $this->getParams();
        $products = $this->getProducts($params);
        $this->rontarXml($products, $params);
    }

    public function getParams()
    {
        $category = Yii::$app->request->get('category');
        $sale = Yii::$app->request->get('sale');

        $params = [
            'section' => $category,
            'section_id' => $this->getSectionByAlias($category),
            'sale' => $sale,
            'dataCamp' => \Yii::$app->config->get('start_rem_camp'),

            'source' => Yii::$app->request->get('source'),
            'lang' => Yii::$app->request->get('lang'),
        ];

        return $params;
    }

    protected function getSectionByAlias($alias)
    {
        return Section::find()->select('id')->where(['alias' => $alias])->scalar();
    }

    protected function getProducts($params)
    {
        $query = CatalogProduct::find()->with(['collections', 'models'])->isPublished();

        if(!empty($params['section_id'])) {
            $query->andWhere(['section_id' => $params['section_id']]);
        }

        return $query->all();
    }

    protected function googleCsv($models, $params)
    {
        $arrayCsv = array();
        $headerCsv = array('"ID"', '"ID2"', '"Item title"', '"Final URL"', '"Image URL"', '"Item subtitle"', '"Item description"', '"Item category"', '"Price"', '"Sale price"');
        array_push($arrayCsv, $headerCsv);

        foreach ($models as $item) {

            $salePrice = '';
            if ($params["sale"] && $item->new_price) {
                $salePrice = $item->new_price . ' USD';

                $image = Url::base(true) . \metalguardian\fileProcessor\helpers\FPM::src($item->image_id, 'catalog', 'modelTop');
                $title = substr($item->label, 0, 24);
                $productArr = array(
                    $item->id . '-' . Yii::$app->language,
                    $item->alias,
                    $title,
                    Url::base(true) . '/' . Yii::$app->language . '/models/' . $item->alias,
                    $image,
                    $item->sub_label,
                    '"' . htmlspecialchars(trim(preg_replace('/\s\s+/', ' ',  $item->description))) . '"',
                    $params["section"],
                    $item->price . ' USD',
                    $salePrice
                );
                array_push($arrayCsv, $productArr);
            } elseif(empty($params["sale"])) {
                $image = Url::base(true) . \metalguardian\fileProcessor\helpers\FPM::src($item->image_id, 'catalog', 'modelTop');
                $title = substr($item->label, 0, 24);
                $productArr = array(
                    $item->id . '-' . Yii::$app->language,
                    $item->alias,
                    $title,
                    Url::base(true) . '/' . Yii::$app->language . '/models/' . $item->alias,
                    $image,
                    $item->sub_label,
                    '"' . htmlspecialchars(trim(preg_replace('/\s\s+/', ' ',  $item->description))) . '"',
                    $params["section"],
                    $item->price . ' USD',
                    $salePrice
                );
                array_push($arrayCsv, $productArr);
            }

        }

        $saleVal = empty($params['sale']) ? '' : '_sale';
        $section = !empty($params['section']) ? $params['section'] : 'all';
        $fileName = 'r_' . $section . '_' . Yii::$app->language . $saleVal;

        header('Content-type: text/csv; charset=utf-8;');
        header('Content-Disposition: attachment; filename=' . $fileName . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF"; //UTF-8 BOM

        $content = '';
        foreach ($arrayCsv as $item) {
            $content .= implode(';', $item);
            $content .= "\r\n";
        }
        echo $content;
    }

    protected function facebookCsv($models, $params)
    {
        $baseUrl = Url::base(true);
        $headerCsv = ['id', 'title', 'description', 'price', 'availability', 'condition', 'image_link', 'link', 'brand'];

        $productData = [];

        foreach ($models as $item) {
            if ($params['sale'] != 0) {
                if(($params['sale'] == '1' && $item->new_price == 0)
                    || ($params['sale'] == '-1' && $item->new_price > 0)
                ) {
                    continue;
                }
            }

            $id = $item->id;
            if (FbPixel::CONCAT_LANG && !empty($params['lang'])) {
                $id .= '-' . $params['lang'];
            }

            $productData[] = [
                'id' => $id,
                'title' => substr($item->label, 0, 100),
                'description' => iconv('utf-8', 'windows-1251', $item->description),
                'price' => !empty($item->new_price) ? $item->new_price : $item->price,
                'availability' => 'in stock',
                'condition' => 'new',
                'image_link ' => $baseUrl . \metalguardian\fileProcessor\helpers\FPM::src($item->image_id, 'catalog', 'feed'),
                'link' => Url::to($item->getPageUrl(['language' => Yii::$app->language]), true) . $this->getFacebookUtm($item, $params),
                'brand' => 'Noblesse',
            ];
        }

        if ($params['sale'] == 1) {
            $saleVal = '_sale';
        } elseif ($params['sale'] == '-1') {
            $saleVal = '_nonsale';
        } else {
            $saleVal = '';
        }

        $langVal = empty($params['lang']) ? '' : '_' . $params['lang'];
        $fileName = 'f_' . $params['section'] . '_' . Yii::$app->language . $langVal . $saleVal;

        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename=' . $fileName . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        //echo "\xEF\xBB\xBF"; //UTF-8 BOM

        $out = fopen('php://output', 'w');
        fputcsv($out, $headerCsv, ',');
        foreach ($productData as $row) {
            fputcsv($out, $row, ',');
        }

        fclose($out);
    }

    protected function getArrayXml($models, $params)
    {
        $arrayXml = array();

        $i = 0;
        foreach ($models as $item) {
            $model = CatalogProduct::getModel($item);

            $paramsUrl = $this->getRontarUtm($model, $params);

            $url = Url::base(true) . '/' . Yii::$app->language . '/models/' . $item->alias.$paramsUrl;
            $image = Url::base(true) . \metalguardian\fileProcessor\helpers\FPM::src($item->image_id, 'catalog', 'modelTop');

            $arrayXml[$i]["id"] = $item->id;
            $arrayXml[$i]["url"] = $url;
            $arrayXml[$i]["price"] = $item->price;
            $arrayXml[$i]["categoryId"] = $item->section_id;
            $arrayXml[$i]["picture"] = Url::base(true) . $image;
            $arrayXml[$i]["name"] = $item->label;
            $arrayXml[$i]["description"] = $item->description;

            $i++;
        }

        return $arrayXml;
    }

    protected function rontarXml($products, $params)
    {

        header("Content-type: text/xml");
        header("Content-Disposition: attachment; filename=rontar.xml");
        header("Pragma: no-cache");
        header("Expires: 0");

        $dom = new \DomDocument("1.0", "windows-1251");
        $root = $dom->createElement("xml_catalog");
        $root->setAttribute("date", date("Y-m-d H:i"));
        $subRoot = $dom->createElement("shop");
        $root->appendChild($subRoot);
        $dom->appendChild($root);

        // currencies
        $currencies = $dom->createElement("currencies");

        $currency = $dom->createElement("currency");
        $currency->setAttribute("id", "USD");
        $currency->setAttribute("rate", "1");

        $currencies->appendChild($currency);
        $subRoot->appendChild($currencies);

        //categories
        $categories = $dom->createElement("categories");

        $category = $dom->createElement("category", "Accessories");
        $category->setAttribute("id", "0");
        $categories->appendChild($category);

        $category = $dom->createElement("category", "Phones");
        $category->setAttribute("id", "1");
        $categories->appendChild($category);

        $category = $dom->createElement("category", "Watches");
        $category->setAttribute("id", "2");
        $categories->appendChild($category);

        $subRoot->appendChild($categories);

        //offers
        $offers = $dom->createElement("offers");

        $arrOffers = $this->getArrayXml($products, $params);
        foreach ($arrOffers as $item) {

            //offer
            $offer = $dom->createElement("offer");
            $offer->setAttribute("id", $item["id"]);

            $cdata = $dom->createCDATASection("'".$item["url"]."'");
            $url = $dom->createElement("url");
            $url->appendChild($cdata);
            $offer->appendChild($url);

            $price = $dom->createElement("price", $item["price"]);
            $offer->appendChild($price);

            $categoryId = $dom->createElement("categoryId", $item["categoryId"]);
            $offer->appendChild($categoryId);

            $picture = $dom->createElement("picture", $item["picture"]);
            $offer->appendChild($picture);

            $name = $dom->createElement("name", $item["name"]);
            $offer->appendChild($name);

            $description = $dom->createElement("description", $item["description"]);
            $offer->appendChild($description);

            $offers->appendChild($offer);
        }

        $subRoot->appendChild($offers);

        print $dom->saveXML();
    }

    protected function getRontarUtm($model, $params)
    {
        $optionsModel = '';
        if($model->section_id == 1)
            $optionsModel = 'iphone-';
        elseif($model->section_id == 2)
            $optionsModel = 'watch-';

        $formatName = trim($model->label);
        $formatName = str_replace(' ', '-', $formatName);
        $formatName =  mb_strtolower($formatName);

        if($model->collections) {
            $collection = mb_strtolower($model->collections->label);
            $collection = trim($collection);
            $collectionContent = "-".$collection;
        } else {
            $collection = '';
            $collectionContent = '';
        }

        $data[] = 'utm_source=rontar';
        $data[] = 'utm_medium=cpc';
        $data[] = 'utm_campaign=rontar-cpc-'.$optionsModel.$params['dataCamp'];
        $data[] = 'utm_term='.$collection;
        $data[] = 'utm_content='.$formatName.$collectionContent;

        return '?' . implode('&', $data);
    }

    protected function getFacebookUtm($item, $params)
    {
        if (!empty($item->model_id)) {
            $modelName = trim($item->models->label);
            $modelName = str_replace(' ', '-', $modelName);
            $modelName =  mb_strtolower($modelName);

        } else {
            $modelName = '';
        }

        if(!empty($params['lang'])) {
            $lang = $params['lang'] . '-';
        } else {
            $lang = '';
        }

        if ($params['sale']) {
            $cmp = $params['source'] . '-cpc-' . $lang . 'dynamic-sale-' . $params['dataCamp'];
        } else {
            $cmp = $params['source'] . '-cpc-' . $lang . 'dynamic-' . $params['dataCamp'];
        }

        $data[] = 'utm_source=' . $params['source'];
        $data[] = 'utm_medium=cpc';
        $data[] = 'utm_term=' . $item->id . '-' . $item->alias;
        $data[] = 'utm_content=' . $modelName;
        $data[] = 'utm_campaign=' . $cmp;

        return '?' . implode('&', $data);
    }
}
