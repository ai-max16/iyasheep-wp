<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class HeadspaScrapingController extends Controller
{
    public function scrape()
    {
        $client = new Client();
        // 北海道のページからHTMLを取得
        $response = $client->request('GET', 'https://beauty.hotpepper.jp/genre/kgkw095/pre01/');

        $html = (string) $response->getBody();

        $crawler = new Crawler($html);

        // .slcHead 内のすべてのaタグを取得
        $links = $crawler->filter('.slcHead a');

        // 各リンクに対してループ処理
        $results = [];
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
            $popularMenu1 = $shopCrawler->filter('.couponMenuName')->eq(1)->text();
            $popularMenu2 = $shopCrawler->filter('.couponMenuName')->eq(2)->text();
            $popularMenu3 = $shopCrawler->filter('.couponMenuName')->eq(3)->text();

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

        dd($results);
    }
}
