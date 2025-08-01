<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * 支払い方法選択 のページクラス.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 */
class LC_Page_Shopping_Payment extends LC_Page_Ex
{
    /** @var bool */
    public $is_single_deliv;
    /** @var bool */
    public $is_download;
    /** @var string */
    public $tpl_back_url;
    /** @var bool */
    public $img_show;

    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->tpl_onload = 'eccube.togglePointForm();';
        $this->tpl_title = 'お支払方法・お届け時間等の指定';
        $masterData = new SC_DB_MasterData_Ex();
        $this->arrPref = $masterData->getMasterData('mtb_pref');
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    public function process()
    {
        parent::process();
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    public function action()
    {
        // 決済処理中ステータスのロールバック
        $objPurchase = new SC_Helper_Purchase_Ex();
        $objPurchase->cancelPendingOrder(PENDING_ORDER_CANCEL_FLAG);

        $objSiteSess = new SC_SiteSession_Ex();
        $objCartSess = new SC_CartSession_Ex();
        $objCustomer = new SC_Customer_Ex();
        $objFormParam = new SC_FormParam_Ex();
        $objDelivery = new SC_Helper_Delivery_Ex();

        $this->is_multiple = $objPurchase->isMultiple();

        // カートの情報を取得
        $this->arrShipping = $objPurchase->getShippingTemp($this->is_multiple);

        $this->tpl_uniqid = $objSiteSess->getUniqId();
        $cart_key = $objCartSess->getKey();
        $this->cartKey = $cart_key;
        $objPurchase->verifyChangeCart($this->tpl_uniqid, $objCartSess);

        // 配送業者を取得
        $this->arrDeliv = $objDelivery->getList($cart_key);
        $this->is_single_deliv = $this->isSingleDeliv($this->arrDeliv);
        $this->is_download = ($this->cartKey == PRODUCT_TYPE_DOWNLOAD);

        // 会員情報の取得
        if ($objCustomer->isLoginSuccess(true)) {
            $this->tpl_login = '1';
            $this->tpl_user_point = $objCustomer->getValue('point');
            $this->arrCustomer = $objCustomer->getValues();

            // 旧テンプレート互換
            $this->name01 = $this->arrCustomer['name01'] ?? '';
            $this->name02 = $this->arrCustomer['name02'] ?? '';
        }

        // 戻り URL の設定
        // @deprecated 2.12.0 テンプレート直書きに戻した
        $this->tpl_back_url = '?mode=return';

        $arrOrderTemp = $objPurchase->getOrderTemp($this->tpl_uniqid);
        // 正常に受注情報が格納されていない場合はカート画面へ戻す
        if (SC_Utils_Ex::isBlank($arrOrderTemp)) {
            SC_Response_Ex::sendRedirect(CART_URL);
            SC_Response_Ex::actionExit();
        }

        // カート内商品の妥当性チェック
        $this->tpl_message = $objCartSess->checkProducts($cart_key);
        if (strlen($this->tpl_message) >= 1) {
            SC_Response_Ex::sendRedirect(CART_URL);
            SC_Response_Ex::actionExit();
        }

        /*
         * 購入金額の取得
         * ここでは送料を加算しない
         */
        $this->arrPrices = $objCartSess->calculate($cart_key, $objCustomer);

        // お届け日一覧の取得
        $this->arrDelivDate = $objPurchase->getDelivDate($objCartSess, $cart_key);

        switch ($this->getMode()) {
            /*
             * 配送業者選択時のアクション
             * モバイル端末以外の場合は, JSON 形式のデータを出力し, ajax で取得する.
             */
            case 'select_deliv':
                $this->setFormParams($objFormParam, $arrOrderTemp, true, $this->arrShipping);
                $objFormParam->setParam($_POST);
                $this->arrErr = $objFormParam->checkError();
                if (SC_Utils_Ex::isBlank($this->arrErr)) {
                    $deliv_id = $objFormParam->getValue('deliv_id');
                    $arrSelectedDeliv = $this->getSelectedDeliv($objCartSess, $deliv_id);
                    $arrSelectedDeliv['error'] = false;
                } else {
                    $arrSelectedDeliv = ['error' => true];
                    $this->tpl_mainpage = 'shopping/select_deliv.tpl'; // モバイル用
                }

                if (SC_Display_Ex::detectDevice() != DEVICE_TYPE_MOBILE) {
                    echo SC_Utils_Ex::jsonEncode($arrSelectedDeliv);
                    SC_Response_Ex::actionExit();
                } else {
                    $this->arrPayment = $arrSelectedDeliv['arrPayment'];
                    $this->arrDelivTime = $arrSelectedDeliv['arrDelivTime'];
                }
                break;

                // 登録処理
            case 'confirm':
                // パラメーター情報の初期化
                $this->setFormParams($objFormParam, $_POST, $this->is_download, $this->arrShipping);
                $this->arrErr = $this->lfCheckError($objFormParam, $this->arrPrices['subtotal'], $this->tpl_user_point);

                $deliv_id = $objFormParam->getValue('deliv_id');
                if ((string) $deliv_id !== (string) ((int) $deliv_id)) {
                    $deliv_id = $this->arrDeliv[0]['deliv_id'];
                    $objFormParam->setValue('deliv_id', $deliv_id);
                }

                $arrSelectedDeliv = $this->getSelectedDeliv($objCartSess, $deliv_id);
                $this->arrPayment = $arrSelectedDeliv['arrPayment'];
                $this->arrDelivTime = $arrSelectedDeliv['arrDelivTime'];
                $this->img_show = $arrSelectedDeliv['img_show'];

                if (empty($this->arrErr)) {
                    $this->saveShippings($objFormParam, $this->arrDelivTime);
                    $this->lfRegistData($this->tpl_uniqid, $objFormParam->getDbArray(), $objPurchase, $this->arrPayment);

                    // 正常に登録されたことを記録しておく
                    $objSiteSess->setRegistFlag();

                    // 確認ページへ移動
                    SC_Response_Ex::sendRedirect(SHOPPING_CONFIRM_URLPATH);
                    SC_Response_Ex::actionExit();
                }

                break;

                // 前のページに戻る
            case 'return':
                // 正常な推移であることを記録しておく
                $objSiteSess->setRegistFlag();

                $url = null;
                if ($this->is_multiple) {
                    $url = MULTIPLE_URLPATH.'?from=multiple';
                } elseif ($objCustomer->isLoginSuccess(true)) {
                    if ($this->cartKey == PRODUCT_TYPE_DOWNLOAD) {
                        $url = CART_URL;
                    } else {
                        $url = DELIV_URLPATH;
                    }
                } else {
                    $url = SHOPPING_URL.'?from=nonmember';
                }

                SC_Response_Ex::sendRedirect($url);
                SC_Response_Ex::actionExit();
                break;

            default:
                // FIXME 前のページから戻ってきた場合は別パラメーター(mode)で処理分岐する必要があるのかもしれない
                $this->setFormParams($objFormParam, $arrOrderTemp, $this->is_download, $this->arrShipping);

                if (!$this->is_single_deliv) {
                    $deliv_id = $objFormParam->getValue('deliv_id');
                } else {
                    $deliv_id = $this->arrDeliv[0]['deliv_id'];
                }

                if (!SC_Utils_Ex::isBlank($deliv_id)) {
                    $objFormParam->setValue('deliv_id', $deliv_id);
                    $arrSelectedDeliv = $this->getSelectedDeliv($objCartSess, $deliv_id);
                    $this->arrPayment = $arrSelectedDeliv['arrPayment'];
                    $this->arrDelivTime = $arrSelectedDeliv['arrDelivTime'];
                    $this->img_show = $arrSelectedDeliv['img_show'];
                }
                break;
        }

        // モバイル用 ポストバック処理
        if (SC_Display_Ex::detectDevice() == DEVICE_TYPE_MOBILE
            && SC_Utils_Ex::isBlank($this->arrErr)) {
            $this->tpl_mainpage = $this->getMobileMainpage($this->is_single_deliv, $this->getMode());
        }

        $this->arrForm = $objFormParam->getFormParamList();
    }

    /**
     * パラメーターの初期化を行い, 初期値を設定する.
     *
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @param array        $arrParam     設定する値の配列
     * @param bool      $deliv_only   deliv_id チェックのみの場合 true
     * @param array        $arrShipping  配送先情報の配列
     */
    public function setFormParams(&$objFormParam, $arrParam, $deliv_only, &$arrShipping)
    {
        $this->lfInitParam($objFormParam, $deliv_only, $arrShipping);
        $objFormParam->setParam($arrParam);
        $objFormParam->convParam();
    }

    /**
     * パラメーター情報の初期化を行う.
     *
     * @param  SC_FormParam $objFormParam SC_FormParam インスタンス
     * @param  bool      $deliv_only   必須チェックは deliv_id のみの場合 true
     * @param  array        $arrShipping  配送先情報の配列
     *
     * @return void
     */
    public function lfInitParam(&$objFormParam, $deliv_only, &$arrShipping)
    {
        $objFormParam->addParam('配送業者', 'deliv_id', INT_LEN, 'n', ['EXIST_CHECK', 'MAX_LENGTH_CHECK', 'NUM_CHECK']);
        $objFormParam->addParam('ポイント', 'use_point', INT_LEN, 'n', ['MAX_LENGTH_CHECK', 'NUM_CHECK', 'ZERO_START']);
        $objFormParam->addParam('その他お問い合わせ', 'message', LTEXT_LEN, 'KVa', ['SPTAB_CHECK', 'MAX_LENGTH_CHECK']);
        $objFormParam->addParam('ポイントを使用する', 'point_check', INT_LEN, 'n', ['MAX_LENGTH_CHECK', 'NUM_CHECK'], '2');

        if ($deliv_only) {
            $objFormParam->addParam('お支払い方法', 'payment_id', INT_LEN, 'n', ['MAX_LENGTH_CHECK', 'NUM_CHECK']);
        } else {
            $objFormParam->addParam('お支払い方法', 'payment_id', INT_LEN, 'n', ['EXIST_CHECK', 'MAX_LENGTH_CHECK', 'NUM_CHECK']);

            foreach ($arrShipping as $val) {
                $objFormParam->addParam('お届け時間', 'deliv_time_id'.$val['shipping_id'], INT_LEN, 'n', ['MAX_LENGTH_CHECK', 'NUM_CHECK']);
                $objFormParam->addParam('お届け日', 'deliv_date'.$val['shipping_id'], STEXT_LEN, 'KVa', ['MAX_LENGTH_CHECK']);
            }
        }

        $objFormParam->setParam($arrShipping);
        $objFormParam->convParam();
    }

    /**
     * 入力内容のチェックを行なう.
     *
     * @param  SC_FormParam $objFormParam SC_FormParam インスタンス
     * @param  int      $subtotal     購入金額の小計
     * @param  int      $max_point    会員の保持ポイント
     *
     * @return array        入力チェック結果の配列
     */
    public function lfCheckError(&$objFormParam, $subtotal, $max_point)
    {
        // 入力データを渡す。
        $arrForm = $objFormParam->getHashArray();
        $objErr = new SC_CheckError_Ex($arrForm);
        $objErr->arrErr = $objFormParam->checkError();

        if (USE_POINT === false) {
            return $objErr->arrErr;
        }

        $objErr->doFunc(['ポイントを使用する', 'point_check'], ['EXIST_CHECK']);

        if ($arrForm['point_check'] == '1'
            && SC_Utils_Ex::isBlank($objErr->arrErr['use_point'] ?? '')) {
            $objErr->doFunc(['ポイント', 'use_point'], ['EXIST_CHECK']);
            if ($max_point == '') {
                $max_point = 0;
            }
            // FIXME mobile 互換のため br は閉じない...
            if ($arrForm['use_point'] > $max_point) {
                $objErr->arrErr['use_point'] = '※ ご利用ポイントが所持ポイントを超えています。<br>';
            }
            if (($arrForm['use_point'] * POINT_VALUE) > $subtotal) {
                $objErr->arrErr['use_point'] = '※ ご利用ポイントがご購入金額を超えています。<br>';
            }
            // ポイント差し引き後のお支払い方法チェック
            $objPayment = new SC_Helper_Payment_Ex();
            $arrPayments = $objPayment->get($arrForm['payment_id']);
            if ($arrPayments['rule_max'] > $subtotal - $arrForm['use_point'] * POINT_VALUE) {
                $objErr->arrErr['use_point'] = '※ 選択したお支払い方法では、ポイントは'.($subtotal - $arrPayments['rule_max']).'ポイントまでご利用いただけます。<br>';
            }
        }

        return $objErr->arrErr;
    }

    /**
     * 配送情報を保存する.
     *
     * @param SC_FormParam $objFormParam SC_FormParam インスタンス
     * @param array        $arrDelivTime 配送時間の配列
     */
    public function saveShippings(&$objFormParam, $arrDelivTime)
    {
        // ダウンロード商品の場合は配送先が存在しない
        if ($this->is_download) {
            return;
        }

        $deliv_id = $objFormParam->getValue('deliv_id');
        /* TODO
         * SC_Purchase::getShippingTemp() で取得して,
         * リファレンスで代入すると, セッションに添字を追加できない？
         */
        foreach (array_keys($_SESSION['shipping']) as $key) {
            $shipping_id = $_SESSION['shipping'][$key]['shipping_id'];
            $time_id = $objFormParam->getValue('deliv_time_id'.$shipping_id);
            $_SESSION['shipping'][$key]['deliv_id'] = $deliv_id;
            $_SESSION['shipping'][$key]['time_id'] = $time_id;
            if (isset($arrDelivTime[$time_id])) {
                $_SESSION['shipping'][$key]['shipping_time'] = $arrDelivTime[$time_id];
            } else {
                $_SESSION['shipping'][$key]['shipping_time'] = '';
            }
            $_SESSION['shipping'][$key]['shipping_date'] = $objFormParam->getValue('deliv_date'.$shipping_id);
        }
    }

    /**
     * 受注一時テーブルへ登録を行う.
     *
     * @param  int            $uniqid      受注一時テーブルのユニークID
     * @param  array              $arrForm     フォームの入力値
     * @param  SC_Helper_Purchase $objPurchase SC_Helper_Purchase インスタンス
     * @param  array              $arrPayment  お支払い方法の配列
     *
     * @return void
     */
    public function lfRegistData($uniqid, $arrForm, &$objPurchase, $arrPayment)
    {
        $arrForm['order_temp_id'] = $uniqid;
        $arrForm['update_date'] = 'CURRENT_TIMESTAMP';

        if ($arrForm['point_check'] != '1') {
            $arrForm['use_point'] = 0;
        }

        $objDelivery = new SC_Helper_Delivery();
        $validDelivery = array_filter($objDelivery->getList($this->cartKey), function ($delivery) use ($arrForm) {
            return $arrForm['deliv_id'] == $delivery['deliv_id'];
        });
        if (empty($validDelivery)) {
            trigger_error('無効な配送方法: '.var_export($arrForm['deliv_id'], true), E_USER_ERROR);
        }

        $find = false;
        foreach ($arrPayment as $payment) {
            if ($arrForm['payment_id'] == $payment['payment_id']) {
                $arrForm['charge'] = $payment['charge'];
                $arrForm['payment_method'] = $payment['payment_method'];
                $find = true;
                break;
            }
        }
        if (!$find) {
            trigger_error('無効な支払方法: '.var_export($arrForm['payment_id'], true), E_USER_ERROR);
        }

        $objPurchase->saveOrderTemp($uniqid, $arrForm);
    }

    /**
     * 配送業者IDから, 支払い方法, お届け時間の配列を取得する.
     *
     * 結果の連想配列の添字の値は以下の通り
     * - 'arrDelivTime' - お届け時間の配列
     * - 'arrPayment' - 支払い方法の配列
     * - 'img_show' - 支払い方法の画像の有無
     *
     * @param  SC_CartSession $objCartSess SC_CartSession インスタンス
     * @param  int        $deliv_id    配送業者ID
     *
     * @return array          支払い方法, お届け時間を格納した配列
     */
    public function getSelectedDeliv(&$objCartSess, $deliv_id)
    {
        $arrResults = [];
        if ((string) $deliv_id === (string) ((int) $deliv_id)) {
            $arrResults['arrDelivTime'] = SC_Helper_Delivery_Ex::getDelivTime($deliv_id);
            $total = $objCartSess->getAllProductsTotal($objCartSess->getKey());
            $payments_deliv = SC_Helper_Delivery_Ex::getPayments($deliv_id);
            $objPayment = new SC_Helper_Payment_Ex();
            $payments_total = $objPayment->getByPrice($total);
            $arrPayment = [];
            foreach ($payments_total as $payment) {
                if (in_array($payment['payment_id'], $payments_deliv)) {
                    $arrPayment[] = $payment;
                }
            }
            $arrResults['arrPayment'] = $arrPayment;
            $arrResults['img_show'] = $this->hasPaymentImage($arrResults['arrPayment']);
        }

        return $arrResults;
    }

    /**
     * 支払い方法の画像があるかどうか.
     *
     * @param  array   $arrPayment 支払い方法の配列
     *
     * @return bool 支払い方法の画像がある場合 true
     */
    public function hasPaymentImage($arrPayment)
    {
        foreach ($arrPayment as $val) {
            if (!SC_Utils_Ex::isBlank($val['payment_image'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 配送業者が1社のみかどうか.
     *
     * @param  array   $arrDeliv 配送業者の配列
     *
     * @return bool 配送業者が1社のみの場合 true
     */
    public function isSingleDeliv($arrDeliv)
    {
        if (count($arrDeliv) == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * モバイル用テンプレートのパスを取得する.
     *
     * @param  bool $is_single_deliv 配送業者が1社の場合 true
     * @param  string  $mode            フォームパラメーター 'mode' の文字列
     *
     * @return string  モバイル用テンプレートのパス
     */
    public function getMobileMainpage($is_single_deliv = true, $mode = null)
    {
        switch ($mode) {
            case 'select_deliv':
                return 'shopping/payment.tpl';

            case 'confirm':
            case 'return':
            default:
                if ($is_single_deliv) {
                    return 'shopping/payment.tpl';
                } else {
                    return 'shopping/select_deliv.tpl';
                }
                break;
        }
    }
}
