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
 * ファイル管理 のページクラス.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 */
class LC_Page_Admin_Contents_FileManager extends LC_Page_Admin_Ex
{
    /** @var array */
    public $arrFileList;

    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->tpl_mainpage = 'contents/file_manager.tpl';
        $this->tpl_mainno = 'contents';
        $this->tpl_subno = 'file';
        $this->tpl_maintitle = 'コンテンツ管理';
        $this->tpl_subtitle = 'ファイル管理';
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
        // フォーム操作クラス
        $objFormParam = new SC_FormParam_Ex();
        // パラメーター情報の初期化
        $this->lfInitParam($objFormParam);
        $objFormParam->setParam($this->createSetParam($_POST));
        $objFormParam->convParam();

        // ファイル管理クラス
        $now_dir = $this->lfCheckSelectDir($objFormParam, $objFormParam->getValue('now_dir'));
        $now_dir = SC_Helper_FileManager_Ex::convertToAbsolutePath($now_dir);
        $objUpFile = new SC_UploadFile_Ex($now_dir, $now_dir);
        // ファイル情報の初期化
        $this->lfInitFile($objUpFile);

        // ファイル操作クラス
        $objFileManager = new SC_Helper_FileManager_Ex();

        switch ($this->getMode()) {
            // フォルダ移動
            case 'move':
                $objFormParam = new SC_FormParam_Ex();
                $this->lfInitParamModeMove($objFormParam);
                $objFormParam->setParam($this->createSetParam($_POST));
                $objFormParam->convParam();

                $this->arrErr = $objFormParam->checkError();
                if (SC_Utils_Ex::isBlank($this->arrErr)) {
                    $now_dir = $this->lfCheckSelectDir($objFormParam, $objFormParam->getValue('tree_select_file'));
                    $objFormParam->setValue('now_dir', $now_dir);
                }
                break;

                // ファイルダウンロード
            case 'download':
                $objFormParam = new SC_FormParam_Ex();
                $this->lfInitParamModeView($objFormParam);
                $objFormParam->setParam($this->createSetParam($_POST));
                $objFormParam->convParam();

                $this->arrErr = $objFormParam->checkError();
                $select_file = SC_Helper_FileManager_Ex::convertToAbsolutePath($objFormParam->getValue('select_file'));
                if (SC_Utils_Ex::isBlank($this->arrErr)) {
                    if (is_dir($select_file)) {
                        $disp_error = '※ ディレクトリをダウンロードすることは出来ません。<br/>';
                        $this->setDispError('select_file', $disp_error);
                    } else {
                        $path_exists = SC_Utils_Ex::checkFileExistsWithInBasePath($select_file, USER_REALDIR);
                        if ($path_exists) {
                            // ファイルダウンロード
                            $objFileManager->sfDownloadFile($select_file);
                            SC_Response_Ex::actionExit();
                        }
                    }
                }
                break;
                // ファイル削除
            case 'delete':
                $objFormParam = new SC_FormParam_Ex();
                $this->lfInitParamModeView($objFormParam);
                $objFormParam->setParam($this->createSetParam($_POST));
                $objFormParam->convParam();
                $this->arrErr = $objFormParam->checkError();
                $select_file = SC_Helper_FileManager_Ex::convertToAbsolutePath($objFormParam->getValue('select_file'));
                if (realpath($select_file) === realpath(USER_REALDIR)) {
                    GC_Utils_Ex::gfPrintLog($select_file.' は削除できません.');
                    $tpl_onload = "alert('user_dataは削除できません');";
                    $this->setTplOnLoad($tpl_onload);

                    break;
                }
                $path_exists = SC_Utils::checkFileExistsWithInBasePath($select_file, USER_REALDIR);
                if (SC_Utils_Ex::isBlank($this->arrErr) && $path_exists) {
                    SC_Helper_FileManager_Ex::deleteFile($select_file);
                }
                break;
                // ファイル作成
            case 'create':
                $objFormParam = new SC_FormParam_Ex();
                $this->lfInitParamModeCreate($objFormParam);
                $objFormParam->setParam($this->createSetParam($_POST));
                $objFormParam->convParam();

                $this->arrErr = $objFormParam->checkError();
                if (SC_Utils_Ex::isBlank($this->arrErr)) {
                    if (!$this->tryCreateDir($objFileManager, $objFormParam)) {
                        $disp_error = '※ '.htmlspecialchars($objFormParam->getValue('create_file'), ENT_QUOTES).'の作成に失敗しました。<br/>';
                        $this->setDispError('create_file', $disp_error);
                    } else {
                        $tpl_onload = "alert('フォルダを作成しました。');";
                        $this->setTplOnLoad($tpl_onload);
                    }
                }
                break;
                // ファイルアップロード
            case 'upload':
                // 画像保存処理
                $ret = $objUpFile->makeTempFile('upload_file', false);
                if (SC_Utils_Ex::isBlank($ret)) {
                    $tpl_onload = "alert('ファイルをアップロードしました。');";
                    $this->setTplOnLoad($tpl_onload);
                } else {
                    $this->setDispError('upload_file', $ret);
                }
                break;
                // 初期表示
            default:
                break;
        }

        // 値をテンプレートに渡す
        $this->arrParam = $objFormParam->getHashArray();
        // 現在の階層がルートディレクトリかどうかテンプレートに渡す
        $this->setIsTopDir($objFormParam);
        // 現在の階層より一つ上の階層をテンプレートに渡す
        $this->setParentDir($objFormParam);
        // 現在いる階層(表示用)をテンプレートに渡す
        $this->setDispPath($objFormParam);
        // 現在のディレクトリ配下のファイル一覧を取得
        $this->arrFileList = $objFileManager->sfGetFileList(SC_Helper_FileManager_Ex::convertToAbsolutePath($objFormParam->getValue('now_dir')));
        // 現在の階層のディレクトリをテンプレートに渡す
        $this->setDispParam('tpl_now_file', $objFormParam->getValue('now_dir'));
        // ディレクトリツリー表示
        $this->setDispTree($objFileManager, $objFormParam);
    }

    /**
     * 初期化を行う.
     *
     * @param  SC_FormParam $objFormParam SC_FormParamインスタンス
     *
     * @return void
     */
    public function lfInitParam(&$objFormParam)
    {
        // 共通定義
        $this->lfInitParamCommon($objFormParam);
    }

    /**
     * ディレクトリ移動時、パラメーター定義
     *
     * @param  SC_FormParam $objFormParam SC_FormParam インスタンス
     *
     * @return void
     */
    public function lfInitParamModeMove(&$objFormParam)
    {
        // 共通定義
        $this->lfInitParamCommon($objFormParam);
        $objFormParam->addParam('選択ファイル', 'select_file', MTEXT_LEN, 'a', []);
    }

    /**
     * ファイル表示時、パラメーター定義
     *
     * @param  SC_FormParam $objFormParam SC_FormParam インスタンス
     *
     * @return void
     */
    public function lfInitParamModeView(&$objFormParam)
    {
        // 共通定義
        $this->lfInitParamCommon($objFormParam);
        $objFormParam->addParam('選択ファイル', 'select_file', MTEXT_LEN, 'a', ['SELECT_CHECK']);
    }

    /**
     * ファイル表示時、パラメーター定義
     *
     * @param  SC_FormParam $objFormParam SC_FormParam インスタンス
     *
     * @return void
     */
    public function lfInitParamModeCreate(&$objFormParam)
    {
        // 共通定義
        $this->lfInitParamCommon($objFormParam);
        $objFormParam->addParam('選択ファイル', 'select_file', MTEXT_LEN, 'a', []);
        $objFormParam->addParam('作成ファイル名', 'create_file', MTEXT_LEN, 'a', ['EXIST_CHECK', 'FILE_NAME_CHECK_BY_NOUPLOAD']);
    }

    /**
     * ファイル表示時、パラメーター定義
     *
     * @param  SC_FormParam $objFormParam SC_FormParam インスタンス
     *
     * @return void
     */
    public function lfInitParamCommon(&$objFormParam)
    {
        $objFormParam->addParam('ルートディレクトリ', 'top_dir', MTEXT_LEN, 'a', []);
        $objFormParam->addParam('現在の階層ディレクトリ', 'now_dir', MTEXT_LEN, 'a', []);
        $objFormParam->addParam('現在の階層ファイル', 'now_file', MTEXT_LEN, 'a', []);
        $objFormParam->addParam('ツリー選択状態', 'tree_status', MTEXT_LEN, 'a', []);
        $objFormParam->addParam('ツリー選択ディレクトリ', 'tree_select_file', MTEXT_LEN, 'a', []);
    }

    /*
     * ファイル情報の初期化
     *
     * @param  object $objUpFile SC_UploadFileインスタンス
     * @return void
     */
    public function lfInitFile(&$objUpFile)
    {
        $objUpFile->addFile('ファイル', 'upload_file', [], FILE_SIZE, true, 0, 0, false);
    }

    /**
     * テンプレートに渡す値を整形する
     *
     * @param  array $arrVal $_POST
     *
     * @return array $setParam テンプレートに渡す値
     */
    public function createSetParam($arrVal)
    {
        $setParam = $arrVal;
        $setParam['top_dir'] = USER_DIR;
        // 初期表示はルートディレクトリ(user_data/)を表示
        if (SC_Utils_Ex::isBlank($this->getMode())) {
            $setParam['now_dir'] = $setParam['top_dir'];
        }

        return $setParam;
    }

    /**
     * テンプレートに値を渡す
     *
     * @param  string $key キー名
     * @param  string $val 値
     *
     * @return void
     */
    public function setDispParam($key, $val)
    {
        $this->$key = $val;
    }

    /**
     * ディレクトリを作成
     *
     * @param  SC_Helper_FileManager_Ex       $objFileManager SC_Helper_FileManager_Exインスタンス
     * @param  SC_FormParam $objFormParam   SC_FormParamインスタンス
     *
     * @return bool      ディレクトリ作成できたかどうか
     */
    public function tryCreateDir($objFileManager, $objFormParam)
    {
        $create_dir_flg = false;
        $now_dir = $this->lfCheckSelectDir($objFormParam, $objFormParam->getValue('now_dir'));
        $objFormParam->setValue('now_dir', $now_dir);
        $create_dir = SC_Helper_FileManager_Ex::convertToAbsolutePath(rtrim($now_dir, '/'));

        // ファイル作成
        if ($objFileManager->sfCreateFile($create_dir.'/'.$objFormParam->getValue('create_file'), 0755)) {
            $create_dir_flg = true;
        }

        return $create_dir_flg;
    }

    /**
     * ファイル表示を行う
     *
     * @param  SC_FormParam $objFormParam SC_FormParamインスタンス
     *
     * @return bool      ファイル表示するかどうか
     */
    public function tryView(&$objFormParam)
    {
        $view_flg = false;
        $now_dir = $this->lfCheckSelectDir($objFormParam, dirname($objFormParam->getValue('select_file')));
        $objFormParam->setValue('now_dir', $now_dir);
        if (!strpos($objFormParam->getValue('select_file'), $objFormParam->getValue('top_dir'))) {
            $view_flg = true;
        }

        return $view_flg;
    }

    /**
     * 現在の階層の一つ上の階層のディレクトリをテンプレートに渡す
     *
     * @param  SC_FormParam $objFormParam SC_FormParamインスタンス
     *
     * @return void
     */
    public function setParentDir($objFormParam)
    {
        $parent_dir = $this->lfGetParentDir($objFormParam->getValue('now_dir'));
        $this->setDispParam('tpl_parent_dir', $parent_dir);
    }

    /**
     * 現在の階層のパスをテンプレートに渡す
     *
     * @param  SC_FormParam $objFormParam SC_FormParamインスタンス
     *
     * @return void
     */
    public function setDispPath($objFormParam)
    {
        // Windows 環境で DIRECTORY_SEPARATOR が JavaScript に渡るとエスケープ文字と勘違いするので置換
        $html_realdir = str_replace(DIRECTORY_SEPARATOR, '/', HTML_REALDIR);
        $arrNowDir = preg_split('/\//', str_replace($html_realdir, '', $objFormParam->getValue('now_dir')));
        $this->setDispParam('tpl_now_dir', SC_Utils_Ex::jsonEncode($arrNowDir));
        $this->setDispParam('tpl_file_path', '');
    }

    /**
     * エラーを表示用の配列に格納
     *
     * @param  string $key   キー名
     * @param  string $value エラー内容
     *
     * @return void
     */
    public function setDispError($key, $value)
    {
        // 既にエラーがある場合は、処理しない
        if (SC_Utils_Ex::isBlank($this->arrErr[$key])) {
            $this->arrErr[$key] = $value;
        }
    }

    /**
     * javascriptをテンプレートに渡す
     *
     * @param  string $tpl_onload javascript
     *
     * @return void
     */
    public function setTplOnLoad($tpl_onload)
    {
        $this->tpl_onload .= $tpl_onload;
    }

    /*
     * 選択ディレクトリがUSER_REALDIR以下かチェック
     *
     * @param  object $objFormParam SC_FormParamインスタンス
     * @param  string $dir          ディレクトリ
     * @return string $select_dir 選択ディレクトリ
     */
    public function lfCheckSelectDir($objFormParam, $dir)
    {
        $select_dir = '';
        $top_dir = $objFormParam->getValue('top_dir');
        // USER_REALDIR以下の場合
        if (preg_match("@^\Q".$top_dir."\E@", $dir) > 0) {
            // 相対パスがある場合、USER_REALDIRを返す.
            if (preg_match("@\Q..\E@", $dir) > 0) {
                $select_dir = $top_dir;
            // 相対パスがない場合、そのままディレクトリパスを返す.
            } else {
                $select_dir = $dir;
            }
        // USER_REALDIR以下でない場合、USER_REALDIRを返す.
        } else {
            $select_dir = $top_dir;
        }

        return $select_dir;
    }

    /**
     * 親ディレクトリ取得
     *
     * @param  string $dir 現在いるディレクトリ
     *
     * @return string $parent_dir 親ディレクトリ
     */
    public function lfGetParentDir($dir)
    {
        $parent_dir = '';
        $dir = rtrim($dir, '/');
        $arrDir = explode('/', $dir);
        array_pop($arrDir);
        foreach ($arrDir as $val) {
            $parent_dir .= "$val/";
        }
        $parent_dir = rtrim($parent_dir, '/');

        return $parent_dir;
    }

    /**
     * ディレクトリツリー生成
     *
     * @param  SC_Helper_FileManager_Ex       $objFileManager SC_Helper_FileManager_Exインスタンス
     * @param  SC_FormParam $objFormParam   SC_FormParamインスタンス
     *
     * @return void
     */
    public function setDispTree($objFileManager, $objFormParam)
    {
        $tpl_onload = '';
        // ツリーを表示する divタグid, ツリー配列変数名, 現在ディレクトリ, 選択ツリーhidden名, ツリー状態hidden名, mode hidden名
        $now_dir = $objFormParam->getValue('now_dir');
        $treeView = "eccube.fileManager.viewFileTree('tree', arrTree, '$now_dir', 'tree_select_file', 'tree_status', 'move');";
        if (!empty($this->tpl_onload)) {
            $tpl_onload .= $treeView;
        } else {
            $tpl_onload = $treeView;
        }
        $this->setTplOnLoad($tpl_onload);

        $tpl_javascript = '';
        $arrTree = $objFileManager->sfGetFileTree(SC_Helper_FileManager_Ex::convertToAbsolutePath($objFormParam->getValue('top_dir')), $objFormParam->getValue('tree_status'));
        $tpl_javascript .= "arrTree = new Array();\n";
        foreach ($arrTree as $arrVal) {
            $arrVal['path'] = SC_Helper_FileManager_Ex::convertToRelativePath($arrVal['path']);
            $tpl_javascript .= 'arrTree['.$arrVal['count'].'] = new Array('.$arrVal['count'].", '".$arrVal['type']."', '".$arrVal['path']."', ".$arrVal['rank'].',';
            if ($arrVal['open']) {
                $tpl_javascript .= "true);\n";
            } else {
                $tpl_javascript .= "false);\n";
            }
        }
        $this->setDispParam('tpl_javascript', $tpl_javascript);
    }

    /**
     * 現在の階層がルートディレクトリかどうかテンプレートに渡す
     *
     * @param  SC_FormParam $objFormParam SC_FormParamインスタンス
     *
     * @return void
     */
    public function setIsTopDir($objFormParam)
    {
        // トップディレクトリか調査
        $is_top_dir = false;
        // 末尾の/をとる
        $top_dir_check = rtrim($objFormParam->getValue('top_dir'), '/');
        $now_dir_check = rtrim($objFormParam->getValue('now_dir'), '/');
        if ($top_dir_check == $now_dir_check) {
            $is_top_dir = true;
        }
        $this->setDispParam('tpl_is_top_dir', $is_top_dir);
    }
}
