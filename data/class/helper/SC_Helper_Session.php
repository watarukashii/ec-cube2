<?php
/*
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 */

/**
 * セッション関連のヘルパークラス.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 */
class SC_Helper_Session
{
    public $objDb;

    /**
     * デフォルトコンストラクタ.
     *
     * 各関数をセッションハンドラに保存する
     */
    public function __construct()
    {
        $this->objDb = new SC_Helper_DB_Ex();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_save_handler(
                [&$this, 'sfSessOpen'],
                [&$this, 'sfSessClose'],
                [&$this, 'sfSessRead'],
                [&$this, 'sfSessWrite'],
                [&$this, 'sfSessDestroy'],
                [&$this, 'sfSessGc']
            );
        }

        // 通常よりも早い段階(オブジェクトが破棄される前)でセッションデータを書き込んでセッションを終了する
        // XXX APC による MDB2 の破棄タイミングによる不具合を回避する目的
        register_shutdown_function('session_write_close');
    }

    /**
     * セッションを開始する.
     *
     * @param  string $save_path    セッションを保存するパス(使用しない)
     * @param  string $session_name セッション名(使用しない)
     *
     * @return bool   セッションが正常に開始された場合 true
     */
    public function sfSessOpen($save_path, $session_name)
    {
        return true;
    }

    /**
     * セッションを閉じる.
     *
     * @return bool セッションが正常に終了した場合 true
     */
    public function sfSessClose()
    {
        return true;
    }

    /**
     * セッションのデータをDBから読み込む.
     *
     * @param  string $id セッションID
     *
     * @return string セッションデータの値
     */
    public function sfSessRead($id)
    {
        // SameSite=None を未サポート UA 向け対応
        if (empty($_COOKIE['ECSESSID']) && isset($_COOKIE['legacy-ECSESSID']) && $id !== $_COOKIE['legacy-ECSESSID']) {
            // session_id と $_COOKIE['legacy-ECSESSID'] が異なる場合は ECSESSID の cookie が拒否されたと見なす
            GC_Utils_Ex::gfPrintLog('replace session id: ECSESSID=>legacy-ECSESSID');
            $id = $_COOKIE['legacy-ECSESSID']; // 互換用 cookie からセッションデータを読み込む
            unset($_COOKIE['legacy-ECSESSID']);
        }
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $arrRet = $objQuery->select('sess_data', 'dtb_session', 'sess_id = ?', [$id]);
        if (empty($arrRet)) {
            return '';
        } else {
            return $arrRet[0]['sess_data'];
        }
    }

    /**
     * セッションのデータをDBに書き込む.
     *
     * @param  string $id        セッションID
     * @param  string $sess_data セッションデータの値
     *
     * @return bool   セッションの書き込みに成功した場合 true
     */
    public function sfSessWrite($id, $sess_data)
    {
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $exists = $objQuery->exists('dtb_session', 'sess_id = ?', [$id]);
        $sqlval = [];
        if ($exists) {
            // レコード更新
            $sqlval['sess_data'] = $sess_data;
            $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
            $objQuery->update('dtb_session', $sqlval, 'sess_id = ?', [$id]);
        } else {
            // セッションデータがある場合は、レコード作成
            if (strlen($sess_data) > 0) {
                $sqlval['sess_id'] = $id;
                $sqlval['sess_data'] = $sess_data;
                $sqlval['update_date'] = 'CURRENT_TIMESTAMP';
                $sqlval['create_date'] = 'CURRENT_TIMESTAMP';
                $objQuery->insert('dtb_session', $sqlval);
            }
        }

        return true;
    }

    // セッション破棄

    /**
     * セッションを破棄する.
     *
     * @param  string $id セッションID
     *
     * @return bool   セッションを正常に破棄した場合 true
     */
    public function sfSessDestroy($id)
    {
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $objQuery->delete('dtb_session', 'sess_id = ?', [$id]);

        return true;
    }

    /**
     * ガーベジコレクションを実行する.
     *
     * 引数 $maxlifetime の代りに 定数 MAX_LIFETIME を使用する.
     *
     * @param int $maxlifetime セッションの有効期限(使用しない)
     */
    public function sfSessGc($maxlifetime)
    {
        // MAX_LIFETIME以上更新されていないセッションを削除する。
        $objQuery = SC_Query_Ex::getSingletonInstance();
        $limit = date('Y-m-d H:i:s', time() - MAX_LIFETIME);
        $where = "update_date < '".$limit."' ";
        $objQuery->delete('dtb_session', $where);

        return true;
    }

    /**
     * トランザクショントークンを生成し, 取得する.
     *
     * 悪意のある不正な画面遷移を防止するため, 予測困難な文字列を生成して返す.
     * 同時に, この文字列をセッションに保存する.
     *
     * この関数を使用するためには, 生成した文字列を次画面へ渡すパラメーターとして
     * 出力する必要がある.
     *
     * 例)
     * <input type='hidden' name='transactionid' value="この関数の返り値" />
     *
     * 遷移先のページで, LC_Page::isValidToken() の返り値をチェックすることにより,
     * 画面遷移の妥当性が確認できる.
     *
     * @return string トランザクショントークンの文字列
     */
    public static function getToken()
    {
        if (empty($_SESSION[TRANSACTION_ID_NAME])) {
            $_SESSION[TRANSACTION_ID_NAME] = SC_Helper_Session_Ex::createToken();
        }

        return $_SESSION[TRANSACTION_ID_NAME];
    }

    /**
     * トランザクショントークン用の予測困難な文字列を生成して返す.
     *
     * @return string トランザクショントークン用の文字列
     */
    public static function createToken()
    {
        return sha1(uniqid(rand(), true));
    }

    /**
     * トランザクショントークンの妥当性をチェックする.
     *
     * 生成されたトランザクショントークンの妥当性をチェックする.
     * この関数を使用するためには, 前画面のページクラスで LC_Page::getToken()
     * を呼んでおく必要がある.
     *
     * トランザクショントークンは, SC_Helper_Session::getToken() が呼ばれた際に
     * 生成される.
     * 引数 $is_unset が false の場合は, トークンの妥当性検証が不正な場合か,
     * セッションが破棄されるまで, トークンを保持する.
     * 引数 $is_unset が true の場合は, 妥当性検証後に破棄される.
     *
     * @param bool $is_unset 妥当性検証後, トークンを unset する場合 true;
     *                          デフォルト値は false
     *
     * @return bool トランザクショントークンが有効な場合 true
     */
    public static function isValidToken($is_unset = false)
    {
        // token の妥当性チェック
        $ret = ($_REQUEST[TRANSACTION_ID_NAME] ?? '') === ($_SESSION[TRANSACTION_ID_NAME] ?? '');

        if (empty($_REQUEST[TRANSACTION_ID_NAME]) || empty($_SESSION[TRANSACTION_ID_NAME])) {
            $ret = false;
        }

        if ($is_unset || $ret === false) {
            SC_Helper_Session_Ex::destroyToken();
        }

        return $ret;
    }

    /**
     * トランザクショントークンを破棄する.
     *
     * @return void
     */
    public static function destroyToken()
    {
        unset($_SESSION[TRANSACTION_ID_NAME]);
    }

    /**
     * 管理画面の認証を行う.
     *
     * mtb_auth_excludes へ登録されたページは, 認証を除外する.
     *
     * @return void
     */
    public static function adminAuthorization()
    {
        if (($script_path = realpath($_SERVER['SCRIPT_FILENAME'])) !== false) {
            $arrScriptPath = explode('/', str_replace('\\', '/', $script_path));
            $arrAdminPath = explode('/', str_replace('\\', '/', substr(HTML_REALDIR.ADMIN_DIR, 0, -1)));
            $arrDiff = array_diff_assoc($arrAdminPath, $arrScriptPath);
            if (in_array(substr(ADMIN_DIR, 0, -1), $arrDiff)) {
                return;
            } else {
                $masterData = new SC_DB_MasterData_Ex();
                $arrExcludes = $masterData->getMasterData('mtb_auth_excludes');
                foreach ($arrExcludes as $exclude) {
                    $arrExcludesPath = explode('/', str_replace('\\', '/', HTML_REALDIR.ADMIN_DIR.$exclude));
                    $arrDiff = array_diff_assoc($arrExcludesPath, $arrScriptPath);
                    if (count($arrDiff) === 0) {
                        return;
                    }
                }
            }
        }
        SC_Utils_Ex::sfIsSuccess(new SC_Session_Ex());
    }
}
