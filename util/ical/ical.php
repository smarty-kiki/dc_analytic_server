<?php

/**
 * ICAL CLASS
 * OUTPUT AN ICS FILE TO BROWSER OR HARDDRIVE
 *
 * Please refer to http://tools.ietf.org/html/rfc5545 if infos are needed
 * 
 * @author ANDY KAYL
 * @version 0.2
 * @license http://opensource.org/licenses/BSD-3-Clause
 *
 * Copyright (c) 2017, ND.K
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided
 * that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of conditions and
 * the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 * Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to endorse or promote products
 * derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class ical {

    /**
     * CLASS CONSTRUCTOR
     */

    public function __construct() {
        $this->prodid = "PHP iCal Generator";
        $this->alarmdesc = "You gotta do what you gotta do! You have a job soon...";
        $this->alarmtrigger = 60;
        $this->refresh_ttl = 60;
        $this->status = "CONFIRMED";
        $this->events = array();
        $this->evnum = null;
        return true;
    }

    /**
     * START A NEW EVENT
     * @var NONE
     * @return BOOL TRUE
     */

    public function new_event() {
        $num = count($this->events);
        $this->evnum =& $num;
        $this->events[$num] = array(
            "data" => array(),
            "alarms" => array(),
            "custom" => array()
        );
        return true;
    }

    /**
     * SET PRODUCT ID
     * @var STRING PRODUCT ID STRING
     * @return BOOL TRUE
     */

    public function set_product_id($string) {
        $this->prodid = $string;
        return true;
    }

    /**
     * SET THE REFRESH INTERVAL
     * @var INT MINUTES
     * @return BOOL TRUE
     */

    public function set_refresh($minutes) {
        $this->refresh_ttl = (int) $minutes;
        return true;
    }

    /**
     * ADD EVENT SUMMARY TEXT
     * @var STRING SUMMARY TEXT
     * @return BOOL TRUE
     */

    public function set_title($string) {
        $this->events[$this->evnum]['data']['title'] = preg_replace("/(<br[ ]?\/?>|\n)/", "\\n", $string);
        return true;
    }

    /**
     * ADD DESCRIPTION TEXT
     * @var STRING DESCRIPTION
     * @return BOOL TRUE
     */

    public function set_description($string) {
        $this->events[$this->evnum]['data']['description'] = preg_replace("/(<br[ ]?\/?>|\n)/", "\\n", $string);
        return true;
    }

    /**
     * ADD DATES TO EVENT
     * @var STRING BEGIN DATE (Y-M-D H:S)
     * @var STRING END DATE (Y-M-D H:S)
     * @return BOOL TRUE
     */

    public function set_dates($begin, $end) {
        $this->events[$this->evnum]['data']['begin_date'] = date("Ymd\THis", strtotime($begin));
        $this->events[$this->evnum]['data']['end_date'] = date("Ymd\THis", strtotime($end));
        return true;
    }

    /**
     * SET LOCATION
     * @var STRING LOCATION NAME
     * @return BOOL TRUE
     */

    public function set_location($string) {
        $this->events[$this->evnum]['data']['location'] = $string;
        return true;
    }

    /**
     * ADD CUSTOM ICAL PROPERTY
     * @var STRING PROPERTY NAME
     * @var STRING PROPERTY VALUE
     * @return BOOL TRUE
     */

    public function add_custom($property, $value) {
        $property = mb_strtoupper($property);
        $this->events[$this->evnum]['custom'][$property] = $value;
        return true;
    }

    /**
     * SET ALARM
     * @var STRING TYPE => DISPLAY (DEFAULT), AUDIO (UNSUPPORTED), EMAIL (UNSUPPORTED)
     * @return BOOL TRUE
     */

    public function set_alarm($type="display") {
        $type = mb_strtoupper($type);
        $num = count($this->events[$this->evnum]['alarms']);
        $this->alnum =& $num;
        $this->events[$this->evnum]['alarms'][$this->alnum]['type'] = $type;
        return true;
    }

    /**
     * SET ALARM TEXT TO DISPLAY
     * @var STRING MESSAGE TEXT
     * @return BOOL TRUE
     */

    public function set_alarm_text($string) {
        $this->events[$this->evnum]['alarms'][$this->alnum]['description'] = $string;
        return true;
    }

    /**
     * SET ALARM TRIGGER
     * @var INT NUMBER OF MINUTES BEFORE EVENT
     * @return BOOL TRUE
     */

    public function set_alarm_trigger($minutes) {
        $minutes = abs((int) $minutes);
        $this->events[$this->evnum]['alarms'][$this->alnum]['trigger'] = $minutes;
        return true;
    }

    /**
     * SET EVENT STATUS
     * @var STRING STATUS => TENTATIVE, CONFIRMED, CANCELLED
     * @return BOOL TRUE
     */

    public function set_status($status) {
        $status = mb_strtoupper($status);
        $this->events[$this->evnum]['data']['status'] = $status;
        return true;
    }

    /**
     * STORE CONTENT TO FILE
     * @var STRING CALENDAR
     * @var STRING FILENAME
     * @return BOOL TRUE/FALSE
     */

    private function store_file($content, $destfile) {
        try {
            $fh = fopen($destfile, "w");
            if (!$fh) {
                throw new Exception("file could not be created");
            }
            $ret_write = fwrite($fh, $content);
            if (!$ret_write) {
                throw new Exception("Could not write content to file");
            }
            fclose($fh);
            return true;
        }
        catch (Exception $e) {
            print $e->getMessage();
            return false;
        }
    }

    /**
     * CLEAN AND FORMAT EVENT ENTRIES
     * @VAR NONE
     * @return BOOL TRUE
     */

    private function encode_values() {
        foreach($this->events as $i => $event) {
            foreach($event['data'] as $key => $data) {
                $this->events[$i]['data'][$key] = html_entity_decode($data);
            }
            foreach ($event['custom'] as $key => $custom) {
                $this->events[$i]['custom'][$key] = html_entity_decode($custom);
            }
            foreach ($event['alarms'] as $j => $alarms) {
                foreach ($alarms as $key => $data) {
                    $this->events[$i]['alarms'][$j][$key] = html_entity_decode($data);
                }
            }
        }
        return true;
    }

    /**
     * WRITE ICAL DATA TO BROWSER OR FILE
     * @var STRING DESTINATION FILE
     * @return BOOL TRUE OR FALSE
     */

    public function write($destfile=false) {
        $this->encode_values();
        $cal = "BEGIN:VCALENDAR\n";
        $cal .= "PRODID:-//{$this->prodid}//EN\n";
        $cal .= "VERSION:2.0\n";
        $cal .= "METHOD:PUBLISH\n";
        $cal .= "X-PUBLISHED-TTL:PT{$this->refresh_ttl}M\n";
        foreach ($this->events as $event) {
            $cal .= "BEGIN:VEVENT\n";
            $cal .= "CLASS:PUBLIC\n";
            $cal .= "CREATED:".date("Ymd\THis", time())."\n";
            $cal .= "SUMMARY:{$event['data']['title']}\n";
            $status = (!empty($event['status'])) ? $event['status'] : mb_strtoupper($this->status);
            $cal .= "STATUS:{$status}\n";
            if (!empty($event['data']['description'])) {
                $cal .= "DESCRIPTION:{$event['data']['description']}\n";
            }
            if (!empty($event['data']['location'])) {
                $cal .= "LOCATION:{$event['data']['location']}\n";
            }
            $cal .= "DTSTART:{$event['data']['begin_date']}\n";
            $cal .= "DTEND:{$event['data']['end_date']}\n";
            $cal .= "DTSTAMP:".date("Ymd\THis", time())."\n";
            $cal .= "UID:".md5(uniqid("", true))."\n";
            foreach($event['custom'] as $prop => $val) {
                $cal .= "{$prop}:{$val}\n";
            }
            foreach ($event['alarms'] as $alarms) {
                $cal .= "BEGIN:VALARM\n";
                $cal .= "ACTION:{$alarms['type']}\n";
                $alarmtrigger = (!empty($alarms['trigger'])) ? $alarms['trigger'] : $this->alarmtrigger;
                $cal .= "TRIGGER:-PT{$alarmtrigger}M\n";
                $alarmdesc = (!empty($alarms['description'])) ? $alarms['description'] : $this->alarmdesc;
                $cal .= "DESCRIPTION:{$alarmdesc}\n";
                $cal .= "END:VALARM\n";
            }
            $cal .= "END:VEVENT\n";
        }
        $cal .= "END:VCALENDAR\n";
        if (!$destfile) {
            header("Content-Type: text/Calendar");
            header("Content-Disposition: attachment; filename=events.ics");
            print $cal;
            return true;
        }
        else {
            return $this->store_file($cal, $destfile);
        }
    }

}
