<?php

/* vim: set fdm=marker: */

/**************************************************************************
 * ________________________________
 *
 * jcode-LE.php by TOMO
 * ________________________________
 *
 * ■ 概要
 *
 * jcode-LE.phpはPHP用日本語文字コード変換ユーティリティjcode.phpsの
 * 簡易版(Light Edition)です。EUC/SJIS/JISを相互に変換することができます。
 * また文字コードの判別と半角<->全角の変換が可能です。使用方法は同梱の
 * readme.txtをお読み下さい。
 *
 * ■ バージョン
 *
 * 1.7 (2002/08/09)
 *
 * ■ 変更履歴
 *
 * v1.7: AutoDetect()のロジックを変更
 * JISがらみの関数のバグを修正
 * v1.6: 名前を変更
 * 半角->全角変換で濁点/半濁点が付く文字が一文字になるように修正。
 * v1.5: 自動判別の方法を変更。半角カナ混じりの判別もサポート。
 * v1.4: 処理の高速化。値渡しから参照渡しに変更。
 * v1.3: HANtoZEN_JISの処理を改善。JIStoSJIS、JIStoEUCの不具合修正。
 * 全角->半角サポート。
 * v1.2: JIStoSJIS, JIStoEUC, HANtoZEN_JISで末尾のアスキー文字1文字が削除
 * されるのを修正
 * v1.1: EUC→SJISとJIS→SJISへの変換で一部文字化けの不具合修正
 *
 * ■ 利用規定
 *
 * 著作権は放棄しませんが、スクリプトの一部または全部を使用・改造・再配
 * 布することは自由です。
 * このスクリプトを使用したことで生じたいかなる不都合・損害にも作者は一
 * 切その責任を負いません。
 *
 * ■ 作者
 *
 * TOMO <groove@spencernetwork.org>
 *
 * ■ 配布元
 *
 * http://www.spencernetwork.org/jcode-LE/
 **************************************************************************
 * @param $str
 * @param $from
 * @param $to
 * @return string
 */

/* {{{ JcodeConvert() */
function JcodeConvert($str, $from, $to)
{
    //0:AUTO DETECT

    //1:EUC-JP

    //2:Shift_JIS

    //3:ISO-2022-JP(JIS)

    if (0 == $from) {
        $from = AutoDetect($str);
    }

    if (1 == $from && 2 == $to) {
        return EUCtoSJIS($str);
    }

    if (1 == $from && 3 == $to) {
        return EUCtoJIS($str);
    }

    if (2 == $from && 1 == $to) {
        return SJIStoEUC($str);
    }

    if (2 == $from && 3 == $to) {
        return SJIStoJIS($str);
    }

    if (3 == $from && 1 == $to) {
        return JIStoEUC($str);
    }

    if (3 == $from && 2 == $to) {
        return JIStoSJIS($str);
    }

    return $str;
} /* }}} JcodeConvert() */

/* {{{ AutoDetect() */
function AutoDetect($str)
{
    //0:US-ASCII

    //1:EUC-JP

    //2:Shift_JIS

    //3:ISO-2022-JP(JIS)

    //4:(Not available)

    //5:Unknown

    if (!preg_match("[\x80-\xFF]", $str)) {
        // --- Check ISO-2022-JP ---

        if (preg_match("\x1B", $str)) {
            return 3;
        } // ISO-2022-JP(JIS)
        return 0; //US-ASCII
    }

    $b = unpack('C*', preg_replace("^[^\x80-\xFF]+", '', $str));

    $n = count($b);

    // --- Check EUC-JP ---

    $euc = true;

    for ($i = 1; $i <= $n; ++$i) {
        if ($b[$i] < 0x80) {
            continue;
        }

        if ($b[$i] < 0x8E) {
            $euc = false;

            break;
        }

        if (0x8E == $b[$i]) {
            if (!isset($b[++$i])) {
                $euc = false;

                break;
            }

            if (($b[$i] < 0xA1) || ($b[$i] > 0xDF)) {
                $euc = false;

                break;
            }
        } elseif (($b[$i] >= 0xA1) && ($b[$i] <= 0xFE)) {
            if (!isset($b[++$i])) {
                $euc = false;

                break;
            }

            if (($b[$i] < 0xA1) || ($b[$i] > 0xFE)) {
                $euc = false;

                break;
            }
        } else {
            $euc = false;

            break;
        }
    }

    if ($euc) {
        return 1;
    } // EUC-JP

    // --- Check Shift_JIS ---

    $sjis = true;

    for ($i = 1; $i <= $n; ++$i) {
        if (($b[$i] <= 0x80) || ($b[$i] >= 0xA1 && $b[$i] <= 0xDF)) {
            continue;
        }

        if ((0xA0 == $b[$i]) || ($b[$i] > 0xEF)) {
            $sjis = false;

            break;
        }

        if (!isset($b[++$i])) {
            $sjis = false;

            break;
        }

        if (($b[$i] < 0x40) || (0x7F == $b[$i]) || ($b[$i] > 0xFC)) {
            $sjis = false;

            break;
        }
    }

    if ($sjis) {
        return 2;
    } // Shift_JIS
    return 5; // Unknown
} /* }}} AutoDetect() */

/* {{{ HANtoZEN() */
function HANtoZEN(&$str, $encode)
{
    //0:PASS

    //1:EUC-JP

    //2:Shift_JIS

    //3:ISO-2022-JP(JIS)

    if (0 == $encode) {
        return $str;
    }

    if (1 == $encode) {
        return HANtoZEN_EUC($str);
    }

    if (2 == $encode) {
        return HANtoZEN_SJIS($str);
    }

    if (3 == $encode) {
        return HANtoZEN_JIS($str);
    }

    return $str;
} /* }}} HANtoZEN() */

/* {{{ ZENtoHAN() */
function ZENtoHAN(&$str, $encode, $kana = 1, $alph = 1)
{
    //0:PASS

    //1:EUC-JP

    //2:Shift_JIS

    //3:ISO-2022-JP(JIS)

    if (0 == $encode) {
        return $str;
    }

    if (1 == $encode) {
        return ZENtoHAN_EUC($str, $kana, $alph, $kana);
    }

    if (2 == $encode) {
        return ZENtoHAN_SJIS($str, $kana, $alph, $kana);
    }

    if (3 == $encode) {
        return ZENtoHAN_JIS($str, $kana, $alph, $kana);
    }

    return $str;
} /* }}} ZENtoHAN() */

/* {{{ JIStoSJIS() */
function JIStoSJIS($str_JIS)
{
    $str_SJIS = '';

    $mode = 0;

    $b = unpack('C*', $str_JIS);

    $n = count($b);

    for ($i = 1; $i <= $n; ++$i) {
        //Check escape sequence

        while (0x1B == $b[$i]) {
            if ((0x24 == $b[$i + 1] && 0x42 == $b[$i + 2])
                || (0x24 == $b[$i + 1] && 0x40 == $b[$i + 2])) {
                $mode = 1;
            } elseif ((0x28 == $b[$i + 1] && 0x49 == $b[$i + 2])) {
                $mode = 2;
            } else {
                $mode = 0;
            }

            $i += 3;

            if (!isset($b[$i])) {
                break 2;
            }
        }

        //Do convert

        if (1 == $mode) {
            $b1 = $b[$i];

            $b2 = $b[++$i];

            if ($b1 & 0x01) {
                $b1 >>= 1;

                if ($b1 < 0x2F) {
                    $b1 += 0x71;
                } else {
                    $b1 -= 0x4F;
                }

                if ($b2 > 0x5F) {
                    $b2 += 0x20;
                } else {
                    $b2 += 0x1F;
                }
            } else {
                $b1 >>= 1;

                if ($b1 <= 0x2F) {
                    $b1 += 0x70;
                } else {
                    $b1 -= 0x50;
                }

                $b2 += 0x7E;
            }

            $str_SJIS .= chr($b1) . chr($b2);
        } elseif (2 == $mode) {
            $str_SJIS .= chr($b[$i] + 0x80);
        } else {
            $str_SJIS .= chr($b[$i]);
        }
    }

    return $str_SJIS;
} /* }}} JIStoSJIS() */

/* {{{ JIStoEUC() */
function JIStoEUC($str_JIS)
{
    $str_EUC = '';

    $mode = 0;

    $b = unpack('C*', $str_JIS);

    $n = count($b);

    for ($i = 1; $i <= $n; ++$i) {
        //Check escape sequence

        while (0x1B == $b[$i]) {
            if ((0x24 == $b[$i + 1] && 0x42 == $b[$i + 2])
                || (0x24 == $b[$i + 1] && 0x40 == $b[$i + 2])) {
                $mode = 1;
            } elseif ((0x28 == $b[$i + 1] && 0x49 == $b[$i + 2])) {
                $mode = 2;
            } else {
                $mode = 0;
            }

            $i += 3;

            if (!isset($b[$i])) {
                break 2;
            }
        }

        //Do convert

        if (1 == $mode) {
            $str_EUC .= chr($b[$i] + 0x80) . chr($b[++$i] + 0x80);
        } elseif (2 == $mode) {
            $str_EUC .= chr(0x8E) . chr($b[$i] + 0x80);
        } else {
            $str_EUC .= chr($b[$i]);
        }
    }

    return $str_EUC;
} /* }}} JIStoEUC() */

/* {{{ SJIStoJIS() */
function SJIStoJIS($str_SJIS)
{
    $str_JIS = '';

    $mode = 0;

    $b = unpack('C*', $str_SJIS);

    $n = count($b);

    //Escape sequence

    $ESC = [chr(0x1B) . chr(0x28) . chr(0x42),
             chr(0x1B) . chr(0x24) . chr(0x42),
             chr(0x1B) . chr(0x28) . chr(0x49),
];

    for ($i = 1; $i <= $n; ++$i) {
        $b1 = $b[$i];

        if ($b1 >= 0xA1 && $b1 <= 0xDF) {
            if (2 != $mode) {
                $mode = 2;

                $str_JIS .= $ESC[$mode];
            }

            $str_JIS .= chr($b1 - 0x80);
        } elseif ($b1 >= 0x80) {
            if (1 != $mode) {
                $mode = 1;

                $str_JIS .= $ESC[$mode];
            }

            $b2 = $b[++$i];

            $b1 <<= 1;

            if ($b2 < 0x9F) {
                if ($b1 < 0x13F) {
                    $b1 -= 0xE1;
                } else {
                    $b1 -= 0x61;
                }

                if ($b2 > 0x7E) {
                    $b2 -= 0x20;
                } else {
                    $b2 -= 0x1F;
                }
            } else {
                if ($b1 < 0x13F) {
                    $b1 -= 0xE0;
                } else {
                    $b1 -= 0x60;
                }

                $b2 -= 0x7E;
            }

            $str_JIS .= chr($b1) . chr($b2);
        } else {
            if (0 != $mode) {
                $mode = 0;

                $str_JIS .= $ESC[$mode];
            }

            $str_JIS .= chr($b1);
        }
    }

    if (0 != $mode) {
        $str_JIS .= $ESC[0];
    }

    return $str_JIS;
} /* }}} SJIStoJIS() */

/* {{{ SJIStoEUC() */
function SJIStoEUC($str_SJIS)
{
    $b = unpack('C*', $str_SJIS);

    $n = count($b);

    $str_EUC = '';

    for ($i = 1; $i <= $n; ++$i) {
        $b1 = $b[$i];

        if ($b1 >= 0xA1 && $b1 <= 0xDF) {
            $str_EUC .= chr(0x8E) . chr($b1);
        } elseif ($b1 >= 0x81) {
            $b2 = $b[++$i];

            $b1 <<= 1;

            if ($b2 < 0x9F) {
                if ($b1 < 0x13F) {
                    $b1 -= 0x61;
                } else {
                    $b1 -= 0xE1;
                }

                if ($b2 > 0x7E) {
                    $b2 += 0x60;
                } else {
                    $b2 += 0x61;
                }
            } else {
                if ($b1 < 0x13F) {
                    $b1 -= 0x60;
                } else {
                    $b1 -= 0xE0;
                }

                $b2 += 0x02;
            }

            $str_EUC .= chr($b1) . chr($b2);
        } else {
            $str_EUC .= chr($b1);
        }
    }

    return $str_EUC;
} /* }}} SJIStoEUC() */

/* {{{ EUCtoJIS() */
function EUCtoJIS($str_EUC)
{
    $str_JIS = '';

    $mode = 0;

    $b = unpack('C*', $str_EUC);

    $n = count($b);

    //Escape sequence

    $ESC = [chr(0x1B) . chr(0x28) . chr(0x42),
             chr(0x1B) . chr(0x24) . chr(0x42),
             chr(0x1B) . chr(0x28) . chr(0x49),
];

    for ($i = 1; $i <= $n; ++$i) {
        $b1 = $b[$i];

        if (0x8E == $b1) {
            if (2 != $mode) {
                $mode = 2;

                $str_JIS .= $ESC[$mode];
            }

            $str_JIS .= chr($b[++$i] - 0x80);
        } elseif ($b1 > 0x8E) {
            if (1 != $mode) {
                $mode = 1;

                $str_JIS .= $ESC[$mode];
            }

            $str_JIS .= chr($b1 - 0x80) . chr($b[++$i] - 0x80);
        } else {
            if (0 != $mode) {
                $mode = 0;

                $str_JIS .= $ESC[$mode];
            }

            $str_JIS .= chr($b1);
        }
    }

    if (0 != $mode) {
        $str_JIS .= $ESC[0];
    }

    return $str_JIS;
}/* }}} EUCtoJIS() */

/* {{{ EUCtoSJIS() */
function EUCtoSJIS($str_EUC)
{
    $str_SJIS = '';

    $b = unpack('C*', $str_EUC);

    $n = count($b);

    for ($i = 1; $i <= $n; ++$i) {
        $b1 = $b[$i];

        if ($b1 > 0x8E) {
            $b2 = $b[++$i];

            if ($b1 & 0x01) {
                $b1 >>= 1;

                if ($b1 < 0x6F) {
                    $b1 += 0x31;
                } else {
                    $b1 += 0x71;
                }

                if ($b2 > 0xDF) {
                    $b2 -= 0x60;
                } else {
                    $b2 -= 0x61;
                }
            } else {
                $b1 >>= 1;

                if ($b1 <= 0x6F) {
                    $b1 += 0x30;
                } else {
                    $b1 += 0x70;
                }

                $b2 -= 0x02;
            }

            $str_SJIS .= chr($b1) . chr($b2);
        } elseif (0x8E == $b1) {
            $str_SJIS .= chr($b[++$i]);
        } else {
            $str_SJIS .= chr($b1);
        }
    }

    return $str_SJIS;
}/* }}} EUCtoSJIS() */
