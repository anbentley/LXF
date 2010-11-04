<?php
/**
 * This class incorporates methods to display a basic calendar in multiple formats and sizes.
 *
 * @author	Alex Bentley
 * @history	1.1     UI simplification to allow for date and time freeform input
 *          1.0     initial release
 */
class CALENDAR {

static $defaults = array(
	'format'		=> 'small',	
	'events'		=> array(),
	'events-only'	=> true,
	'event-codes'	=> array(
		'F' => 'federal', 
		'S' => 'special', 
        'E' => 'event',
		'C' => 'conference', 
		'B' => 'birthday', 
		'H' => 'holiday'),
);


static $months = array(
	1	=> 'january',
	2	=> 'february',
	3	=> 'march',
	4	=> 'april',
	5	=> 'may',
	6	=> 'june',
	7	=> 'july',
	8	=> 'august',
	9	=> 'september',
	10	=> 'october',
	11	=> 'november',
	12	=> 'december',
);

static $days = array(
	'names' => array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
	'small'	=> 1, 'normal'=> 3, 'large'	=> 9, 'list' => 3,
);

function eventFields($dir, $ecodes='ECB', $type='add', $id=0, $data=array()) {
    
    $types = '';
    $ecd = str_split($ecodes);
    foreach ($ecd as $ec) {
        if (array_key_exists($ec, self::$defaults['event-codes'])) {
            append($types, $ec.':'.ucwords(self::$defaults['event-codes'][$ec]), ' ~ ');
        }
    }
    
    switch ($type) {
        case 'add':
            return array(
                'element:popup | name:event[type]  | values:('.$types.') | label:type | class:inline',
                'element:text  | name:event[date]  | size:10 | label:date | required | class:inline',
                'element:text  | name:event[start] | size:10 | label:start time | required | class:inline',
                'element:text  | name:event[end]   | size:10 | label:end time   | required | class:inline',
                'element:text  | name:event[name]  | size:60 | label:name | required',
                'element:text  | name:event[desc]  | size:60 | rows:4     | label:description | required',
                'element:text  | name:event[where] | size:60 | label:where | required',
                'element:text  | name:event[sponsor] | size:20 | label:sponsored by',
                'element:text  | name:event[phone] | size:30 | label:phone',
                'element:text  | name:event[email] | size:30 | label:email',
                'element:text  | name:event[link]  | size:60 | label:external url',
            );
        case 'date':
            return array(
                'element:comment | value:Enter the date of the Event',
                'element:text  | name:date | size:10 | label:date | required',
            );
            
        case 'edit':
            return array(
                 'element:checkbox | name:delete['.$id.'] | label:delete this entry | class:inline',
                 'element:popup | name:event[type]  | values:('.$types.') | label:type | value:'.$data['type'],
                 'element:text  | name:event['.$id.'][start] | size:10 | label:start time | class:inline | value:'.$data['start'],
                 'element:text  | name:event['.$id.'][end]   | size:10 | label:end time   | class:inline | value:'.$data['end'],
                 'element:text  | name:event['.$id.'][name]  | size:60 | label:name  | value:'.$data['name'],
                 'element:text  | name:event['.$id.'][desc]  | size:60 | rows:4      | label:description | value:'.$data['desc'],
                 'element:text  | name:event['.$id.'][where] | size:60 | label:where  | value:'.$data['where'],
                 'element:text  | name:event['.$id.'][sponsor] | size:20 | label:sponsored by | value:'.$data['sponsor'],
                 'element:text  | name:event['.$id.'][phone] | size:30 | label:phone | value:'.$data['phone'],
                 'element:text  | name:event['.$id.'][email] | size:30 | label:email | value:'.$data['email'],
                 'element:text  | name:event['.$id.'][link]  | size:60 | label:external url | value:'.$data['link'],
             );
    }
}
        

/**
 * this function displays and processes an add event request
 *
 * @param   $title      the page title to use
 * @param   $dir        the directory where event fies are kept
 * @param   $ecodes     an optional string of enterable event codes
 */
function addEvent($title, $dir, $ecodes='ECB') {        
    HTML::title($title);
    
    echo h2('', $title);
    
    $fields = self::eventFields($dir, $ecodes, 'add');
    
    if (FORM::complete($fields)) {
        $event = param('event');
        $basetime = strtotime($event['date']);
        $date = date('m/d/Y', $basetime);
        $year = date('Y', $basetime);
        
        $eventsfile = $dir.'/'.$year.'/calendar.txt';
        $events = @unserialize(@file_get_contents($eventsfile));
        if (!is_array($events)) $events = array();
        
        if (!array_key_exists($date, $events)) $events[$date] = array();
        $events[$date][] = param('event');
        
        file_put_contents($eventsfile, serialize($events));
        
        echo p('', 'Event Added');
    }
}

/**
 * this function displays and processes an edit event request
 *
 * @param   $title      the page title to use
 * @param   $dir        the directory where event fies are kept
 * @param   $ecodes     an optional string of enterable event codes
 */
function editEvent($title, $dir, $ecodes='ECB') {        
    HTML::title($title);
    
    echo h2('', $title);
    
    $fields = self::eventFields($dir, $ecodes, 'date');
    
    if (FORM::complete($fields)) {
        $basetime = strtotime(param('date'));
        $date = date('m/d/Y', $basetime);
        $year = date('Y', $basetime);
        
        $eventsfile = $dir.'/'.$year.'/calendar.txt';
        $events = @unserialize(@file_get_contents($eventsfile));
        if (!is_array($events)) $events = array();
        
        if (array_key_exists($date, $events) && count($events[$date])) {            
            $editflds = array(
                'element:text | name:date | hidden',
                'element:text | name:submit | hidden',
                'element:checkbox | name:deleteall | label:delete all',
            );
            
            $current = array();
            foreach ($events[$date] as $id => $evt) {
                $current[$id]['id'] = $id;
                $editflds = array_merge($editflds, self::eventFields($dir, $ecodes, 'edit', $id, $evt));
            }
            
            if (FORM::complete($editflds, array('submit-name' => 'update', 'submit' => 'Update'))) {
                $event = param('event');
                
                // merge new back into old
                if (param('deleteall') == 'on') {
                    $current = array();
                    $event   = array();
                }

                foreach ($event as $id => $evt) {
                    if (is_array($evt) && array_key_exists('id', $evt)) unset($evt['id']);
                    $current[$id] = $evt;
                }
                
                // process deleted events
                $deletes = param('delete', array());
                krsort($deletes);
                foreach ($deletes as $id => $on) unset($current[$id]);
                                
                $events[$date] = $current;
                file_put_contents($eventsfile, serialize($events));
                
                echo h3('', 'Event(s) Updated');
                
            }
            
        } else {
            echo h3('', 'No Events are scheduled for '.$date);
        }
    }
        
}
    
/**
 * this function produces the HTML for a month of a calendar in a given format.
 *
 * @param	$month		the month of the specified year to display.
 * @param	$year		the specified year.
 * @param	$options	an array of options to change the display.
 *						the format to use (can be 'small', 'normal', 'large', 'list'); default is small.
 *						a list of events as an array of dates, if multiple events occur on the same date then they are passed as an array
 *						a list of codes and style to extend the default types of events
 *						in list format an events only mode
 *						a title string
 * @return				the html to render the month as defined.
 */
function displayMonth($month, $year, $options=array()) {
	$options = smart_merge(self::$defaults, $options);
	// normalize name
	if (in_array($month, array_keys(self::$months))) {
		$name = self::$months[$month];
	} else if (in_array(strtolower($month), self::$months)) {
		$name = strtolower($month);
	} else {
		return '';
	}
	
	$mn = array_search($name, self::$months);
	
	$year = $year + 0; // insure $year is numeric

	$format = $options['format'];
	$daynamelen = self::$days[$format];
	
	$class = 'calendar-'.$format;
	$listing = ($format == 'list');
	
	$metrics = self::getMetrics($mn, $year);
	$nod = $metrics['nod'];
	$sdow = $metrics['sdow'];
	
	$events = self::getMonthEvents($mn, $year, $options['events']);
    
	// display nothing if this is an inactive month
	if (($listing && $options['events-only']) && ($events == array())) return '';
	
    // get next and last month
    $current = strtotime($mn.'/1/'.$year);
    list($lm, $ly) = explode('/', date('n/Y', strtotime('-1 month', $current)));
    list($nm, $ny) = explode('/', date('n/Y', strtotime('+1 month', $current)));    

	// display month header
	echo table('class:'.$class);
	echo tr('class:titles');
    echo td('', LINK::paramTag(page(), array('m' => $lm, 'y' => $ly), '<<', LINK::rtn()));
    echo td('colspan:5', ucwords($name).' '.$year);
    echo td('', LINK::paramTag(page(), array('m' => $nm, 'y' => $ny), '>>', LINK::rtn()));
    echo tr('/');
	
	if (!$listing) {
		echo tr('class:titles');
		foreach (self::$days['names'] as $day) echo td('', substr($day, 0, $daynamelen));
		echo tr('/');
	}
	

	// display weeks (make sure there are always 6 even in the last are blank)
	$weekcount = 0;
	$week = $sdow;
	if (!$listing) {
		echo tr();
		for ($i = 0; $i < $sdow; $i++) echo td('class:empty-day', nbsp());
	}
	for ($i = 1; $i <= $nod; $i++) {
		$today = date('m/d/Y', strtotime($mn.'/'.$i.'/'.$year));

		if (array_key_exists($today, $events)) {
			$daysEvents = $events[$today];
		} else {
			$daysEvents = false;
		}
        
		if ($week == 7) {
			$week = 0;
			$weekcount++;
			if (!$listing) {
				echo tr('/');
				echo tr('class:day');
			}
		}
		if (!$listing || ($listing && !$options['events-only']) || ($listing && $options['events-only'] && $daysEvents)) {
			$contents = '';
			$eventData = '';
			if ($daysEvents) {
				foreach ((array)$daysEvents as $details) {
                    $start = strtotime($details['start'], strtotime($today));
                    $end = strtotime($details['end'], strtotime($today));
                    $time = date('g:ia', $start).'-'.date('g:ia', $end);
                    $full = $time.': '.$details['name']."\n".
                        $details['desc']."\n".
                        'Where:        '."\n ".$details['where']."\n".
                        'Sponsored by: '.$details['sponsor']."\n".
                        'Phone:        '.$details['phone']."\n".
                        'Email:        '.$details['email']."\n";
                        
					$type = $options['event-codes'][$details['type']];					
					if ($listing) {
						append($eventData, span('class:'.$type, str_replace("\n", br(), $full)), br());
                        
					} else if ($format == 'large') {
                        $label = $time.br().nbsp(2).substr($details['name'], 0, 15).'&hellip;';
                        $link = '<a href="javascript:alert(\''.str_replace(array("\n", '\''), array('\n', "\'"), $full).'\');">'.$label.'</a>';
						append($eventData, p('class:'.$type.' | title:'.$details['name'], $link), '');
                        
					} else if (($format == 'normal') || ($format == 'small')) {
						append($eventData, div('class:'.$type.' | title:'.$full), '');
					}
				}
				if ($listing) {
					$in = $i;
					if ($in < 10) $in = nbsp().$in;
					echo td('class:titles', $in);
				}
				$contents = div('class:special-day', $eventData);
			}
			if (!$listing) {
				$contents = span('class:date', $i).$contents;
				echo td('', $contents);
			} else {
				echo tr();
				echo td('class:list-dow', substr(self::$days['names'][$week], 0, $daynamelen));
				echo td('class:list-day', $in);
				echo td('', $contents);
				echo tr('/');
			}
		}
		$week++;
	}
	if ($week && !$listing) {
		if ($week < 7) {
			for ($i = $week; $i <= 6 ; $i++) echo td('class:empty-day', nbsp());
			$week = 7;
		}
		echo tr('/');
		$weekcount++;
	} else if ($listing) {
		$weekcount = 10;
	}
		
	// fill out the month
	for ($i = $weekcount; $i < 6; $i++) {
		echo tr();
		for ($d = 0; $d <= 6; $d++) echo td('class:empty-day', nbsp());
		echo tr('/');
	}
	echo table('/');	
}

function getMonthEvents($month, $year, $events, $hideEvents=array()) {	
	if (!is_array($events)) $events = array($events);
	
	$results = array();
    
	// process events for this month and expand any ranges	
	foreach ($events as $date => $event) {		
		if (!is_array($event)) $event = array($event);
		foreach ($event as $ind => $ev) {
			if (in_array($ev['type'], $hideEvents)) unset($event[$ind]);
		}
		if ($event == array()) continue; // no visible events
		
		@list($em, $ed, $ey) = explode('/', $date);
		if ($em != $month) continue; // not this month
		
		if ($ey == '') $ey = $year;
		if ($ey != $year) continue; // not this year
		
		@list($ed1, $ed2) = explode('-', $ed);
		if ($ed2 == '') $ed2 = $ed1;
	
		for ($i = $ed1; $i <= $ed2; $i++) $results[date('m/d/Y', strtotime($em.'/'.$i.'/'.$ey))] = $event;
	}

	return $results;
}

function addFederalHolidays($events, $year) {
	$fed = self::federalHolidays($year);
	
	foreach ($events as $date => $event) {
		if (array_key_exists($date, $fed)) {
			if (!is_array($event)) {
				$event = array($fed[$date], $event);
			} else {
				$event = array_merge(array($fed[$date]), $event);
			}
			$events[$date] = $event;
			unset($fed[$date]); // so when we merge we don't add this in again and overwrite what we just did
		}
	}
	$events = array_merge($fed, $events);
		
	return $events;
}


function listEvents($fromMonth, $fromYear, $toMonth, $toYear, $options) {
	$options['format'] = 'list';
	$options = array_merge(self::$defaults, $options);
	
	if ($fromYear == $toYear) {
		for ($m = $fromMonth; $m <= $toMonth; $m++) {
			self::displayMonth($m, $fromYear, $options);
		}
	} else {
		for ($m = $fromMonth; $m <= 12; $m++) {
			self::displayMonth($m, $fromYear, $options);
		}
		for ($y = $fromYear+1; $y < $toYear; $y++) {
			for ($m = 1; $m <= 12; $m++) {
				self::displayMonth($m, $fromYear, $options);
			}
		}
		for ($m = 1; $m <= $toMonth; $m++) {
			self::displayMonth($m, $toYear, $options);
		}
	}
}

function getMetrics($month, $year) {
	$dayone = strtotime("$month/1/$year");
	return	array(
				'nod'	=> date('t', $dayone), 
				'sdow'	=> date('w', $dayone)
			);
}

/**
 * This determines the dates of the Federal holidays for the specified year and indicates when they are observed.
 *
 * @param	$year	the year to process.
 *
 * @returns	an array of coded events for the Federal holidays.
 */
function federalHolidays($year) {
/*
Date			Official Name						Remarks
Jan 1			New Year's Day						Celebrates beginning of the Gregorian calendar year. Festivities include countdowns to midnight (12:00 AM).
3rd Mon in Jan	Birthday of Martin Luther King, Jr.	Honors Martin Luther King, Jr., Civil Rights leader; combined with other holidays in several states (King's birthday was January 15)
3rd Mon in Feb	Washington's Birthday				Honors George Washington. Often popularly observed as "Presidents Day" in recognition of other American presidents, such as Abraham Lincoln (who was born February 12). The legal name of the federal holiday, however, is "Washington's Birthday". (It was historically observed on February 22, prior to passage of the Uniform Monday Holiday Act by Congress)
Last Mon in May	Memorial Day						Also known as "Decoration Day", Memorial Day originated in the nineteenth century as a day to remember the soldiers who gave their lives in the American Civil War by decorating their graves with flowers. Later, the practice of decorating graves came to include members of one's own family, whether they saw military service or not. Memorial Day is traditionally the beginning of the summer recreational season in America. (It was historically observed on May 30, prior to the Uniform Monday Holiday Act)
July 4			Independence Day					Celebrates the signing of the Declaration of Independence. More commonly known as "the Fourth of July".
1st Mon in Sep	Labor Day							Celebrates achievements of workers and the labor movement. Labor Day traditionally marks the end of the summer recreational season in America. The following day often marks the beginning of autumn classes in primary and secondary schools.
2nd Mon in Oct	Columbus Day						Celebrated since 1792 in New York City, honors Christopher Columbus, who landed in the Americas on October 12, 1492. In some areas it is also a celebration of Italian-American culture and heritage. Congress and President Franklin Delano Roosevelt set aside Columbus Day in 1934 as a Federal holiday at the behest of the Knights of Columbus (historically observed on October 12, prior to the Uniform Monday Holiday Act)
Nov 11			Veterans Day						Also known as Armistice Day, and very occasionally called "Remembrance Day", 'Veterans Day' is the American name for the international holiday which commemorates the signing of the Armistice ending World War I. In the United States, the holiday honors all veterans of the United States Armed Forces, whether or not they have served in a conflict; but it especially honors the surviving veterans of World War I, World War II, the Korean War, and the Vietnam War. The American holiday was briefly moved to the final Monday in October under the Uniform Monday Holiday Act, but the change was greatly disliked and soundly criticized - among other reasons, because it put Veterans Day out of sync with international observance; so it was restored to November 11.
4th Thu in Nov	Thanksgiving Day					Traditionally celebrates giving thanks for the autumn harvest, and customarily includes the consumption of a turkey dinner. (historically observed on various days, but finally becoming so fixed to the fourth Thursday in November in the hearts and minds of Americans, that Americans rebelled (albeit politely) when President Franklin Delano Roosevelt attempted to move it to the third Thursday of November, at the request of numerous powerful American merchants). However it is also influenced by a dinner shared by Native American Indians and the Pilgrims at Plymouth, Massachusetts.
Dec 25			Christmas Day						A Christian holiday that celebrates the birth of Jesus Christ. Aspects of the holiday include decorations, emphasis on family togetherness, and gift giving. Designated a federal holiday by Congress and President U.S. Grant in 1870. [2]
*/

	$metrics = array();
	$sdow = array();
	for ($m = 1; $m <= 12; $m++) {
		$metrics[$m] = self::getMetrics($m, $year);
		$sdow[$m] = $metrics[$m]['sdow'];
	}
	
	$fmon	= array( 2,  1,  7,  6,  5,  4,  3);
	$lmon	= array(30, 29, 28, 27, 26, 25, 31);
	$tg		= array(26, 25, 24, 23, 23, 28, 27);
	
	$fd = array(
		"1/1/$year"							=> "F:New Year&rsquo;s Day",
		"7/4/$year"							=> "F:Independence Day",
		"11/11/$year"						=> "F:Veterans Day",
		"12/25/$year"						=> "F:Christmas Day",
		"1/".($fmon[$sdow[1]]+14)."/$year"	=> "F:Birthday of Martin Luther King, Jr.",
		"2/".($fmon[$sdow[2]]+14)."/$year"	=> "F:Washington&rsquo;s Birthday",
		"5/".$lmon[$sdow[5]]."/$year"		=> "F:Memorial Day",
		"9/".$fmon[$sdow[9]]."/$year"		=> "F:Labor Day",
		"10/".($fmon[$sdow[10]]+7)."/$year"	=> "F:Columbus Day",
		"11/".$tg[$sdow[11]]."/$year"		=> "F:Thanksgiving Day",
	);
	$fixeddates = 4;
	foreach ($fd as $date => $event) {
		$when = strtotime($date);
		$dow = date('w', $when);
		$odate = '';
		if ($dow == 0) {
			$odate = date('m/d/Y', strtotime("$date + 1 day"));
		} else if ($dow == 6) {
			$odate = date('n/j/Y', strtotime("$date - 1 day"));
		}
		if ($odate != '') $fd[$odate] = $event.' (observed)';
		
		$fixeddates--;
		if ($fixeddates == 0) break;
	}
	
	return $fd;
}

/**
 * Takes an array of events and returns a flattened array of events taking into account of day ranges and hidden events.
 *
 * @param	$events		an array of events by date.
 * @param	$year		the year to process.
 * @param	$hideEvents	an array of event codes to remove.
 * @param	$addFH		a boolean to indicate if US Federal Holidays should be added.
 *
 * returns	an array of events fully qualified by date for the given year.
 */
function expandEvents($events, $year, $hideEvents=array(), $addFH=true) {
	$expandedevents = array();
	
	for ($m = 1; $m <= 12; $m++) {
		$mevents = self::getMonthEvents($m, $year, $events, $hideEvents);
		if ($mevents != array()) $expandedevents = array_merge($expandedevents, $mevents);
	}
	if (!in_array('F', $hideEvents) && $addFH) {
		$expandedevents = self::addFederalHolidays($expandedevents, $year);
	}

	return $expandedevents;
}

}

?>
	

	