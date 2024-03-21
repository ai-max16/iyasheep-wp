<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;

class HotPepperScrape extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:hotpepper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ホットペッパービューティからヘッドスパの店舗情報を取得する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 実行時間を無期限に設定
        ini_set('max_execution_time', 0);

        $client = new Client();

        // 47都道府県分のページをループで処理する
        for ($prefecture = 1; $prefecture <= 47; $prefecture++) {
            // 都道府県ページのURLを構築
            $prefectureUrl = 'https://beauty.hotpepper.jp/genre/kgkw095/pre' . str_pad($prefecture, 2, '0', STR_PAD_LEFT) . '/';

            // 都道府県ページにアクセスしてHTMLを取得
            $response = $client->request('GET', $prefectureUrl);
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
                $pageUrl = 'https://beauty.hotpepper.jp/genre/kgkw095/pre' . str_pad($prefecture, 2, '0', STR_PAD_LEFT) . '/' . ($page > 1 ? 'PN' . $page . '/' : '');

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
                    $addresses = $shopCrawler->filter('th:contains("住所") + td')->text() ?? '';

                    // アクセスを取得
                    $access = $shopCrawler->filter('th:contains("アクセス・道案内") + td')->text() ?? '';

                    // 営業時間を取得
                    $businessHours = $shopCrawler->filter('th:contains("営業時間") + td')->text() ?? '';

                    // 定休日を取得
                    $regularHoliday = $shopCrawler->filter('th:contains("定休日") + td')->text() ?? '';


                    // 人気メニューを3つ取得
                    $popularMenu1 = $shopCrawler->filter('.couponMenuName')->eq(1)->count() > 0 ? $shopCrawler->filter('.couponMenuName')->eq(1)->text() : '';
                    $popularMenu2 = $shopCrawler->filter('.couponMenuName')->eq(2)->count() > 0 ? $shopCrawler->filter('.couponMenuName')->eq(2)->text() : '';
                    $popularMenu3 = $shopCrawler->filter('.couponMenuName')->eq(3)->count() > 0 ? $shopCrawler->filter('.couponMenuName')->eq(3)->text() : '';

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

                // 400件ごとに処理を停止して保存
                if (count($results) >= 400 || $page == $pages) {

                    // CSVファイルに書き込み
                    $csvFileName = 'hotpepper_shops.csv';

                    // データをCSVファイルに追記する
                    foreach ($results as $result) {
                        $row = [
                            $result['店舗名'],
                            $result['住所'],
                            $result['アクセス'],
                            $result['営業時間'],
                            $result['定休日'],
                            $result['人気メニュー1'],
                            $result['人気メニュー2'],
                            $result['人気メニュー3'],
                            $result['店舗URL'],
                            $result['画像ファイルパス'],
                        ];
                        // CSVファイルへの追記
                        Storage::disk()->append($csvFileName, implode(',', $row));
                    }

                    // データベースに保存
                    foreach ($results as $result) {
                        // Shopモデルを使用して、データベースに保存する
                        Shop::create([
                            'name' => $result['店舗名'],
                            'address' => $result['住所'],
                            'access' => $result['アクセス'],
                            'business_hours' => $result['営業時間'],
                            'regular_holiday' => $result['定休日'],
                            'popular_menu1' => $result['人気メニュー1'],
                            'popular_menu2' => $result['人気メニュー2'],
                            'popular_menu3' => $result['人気メニュー3'],
                            'image_path' => $result['画像ファイルパス'],
                            'shop_url' => $result['店舗URL'],
                        ]);
                    }

                    // 配列をクリア
                    $results = [];

                    // 最後のページの場合は処理を終了
                    if ($page == $pages) {
                        break;
                    }

                    // 3秒待機
                    sleep(3);
                }
            }
        }
    }
}
