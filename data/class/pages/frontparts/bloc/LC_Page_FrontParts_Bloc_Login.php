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
 * ログイン のページクラス.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 */
class LC_Page_FrontParts_Bloc_Login extends LC_Page_FrontParts_Bloc_Ex
{
    /** @var bool */
    public $tpl_disable_logout;

    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->tpl_login = false;
        $this->tpl_disable_logout = false;
        $this->httpCacheControl('nocache');
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    public function process()
    {
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
        $objCustomer = new SC_Customer_Ex();
        // クッキー管理クラス
        $objCookie = new SC_Cookie_Ex();

        // ログイン判定
        if ($objCustomer->isLoginSuccess()) {
            $this->tpl_login = true;
            $this->tpl_user_point = $objCustomer->getValue('point');
            $this->arrCustomer = $objCustomer->getValues();

            // 旧テンプレート互換
            $this->tpl_name1 = $this->arrCustomer['name01'] ?? '';
            $this->tpl_name2 = $this->arrCustomer['name02'] ?? '';
        } else {
            // クッキー判定
            $this->tpl_login_email = $objCookie->getCookie('login_email');
            if ($this->tpl_login_email != '') {
                $this->tpl_login_memory = '1';
            }
            // POSTされてきたIDがある場合は優先する。
            if (isset($_POST['login_email']) && $_POST['login_email'] != '') {
                $this->tpl_login_email = $_POST['login_email'];
            }
        }

        $this->tpl_disable_logout = $this->lfCheckDisableLogout();
        // スマートフォン版ログアウト処理で不正なページ移動エラーを防ぐ為、トークンをセット
        $this->transactionid = SC_Helper_Session_Ex::getToken();
    }

    /**
     * lfCheckDisableLogout.
     *
     * @return bool
     */
    public function lfCheckDisableLogout()
    {
        $masterData = new SC_DB_MasterData_Ex();
        $arrDisableLogout = $masterData->getMasterData('mtb_disable_logout');

        $current_page = $_SERVER['SCRIPT_NAME'];

        foreach ($arrDisableLogout as $val) {
            if ($current_page == $val) {
                return true;
            }
        }

        return false;
    }
}
