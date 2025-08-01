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

class SC_View
{
    /** @var \Smarty\Smarty */
    public $_smarty;

    /** @var LC_Page */
    public $objPage;

    /** @var int */
    public $time_start;

    // コンストラクタ
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->_smarty = new SC_SmartyBc();
        $this->_smarty->setLeftDelimiter('<!--{');
        $this->_smarty->setRightDelimiter('}-->');
        $this->_smarty->registerPlugin('modifier', 'sfDispDBDate', function ($dbdate, $time = true) { return SC_Utils_Ex::sfDispDBDate($dbdate, $time); });
        $this->_smarty->registerPlugin('modifier', 'sfGetErrorColor', ['SC_Utils_Ex', 'sfGetErrorColor']);
        $this->_smarty->registerPlugin('modifier', 'sfTrim', ['SC_Utils_Ex', 'sfTrim']);
        $this->_smarty->registerPlugin('modifier', 'sfCalcIncTax', ['SC_Helper_DB_Ex', 'sfCalcIncTax']);
        $this->_smarty->registerPlugin('modifier', 'sfPrePoint', ['SC_Utils_Ex', 'sfPrePoint']);
        $this->_smarty->registerPlugin('modifier', 'sfGetChecked', ['SC_Utils_Ex', 'sfGetChecked']);
        $this->_smarty->registerPlugin('modifier', 'sfTrimURL', ['SC_Utils_Ex', 'sfTrimURL']);
        $this->_smarty->registerPlugin('modifier', 'sfMultiply', ['SC_Utils_Ex', 'sfMultiply']);
        $this->_smarty->registerPlugin('modifier', 'sfRmDupSlash', ['SC_Utils_Ex', 'sfRmDupSlash']);
        $this->_smarty->registerPlugin('modifier', 'sfCutString', ['SC_Utils_Ex', 'sfCutString']);
        $this->_smarty->registerPlugin('function', 'from_to', 'smarty_function_from_to');
        $this->_smarty->registerPlugin('function', 'include_php_ex', 'smarty_function_include_php_ex');
        $this->_smarty->registerPlugin('modifier', 'h', 'smarty_modifier_h');
        $this->_smarty->registerPlugin('modifier', 'n2s', 'smarty_modifier_n2s');
        $this->_smarty->registerPlugin('modifier', 'nl2br_html', 'smarty_modifier_nl2br_html');
        $this->_smarty->registerPlugin('modifier', 'script_escape', 'smarty_modifier_script_escape');
        $this->_smarty->registerPlugin('modifier', 'u', 'smarty_modifier_u');
        $this->_smarty->registerPlugin('modifier', 'sfMbConvertEncoding', ['SC_Utils_Ex', 'sfMbConvertEncoding']);
        $this->_smarty->registerPlugin('modifier', 'sfGetEnabled', ['SC_Utils_Ex', 'sfGetEnabled']);
        $this->_smarty->registerPlugin('modifier', 'sfNoImageMainList', ['SC_Utils_Ex', 'sfNoImageMainList']);
        $this->_smarty->registerPlugin('modifier', 'file_exists', 'file_exists');
        $this->_smarty->registerPlugin('modifier', 'function_exists', 'function_exists');
        $this->_smarty->registerPlugin('modifier', 'preg_quote', 'preg_quote');
        $this->_smarty->registerPlugin('modifier', 'is_numeric', 'is_numeric');
        $this->_smarty->registerPlugin('modifier', 'php_uname', 'php_uname');
        $this->_smarty->registerPlugin('modifier', 'array_key_exists', 'array_key_exists');
        $this->_smarty->registerPlugin('modifier', 'key', 'key');
        $this->_smarty->registerPlugin('modifier', 'strpos', 'strpos');
        $this->_smarty->registerPlugin('modifier', 'current', 'current');
        $this->_smarty->registerPlugin('modifier', 'var_dump', 'var_dump');
        $this->_smarty->registerPlugin('modifier', 'preg_match', 'preg_match');
        $this->_smarty->registerPlugin('modifier', 'unserialize', 'unserialize');
        $this->_smarty->registerPlugin('modifier', 'realpath', 'realpath');
        // XXX register_function で登録すると if で使用できないのではないか？
        $this->_smarty->registerPlugin('function', 'sfIsHTTPS', ['SC_Utils_Ex', 'sfIsHTTPS']);
        $this->_smarty->registerPlugin('function', 'sfSetErrorStyle', ['SC_Utils_Ex', 'sfSetErrorStyle']);
        $this->_smarty->registerPlugin('function', 'printXMLDeclaration', ['GC_Utils_Ex', 'printXMLDeclaration']);
        $this->_smarty->registerPlugin('modifier', 'format_name', 'smarty_modifier_format_name');
        $this->_smarty->muteUndefinedOrNullWarnings();
        $this->_smarty->default_modifiers = ['script_escape'];

        if (ADMIN_MODE == '1') {
            $this->time_start = microtime(true);
        }

        $this->_smarty->force_compile = SMARTY_FORCE_COMPILE_MODE === true;
        // 各filterをセットします.
        $this->registFilter();
        // smarty:nodefaultsの後方互換を維持
        $this->_smarty->registerFilter('pre', [$this, 'lower_compatibility_smarty']);
    }

    // テンプレートに値を割り当てる

    /**
     * @param string $val1
     */
    public function assign($val1, $val2)
    {
        $this->_smarty->assign($val1, $val2);
    }

    // テンプレートの処理結果を取得
    public function fetch($template)
    {
        return $this->_smarty->fetch($template);
    }

    /**
     * SC_Display用にレスポンスを返す
     *
     * @global string $GLOBAL_ERR
     *
     * @param  array   $template
     * @param  bool $no_error
     *
     * @return string
     */
    public function getResponse($template, $no_error = false)
    {
        if (!$no_error) {
            global $GLOBAL_ERR;
            if (!defined('OUTPUT_ERR')) {
                // GLOBAL_ERR を割り当て
                $this->assign('GLOBAL_ERR', $GLOBAL_ERR);
                define('OUTPUT_ERR', 'ON');
            }
        }
        $res = $this->_smarty->fetch($template);
        if (ADMIN_MODE == '1') {
            $time_end = microtime(true);
            $time = $time_end - $this->time_start;
            $res .= '処理時間: '.sprintf('%.3f', $time).'秒';
        }

        return $res;
    }

    /**
     * Pageオブジェクトをセットします.
     *
     * @param  LC_Page_Ex $objPage
     *
     * @return void
     */
    public function setPage($objPage)
    {
        $this->objPage = $objPage;
    }

    /**
     * Smartyのfilterをセットします.
     *
     * @return void
     */
    public function registFilter()
    {
        $this->_smarty->registerFilter('pre', [&$this, 'prefilter_transform']);
        $this->_smarty->registerFilter('output', [&$this, 'outputfilter_transform']);
    }

    /**
     * prefilter用のフィルタ関数。プラグイン用のフックポイント処理を実行
     *
     * @param  string          $source ソース
     * @param  \Smarty\Template $smarty Smartyのコンパイラクラス
     *
     * @return string          $source ソース
     */
    public function prefilter_transform($source, Smarty\Template $template)
    {
        if (!is_null($this->objPage)) {
            // フックポイントを実行.
            $objPlugin = SC_Helper_Plugin_Ex::getSingletonInstance();
            if ($objPlugin) {
                $objPlugin->doAction('prefilterTransform', [&$source, $this->objPage, $this->getCurrentTemplateFile($template)]);
            }
        }

        return $source;
    }

    /**
     * outputfilter用のフィルタ関数。プラグイン用のフックポイント処理を実行
     *
     * @param  string          $source ソース
     * @param  \Smarty\Template $smarty Smartyのコンパイラクラス
     *
     * @return string          $source ソース
     */
    public function outputfilter_transform($source, Smarty\Template $template)
    {
        if (!is_null($this->objPage)) {
            // フックポイントを実行.
            $objPlugin = SC_Helper_Plugin_Ex::getSingletonInstance();
            if ($objPlugin) {
                $objPlugin->doAction('outputfilterTransform', [&$source, $this->objPage, $this->getCurrentTemplateFile($template)]);
            }
        }

        return $source;
    }

    /**
     * テンプレートの処理結果を表示
     *
     * @param string $template
     * @param bool $no_error
     */
    public function display($template, $no_error = false)
    {
        if (!$no_error) {
            global $GLOBAL_ERR;
            if (!defined('OUTPUT_ERR')) {
                // GLOBAL_ERR を割り当て
                $this->assign('GLOBAL_ERR', $GLOBAL_ERR);
                define('OUTPUT_ERR', 'ON');
            }
        }

        $this->_smarty->display($template);
        if (ADMIN_MODE == '1') {
            $time_end = microtime(true);
            $time = $time_end - $this->time_start;
            echo '処理時間: '.sprintf('%.3f', $time).'秒';
        }
    }

    /**
     * オブジェクト内の変数を全て割り当てる。
     *
     * @param object $obj
     */
    public function assignobj($obj)
    {
        $data = get_object_vars($obj);

        foreach ($data as $key => $value) {
            $this->_smarty->assign($key, $value);
        }
    }

    /**
     * 連想配列内の変数を全て割り当てる。
     *
     * @param array $array
     */
    public function assignarray($array)
    {
        foreach ($array as $key => $val) {
            $this->_smarty->assign($key, $val);
        }
    }

    /**
     * テンプレートパスをアサインする.
     *
     * @param int $device_type_id 端末種別ID
     */
    public function assignTemplatePath($device_type_id)
    {
        // テンプレート変数を割り当て
        $this->assign('TPL_URLPATH', SC_Helper_PageLayout_Ex::getUserDir($device_type_id, true));

        // ヘッダとフッタを割り当て
        $templatePath = SC_Helper_PageLayout_Ex::getTemplatePath($device_type_id);
        $header_tpl = $templatePath.'header.tpl';
        $footer_tpl = $templatePath.'footer.tpl';

        $this->assign('header_tpl', $header_tpl);
        $this->assign('footer_tpl', $footer_tpl);
    }

    /**
     * デバッグ
     *
     * @param bool $var
     */
    public function debug($var = true)
    {
        $this->_smarty->debugging = $var;
    }

    /**
     * 2.13のテンプレートのまま動作するためにsmartyの後方互換処理
     *
     * @param mixed $tpl_source
     * @param mixed $smarty
     *
     * @return array|string|null
     */
    public function lower_compatibility_smarty($tpl_source, $smarty)
    {
        $pattern = ["/\|smarty:nodefaults/", '/include_php /', '/=`(.+?)`/', '/html_checkboxes_ex /', '/html_radios_ex /'];
        $replace = [' ', 'include_php_ex ', '=$1', 'html_checkboxes ', 'html_radios '];

        return preg_replace($pattern, $replace, $tpl_source);
    }

    /**
     * 現在のテンプレートファイルパスを返す.
     *
     * 2.13(Smarty2) との後方互換用.
     * 2.13 で使用していた $template->smarty->_current_file は $template->source->filepath の後方互換用変数だが
     * 以下ような振舞いの違いがある
     * - Smarty2: テンプレートディレクトリからの相対パス
     * - Smarty3: テンプレートディレクトリからの絶対パス
     * この関数は 2.13 と同様にテンプレートディレクトリからの相対パスを返す
     *
     * @param Smarty_Internal_Template $template
     *
     * @return string 現在のテンプレートファイルパス
     */
    public function getCurrentTemplateFile(Smarty\Template $template)
    {
        $current_file = str_replace($template->getSmarty()->getTemplateDir(), '', $template->getSource()->getFilepath());

        return str_replace('\\', '/', $current_file); // Windows 向けにパスの区切り文字を正規化する
    }
}
