<?php

/*****************
 * PHP Analyzer     by ToR
 *
 * http://php.s3.to/
 *
 * 2001/06/11 何度か書き直して完成
 * 2001/06/12 v1.01 co.jp集計がおかしかった
 * 2001/06/13 v1.02 biglobe,Win XPを追加、拒否チェック
 * 2001/06/21 v1.03 CERN串をWinCEと誤認、検索大文字小文字区別しない
 * 2001/06/30 v1.04 認証をつけました。まだロボットがあるようです
 * 2001/08/18 v1.05 0時の詳細バグ。検索機能をアップしました。（複数語、AND OR
 * 2001/11/07 v1.07 合計追加（激重、NN4の時のリンク
 * 2001/12/22 v1.08 SJISにする。i18n→mb_convertへ
 * 2002/06/26 v1.08 ボット、ブラウザ追加
 *
 * 2002/07/05 v2.00 PHP4.1.0以降のバージョン用に書き換え
 * 2002/07/11 v2.01 外部変数チェック XSS対策 他
 * 2005/05/26 v2.2  konque追加、検索エンジン時の生ログ表示
 * 2005/05/30 v2.25 OS判別修正,XP.NET
 * 2005/06/09 v2.26 jcode-LE.phpにファイル名修正。file:///の場合のエラー
 * 2005/06/20 v2.27 bbtec対策（ホスト名、リンク元ｸｴﾘ設定
 * 2005/12/07 v2.3  OSリストをOSモードのみ読み込み、メモリ節約
 *
 * =====データ形式
 * 20010101.txt ファイルの最後が新しいログ
 *
 * $data[0] 2001/05/01[Mon]	$data[1] 11:12:33	$data[2] www.xxx.com(host
 * $data[3] Mozilla4.0(UA	$data[4] http://www(ref	$data[5] 1(ﾕﾆｰｸ=1)
 */
//---------設定
$logdir = './log/';        //ログ格納ディレクトリ
$suf = '.txt';          //ログ拡張子
$repfile = './log/url.txt'; //URL置換用ファイル
$page_def = 20;              //各モードの1ページ表示数
$id = '';         //ログインID
$pass = '';          //ログインパス
$noque = 1;               //リンク元で?以降を削除する Yes=1 No=0

if ('os' == $_GET['act']) {
    $winoslist = [//WinOSリスト 上から順に判別
    'XP' => 'Windows XP?',
'9x 4.90' => 'Windows Me',
'95' => 'Windows 95',
'NT 5.0' => 'Windows 2000',
'NT 5.1' => 'Windows XP',
'NT 5.2' => 'Windows.NET',
'NT' => 'Windows NT',
'2000' => 'Windows 2000',
'98' => 'Windows 98',
'CE' => 'Windows CE',
'32' => 'Win32',
'67' => 'Win67',
];

    $oslist = [//その他OSリスト
    'Mac_PowerPC' => 'Macintosh',
'Macintosh' => 'Macintosh',
'FreeBSD' => 'FreeBSD',
'Linux' => 'Linux',
'IRIX' => 'IRIX',
'SunOS' => 'SunOS',
'OS/2' => 'OS/2',
'WebTV' => 'WebTV',
'DreamPassport' => 'DreamCast',
'DoCoMo' => 'DoCoMo',
'J-PHONE' => 'J-PHONE',
'UP.Browser' => 'EzWeb',
];

    $bralist = [//ブラウザリスト
    'Lynx' => 'Lynx',
'w3m' => 'w3m',
'DreamPassport' => 'DreamPassport',
'WWWC' => 'WWWC',
'WWWD' => 'WWWD',
'PerMan' => '波乗野郎',
'Pockey' => 'GetHTMLW',
'Asahina-Antenna' => '朝日奈アンテナ',
'MSProxy' => 'ProxyServer',
'NetCaptor' => 'NetCaptor',
'iCab' => 'iCab',
'Cuam' => 'Cuam',
'Sleipnir' => 'Sleipnir',
'Opera' => 'Opera',
'OmniWeb' => 'OmniWeb',
'SpaceBison' => 'Proxomitron',
'WebTV' => 'WebTV',
'DoCoMo' => 'DoCoMo',
'J-PHONE' => 'J-PHONE',
'UP.Browser' => 'EzWeb',
'Googlebot' => 'Googlebot',
'MSIE 3.' => 'InternetExplorer 3.x',
'MSIE 4.' => 'InternetExplorer 4.x',
'MSIE 5.' => 'InternetExplorer 5.x',
'MSIE 6.' => 'InternetExplorer 6.x',
'Mozilla/3' => 'Netscape 3.x',
'Mozilla/4.' => 'Netscape 4.x',
'Netscape6' => 'Netscape 6.x',
'Netscape/7' => 'Netscape 7.x',
'Gecko' => 'Mozilla',
'PHP/' => 'PHP',
'Konqueror/3.' => 'Konqueror/3.x',
'Konqueror/2.' => 'Konqueror/2.x',
];
}
if ('eng' == $_GET['act']) {
    $eng_list = [//サーチエンジン（エンジン名、クエリー、対象URI）
['Google', 'q', 'www.google.com'],		['Google', 'q', 'google.co.jp'],
['goo', 'MT', 'goo.ne.jp'],			['@Search', 'q', 'asearch.cab.infoweb.ne.jp'],
['Yahoo !', 'p', 'search.yahoo.co.jp'],	['Yahoo !/Google', 'p', 'google.yahoo.co.jp'],
['InfoSeek', 'qt', 'www.infoseek.co.jp'],	['InfoSeek', 'qt', 'infoseek.com'],
['Lycos', 'query', 'www.lycos.co.jp'],	['Excite', 'search', 'www.excite.co.jp'],
['MSN', 'q', 'search.msn.co.jp'],		['MSN', 'MT', 'search.msn.co.jp'],
['Fresheye', 'kw', 'search.fresheye.com'],	['kensaku.org', 'key', 'kensaku.org'],
['Biglobe', 'q', 'cgi.search.biglobe.ne.jp'], ['alltheweb', 'q', 'www.alltheweb.com'],
['MSN.com', 'q', 'search.msn.com'],
['AltaVista', 'query', 'www.altavista.com'],	['Fast Search', 'query', 'www.alltheweb.com'],
['GO.com', 'qt', 'www.go.com'],		['Excite.com', 'search', 'search.excite.com'],
];
}
/* ロボット、巡回ソフトUAリスト */
$robot = '/(FlashGet|WebBooster|Ninja|MIDown|moget|InternetLinkAgent|Wget|InterGet|WebFetch|WebCrawler|ArchitextSpider|Scooter|WebAuto|InfoNaviRobot|httpdown|Inetdown|WiseWire-Spider|Slurp|Lycos_Spider|^Iron33|^fetch|^PageDown|^BMChecker|^Jerky|^Nutscrape)/m';

/* 検索文字列変換設定 */
$mb = (@function_exists('mb_convert_encoding')) ? 1 : 0; //国際化関数を使えるか？
if (0 == $mb) {
    @require './jcode-LE.php';
}  //通常はTOMOさんのjcode-LE.php使用

//グラフgif画像（１が青、２が赤）
$gif1 = 'R0lGODlhEAABALP/AP///7nR/LPK96vC8aO665qx5ZKp4Imf2X6T0XKIyWh+wmF2vWB1vAAAAAAAAAAAACwAAAAAEAABAAAECnDJqRI6ppAhQgQAOwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACwAAAAAEAABAAAFDiD0OEpyFEMgEAayNEwIADs=';
$gif2 = 'R0lGODlhEAABALMAAPPn2PfKs/HCq+u6o+WxmuCpktmfidGTcsmIcsJ+aL12Yf4BAgAAAAAAAAAAAAAAACH5BAUUAAsALAAAAAAQAAEAAAQKUMmZ0DGFDBFABAA7';
//==================ここまで
$f = (int)$_GET[f];
$h = $_GET[h];
$act = htmlspecialchars($_GET[act], ENT_QUOTES | ENT_HTML5);
$sel = htmlspecialchars($_GET[sel], ENT_QUOTES | ENT_HTML5);
$page = $_GET[page];
$PHP_SELF = $_SERVER[PHP_SELF];

if ($_GET[g]) {//バー画像の表示
    header('Content-Type: image/gif');

    $img = (1 == $_GET[g]) ? $gif1 : $gif2;

    echo base64_decode($img, true);

    exit;
}

/* ログを全て読み込む */
$today = gmdate('Ymd', time() + 9 * 3600);
if ('' == $f) {
    $f = $today;
}		//指定がないなら今日
$logfile = $logdir . $f . $suf;
$all = @file($logfile);

$ma = mb_substr($f, 4, 2); //月
$da = mb_substr($f, 6, 2); //日
?>
<html><head>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=EUC-JP">
<title>PHP Analyzer</title>
<style type="text/css">
<!--
BODY       { font-family: Helvetica, Arial; font-size: 11px; color: 000000; }

A          { font-family: Helvetica, Arial; font-size: 11px; color: #204020; font-weight: bold }
A:link     { font-family: Helvetica, Arial; font-size: 11px; color: #204020; font-weight: normal; text-decoration: none }
A:visited  { font-family: Helvetica, Arial; font-size: 11px; color: #204020; font-weight: normal; text-decoration: none }
A:hover    { font-family: Helvetica, Arial; font-size: 11px; color: #EEEE20; font-weight: normal; text-decoration: underline }

TH       { font-family: Helvetica, Arial; font-size: 11px; color:   #004080; background: #A5EFC7 }
TD     { font-family: Helvetica, Arial; font-size: 11px; color:   #004080; background: #c6cfd6 }
TD#a     { background: #C5FFE0; }
-->
</style>
</head>
<body bgcolor=ffffff text=408080>
<center>
<?php
//TOPメニュー
echo "<table><tr>
<td><a href=\"$PHP_SELF?f=$f\">トップ</a></td>
<td><a href=\"$PHP_SELF?f=$f&act=last\">最新ログ</a></td><td><a href=\"$PHP_SELF?f=$f&act=ref\">リンク元</a></td>
<td><a href=\"$PHP_SELF?f=$f&act=eng\">検索エンジン</a></td><td><a href=\"$PHP_SELF?f=$f&act=os\">OS・ブラウザ</a></td>
<td><a href=\"$PHP_SELF?f=$f&act=host\">ホスト</a></td><td><a href=\"$PHP_SELF?f=$f&act=search\">ログ検索</a></td>
</tr></table>\n";
//日別リンク
echo '<table><tr>';
$d = dir($logdir); //ログディレクトリ走査
while ($ent = $d->read()) {
    if (preg_match("^[0-9]{8}$suf", $ent)) {
        $entarr[] = $ent;
    }
}
$d->close();
if (is_array($entarr)) {
    arsort($entarr); //新しいものから表示

    while (list(, $name) = each($entarr)) {
        if ('99' == $f) {
            $logfile = $logdir . $name;

            $tmp = @file($logfile);

            for ($c = 0, $cMax = count($tmp); $c < $cMax; $c++) {
                $all[] = $tmp[$c];
            }

            $n++;
        }

        printf(
            "<td>&nbsp;<a href=\"%s?f=%s&act=%s\">%1d-%1d</a>&nbsp;</td>\n",
            $PHP_SELF,
            mb_substr($name, 0, 8),
            $act,
            mb_substr($name, 4, 2),
            mb_substr($name, 6, 2)
        );
    }
}
echo "<td><a href=\"$PHP_SELF?f=99&act=$act\">合計</a></td></tr></table>";

//タイトル
if ('99' == $f) {
    echo "<h3>過去 $n 日間の合計</h3>";
} else {
    echo "<h3>$ma 月 $da 日 のアクセス</h3>";
}
if (!$all) {
    die('データがありません');
}

function foot()
{//フッター著作権表示
    die('</center><div align=right>PHP Analyzer by<a href=http://php.s3.to target=_blank>レッツPHP!</a></body></html>');
}
function getper($val = 0, $total = 1)
{//％の計算とグラフ幅
    $per[0] = @sprintf('%01.1f', ($val / $total * 100));

    $per[1] = (int)$per[0] * 2;

    return $per;
}
function showtable($top, $val_arr, $total)
{//テーブルの表示(タイトル、データ配列、合計、表示数）
    global $page_def,$f,$act,$page,$repfile,$PHP_SELF;

    echo "<table border=0 width=75%><tr><th align=left><font size=+1>&nbsp;$top</font><br><div align=right>有効件数 : $total</div></th></tr></table>\n";

    echo '<table width=75%><tr>';

    if ($page) {
        $st = ($page - 1) * $page_def;

        if ('all' == $page) {
            $st = 0;

            $page_def = count($val_arr);
        }
    } else {
        $page = 1;
    }

    arsort($val_arr);

    reset($val_arr);

    while (list($data, $num) = each($val_arr)) {
        $per = getper($num, $total);

        if ('host' == $act) {
            $sv = "<a href=\"http://www.$data\" target=_blank>☆</a>";
        }

        if ('ref' == $act) {
            $url = $data;

            $fc = @fopen($repfile, 'rb'); //置換URL

            while ($dat = fgetcsv($fc, 200)) {
                if ($dat[1] == $data) {
                    $url = $dat[2];
                }
            }

            fclose($fc);

            $sv = "<a href=\"$url\" target=_blank>☆</a>";
        }

        if ('' != $data && $i >= $st && $i < $st + $page_def) {
            $cls = ($i % 2) ? 'id=a' : '';

            $enc = urlencode($data);

            echo "<tr><td $cls>$sv&nbsp;<a href=\"$PHP_SELF?f=$f&act=$act&sel=$enc\">$data</a>";

            echo "<td align=right $cls>&nbsp;$num&nbsp;</td>";

            echo "<td $cls><img src=\"$PHP_SELF?g=1\" height=8 width=$per[1]>&nbsp;$per[0]%</td></tr>\n";
        }

        $i++;
    }

    if (count($val_arr) > $page_def) {//ページング作成
        for ($j = 1; $j * $page_def < count($val_arr) + $page_def; $j++) {
            if ($page == $j) {//今表示しているのはﾘﾝｸしない
                $next .= " $j ";
            } else {
                $next .= " <a href=\"$PHP_SELF?f=$f&act=$act&page=$j\">$j</a> "; //他はﾘﾝｸ
            }
        }

        printf(
            '<tr><td colspan=3 align=left>[ Page: %s <a href="%s?f=%s&act=%s&page=all">ALL</a> ]</td></tr>',
            $next,
            $PHP_SELF,
            $f,
            $act
        );
    }

    echo "</table><br><br>\n";
}
function keywords($ref_url)
{//検索文字列の解析
    global $eng_list,$mb;

    if (preg_match('^htt', $ref_url)) {
        $url = parse_url($ref_url);
    } //URIを分割
  $query = $url['query'];      //?以降の部分
  $host = $url['host'];       //ホスト部分
  parse_str($query);           //クエリーを変数に代入

  $keywords = '';

    $found = false;

    for ($c = 0; $c < count($eng_list) && !$found; $c++) {
        if ($host == $eng_list[$c][2]) {
            true === $found;

            $engine[0] = $eng_list[$c][0];

            if (isset($$eng_list[$c][1])) {//そのエンジンのキーがあったら文字コードSJISに変換
                if ($mb) {
                    $engine[1] = mb_convert_encoding($$eng_list[$c][1], 'EUC', 'auto');
                } else {
                    $engine[1] = JcodeConvert($$eng_list[$c][1], 0, 1);
                }
            }

            $engine[1] = stripslashes($engine[1]);
        }
    }

    // $engine[0] = エンジン名 $engine[1] = キーワード

    return $engine;
}
function platform($hua)
{//OSの判別
    global $winoslist,$oslist;

    $find = false;

    if (preg_match('Win', $hua)) {//Winの場合
        foreach ($winoslist as $k_ua => $replace) {
            if (preg_match($k_ua, $hua) && !$find) {
                $find = true;

                $os = $replace;

                break;
            }
        }

        if (!$find && '' != $hua) {
            $os = 'Other Win';
        }
    } else {//Win以外のOS
        foreach ($oslist as $k_ua => $replace) {
            if (preg_match($k_ua, $hua) && !$find) {
                $find = true;

                $os = $replace;

                break;
            }
        }

        if (!$find && '' != $hua) {
            $os = 'Unknown OS';
        }
    }

    return $os;
}
function browser($hua)
{//ブラウザの判別
    global $bralist,$robot;

    $find = false;

    if (preg_match($robot, $hua)) {//ロボット判別
        $bra = 'Robot,Prefetcher';

        $find = true;
    }

    reset($bralist);

    while (list($k_bra, $replace) = each($bralist)) {
        if (preg_match($k_bra, $hua) && !$find) {
            $find = true;

            $bra = $replace;

            break;
        }
    }

    if (!$find && '' != $hua) {
        $bra = 'Unknown Browser';
    }

    return $bra;
}

/*=============Main=============*/
$a = ['ref' => '4', 'eng' => '4', 'os' => '3', 'host' => '2'];
$b = ['ref' => 'リンク元', 'eng' => '検索エンジン', 'os' => '利用OS', 'host' => 'ホスト'];
$c = ['eng' => '検索文字列', 'os' => '利用ブラウザ', 'host' => 'co.jp'];
if ('ref' == $_POST[act]) {//ﾏｯﾁﾝｸﾞURL登録
    if ('' == $_POST[u] || '' == $_POST[t] || '' == $_POST[url]) {
        echo '<b>記入もれがあります</b>';
    } else {
        $url = rawurldecode($_POST[url]); //%文字をデコード
        if (get_magic_quotes_gpc()) {//￥マーク除去
          $u = stripslashes($_POST[u]);

            $t = stripslashes($_POST[t]);

            $url = stripslashes($_POST[url]);
        }

        $matching = "$u,$t,$url,\n"; //新規ﾏｯﾁﾝｸﾞ追加

        $fp = fopen($repfile, 'ab');

        flock($fp, 2);

        fwrite($fp, $matching);

        fclose($fp);

        echo htmlspecialchars($u, ENT_QUOTES | ENT_HTML5) . '=&gt; <a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5) . '" target=_blank><font color=blue>' . htmlspecialchars($t, ENT_QUOTES | ENT_HTML5) . '</font></a> を登録しました';
    }
}
$rep_arr = @file($repfile) || die("URI置換用 $repfile が見つかりません");
// メイン分岐
if ('ref' == $act || 'eng' == $act || 'os' == $act || 'host' == $act) {
    if ($sel) {
        echo '<div align=left><pre>';
    }

    while (list(, $dat) = each($all)) {
        $ret = '';

        $retb = '';

        $line = explode("\t", htmlspecialchars($dat, ENT_QUOTES | ENT_HTML5));

        if (1 == $line[5] && !empty($line[$a[$act]]) && 'none' != $line[$a[$act]]) {
            switch ($act) {
      case 'ref'://リンク元
        $ret = $line[4];
        reset($rep_arr);
        while (list(, $val) = each($rep_arr)) {//文字に置換
            [$prev, $next] = explode(',', $val);

            if ($prev && preg_match($prev, $line[4])) {
                $ret = $next;
            }
        }
        if ($noque && mb_strstr($ret, '?')) {
            $ret = mb_substr($ret, 0, mb_strpos($ret, '?'));
        }
        break;
      case 'eng'://検索エンジン、検索文字
        $retn = keywords($line[4]);
        $ret = $retn[0];
        $retb = $retn[1];
        break;
      case 'os'://OS、ブラウザ
        $ret = platform($line[3]);
        $retb = browser($line[3]);
        break;
      case 'host'://ホスト
        if (eregi('jp$', $line[2])) {
            $ret = eregi_replace(".+\.(.+\..+\..+)$", '\\1', $line[2]);
        } elseif (eregi('[[:alpha:]]', $line[2])) {
            $ret = preg_replace(".+\.(.+\..+)$", '\\1', $line[2]);
        } else {
            $ret = $line[2];
        }
        if (preg_match('co.jp', $ret)) {
            $retb = $ret;
        } elseif (preg_match('ac.jp', $ret)) {
            $ac[$ret]++;

            $actotal++;
        } elseif (preg_match('go.jp', $ret)) {
            $go[$ret]++;

            $gototal++;
        }
        break;
      }

            if (($sel == $ret || $sel == $retb) && !empty($sel)) {//生ログ閲覧
                printf("%-s %-40s %-70s %-s\n", $line[1], $line[2], $line[3], $line[4]);
            }

            if (!empty($ret)) {
                $data[$ret]++;

                $total++;
            }

            if (!empty($retb)) {
                $datab[$retb]++;

                $btotal++;
            }
        }
    }

    if ($sel) {
        die('</pre></div>');
    }

    // 表作成

    if (is_array($data)) {
        showtable($b[$act], $data, $total);
    }

    if (is_array($datab)) {
        showtable($c[$act], $datab, $btotal);
    }

    if (is_array($ac)) {
        showtable('ac.jp', $ac, $actotal);
    }

    if (is_array($go)) {
        showtable('go.jp', $go, $gototal);
    }

    if ('ref' == $act) {//置換用フォーム
        echo "<div align=left><form action=\"$PHP_SELF\" method=post><input type=hidden name=act value=ref>
  対象URI：<input type=text name=u size=70><br>タイトル ：<input type=text name=t size=50><br>
  置換URI：<input type=re name=url size=70>　<input type=submit name=submit value=\"変換\"></div>";
    }

    foot();
}
if ('last' == $act) {//最新ログ
    echo "\n<table width=95%><tr><th>Access</th><th>Host</th><th>UserAgent</th><th>Refererer</th></tr>";

    if ($page) {
        $st = $page;
    } else {
        $st = count($all) - 1;
    }

    for ($i = $st; $i > $st - $page_def; $i--) {
        $line = explode("\t", htmlspecialchars($all[$i], ENT_QUOTES | ENT_HTML5));

        $line[4] = urldecode($line[4]);

        if ($mb) {
            $line[4] = mb_convert_encoding($line[4], 'EUC', 'auto');
        } else {
            $line[4] = JcodeConvert($line[4], 0, 1);
        }

        printf(
            "<tr><td>%-s</td><td>%-35s</td><td>%-60s</td><td>%-s</td></tr>\n",
            $line[1],
            $line[2],
            $line[3],
            $line[4]
        );

        $s++;
    }

    $prev = $st + $page_def;

    $next = $st - $page_def;

    echo '<tr><td colspan=4 align=left>'; //ページング作成

    if ($page && $prev < count($all)) {
        echo "<a href=\"?f=$f&act=last&page=$prev\">&lt;&lt; PREV</a>";
    }

    if ($s >= $page_def && $next > 0) {
        echo " <a href=\"?f=$f&act=last&page=$next\">NEXT &gt;&gt;</a>";
    }

    echo "</tr></table><br><br>\n";

    foot();
}
if ('search' == $act) {//文字サーチ
    echo "<form action=\"$PHP_SELF?act=search&f=$f\" method=post>
  <input type=text name=w size=30 value='" . htmlspecialchars($w, ENT_QUOTES | ENT_HTML5) . "'>
<select name='andor'>
<option value='or' selected>OR
<option value='and'>AND
</select>
<input type=submit value=\"検索\"><br><br>
  ";

    $w = $_POST[w];

    $andor = $_POST[andor];

    if (get_magic_quotes_gpc()) {
        $w = stripslashes($w);
    }

    $w = htmlspecialchars($w, ENT_QUOTES | ENT_HTML5);

    $w = trim($w);

    if (!empty($w)) {
        $keys = preg_preg_split('/(　| )+/', $w);

        while (list(, $dat) = each($all)) {
            $find = false;

            for ($c = 0, $cMax = count($keys); $c < $cMax; $c++) {
                if (mb_stristr($dat, $keys[$c])) {//マッチしたものを色つけて配列に
                    $dat = eregi_replace($keys[$c], "<b style='color:green;background-color:#ffff66'>$keys[$c]</b>", $dat);

                    $find = true;
                } else {
                    if ('and' == $andor) {
                        $find = false;

                        break;
                    }
                }
            }

            if ($find) {
                $result[] = $dat;
            }
        }

        echo '<div align=left>検索結果' . count($result) . '件<pre>';

        while (list(, $data) = @each($result)) {
            $val = explode("\t", $data);

            printf("%-s %-40s %-70s %-s\n", $val[1], $val[2], $val[3], $val[4]);
        }

        echo '</pre></div>';

        if (!is_array($val)) {
            echo "<b>$w</b>に該当するデータが見つかりませんでした";
        }
    }

    foot();
}
//時間別(TOP)
if ('' != $h) {
    echo '<div align=left><pre>';
}
while (list(, $dat) = each($all)) {
    $line = explode("\t", $dat);

    $tim = explode(':', $line[1]);

    if ($h == $tim[0]) {
        printf("%-s %-40s %-70s %-s\n", $line[1], $line[2], $line[3], $line[4]);
    }

    if (1 == $line[5]) {//データの最後が1ならユニークアクセス
        $uniq[$tim[0]]++;

        $utotal++;
    }

    $cnt[$tim[0]]++;

    $total++;
}
if ('' != $h) {
    die('</pre></div>');
}
//グラフの高さは一番大きいのが基準
arsort($cnt);
[, $tval] = each($cnt);
$timmax = $tval;

echo "<div align=left><b>トータルアクセス : $total<br>ユニークアクセス: $utotal</b></div><br>";
echo '<table cellpadding=1 cellspacing=1 border=0><tr>';
for ($h = 0; $h < 24; $h++) {//グラフ作成
    $h = sprintf('%02d', $h);

    $hei = getper($cnt[$h], $timmax);

    $uhei = getper($uniq[$h], $timmax);

    echo '<td valign=bottom align=center width=25>';

    echo "<font size=-1>$cnt[$h]<br><font color=#806040>$uniq[$h]</font><br>";

    printf('<img src="%s?g=1" height=%d width=8>', $PHP_SELF, $hei[1] / 2);

    printf("<img src=\"%s?g=2\" height=%d width=8></td>\n", $PHP_SELF, $uhei[1] / 2);
}
echo '</tr><tr>';
for ($i = 0; $i < 24; $i++) {//時間表示
    echo "<td align=center><a href=\"?h=$i&f=$f\">$i</a></td>";
}
echo '</tr></table><br><br>';
//日別
echo '<br><table cellpadding=1 border=0>';
echo '<tr><th>Day</th><th>&nbsp;Num&nbsp;</th>';
echo "<th class=vis>&nbsp;Uniq&nbsp;</th><th class=vis>&nbsp;Per&nbsp;</th></tr>\n";
$d = dir($logdir);
while ($entry = $d->read()) {//ディレクトリ走査
    if (preg_match("^[0-9]{8}$suf", $entry)) {
        $dlog_arr = file("$logdir$entry");

        $unique = '';

        while (list(, $dat) = each($dlog_arr)) {//ユニーク
            $line = explode("\t", $dat);

            if (1 == $line[5]) {
                $unique++;
            }
        }

        $daynum = count($dlog_arr); //総アクセスは行数
    $day[$i][name] = mb_substr($entry, 0, 8); //20010101
    $day[$i][dnum] = $daynum;

        $day[$i][uniq] = $unique;

        if ($daymax < $daynum) {
            $daymax = $daynum;
        } //グラフ用

        $i++;
    }
}
$d->close();
asort($day);
while (list(, $val) = each($day)) {//日別推移表示
    $per = getper($val[dnum], $daymax);

    $uper = getper($val[uniq], $daymax);

    printf(
        '<tr><td>&nbsp;<a href="?f=%s">%4d-%2d-%2d</a>&nbsp;',
        $val[name],
        mb_substr($val[name], 0, 4),
        mb_substr($val[name], 4, 2),
        mb_substr($val[name], 6, 2)
    );

    echo "<td>&nbsp;$val[dnum]&nbsp;</td><td>&nbsp;$val[uniq]&nbsp;</td>";

    echo "<td><img src=\"$PHP_SELF?g=1\" height=8 width=$per[1]>&nbsp;<br>";

    echo "<img src=\"$PHP_SELF?g=2\" height=8 width=$uper[1]>&nbsp;</td></tr>\n";
}
echo '</table><br><br>';
foot();
?>
