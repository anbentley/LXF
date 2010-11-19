<?php

/**
 * This class is intended to combine simple elements into more complex UI elements
 * to provide a richer set of tools than just CSS and convention provides.
 *
 * @author	Alex Bentley
 * @history	1.0	Initial Release
 *
 */
class UI {

function editableInfoBlock($title, $detailCode, $allowed, $summary=array()) {
	$results = div('class:infoBlock');
	if ($allowed) {
		@list($page, $params) = $summary;
		$details = $params;
		$details[] = $detailCode;
		if ($summary != array()) {
			$detail = param($detailCode, 'exists');
			$detailButton = self::selectButton(LINK::paramtag($page, $details, 'All', LINK::rtn()), $detail, true);
			$summaryButton = self::selectButton(LINK::paramtag($page, $params, 'Summary', LINK::rtn()), !$detail, true);
			$link = $summaryButton.' '.$detailButton;
		} else {
			$link = '';
		}
	}
	$results .= h3('class:infoTitle', $title.$link);
	return $results;
	
}


function rightsidebar($title, $content) {
	echo div('class:rightsidebar');
	echo div('class:sidesubhead', $title);
	foreach ($content as $item) echo $item;
	echo div('/');
}

function sidesubhead($title, $content) {
	echo div('class:fsblock');
	echo div('class:sidesubhead', $title);
	foreach ($content as $item) echo div('class:fsitem', $item);
	echo div('/');
}

function sectionStart($title) {
	echo div('class:fsblock');
	echo div('class:sectionhead', $title);
}

function itemStart() {
	echo div('class:fsitem');
}

function itemEnd() {
	echo div('/');
}

function sectionEnd() {
	echo div('/');
}

function selectBar($contents, $direction='right') {
	switch ($direction) {
		case 'left':
		echo div('class:select_bar_left', $content);
		break;
		
		default:
		echo div('class:select_bar_right', $content);
		break;
	}
}

function decisionButton($link, $selected=false, $return=false) {
	$class = 'admin_select_button';
	if ($selected) $class .= '_on';
	$result = div('class:'.$class, $link);
	if ($return) return $result;
	echo $result;
}

function selectButton($link, $selected=false, $return=false) {
	$class = 'select_button';
	if ($selected) $class .= '_on';
	$result = div('class:'.$class, $link);
	if ($return) return $result;
	echo $result;
}

function decisionButton2($link, $selected=false, $return=false) {
	$class = 'admin_select_button2';
	if ($selected) $class .= '_on';
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
		$result = div('class:'.$class, span('style:padding-top: 0;', $link));
	} else {
		$result = div('class:'.$class, span('', $link));
	}
	if ($return) return $result;
	echo $result;
}

function selectButton2($link, $selected=false, $return=false) {
	$class = 'select_button2';
	if ($selected) $class .= '_on';
	$result = div('class:'.$class, span('', $link));
	if ($return) return $result;
	echo $result;
}

function progressBar($width, $percentComplete) {
	return div('class:progress-bg | style:width: '.$width.'px;', div('class:progress | style:width:'.round($percentComplete*$width).'px;', '&nbsp;'));
}
	
}

?>


