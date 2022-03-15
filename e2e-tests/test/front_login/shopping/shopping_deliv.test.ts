import PlaywrightConfig from '../../../../playwright.config';
import { ZapClient, ContextType, Risk, HttpMessage } from '../../../utils/ZapClient';
import { intervalRepeater } from '../../../utils/Progress';
const zapClient = new ZapClient();

const url = `${PlaywrightConfig.use.baseURL}/shopping/deliv.php`;

// 商品をカートに入れて購入手続きへ進むフィクスチャ
import { test, expect } from '../../../fixtures/cartin.fixture';

test.describe.serial('お届け先指定画面のテストをします', () => {
  test.beforeAll(async () => {
    await zapClient.startSession(ContextType.FrontLogin, 'front_login_shopping_deliv')
      .then(async () => expect(await zapClient.isForcedUserModeEnabled()).toBeTruthy());
  });

  test('お届け先指定画面へ遷移します', async ( { page }) => {
    await page.goto(url);       // url を履歴に登録しておく
    await expect(page.locator('h2.title')).toContainText('お届け先の指定');
  });

  test.describe('テストを実行します[GET] @attack', () => {
    let scanId: number;
    test('アクティブスキャンを実行します', async ( { page } ) => {
      scanId = await zapClient.activeScanAsUser(url, 2, 110, false, null, 'GET');
      await intervalRepeater(async () => await zapClient.getActiveScanStatus(scanId), 5000, page);
    });

    test('結果を確認します', async () => {
      await zapClient.getAlerts(url, 0, 1, Risk.High)
        .then(alerts => expect(alerts).toEqual([]));
    });
  });

  test('お支払方法・お届け時間等の指定画面へ遷移します', async ( { page } ) => {
    await page.click('input[alt=選択したお届け先に送る]');
    await expect(page.locator('h2.title')).toContainText('お支払方法・お届け時間等の指定');
  });

  test.describe('お支払方法・お届け時間等の指定へ進むテストを実行します[POST] @attack', () => {
    let message: HttpMessage;

    test('履歴を取得します', async () => {
      message = await zapClient.getLastMessage(url);
      expect(message.requestHeader).toContain(`POST ${url}`);
      expect(message.responseHeader).toContain('HTTP/1.1 302 Found');
    });

    let scanId: number;
    test('アクティブスキャンを実行します', async ( { page } ) => {
      scanId = await zapClient.activeScanAsUser(url, 2, 110, false, null, 'POST', message.requestBody);
      await intervalRepeater(async () => await zapClient.getActiveScanStatus(scanId), 5000, page);
    });

    test('結果を確認します', async () => {
      await zapClient.getAlerts(url, 0, 1, Risk.High)
        .then(alerts => expect(alerts).toEqual([]));
    });
  });
});
