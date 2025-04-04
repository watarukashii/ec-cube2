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

<section id="mypagecolumn">
    <h2 class="title"><!--{$tpl_title|h}--></h2>
    <!--{include file=$tpl_navi}-->
    <h3 class="title_mypage"><!--{$tpl_subtitle|h}--></h3>

    <!--★インフォメーション★-->
    <div class="intro">
        <p><span class="attention">※</span>は必須入力項目です。</p>
    </div>

    <form name="form1" id="form1" method="post" action="<!--{$smarty.const.HTTPS_URL}-->mypage/change.php">
        <input type="hidden" name="<!--{$smarty.const.TRANSACTION_ID_NAME}-->" value="<!--{$transactionid}-->" />
        <input type="hidden" name="mode" value="confirm" />
        <input type="hidden" name="customer_id" value="<!--{$arrForm.customer_id|h}-->" />

        <dl class="form_entry">
            <!--{include file="`$smarty.const.SMARTPHONE_TEMPLATE_REALDIR`frontparts/form_personal_input.tpl" flgFields=3 emailMobile=true prefix=""}-->
        </dl>
        <div class="btn_area">
            <p><input type="submit" class="btn data-role-none" value="確認ページへ" name="refusal" id="refusal" /></p>
        </div>
    </form>
</section>

<!--{include file= 'frontparts/search_area.tpl'}-->

