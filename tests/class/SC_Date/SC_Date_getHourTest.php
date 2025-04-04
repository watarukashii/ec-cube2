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

$HOME = realpath(__DIR__).'/../../..';
require_once $HOME.'/tests/class/Common_TestCase.php';

class SC_Date_getHourTest extends Common_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->objDate = new SC_Date_Ex();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ///////////////////////////////////////

    public function testGetHour24の配列を返す()
    {
        $this->expected = 24;
        $this->actual = count($this->objDate->getHour());

        $this->verify('配列の長さ');
    }

    public function testGetHour要素の最低値が0の配列を返す()
    {
        $this->expected = 0;
        $this->actual = min($this->objDate->getHour());

        $this->verify('配列の最低値');
    }

    public function testGetHour要素の最大値が23の配列を返す()
    {
        $this->expected = 23;
        $this->actual = max($this->objDate->getHour());

        $this->verify('配列の最大値');
    }
}
