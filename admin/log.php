<?php

/****************
 * PHP Analyzer  ログ取得用   by ToR
 *
 * http://php.s3.to/
 *
 * 2001/06/11 何度か書き直して完成
 * 2001/10/13 flagがある時もデータ記録してた
 * 2002/11/20 ユニークチェック他更新
 * 2004/01/30 ロック
 *
 * こちらが記録専用スクリプトです。
 * データをとりたいPHPページ内で<?include "log.php";?>を挿入して下さい
 * ログ用ディレクトリを作成してパーミッションを777にします
 *
 * =====データ形式
 * 20010101.txt ファイルの最後が新しいログ
 * $data[0] 2001/05/01[Mon]	$data[1] 11:12:33	$data[2] www.xxx.com
 * $data[3] Mozilla4.0 		$data[4] http://www. 	$data[5] 1(ﾕﾆｰｸ=1)
 */
//---------設定（ana.phpも設定してください./log/
$logdir = './log/';        //ログ格納ディレクトリ
$suf = '.txt';          //ログ拡張子
$f_ip = './log/ip.txt';  //IPチェック用ファイル
$timout = 24;             //同一IPの保存時間（ﾕﾆｰｸｱｸｾｽって1日？
$f_log = './log/past.txt'; //ログNO保存ファイル
$r_day = 7;               //何日間ログを保存するか（古いログから削除されます

//以下の文字が含まれるホストはログ取らない（,で区切る)
$banip = ['ayu.as.wakwak.ne.jp'];
//以下の文字が含まれるアクセス（リンク元）は記録しない
$banref = ['php.s3.to'];

function checkIP($rem_addr)
{//ユニークアクセスのチェック
    global $f_ip,$timout;

    $ip_arr = file($f_ip);

    $now = time();

    $find = false;

    $fp = fopen($f_ip, 'wb');

    foreach ($ip_arr as $ip_dat) {
        [$ip_addr, $tim_stmp] = explode('|', $ip_dat);

        if (($now - $tim_stmp) < $timout * 3600) {//タイムアウトしてないのはそのまま
      if ($ip_addr == $rem_addr) {   //同一IPならTRUEを返す
        $find = true;
      } else {
          fwrite($fp, "$ip_addr|$tim_stmp|\n");
      }
        }
    }

    fwrite($fp, "$rem_addr|$now|\n"); //新規追加

    fclose($fp);

    return $find;
}
function Rotate($prefix)
{//ログのローテーション
    global $logdir,$suf,$logfile,$f_log,$r_day;

    $fp = @fopen($f_log, 'ab') || die($f_log . 'が開けません');

    fwrite($fp, "$prefix\n"); //現ログNO.を追記録

    fclose($fp);

    $log_arr = file($f_log);

    $cnt_log = count($log_arr);

    if ($cnt_log >= $r_day) {//記録回数オーバーなら古いもの削除
        $newline = $cnt_log - $r_day;

        foreach ($log_arr as $i => $no) {
            if ($i < $newline) {
                unlink($logdir . rtrim($no) . $suf);

                $log_arr[$i] = '';
            }
        }

        $lp = @fopen($f_log, 'wb') || die($f_log . 'が開けません');

        stream_set_write_buffer($lp, 0);

        flock($lp, LOCK_EX);

        fwrite($lp, implode('', $log_arr));

        fclose($lp);
    }
}
$data = '';
$now_date = gmdate("Y/m/d[D]\tH:i:s", time() + 9 * 3600); //ログ記録日付
$prefix = gmdate('Ymd', time() + 9 * 3600);              //ログ名日付
//ログNO記録から一日経過したらローテーション
if ((time() - filemtime($f_log)) > 24 * 3600) {
    Rotate($prefix);
}
//ホストとアドレス名を取得。ホスト取得出来なければ逆引き
$host = gethostbyaddr(getenv('REMOTE_ADDR'));
//拒否ホスト
for ($j = 0, $jMax = count($banip); $j < $jMax; $j++) {
    if ($banip[$j] && mb_strstr($host, $banip[$j])) {
        $flag = true;
    }
}
//ブラウザ名取得。一応区切り文字削除
$agent = getenv('HTTP_USER_AGENT');
$agent = str_replace("\t", '', $agent);
//リンク元取得。空なら「none」
$ref = getenv('HTTP_REFERER');
if ('' == $ref) {
    $ref = 'none';
}
if (mb_strrpos($ref, '?') == mb_strlen($ref) - 1) {
    $ref = mb_substr($ref, 0, -1);
}
//拒否リンク元
for ($i = 0, $iMax = count($banref); $i < $iMax; $i++) {
    if ($banref[$i] && mb_stristr($ref, $banref[$i])) {
        $flag = true;
    }
}
$ref = str_replace("\t", '', $ref);
//ユニークチェック(ユニークアクセスならデータの最後にフラグ１
if (!checkIP($host)) {
    $uniq = 1;
}
//ファイル名。
$logfile = $logdir . $prefix . $suf;
//フォーマット
if (!$flag) {
    $data = "$now_date\t$host\t$agent\t$ref\t$uniq\n";

    //ファイルの後ろにどんどん追加。

    if (!file_exists($logfile)) {
        $fp = fopen($logfile, 'wb');

        fclose($fp);

        @chmod($logfile, 0666);
    }

    $fp = fopen($logfile, 'ab');

    stream_set_write_buffer($fp, 0);

    flock($fp, LOCK_EX);

    fwrite($fp, $data);

    fclose($fp);
}
