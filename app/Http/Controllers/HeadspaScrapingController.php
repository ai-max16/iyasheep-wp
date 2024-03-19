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
        $baseURL = 'https://beauty.hotpepper.jp/genre/kgkw095/pre';

        // 各都道府県のページをループして処理
        for ($i = 1; $i <= 47; $i++) {
            $response = $client->request('GET', $baseURL . sprintf('%02d', $i) . '/');
            $html = (string) $response->getBody();
            $crawler = new Crawler($html);

            // 各ページのページネーションを取得
            $paginationLinks = $crawler->filter('.pagination > li > a')->each(function ($node) {
                return $node->attr('href');
            });

            // 各ページネーションから詳細ページのURLを取得
            foreach ($paginationLinks as $link) {
                // HTTPリクエストの送信前に一定時間スリープさせる
                sleep(2); // 2秒間のスリープ

                $response = $client->request('GET', 'https://beauty.hotpepper.jp' . $link);
                $html = (string) $response->getBody();
                $crawler = new Crawler($html);

                $detailPageLinks = $crawler->filter('h3 > a')->each(function ($node) {
                    return $node->attr('href');
                });

                // 各詳細ページから.detailTitleクラスを取得
                foreach ($detailPageLinks as $detailLink) {
                    // HTTPリクエストの送信前に一定時間スリープさせる
                    sleep(2); // 2秒間のスリープ

                    $response = $client->request('GET', 'https://beauty.hotpepper.jp' . $detailLink);
                    $html = (string) $response->getBody();
                    $crawler = new Crawler($html);

                    $detailTitles = $crawler->filter('.detailTitle')->each(function ($node) {
                        return $node->text();
                    });

                    dd($detailTitles);
                }
            }
        }
    }
}
