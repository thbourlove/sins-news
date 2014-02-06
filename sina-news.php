<?php
require 'vendor/autoload.php';

use Guzzle\Http\Client;

$client = new Client('http://api.roll.news.sina.com.cn/');

use Predis\Client as RedisClient;
use Predis\Profile\ServerProfile;

$redis = function ($db = 0) {
    return new RedisClient(['database' => $db], ['profile' => ServerProfile::get('2.8')]);
};
$redis0 = $redis();
$redis14 = $redis(14);

$count = 50;
$cats = array(
    'gn' => array(
        'cat_1'=>'gnxw',
        'cat_2'=>'=gdxw1||=gatxw||=zs-pl||=mtjj',
    ),
    'sh' => array(
        'cat_1' => 'shxw',
        'cat_2' => '=zqsk||=qwys||=shwx||=fz-shyf',
    ),
);
$oneWeekAgo = strtotime('today') - 7 * 24 * 3600;
foreach ($cats as $channel => $cat) {
    $page = 1;
    do {
        print "$channel $page start...\n";
        $query = array(
            'channel' => 'news',
            'level' => '=1||=2',
            'show_num' => $count,
            'tag' => 1,
            'format' => 'json',
            'page' => $page,
        );
        $query += $cat;
        $uri = '/zt_list'.'?'.http_build_query($query);
        $uri = urldecode($uri);
        $results = $client->get($uri)->send()->json();
        $lastTime = $results['result']['last_time'];
        if ($lastTime < $oneWeekAgo) {
            break;
        }
        $news = $results['result']['data'];
        if (empty($news) || count($news) !== $count) {
            print "$channel $page retry...\n";
            continue;
        }
        foreach ($news as $new) {
            $redis14->hmset($channel.':'.$new['id'], $new);
        }
        $redis0->set('news:'.$channel, $page);
        print "$channel $page ok!\n";
        $page++;
    } while (true);
}
