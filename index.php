<?php
print<<<EOF
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title>BST BRDI計算機 - aki-memo.net</title>
<link rel="stylesheet" href="style.css" />

<!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js" type="text/javascript"></script>
<![endif]-->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="animatedtablesorter/tsort.js"></script>
<script src="animatedtablesorter/setting.js"></script>
<link rel="stylesheet" href="animatedtablesorter/style.css" type="text/css" />


</head>
<body>
<header role="banner">
<h1>BST BRDI 計算機</h1>
</header>

<div role="main">
<h2>当ツール概要</h2>
<p><a href="https://twitter.com/fallen_helga" target="_blank">さんらいく氏</a>考案のBRDI計算法で算出した、ビートストリーム難易度表です。<br>毎日朝6時頃に更新されます。</p>
<p>正式名称は"BeatStream Ranker's Difficulty Index"です。以下考案者より引用</p>
<p><blockquote>・BRDIとは<br>
公式ランキングのスコアを元に算出した 「上級者におけるPERFECT難易度の客観的指標」 を示す。<br>
・問題点<br>
プレイ人数が少ない譜面はBRDIが高くなりやすく、それほど難しくないがBRDIが高くなる場合がある。<br>
↑新曲や解禁が面倒な譜面ほどBRDIは高く、人気楽曲は低くなる傾向<br>
これを用いて詐称・逆詐称の議論は行わないように。<br>
同スコアが50人以上いる曲はBRDIが0になる	<br>
↑解決できるような案募集しています<br>
初代曲とアニトラ曲の純粋なBRDI比較は不可能	<br>
初代でパフェってもアニトラでパフェってないプレイヤーは多い<br>
1位のスコアが理論値前提となっている<br>
</blockquote></p>
<p>当サイトでの計算方法は<br>
<b>BRDI-100 = ((公式ランキング1位のスコア-公式ランキング100位のスコア)*2*((101-公式ランキング1位の人数)/100))*10</b><br>
<b>BRDI-50 = ((公式ランキング1位のスコア-公式ランキング50位のスコア)*2*((51-公式ランキング1位の人数)/50))*10</b><br>
です。</p>

<p>何かあれば<a href="https://twitter.com/Slime_hatena" target="_blank">@Slime_hatena</a>へどうぞ。FF外でもDM送信できます。<br>
 グラフの一番上を押すとソートできます。</p>
</div>
EOF;

// 一番新しいファイルを取得する
$fileList = [];
if ($dir = opendir("json/")) {
    while (($file = readdir($dir)) !== false) {
        if ($file != "." && $file != "..") {
            $fileList += array_merge($fileList, [$file]);
        }
    }
    closedir($dir);
}
$array = json_decode(file_get_contents("json/" . $fileList[count($fileList) - 1]), true);
foreach ($array as $key => $value) {
    $sort[$key] = $value['BRDI-50'];
}

array_multisort($sort, SORT_DESC, $array);


echo "<h2>集計結果</h2><p>最終更新 : " . date('Y/m/d H:i:s', str_replace(".json", "" , $fileList[count($fileList) - 1]))  .  "</p>";

echo '
<table class="tableSorter">
<thead>
<tr>
<th scope="cols">Title</th>
<th scope="cols">BRDI-50</th>
<th scope="cols">BRDI-100</th>
<th scope="cols">1st</th>
<th scope="cols">50th</th>
<th scope="cols">100th</th>
<th scope="cols">Per</th>
</tr>
</thead>
<tbody>
';

foreach ($array as $key => $value) {
    echo '<tr>';
    echo '<td scope="row" class="head">' . $key . '</td>';
    echo '<td class="boldRed">' . sprintf('%.1f',$value['BRDI-50']) . '</td>';
    echo '<td class="boldRed">' . sprintf('%.1f',$value['BRDI-100']) . '</td>';
    echo '<td>' . $value['first_score'] . '</td>';
    echo '<td>' . $value['fifty_score'] . '</td>';
    echo '<td>' . $value['hundred_score'] . '</td>';
    echo '<td>' . $value['perfect'] . '</td>';
    
}



/*



<td>内容がはいります。</td>
<td>内容がはいります。</td>
<td>内容がはいります。</td>
<td>内容がはいります。</td>
<td>内容がはいります。</td>
<td>内容がはいります。</td>
</tr>


*/



print<<<EOF
</tr>
</tbody>
</table>
<footer role="contentinfo">
©2016 Konami Digital Entertainment.<br>
EOF;

echo "&#169;";
$then = 2016;
$now = date('Y');
if ($then < $now) {
    echo $then.'–'.$now;
} else {
    echo $then;
}
echo ' Slime_hatena All Rights Reserved.<br>
ソース的なお話は<a href="" target="_blank">githubへ';


print<<<EOF
</footer>
</body>
</html>
EOF;
?>