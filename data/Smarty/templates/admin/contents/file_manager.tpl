<!--{*
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
*}-->

<style>
#contents-filemanager-tree {
    float: left;
    height: 430px;
}
#contents-filemanager-right {
    width: 720px;
    float: right;
}
#tree {
    width: 230px;
    border: 3px solid #ddd;
    padding: 10px;
    background-color: #F3F3F3;
}
</style>
<script type="text/javascript">//<![CDATA[
    $(function() {
        var bread_crumbs = <!--{$tpl_now_dir}-->;
        var file_path = '<!--{$tpl_file_path|h}-->';
        var $delimiter = '<span>&nbsp;&gt;&nbsp;</span>';
        var $node = $('h2');
        var total = bread_crumbs.length;
        for (var i in bread_crumbs) {
            file_path += bread_crumbs[i] + '/';
            $('<a href="javascript:;" />')
                .attr('data-filepath', file_path)
                .text(bread_crumbs[i])
                .addClass('open')
                .appendTo($node);
            if (i < total - 1) $node.append($delimiter);
        }
        $('a.open[data-filepath]').on('click', function() {
            var filepath = $(this).data('filepath');
            eccube.fileManager.openFolder(filepath);

            return false;
        });
        $('a.cd[data-filepath]').on('click', function() {
            var filepath = $(this).data('filepath');
            var id = $(this).data('id');
            eccube.setValue('tree_select_file', filepath, 'form1');
            eccube.fileManager.selectFile(id, '#808080');
            eccube.setModeAndSubmit('move','','');

            return false;
        });
        $('a.view[data-filepath]').on('click', function() {
            var filepath = $(this).data('filepath');
            var id = $(this).data('id');
            eccube.setValue('select_file', filepath, 'form1');
            eccube.fileManager.selectFile(id, '#808080');
            filepath = filepath.replace(/<!--{$smarty.const.USER_DIR|preg_quote:"/"}-->/, '');
            eccube.openWindow('./file_view.php?file='+ filepath, 'user_data', '600', '400');

            return false;
        });
        $('a.download[data-filepath]').on('click', function () {
            var filepath = $(this).data('filepath');
            var id = $(this).data('id');
            eccube.setValue('select_file', filepath, 'form1');
            eccube.fileManager.selectFile(id, '#808080');
            eccube.fileManager.setTreeStatus('tree_status');
            eccube.setModeAndSubmit('download','','');

            return false;
        });
        $('a.delete[data-filepath]').on('click', function () {
            var filepath = $(this).data('filepath');
            var id = $(this).data('id');
            eccube.setValue('select_file', filepath, 'form1');
            eccube.fileManager.selectFile(id, '#808080');
            eccube.fileManager.setTreeStatus('tree_status');
            eccube.setModeAndSubmit('delete','','');

            return false;
        });
    });

    eccube.fileManager.IMG_FOLDER_CLOSE   = "<!--{$TPL_URLPATH}-->img/contents/folder_close.gif";  // フォルダクローズ時画像
    eccube.fileManager.IMG_FOLDER_OPEN    = "<!--{$TPL_URLPATH}-->img/contents/folder_open.gif";   // フォルダオープン時画像
    eccube.fileManager.IMG_PLUS           = "<!--{$TPL_URLPATH}-->img/contents/plus.gif";          // プラスライン
    eccube.fileManager.IMG_MINUS          = "<!--{$TPL_URLPATH}-->img/contents/minus.gif";         // マイナスライン
    eccube.fileManager.IMG_NORMAL         = "<!--{$TPL_URLPATH}-->img/contents/space.gif";         // スペース
//]]></script>
<form name="form1" id="form1" method="post" action="?"  enctype="multipart/form-data">
    <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
    <input type="hidden" name="mode" value="" />
    <input type="hidden" name="now_file" value="<!--{$tpl_now_dir|h}-->" />
    <input type="hidden" name="now_dir" value="<!--{$tpl_now_file|h}-->" />
    <input type="hidden" name="tree_select_file" value="" />
    <input type="hidden" name="tree_status" value="" />
    <input type="hidden" name="select_file" value="" />
    <div id="admin-contents" class="contents-main">
        <div id="contents-filemanager-tree">
            <div id="tree"></div>
        </div>
        <div id="contents-filemanager-right">
            <table class="now_dir">
                <tr>
                    <th>ファイルのアップロード</th>
                    <td>
                        <!--{if $arrErr.upload_file}--><span class="attention"><!--{$arrErr.upload_file}--></span><!--{/if}-->
                        <input type="file" name="upload_file" size="40" <!--{if $arrErr.upload_file}-->style="background-color:<!--{$smarty.const.ERR_COLOR|h}-->"<!--{/if}--> /><a class="btn-normal" href="javascript:;" onclick="eccube.fileManager.setTreeStatus('tree_status');eccube.setModeAndSubmit('upload','',''); return false;">アップロード</a>
                    </td>
                </tr>
                <tr>
                    <th>フォルダ作成</th>
                    <td>
                        <!--{if $arrErr.create_file}--><span class="attention"><!--{$arrErr.create_file}--></span><!--{/if}-->
                        <input type="text" name="create_file" value="" style="width:336px;<!--{if $arrErr.create_file}--> background-color:<!--{$smarty.const.ERR_COLOR|h}--><!--{/if}-->" /><a class="btn-normal" href="javascript:;" onclick="eccube.fileManager.setTreeStatus('tree_status');eccube.setModeAndSubmit('create','',''); return false;">作成</a>
                    </td>
                </tr>
            </table>
            <h2><!--{* jQuery で挿入される *}--></h2>
            <table class="list">
                <tr>
                    <th>ファイル名</th>
                    <th>サイズ</th>
                    <th>更新日付</th>
                    <th class="edit">表示</th>
                    <th>ダウンロード</th>
                    <th class="delete">削除</th>
                </tr>
                <!--{if !$tpl_is_top_dir}-->
                    <tr id="parent_dir">
                        <td>
                            <img src="<!--{$TPL_URLPATH}-->img/contents/folder_parent.gif" alt="フォルダ">&nbsp;..
                        </td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <!--{/if}-->
                <!--{section name=cnt loop=$arrFileList}-->
                    <!--{assign var="id" value="select_file`$smarty.section.cnt.index`"}-->
                    <tr id="<!--{$id}-->" style="">
                        <td class="file-name">
                            <!--{if $arrFileList[cnt].is_dir}-->
                                <img src="<!--{$TPL_URLPATH}-->img/contents/folder_close.gif" alt="フォルダ" />
                            <!--{else}-->
                                <img src="<!--{$TPL_URLPATH}-->img/contents/file.gif" />
                            <!--{/if}-->
                            <!--{$arrFileList[cnt].file_name|h}-->
                        </td>
                        <td class="right">
                            <!--{$arrFileList[cnt].file_size|n2s}-->
                        </td>
                        <td class="center">
                            <!--{$arrFileList[cnt].file_time|h}-->
                        </td>
                        <!--{if $arrFileList[cnt].is_dir}-->
                            <td class="center">
                                <a href="javascript:;" class="cd"  data-filepath="<!--{$arrFileList[cnt].file_path|h}-->" data-id="<!--{$id|h}-->">表示</a>
                            </td>
                        <!--{else}-->
                            <td class="center">
                                <a href="javascript:;" class="view" data-filepath="<!--{$arrFileList[cnt].file_path|h}-->" data-id="<!--{$id|h}-->">表示</a>
                            </td>
                        <!--{/if}-->
                        <!--{if $arrFileList[cnt].is_dir}-->
                            <!--{* ディレクトリはダウンロード不可 *}-->
                            <td class="center">-</td>
                        <!--{else}-->
                            <td class="center">
                                <a href="javascript:;" class="download" data-filepath="<!--{$arrFileList[cnt].file_path|h}-->" data-id="<!--{$id|h}-->">ダウンロード</a>
                            </td>
                        <!--{/if}-->
                        <td class="center">
                            <a href="javascript:;" class="delete" data-filepath="<!--{$arrFileList[cnt].file_path|h}-->" data-id="<!--{$id|h}-->">削除</a>
                        </td>
                    </tr>
                <!--{/section}-->
            </table>
        </div>
    </div>
</form>
