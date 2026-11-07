<?php

namespace RouterOSFramework\Core;

/**
 * Universal RouterOS Automation Engine
 *
 * Provides authentic, high-speed, ISP-grade MikroTik RouterOS automation scripts
 * (Hotspot Lifecycle, PPPoE Isolir, Schedulers, Certificates, Clock Recovery, Live Reporting).
 */
class AutomationEngine
{
    /**
     * Get /system scheduler on-event script for autoremove-report.
     * Cleans up sales report scripts older than 3 months.
     */
    public static function getAutoremoveReportScript(): string
    {
        return <<<'MKSCRIPT'
:local date [ /system clock get date ];
:local montharrayA ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );
:local montharrayB ("oct","nov","dec" );
:local day [:tonum [:pick $date 4 6 ]];
:local month [ :pick $date 0 3 ];
:local year [ :pick $date 7 11 ];
:local monthint ([ :find $montharrayA $month] + 1);
:local monthlast  ( $monthint - 3);
:if ($day > 9) do={
:if ( $monthlast > 0 ) do={
:local mmm "$[:pick $montharrayA ($monthlast - 1)]";
:local monthdell "$mmm$year";
/sys scr rem [find where owner=$monthdell];
}
:if ( $monthlast < 1) do={
:local mkey ([$monthint - $monthlast ])
:local mmm "$[:pick $montharrayB ($mkey - 1)]";
:local y  ($year - 1);
:local monthdell "$mmm$y";
/sys scr rem [find where owner=$monthdell];
}
}
MKSCRIPT;
    }

    /**
     * Get /system scheduler on-event script for Expire-Monitor (v2603001).
     * Periodically monitors hotspot users and disables/removes expired users.
     */
    public static function getExpireMonitorScript(): string
    {
        return <<<'MKSCRIPT'
:local expMonVer 2603001; :local expireMonitor [/certificate find where name="expireMonitor"]; :if ([:len $expireMonitor] > 1) do={ /certificate remove [find where name="expireMonitor"] }; :delay 1; :if ([:len $expireMonitor] = 0) do={ /certificate add name="expireMonitor" unit="0#0#0#0#" days-valid=2 key-size=1024; :set expireMonitor [/certificate find where name="expireMonitor"] }; :local dateint do={ :local montharray ("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec"); :local days [:pick $d 4 6]; :local month [:pick $d 0 3]; :local year [:pick $d 7 11]; :local monthint ([:find $montharray $month]); :local month ($monthint + 1); :if ([:len [:tostr $month]] = 1) do={ :local zero "0"; :return [:tonum ("$year$zero$month$days")] } else={ :return [:tonum ("$year$month$days")] } }; :local timeint do={ :local hours [:tonum [:pick $t 0 2]]; :local minutes [:tonum [:pick $t 3 5]]; :return ($hours * 60 + $minutes) }; :local convert do={ :local monthr {"jan";"feb";"mar";"apr";"may";"jun";"jul";"aug";"sep";"oct";"nov";"dec"}; :local dd [:pick $date 8 11]; :local yy [:tonum [:pick $date 0 4]]; :local mm [:tonum [:pick $date 5 7]]; :local mmm "$[:pick $monthr ($mm-1)]"; :local newdate "$mmm/$dd/$yy"; :return $newdate }; :local getField do={ :local line $1; :local delimiter $2; :local index $3; :local start 0; :local end 0; :local count 0; :do { :set end [:find $line $delimiter $start]; :if ($end = -1) do={ :set end [:len $line] }; :if ($count = $index) do={ :return [:pick $line $start $end] }; :set start ($end + [:len $delimiter]); :set count ($count + 1) } while=($end < [:len $line]); :return "" }; :local validateTime do={ :if ([:pick $time 2 3] = ":" and [:pick $time 5 6] = ":") do={ :return $time } else={ :local anomaloustime [:pick $time 5 7]; :set anomaloustime "$anomaloustime:59:59"; :return $anomaloustime } }; :local date [/system clock get date]; :local ldate $date; :if ([:pick $date 4 5] = "-" and [:pick $date 7 8] = "-") do={ :set date [$convert date=$date] } else={ :set date $date }; :local time [/system clock get time]; :local today [$dateint d=$date]; :local curtime [$timeint t=$time]; :local tyear [:pick $date 7 11]; :local lyear ($tyear-1); :local nyear ($tyear+1); :local statusValue [/certificate get $expireMonitor unit]; :local status [:tonum [$getField $statusValue "#" 0]]; :local start [:tonum [$getField $statusValue "#" 1]]; :local checkUsers [:tonum [$getField $statusValue "#" 2]]; :local lastRun [$getField $statusValue "#" 3]; :if ([($curtime - $start)] > 9 and $status = 1) do={ /certificate set $expireMonitor unit="0#$curtime#$checkUsers#$time#"; :delay 0.2; :local schowner [/sys sch get [find name=Expire-Monitor] owner]; /sys scr job rem [find where owner=$schowner] }; do { :if ($status != 1) do={ :local userlogin [/ip hotspot user find where comment~"/$tyear" || comment~"/$lyear" || comment~"$tyear-" || comment~"$lyear-" || comment~"/$nyear" || comment~"$nyear-"]; :local totlogin 0; :local expId ""; :foreach i in=$userlogin do={ :local userinfo [/ip hotspot user get $i]; :local comment ($userinfo -> "comment"); :local expdate ""; :local exptime ""; :local gettime ""; :if ([:pick $comment 4 5] = "-" and [:pick $comment 7 8] = "-") do={ :set expdate [$convert date=[:pick $comment 0 10]]; :set gettime [:pick $comment 11 20]; :set gettime [$validateTime time=$gettime]; :set exptime [$timeint t=$gettime] } else={ :set expdate [:pick $comment 0 11]; :set gettime [:pick $comment 12 20]; :set gettime [$validateTime time=$gettime]; :set exptime [$timeint t=$gettime] }; :if ([:pick $expdate 3] = "/" and [:pick $expdate 6] = "/") do={ :set totlogin ([:tonum $totlogin] + 1); :local expd [$dateint d=$expdate]; :if (($expd < $today) or ($expd = $today and $exptime < $curtime)) do={ :local limit ($userinfo -> "limit-uptime"); :local name ($userinfo -> "name"); :local mode ""; :if ($limit != "00:00:01") do={ :if ([:pick $comment 21] = "N") do={ :set mode "N" } else={ :set mode "R" }; :set expId "$expId,$i|$name|$mode" } } } }; /log warning "checking $totlogin users login..."; /certificate set $expireMonitor unit="1#$curtime#$totlogin#$time#"; :set expId [:toarray $expId]; :local totExpId [:len $expId]; :delay 0.2; :local removed 0; :local expired 0; :if ($totExpId > 0) do={ :local expiredN ""; :local expiredR ""; /log warning "about to expire $totExpId user(s)..."; :foreach i in=$expId do={ :local user [$getField $i "|" 0]; :local name [$getField $i "|" 1]; :local mode [$getField $i "|" 2]; :if ($mode = "N") do={ [/ip hotspot user set limit-uptime=1s $user]; [/ip hotspot active remove [find where user=$name]]; :set expired ($expired + 1); :set expiredN "| $expired expired" } else={ :set removed ($removed + 1); [/ip hotspot user remove $user]; [/ip hotspot active remove [find where user=$name]]; :set expiredR "| $removed removed" } }; /log warning "expire monitor done! $expiredR $expiredN"; } else={ /log warning "expire monitor done! no expired users found"; }; /certificate set $expireMonitor unit="0#$curtime#$totlogin#$time#" } } on-error={ /certificate set $expireMonitor unit="0#$curtime#$checkUsers#$time#"; :delay 0.2; /log warning "expire monitor error."; }
MKSCRIPT;
    }

    /**
     * Get /system scheduler on-event script for isolir (PPPoE Secret Isolir Monitor).
     * Checks ppp secret comments against system clock, sets profile=isolir and drops active connection when overdue.
     */
    public static function getIsolirSchedulerScript(): string
    {
        return <<<'MKSCRIPT'
:local dateint do={
:local montharray ( "jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec" );
:local days [ :pick $d 4 6 ];
:local month [ :pick $d 0 3 ];
:local year [ :pick $d 7 11 ];
:local monthint ([ :find $montharray $month]);
:local month ($monthint + 1);
:if ( [len $month] = 1) do={:local zero ("0");
:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];}};

:local timeint do={:local hours [ :pick $t 0 2 ];
:local minutes [ :pick $t 3 5 ];
:return ($hours * 60 + $minutes) ;};

:local date [ /system clock get date ];
:local time [ /system clock get time ];
:local today [$dateint d=$date] ;
:local curtime [$timeint t=$time] ;
:local tyear [ :pick $date 7 11 ];
:local lyear ($tyear-1);

:foreach i in [ /ppp secret find where comment~"/$tyear" || comment~"/$lyear" ] do={
:local comment [ /ppp secret get $i comment]; 
:local komen [:pick $comment 0 100];
:local cekin [ /ppp secret get $i name]; 
:local gettime [:pick $comment 12 20]; 
:if ([:pick $comment 3] = "/" and [:pick $comment 6] = "/") do={
   :local expd [$dateint d=$comment] ; 
   :local expt [$timeint t=$gettime] ; 
   :local profileName [ /ppp secret get $i profile ];
   :local newComment ("BELUM BAYAR | Exp: " . $comment . " " . $gettime . " | pelanggan: " . $profileName);
   :if (($expd < $today and $expt < $curtime) or ($expd < $today and $expt > $curtime) or ($expd = $today and $expt < $curtime)) do={ 
      [ /ppp secret set profile=isolir $i ]; 
      [ /ppp secret set comment=$newComment $i ]; 
      [ /ppp active remove [find where name=$cekin] ]; 
   }
}
}
MKSCRIPT;
    }

    /**
     * Get /system scheduler on-event script for Live-Report-Monitor (v2608001).
     * Computes live daily & monthly sales revenue using certificates liveReport and liveReportBase.
     */
    public static function getLiveReportMonitorScript(): string
    {
        return <<<'MKSCRIPT'
:local liveReportVer 2608001; :local liveReport [/certificate find where name="liveReport"]; :if ([:len $liveReport] > 1) do={ /certificate remove [find where name="liveReport"]; }; :local liveReportBase [/certificate find where name="liveReportBase"]; :if ([:len $liveReportBase] > 1) do={ /certificate remove [find where name="liveReportBase"]; }; :delay 1; :if ([:len $liveReport] = 0) do={ /certificate add name="liveReport" common-name="0#0#;;|;;#0#" days-valid=2 key-size=1024; :set liveReport [/certificate find where name="liveReport"]; }; :if ([:len $liveReportBase] = 0) do={ /certificate add name="liveReportBase" common-name="|0|0|0|0|0|0" days-valid=2 key-size=1024; :set liveReportBase [/certificate find where name="liveReportBase"]; }; :local convert do={ :local monthr {"jan";"feb";"mar";"apr";"may";"jun";"jul";"aug";"sep";"oct";"nov";"dec"}; :local dd [:pick $date 8 11]; :local yy [:tonum [:pick $date 0 4]]; :local mm [:tonum [:pick $date 5 7]]; :local mmm "$[:pick $monthr ($mm-1)]"; :local newdate "$mmm/$dd/$yy"; :return $newdate; }; :local timeint do={ :local hours [:pick $t 0 2]; :local minutes [:pick $t 3 5]; :return ($hours * 60 + $minutes); }; :local fSplit do={ :local inputString [:tostr $1]; :local delimiter [:tostr $2]; :local res ({}); :local lastPos 0; :local delimPos [:find $inputString $delimiter]; :while ([:len $delimPos] > 0) do={ :set ($res->[:len $res]) [:pick $inputString $lastPos $delimPos]; :set lastPos ($delimPos + [:len $delimiter]); :set delimPos [:find $inputString $delimiter $lastPos]; }; :set ($res->[:len $res]) [:pick $inputString $lastPos [:len $inputString]]; :return $res; }; :local getExecutionTime do={ :local startTime $1; :local endTime $2; :local executionTime 0; :local startSeconds (([:tonum [:pick $startTime 0 2]]) * 3600 + ([:tonum [:pick $startTime 3 5]] * 60 ) + ([:tonum [:pick $startTime 6 8]])); :local endSeconds (([:tonum [:pick $endTime 0 2]]) * 3600 + ([:tonum [:pick $endTime 3 5]] * 60 ) + ([:tonum [:pick $endTime 6 8]])); :set executionTime ($endSeconds - $startSeconds); :return $executionTime; }; :local getField do={ :local line $1; :local delimiter $2; :local index $3; :local start 0; :local end 0; :local count 0; :do { :set end [:find $line $delimiter $start]; :if ($end = -1) do={ :set end [:len $line]; }; :if ($count = $index) do={ :return [:pick $line $start $end]; }; :set start ($end + [:len $delimiter]); :set count ($count + 1); } while=($end < [:len $line]); :return ""; }; :do { :local time [/system clock get time]; :local curtime [$timeint t=$time]; :local reportValue [/certificate get $liveReport common-name]; :local reportData [$fSplit $reportValue "#"]; :local status [:tonum ($reportData->0)]; :local start [:tonum ($reportData->1)]; :local summary ($reportData->2); :local lastFull [:tonum ($reportData->3)]; :local baseValue [/certificate get $liveReportBase common-name]; :local baseData [$fSplit $baseValue "|" 0]; :local baseDataMonthOwner ($baseData->0); :local baseDataMonthCount [:tonum ($baseData->1)]; :local baseDataMonthSum [:tonum ($baseData->2)]; :local baseDataDayOwner ($baseData->3); :local baseDataDayCount [:tonum ($baseData->4)]; :local baseDataDaySum [:tonum ($baseData->5)]; :if ([($curtime - $start)] > 5 and $status = 1) do={ /certificate set $liveReport common-name="0#$curtime#$summary#$lastFull#" unit="" key-size=1024; /certificate set $liveReportBase common-name="|0|0|0|0|0|0" unit="executionTime:"; }; :if ($status = 0) do={ /certificate set $liveReport common-name="1#$curtime#$summary#$lastFull#"; :local runMode ""; :local targetOwner ""; :local targetDate [/system clock get date]; :if ([:pick $targetDate 4 5] = "-" and [:pick $targetDate 7 8] = "-") do={ :set targetDate [$convert date=$targetDate]; :local mm [:pic $targetDate 0 3]; :local yy [:pic $targetDate 7 11]; :set targetOwner "$mm$yy"; } else={ :set targetDate $targetDate; :local mm [:pic $targetDate 0 3]; :local yy [:pic $targetDate 7 11]; :set targetOwner "$mm$yy"; }; :local startTime [/system clock get time]; :local endTime "00:00:00"; :local executionTime 0; :local totalHarga 0; :local totalHargaCount 0; :local totalHargaByDate 0; :local totalHargaByDateCount 0; :local diff [($curtime - $lastFull)]; :if (($diff >= 30) || ($diff < 0) || $targetOwner != $baseDataMonthOwner) do={ :set runMode "full-month"; :set lastFull $curtime; :local scripts [/system script find source=$targetOwner]; :if ([:len $scripts] = 0) do={ :set summary "0#$curtime#;;|;;||#"; :set endTime [/system clock get time]; :set executionTime [$getExecutionTime $startTime $endTime]; /certificate set $liveReport common-name="0#$curtime#$summary#$lastFull#"; /certificate set $liveReportBase common-name="$targetOwner|0|0|$targetDate|0|0|$runMode" unit="executionTime: $executionTime seconds"; } else={ :foreach script in=$scripts do={ :local scriptName [/system script get $script name]; :local hargaStr [$getField $scriptName "-|-" 3]; :local scriptDate [$getField $scriptName "-|-" 0]; :local hargaNum [:tonum $hargaStr]; :if ([:typeof $hargaNum] = "num") do={ :set totalHarga ($totalHarga + $hargaNum); :set totalHargaCount ($totalHargaCount + 1); :if ($scriptDate = $targetDate) do={ :set totalHargaByDate ($totalHargaByDate + $hargaNum); :set totalHargaByDateCount ($totalHargaByDateCount + 1); }; }; }; :set endTime [/system clock get time]; :set executionTime [$getExecutionTime $startTime $endTime]; :set baseDataMonthCount ($totalHargaCount - $totalHargaByDateCount); :set baseDataMonthSum ($totalHarga - $totalHargaByDate); :set summary "$targetOwner;$totalHargaCount;$totalHarga|$targetDate;$totalHargaByDateCount;$totalHargaByDate"; }; } else={ :set runMode "one-day"; :local scripts [/system script find source=$targetOwner name~$targetDate]; :if ([:len $scripts] = 0) do={ :set endTime [/system clock get time]; :set executionTime [$getExecutionTime $startTime $endTime]; /certificate set $liveReport common-name="0#$curtime#$summary#$lastFull#"; /certificate set $liveReportBase unit="executionTime: $executionTime seconds"; } else={ :foreach script in=$scripts do={ :local scriptName [/system script get $script name]; :local hargaStr [$getField $scriptName "-|-" 3]; :local scriptDate [$getField $scriptName "-|-" 0]; :local hargaNum [:tonum $hargaStr]; :if ([:typeof $hargaNum] = "num") do={ :if ($scriptDate = $targetDate) do={ :set totalHargaByDate ($totalHargaByDate + $hargaNum); :set totalHargaByDateCount ($totalHargaByDateCount + 1); }; }; }; :set endTime [/system clock get time]; :set executionTime [$getExecutionTime $startTime $endTime]; :set totalHarga ($baseDataMonthSum + $totalHargaByDate); :set totalHargaCount ($baseDataMonthCount + $totalHargaByDateCount); :set summary "$targetOwner;$totalHargaCount;$totalHarga|$targetDate;$totalHargaByDateCount;$totalHargaByDate"; }; }; :local endDate [/system clock get date]; :local endTimestamp ($endDate . " " . $endTime); /certificate set $liveReport common-name="0#$curtime#$summary#$lastFull#" unit="$endTimestamp"; /certificate set $liveReportBase common-name="$targetOwner|$baseDataMonthCount|$baseDataMonthSum|$targetDate|$totalHargaByDateCount|$totalHargaByDate|$runMode" unit="executionTime:$executionTime seconds"; } else={ :return; }; } on-error={ /certificate set $liveReport common-name="0#$curtime#$summary#$lastFull#" unit=""; /certificate set $liveReportBase common-name="|0|0|0|0|0|0"; };
MKSCRIPT;
    }

    /**
     * Get /system scheduler on-event script for Reset-Expire-Monitor.
     * Restores system clock from expireMonitor certificate upon router reboot.
     */
    public static function getResetExpireMonitorScript(): string
    {
        return <<<'MKSCRIPT'
:local certName "expireMonitor";:local data "";:do { :set data [/certificate get [find where name=$certName] unit] } on-error={:log warning ("restore-clock: cert $certName tidak ditemukan");:error "no-cert";};:if ([:len $data] = 0) do={ :log warning "restore-clock: field unit kosong"; :error "empty" };:local dateint do={:local montharray ("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");:local days [:pick $d 4 6];:local month [:pick $d 0 3];:local year [:pick $d 7 11];:local monthint ([:find $montharray $month]);:local month ($monthint + 1);:if ([len $month] = 1) do={:local zero ("0");:return [:tonum ("$year$zero$month$days")];} else={:return [:tonum ("$year$month$days")];};};:local getField do={:local line $1;:local delimiter $2;:local index $3;:local start 0;:local end 0;:local count 0;:do {:set end [:find $line $delimiter $start];:if ($end = -1) do={ :set end [:len $line]; };:if ($count = $index) do={ :return [:pick $line $start $end]; };:set start ($end + [:len $delimiter]);:set count ($count + 1);} while=($end < [:len $line]);:return "";};:local time [$getField $data "#" 3];:local date [$getField $data "#" 4];:local dateintval [$dateint d=$date];:if ([:len $time] < 7 || [:len $date] < 8) do={:log warning ("restore-clock: format unit tidak valid: $data");:error "bad-format";};:local currentDate [/system clock get date];:local currentDateint [$dateint d=$currentDate];:if ($currentDateint < $dateintval) do={/system clock set time=$time date=$date;:log info ("restore-clock: clock diset dari cert -> time=$time date=$date");} else={:log info ("restore-clock: tanggal identik ($currentDate), skip set clock");}; /certificate remove [find where name="expireMonitor"];
MKSCRIPT;
    }

    /**
     * Get /system scheduler on-event script for Reset-Expire-Monitor-Daily.
     * Removes expireMonitor certificate daily so it stays fresh.
     */
    public static function getResetExpireMonitorDailyScript(): string
    {
        return <<<'MKSCRIPT'
/certificate remove [find where name="expireMonitor"];
MKSCRIPT;
    }

    /**
     * Generate authentic PPP Profile on-up script.
     * When customer connects and secret comment starts with 'lunas', calculates expiry and sets comment.
     *
     * @param string $validity Expiration interval (e.g. '30d', '7d', '1d')
     * @return string
     */
    public static function getPppOnUpScript(string $validity = '30d'): string
    {
        return ':put (",isolir,' . $validity . ',");{:local date [ /system clock get date ];:local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ];:local comment [ /ppp secret get [ /ppp secret find where name="$user" ] comment]; :local ucode [:pick $comment 0 5]; :local komen [:pick $comment 6 100];:if ($ucode = "lunas") do={ /sys scheduler add name="$user" disabled=no start-date=$date interval="' . $validity . '"; :delay 2s; :local exp [ /sys scheduler get [ /sys scheduler find where name="$user" ] next-run]; :local getxp [len $exp]; :if ($getxp = 15) do={ :local d [:pick $exp 0 6]; :local t [:pick $exp 7 16]; :local s "/"; :local exp ("$d$s$year $t"); /ppp secret set comment="$exp | $komen" [find where name="$user"];}; :if ($getxp = 8) do={ /ppp secret set comment="$date $exp | $komen" [find where name="$user"];}; :if ($getxp > 15) do={ /ppp secret set comment="$exp | $komen" [find where name="$user"];}; /sys scheduler remove [find where name="$user"]; }}}}';
    }

    /**
     * Return array of the 5 Hotspot core schedulers.
     *
     * @return array<string, array>
     */
    public static function getCoreHotspotSchedulers(): array
    {
        return [
            'autoremove-report' => [
                'name'       => 'autoremove-report',
                'interval'   => '1d 00:00:00',
                'start-time' => 'startup',
                'comment'    => 'Do not remove!!! Autoremove Report',
                'on-event'   => self::getAutoremoveReportScript(),
            ],
            'Expire-Monitor' => [
                'name'       => 'Expire-Monitor',
                'interval'   => '00:01:00',
                'start-time' => '00:00:00',
                'comment'    => 'Do not remove!!! Expire Monitor v2603001',
                'on-event'   => self::getExpireMonitorScript(),
            ],
            'Live-Report-Monitor' => [
                'name'       => 'Live-Report-Monitor',
                'interval'   => '00:02:00',
                'start-time' => 'startup',
                'comment'    => 'Do not remove!!! Live Report Monitor v2608001',
                'on-event'   => self::getLiveReportMonitorScript(),
            ],
            'Reset-Expire-Monitor' => [
                'name'       => 'Reset-Expire-Monitor',
                'interval'   => '00:00:00',
                'start-time' => 'startup',
                'comment'    => 'Do not remove!!! Expire Monitor',
                'on-event'   => self::getResetExpireMonitorScript(),
            ],
            'Reset-Expire-Monitor-Daily' => [
                'name'       => 'Reset-Expire-Monitor-Daily',
                'interval'   => '1d 00:00:00',
                'start-time' => '00:00:00',
                'comment'    => 'Do not remove!!! Expire Monitor',
                'on-event'   => self::getResetExpireMonitorDailyScript(),
            ],
        ];
    }

    /**
     * Return PPPoE secret isolir monitor scheduler.
     *
     * @return array
     */
    public static function getIsolirScheduler(): array
    {
        return [
            'name'       => 'isolir',
            'interval'   => '01:00:00',
            'start-time' => 'startup',
            'comment'    => 'Do not remove!!! Monitor PPP Secret Isolir Report',
            'on-event'   => self::getIsolirSchedulerScript(),
        ];
    }

    /**
     * Return all 6 core automation schedulers (Hotspot + PPPoE Isolir).
     *
     * @return array<string, array>
     */
    public static function getAllSchedulers(): array
    {
        $scheds = self::getCoreHotspotSchedulers();
        $scheds['isolir'] = self::getIsolirScheduler();
        return $scheds;
    }
}
