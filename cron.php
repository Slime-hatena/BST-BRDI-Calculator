<?php
/* ====================================
cronでぶっ叩くファイル
・ bstの公式サイトからBRDI用のランキングを取得
・ 計算してDBに保存

BRDI-50=((公式ランキング1位のスコア-公式ランキング50位のスコア)*2*((51-公式ランキング1位の人数)/50))*10
100はそれの100人版
どちらのほうが正確かは誰にもわからない
===================================== */

/*  bstのソース
<li> <img src="/game/beatstream/animtribe/p/images/music/jk/2015/2633833.jpg" class="music_jk">
<div class="music_tit">あなたの総集編</div>
<div class="score_difflink_list">
<div class="light"><a href="/game/beatstream/animtribe/p/e-amu/ranking/score_detail.html?diff=0&mid=84470450">LIGHT</a></div>
<div class="medium"><a href="/game/beatstream/animtribe/p/e-amu/ranking/score_detail.html?diff=1&mid=84470450">MEDIUM</a></div>
<div class="beast"><a href="/game/beatstream/animtribe/p/e-amu/ranking/score_detail.html?diff=2&mid=84470450">BEAST</a></div>
<div class="nightmare"><a href=score_detail.html?diff=3&mid=59747349>NIGHTMARE</a></div>
</cmd:If>
</div>
<div style="clear:both;"></div>
</li>
*/

function getURlHtml($url){
    //ページ取得 これはsjisになってる
    $html = file_get_contents($url);
    // UTF8に変換しようね
    $html = mb_convert_encoding($html, 'UTF-8', 'SJIS');
    return $html;
}

function printLog($str){
    // ログ出力用にまとめたやつ
    $t = sprintf('%.3f', microtime(true) - $GLOBALS['now']);
    echo "(" . $t . "ms) " . $str;
    flush();
    ob_flush();
}

// ログ時間出力用
set_time_limit(300);
$GLOBALS['now'] = microtime(true);

printLog("Load : " . "総合ページ" . "<br>");
$html = getURlHtml("http://p.eagate.573.jp/game/beatstream/animtribe/p/e-amu/ranking/score.html");

$pattern = '/<li> *<img[^<>]*"music_jk"> *<div class="music_tit">([^<>]*)<\/div> *<div class="score_difflink_list">.{0,500}<div class="beast"><a href="([^<>]{0,400})">BEAST<\/a><\/div> *<div class="nightmare">(.{0,400})<\/div> *<\/cmd:If> *<\/div> *<div style="clear:both;"><\/div> *<\/li>/um';
preg_match_all($pattern, $html, $query_date, PREG_SET_ORDER);

$bstUrlList = array();
// いい感じに配列化する
foreach($query_date  as $value){
    if ($value[3] != ""){
        $n = "http://p.eagate.573.jp/game/beatstream/animtribe/p/e-amu/ranking/" . str_replace(">NIGHTMARE</a>", "", str_replace("<a href=", "", $value[3]));
    }else{
        $n = false;
    }
    
    $bstUrlList += [ // +=
    $value[1] => [
    "beast" => "http://p.eagate.573.jp" . $value[2],
    "nightmare" => $n
    ]];
    
}

// URLがリスト化できたのでいい感じに取得していく
// 取得できたらこれに入れよう name => first_score fifty_score hundred_score perfect
$musicScoreList = [];

foreach ($bstUrlList as $key => $value) {
    printLog("Load : " . $key . "<br>");
    $html = getURlHtml($value['beast']);
    
    // 1位判定用
    $pattern1 = '/<div class="rank"><img src=\/game\/beatstream\/animtribe\/p\/images\/e-amu\/ranking\/r_1.png><\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
    preg_match_all($pattern1, $html, $query_1, PREG_SET_ORDER);
    // 50位判定用
    $i = 50;
    while(true){
        if ($i <= 10){
            $pattern2 = '/<div class="rank"><img src=\/game\/beatstream\/animtribe\/p\/images\/e-amu\/ranking\/r_' . $i .  '.png><\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
        }else{
            $pattern2 = '/<div class="rank">' . $i . '<\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
        }
        preg_match_all($pattern2, $html, $query_50, PREG_SET_ORDER);
        if (count($query_50) != ""){
            break;
    }
    --$i;
}
// 100位判定用
$i = 100;
while(true){
    if ($i < 10){
        $pattern3 = '/<div class="rank"><img src=\/game\/beatstream\/animtribe\/p\/images\/e-amu\/ranking\/r_' . $i .  '.png><\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
    }else{
        $pattern3 = '/<div class="rank">' . $i . '<\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
    }
    preg_match_all($pattern3, $html, $query_100, PREG_SET_ORDER);
    if (count($query_100) != ""){
        break;
}
--$i;
}
// p人数判定
$pattern4 = '/<div class="total_score">' . $query_1[0][1] . '<\/div> /um';
preg_match_all($pattern4, $html, $query_p, PREG_SET_ORDER);


// ((H5-I5)*2*((51-J5)/50))*10

$brdi50 = (($query_1[0][1] - $query_50[0][1] ) * 2 * ((51 - count($query_p) ) / 50 )) * 10 / 1000;
$brdi100 = (($query_1[0][1] - $query_100[0][1] ) * 2 * ((101 - count($query_p) ) / 100 )) * 10 / 2000;


$musicScoreList += [
$key =>[
'first_score' => $query_1[0][1],                  // 1位スコア
'fifty_score' => $query_50[0][1],                // 50位スコア
'hundred_score' => $query_100[0][1],      //100位スコア
'perfect' => count($query_p),                    //P人数
'BRDI-50' => $brdi50,
'BRDI-100' => $brdi100
]
];

if ($value['nightmare'] != false){
    
    printLog("Load : " . $key . "(N)<br>");
    $html = getURlHtml($value['nightmare']);
    
    // 1位判定用
    $pattern1 = '/<div class="rank"><img src=\/game\/beatstream\/animtribe\/p\/images\/e-amu\/ranking\/r_1.png><\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
    preg_match_all($pattern1, $html, $query_1, PREG_SET_ORDER);
    // 50位判定用
    $i = 50;
    while(true){
        if ($i <= 10){
            $pattern2 = '/<div class="rank"><img src=\/game\/beatstream\/animtribe\/p\/images\/e-amu\/ranking\/r_' . $i .  '.png><\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
        }else{
            $pattern2 = '/<div class="rank">' . $i . '<\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
        }
        preg_match_all($pattern2, $html, $query_50, PREG_SET_ORDER);
        if (count($query_50) != ""){
            break;
    }
    --$i;
}
// 100位判定用
$i = 100;
while(true){
    if ($i < 10){
        $pattern3 = '/<div class="rank"><img src=\/game\/beatstream\/animtribe\/p\/images\/e-amu\/ranking\/r_' . $i .  '.png><\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
    }else{
        $pattern3 = '/<div class="rank">' . $i . '<\/div> *<div class="player_name">[^<>]*<\/div> *<div class="total_score">([0-9]*)<\/div>/um';
    }
    preg_match_all($pattern3, $html, $query_100, PREG_SET_ORDER);
    if (count($query_100) != ""){
        break;
}
--$i;
}
// p人数判定
$pattern4 = '/<div class="total_score">' . $query_1[0][1] . '<\/div> /um';
preg_match_all($pattern4, $html, $query_p, PREG_SET_ORDER);

$brdi50 = (($query_1[0][1] - $query_50[0][1] ) * 2 * ((51 - count($query_p) ) / 50 )) * 10 / 1000;
$brdi100 = (($query_1[0][1] - $query_100[0][1] ) * 2 * ((101 - count($query_p) ) / 100 )) * 10 / 2000;

$musicScoreList += [
$key. "(N)" =>[
'first_score' => $query_1[0][1],                  // 1位スコア
'fifty_score' => $query_50[0][1],                // 50位スコア
'hundred_score' => $query_100[0][1],      //100位スコア
'perfect' => count($query_p),                    //P人数
'BRDI-50' => $brdi50,
'BRDI-100' => $brdi100
]
];
}   //end if
}   //end foreach

var_dump($musicScoreList);

$musicScoreList += ["time"=> time()];

// jsonに保存していつでも取り出せるようにしておく
$json = json_encode( $musicScoreList , JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$filename = 'json/music.json';
file_put_contents($filename, $json);
