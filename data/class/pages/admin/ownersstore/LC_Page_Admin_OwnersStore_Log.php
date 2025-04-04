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
 * オーナーズストア：インストールログ のページクラス.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 */
class LC_Page_Admin_OwnersStore_Log extends LC_Page_Admin_Ex
{
    /** @var array */
    public $arrLogDetail;
    /** @var array */
    public $arrInstallLogs;

    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->tpl_mainpage = 'ownersstore/log.tpl';
        $this->tpl_mainno = 'ownersstore';
        $this->tpl_subno = 'log';
        $this->tpl_maintitle = 'オーナーズストア';
        $this->tpl_subtitle = 'ログ管理';
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
        switch ($this->getMode()) {
            case 'detail':
                $objForm = $this->initParam();
                if ($objForm->checkError()) {
                    SC_Utils_Ex::sfDispError('');
                }
                $this->arrLogDetail = $this->getLogDetail($objForm->getValue('log_id'));
                if (count($this->arrLogDetail) == 0) {
                    SC_Utils_Ex::sfDispError('');
                }
                $this->tpl_mainpage = 'ownersstore/log_detail.tpl';
                break;
            default:
                break;
        }
        $this->arrInstallLogs = $this->getLogs();
    }

    public function getLogs()
    {
        $sql = <<<END
            SELECT
                *
            FROM
                dtb_module_update_logs JOIN (
                SELECT
                    module_id,
                    module_name
                FROM
                    dtb_module
                ) AS modules ON dtb_module_update_logs.module_id = modules.module_id
            ORDER BY update_date DESC
            END;
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $arrRet = $objQuery->getAll($sql);

        return $arrRet;
    }

    public function initParam()
    {
        $objForm = new SC_FormParam_Ex();
        $objForm->addParam('log_id', 'log_id', INT_LEN, '', ['EXIST_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK']);
        $objForm->setParam($_GET);

        return $objForm;
    }

    public function getLogDetail($log_id)
    {
        $sql = <<<END
            SELECT
                *
            FROM
                dtb_module_update_logs JOIN (
                SELECT
                    module_id,
                    module_name
                FROM
                    dtb_module
                ) AS modules ON dtb_module_update_logs.module_id = modules.module_id
            WHERE
                log_id = ?
            END;
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $arrRet = $objQuery->getAll($sql, [$log_id]);

        return $arrRet[0] ?? [];
    }
}
