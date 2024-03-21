<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class HeadspaScrapingController extends Controller
{
    public function scrape()
    {
        // 実行時間を無期限に設定
        ini_set('max_execution_time', 0);

        $client = new Client();
        // 北海道のページからHTMLを取得
        $response = $client->request('GET', 'https://beauty.hotpepper.jp/genre/kgkw095/pre01/');

        $html = (string) $response->getBody();

        $crawler = new Crawler($html);

        // 総件数を取得
        $totalCount = (int)$crawler->filter('.numberOfResult')->text();

        // ページ数を計算
        $pages = ceil($totalCount / 20);

        // 結果格納用の配列
        $results = [];

        // 各ページにアクセス
        for ($page = 1; $page <= $pages; $page++) {
            // ページURL
            $pageUrl = 'https://beauty.hotpepper.jp/genre/kgkw095/pre01/' . ($page > 1 ? 'PN' . $page . '/' : '');

            // ページごとのHTMLを取得
            $response = $client->request('GET', $pageUrl);
            $html = (string)$response->getBody();

            // Crawlerを作成
            $crawler = new Crawler($html);

            // .slcHead 内のすべてのaタグを取得
            $links = $crawler->filter('.slcHead a');

            // 各リンクに対してループ処理
            foreach ($links as $link) {
                $linkUrl = $link->getAttribute('href');
                $response = $client->request('GET', $linkUrl);
                $shopHtml = (string) $response->getBody();
                $shopCrawler = new Crawler($shopHtml);

                // 店舗名を取得
                $titles = $shopCrawler->filter('.detailTitle a')->text();

                // 住所を取得
                $addresses = $shopCrawler->filter('.w620')->eq(1)->text();

                // アクセスを取得
                $access = $shopCrawler->filter('.w620')->eq(2)->text();

                // 営業時間を取得
                $businessHours = $shopCrawler->filter('.w620')->eq(3)->text();

                // 定休日を取得
                $regularHoliday = $shopCrawler->filter('.w620')->eq(4)->text();

                // 人気メニューを3つ取得
                try {
                    $popularMenu1 = $shopCrawler->filter('.couponMenuName')->eq(1)->text();
                } catch (\Exception $e) {
                    $popularMenu1 = null; // エラーが発生した場合、$popularMenu1 を null に設定する
                }

                try {
                    $popularMenu2 = $shopCrawler->filter('.couponMenuName')->eq(2)->text();
                } catch (\Exception $e) {
                    $popularMenu2 = null; // エラーが発生した場合、$popularMenu2 を null に設定する
                }

                try {
                    $popularMenu3 = $shopCrawler->filter('.couponMenuName')->eq(3)->text();
                } catch (\Exception $e) {
                    $popularMenu3 = null; // エラーが発生した場合、$popularMenu3 を null に設定する
                }

                // 画像ファイルパスを取得
                $imagePath = $shopCrawler->filter('.slnTopImgViewer img')->first()->attr('src');

                // 結果を配列に追加
                $results[] = [
                    '店舗名' => $titles,
                    '住所' => $addresses,
                    'アクセス' => $access,
                    '営業時間' => $businessHours,
                    '定休日' => $regularHoliday,
                    '人気メニュー1' => $popularMenu1,
                    '人気メニュー2' => $popularMenu2,
                    '人気メニュー3' => $popularMenu3,
                    '店舗URL' => $linkUrl,
                    '画像ファイルパス' => $imagePath,
                ];
            }
        }
        dd($results);
    }
}
